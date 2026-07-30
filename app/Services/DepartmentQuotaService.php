<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\RequestQuota;
use App\Models\StockOut;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal logika kuota permintaan ATK per bidang.
 *
 * Kuota efektif untuk sebuah (bidang, barang) diambil dari RequestQuota
 * (paling spesifik: barang → kategori → default bidang), dengan fallback
 * ke heuristik Item::monthlyQuota() bila belum dikonfigurasi. Pemakaian
 * dihitung KUMULATIF pada periode berjalan (bulanan/triwulan/tahunan).
 *
 * Dipakai oleh: alur pengajuan permintaan (evaluate), dashboard Kepala
 * Bidang, dan halaman Sisa Kuota Bidang (itemUsage).
 */
class DepartmentQuotaService
{
    /**
     * Ringkasan pemakaian & sisa kuota per barang untuk sebuah bidang.
     *
     * @return Collection<int, array{item: Item, used: int, outstanding: int, total: int, quota: int, remaining: int, limit: int, threshold_percent: int, policy: string, period_type: string, configured: bool}>
     */
    public function itemUsage(int $departmentId): Collection
    {
        $quotas = $this->activeQuotas($departmentId);
        $maps   = $this->usageMapsByPeriod($departmentId);

        return Item::with('category')->orderBy('name')->get()->map(function (Item $item) use ($departmentId, $quotas, $maps) {
            $cfg = $this->resolve($item, $departmentId, $quotas);
            [$usedMap, $outMap] = $maps[$cfg['period_type']];

            $used        = (int) ($usedMap->get($item->id) ?? 0);
            $outstanding = (int) ($outMap->get($item->id) ?? 0);
            $total       = $used + $outstanding;

            return array_merge($cfg, [
                'item'        => $item,
                'used'        => $used,
                'outstanding' => $outstanding,
                'total'       => $total,
                'remaining'   => max(0, $cfg['quota'] - $total),
            ]);
        });
    }

    /**
     * Evaluasi baris permintaan terhadap kuota bidang.
     *
     * @param  array<int, array{item_id: mixed, qty: mixed}>  $lines
     * @return array{flagged: bool, blocked: list<string>, warnings: list<string>}
     */
    public function evaluate(int $departmentId, array $lines): array
    {
        $quotas = $this->activeQuotas($departmentId);
        $maps   = $this->usageMapsByPeriod($departmentId);

        // Gabungkan qty per barang (baris ganda untuk barang sama dijumlahkan).
        $requested = [];
        foreach ($lines as $line) {
            $id = (int) ($line['item_id'] ?? 0);
            if ($id > 0) {
                $requested[$id] = ($requested[$id] ?? 0) + (int) ($line['qty'] ?? 0);
            }
        }

        $items    = Item::whereIn('id', array_keys($requested))->get()->keyBy('id');
        $flagged  = false;
        $blocked  = [];
        $warnings = [];

        foreach ($requested as $itemId => $qty) {
            $item = $items->get($itemId);
            if (! $item) {
                continue;
            }

            $cfg = $this->resolve($item, $departmentId, $quotas);
            [$usedMap, $outMap] = $maps[$cfg['period_type']];

            $currentUse = (int) ($usedMap->get($item->id) ?? 0) + (int) ($outMap->get($item->id) ?? 0);
            $projected  = $currentUse + $qty;

            if ($projected > $cfg['limit']) {
                $msg = "{$item->name}: total {$projected} melebihi ambang {$cfg['limit']} "
                     . "(kuota {$cfg['quota']} × {$cfg['threshold_percent']}%, sudah terpakai/diproses {$currentUse}).";
                $this->classify($cfg, $msg, $flagged, $blocked, $warnings);
            }

            // Cooldown: barang yang sama diminta lagi terlalu cepat.
            if ($cfg['configured'] && $cfg['cooldown_days'] > 0 && $this->recentlyRequested($departmentId, $item->id, $cfg['cooldown_days'])) {
                $msg = "{$item->name}: baru diminta dalam {$cfg['cooldown_days']} hari terakhir (masa jeda/cooldown).";
                $this->classify($cfg, $msg, $flagged, $blocked, $warnings);
            }
        }

        return ['flagged' => $flagged, 'blocked' => $blocked, 'warnings' => $warnings];
    }

    /**
     * Konfigurasi kuota efektif untuk (barang, bidang).
     *
     * @return array{quota: int, limit: int, threshold_percent: int, cooldown_days: int, policy: string, period_type: string, configured: bool}
     */
    public function resolve(Item $item, int $departmentId, ?Collection $activeQuotas = null): array
    {
        $quotas = $activeQuotas ?? $this->activeQuotas($departmentId);

        // Di tiap level kekhususan, aturan khusus bidang menang atas aturan
        // global (department_id null / "Semua Bidang").
        $pick = fn (callable $predicate) =>
            $quotas->first(fn ($q) => $q->department_id === $departmentId && $predicate($q))
            ?? $quotas->first(fn ($q) => $q->department_id === null && $predicate($q));

        // Prioritas: aturan barang → aturan kategori → default bidang.
        $match = $pick(fn ($q) => $q->item_id === $item->id)
            ?? $pick(fn ($q) => $q->item_id === null && $q->category_id === $item->category_id)
            ?? $pick(fn ($q) => $q->item_id === null && $q->category_id === null);

        $cfg = $match
            ? [
                'quota'             => (int) $match->quota_quantity,
                'threshold_percent' => (int) $match->threshold_percent,
                'cooldown_days'     => (int) $match->cooldown_days,
                'policy'            => $match->policy,
                'period_type'       => $match->period_type,
                'configured'        => true,
            ]
            : [
                'quota'             => $item->monthlyQuota(),
                // 100% = kuota adalah batas nyata, sama seperti aturan baru dari
                // halaman Kuota Bidang — agar meter "sisa" di Sisa Kuota Bidang
                // sinkron dengan ambang yang benar-benar ditegakkan.
                'threshold_percent' => 100,
                'cooldown_days'     => 0,
                'policy'            => 'warn',
                'period_type'       => 'monthly',
                'configured'        => false,
            ];

        $cfg['limit'] = (int) floor($cfg['quota'] * $cfg['threshold_percent'] / 100);

        return $cfg;
    }

    /** Masukkan pesan pelanggaran ke daftar block/warning sesuai kebijakan. */
    private function classify(array $cfg, string $msg, bool &$flagged, array &$blocked, array &$warnings): void
    {
        if ($cfg['policy'] === 'block' && $cfg['configured']) {
            $blocked[] = $msg;
        } else {
            $flagged    = true;
            $warnings[] = $msg;
        }
    }

    /** Kuota aktif & berlaku untuk sebuah bidang (termasuk kuota global department_id NULL). */
    private function activeQuotas(int $departmentId): Collection
    {
        return RequestQuota::where(fn ($q) => $q->where('department_id', $departmentId)->orWhereNull('department_id'))
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', today())
            ->get();
    }

    /** Peta pemakaian (used, outstanding) per barang untuk tiap tipe periode. */
    private function usageMapsByPeriod(int $departmentId): array
    {
        $maps = [];
        foreach (['monthly', 'quarterly', 'yearly'] as $periodType) {
            [$start, $end]     = $this->periodRange($periodType);
            $maps[$periodType] = $this->usageMaps($departmentId, $start, $end);
        }

        return $maps;
    }

    /** Rentang tanggal periode berjalan menurut tipe. */
    private function periodRange(string $periodType): array
    {
        return match ($periodType) {
            'yearly'    => [now()->startOfYear(), now()->endOfYear()],
            'quarterly' => [now()->startOfQuarter(), now()->endOfQuarter()],
            default     => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    /**
     * used = barang yang sudah terdistribusi; outstanding = yang masih
     * pending/disetujui (pakai jumlah disetujui bila ada, jika belum diminta).
     *
     * @return array{0: Collection, 1: Collection}
     */
    private function usageMaps(int $departmentId, Carbon $start, Carbon $end): array
    {
        $used = StockOut::where('type', 'request')
            ->where('department_id', $departmentId)
            ->whereHas('request', fn ($q) => $q->whereBetween('request_date', [$start, $end]))
            ->selectRaw('item_id, SUM(quantity) as qty')
            ->groupBy('item_id')
            ->pluck('qty', 'item_id');

        $outstanding = ItemRequest::where('department_id', $departmentId)
            ->whereIn('status', ['pending', 'disetujui'])
            ->whereBetween('request_date', [$start, $end])
            ->with('details')
            ->get()
            ->flatMap->details
            ->groupBy('item_id')
            ->map(fn ($rows) => (int) ($rows->sum('quantity_approved') ?: $rows->sum('quantity_requested')));

        return [$used, $outstanding];
    }

    /** Apakah bidang meminta barang ini dalam N hari terakhir (selain yang ditolak)? */
    private function recentlyRequested(int $departmentId, int $itemId, int $cooldownDays): bool
    {
        return ItemRequest::where('department_id', $departmentId)
            ->where('status', '!=', 'ditolak')
            ->where('request_date', '>=', today()->subDays($cooldownDays))
            ->whereHas('details', fn ($d) => $d->where('item_id', $itemId))
            ->exists();
    }
}
