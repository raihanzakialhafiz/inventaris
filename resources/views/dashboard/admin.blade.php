@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Administrator')
@section('page-crumb', 'Kontrol penuh sistem inventaris ATK · ' . now()->isoFormat('D MMM Y'))

@section('content')

    {{-- Stat Cards --}}
    <div class="grid g-4">
        <div class="stat {{ $lowStock->count() + $outStock->count() > 0 ? 'warn' : 'ok' }}">
            <div class="lbl">Stok Perlu Perhatian</div>
            <div class="val">{{ $lowStock->count() + $outStock->count() }}</div>
            <div class="tag badge {{ $lowStock->count() + $outStock->count() > 0 ? 'b-warn' : 'b-ok' }}">
                {{ $outStock->count() }} habis · {{ $lowStock->count() }} menipis
            </div>
        </div>
        <div class="stat {{ $pending > 0 ? 'danger' : 'ok' }}">
            <div class="lbl">Permintaan Pending</div>
            <div class="val">{{ $pending }}</div>
            <div class="tag badge {{ $pending > 0 ? 'b-danger' : 'b-ok' }}">menunggu Kasubag</div>
        </div>
        <div class="stat">
            <div class="lbl">Total Jenis Barang</div>
            <div class="val">{{ $items->count() }}</div>
            <div class="tag badge b-neutral">{{ $items->where('stock', '>', 0)->count() }} aktif</div>
        </div>
        <div class="stat {{ $stockInCount > 0 ? 'ok' : '' }}">
            <div class="lbl">Penerimaan Bulan Ini</div>
            <div class="val">{{ $stockInCount }}</div>
            <div class="tag badge b-ok">transaksi masuk</div>
        </div>
    </div>

    {{-- Alert Over-Request --}}
    @if ($overReqs > 0)
        <div class="notice warn mt">
            <span class="ic"><x-icon name="alert" /></span>
            <div>
                <b>{{ $overReqs }} permintaan over-request</b> menunggu persetujuan Kasubag.
                <a href="{{ route('permintaan.index') }}" style="margin-left:8px;font-weight:600">Lihat semua <x-icon
                        name="arrow-right" width="13" height="13" style="vertical-align:-2px" /></a>
            </div>
        </div>
    @endif

    {{-- Grafik ringkas --}}
    <div class="grid g-2 mt">
        <div class="card">
            <div class="card-h">
                <h3>Tren Barang Keluar</h3>
                <span class="hint">jumlah unit didistribusikan · 6 bulan terakhir</span>
            </div>
            <div class="card-b">
                <x-chart-columns :data="$outTrend" satuan="unit" />
            </div>
        </div>
        <div class="card">
            <div class="card-h">
                <h3>Permintaan per Bidang</h3>
                <span class="hint">periode {{ $period }}</span>
            </div>
            <div class="card-b">
                <x-chart-hbars :data="$deptChart" satuan="permintaan" />
            </div>
        </div>
    </div>

    <div class="grid g-2 mt">
        {{-- Barang Kritis --}}
        <div class="card">
            <div class="card-h">
                <h3>Barang Kritis</h3>
                <span class="hint">stok ≤ minimum</span>
            </div>
            <div class="card-b" style="padding:6px 0">
                @forelse($lowStock->merge($outStock) as $item)
                    @php $st = $item->stock <= 0 ? ['b-danger','Habis'] : ['b-warn','Menipis']; @endphp
                    <div
                        style="display:flex;align-items:center;gap:12px;padding:10px 18px;border-bottom:1px solid var(--line)">
                        <span class="code" style="flex:0 0 auto">{{ $item->code }}</span>
                        <div style="flex:1;min-width:0">
                            <div class="t-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                {{ $item->name }}</div>
                            <div class="t-sub">min {{ $item->minimum_stock }} {{ $item->unit }}</div>
                        </div>
                        <b
                            style="{{ $item->stock <= 0 ? 'color:var(--danger)' : 'color:var(--warn)' }}">{{ $item->stock }}</b>
                        <span class="badge {{ $st[0] }}">{{ $st[1] }}</span>
                        <a href="{{ route('barang.index', ['search' => $item->code]) }}"
                            class="btn btn-ghost btn-sm">Kelola</a>
                    </div>
                @empty
                    <div class="empty" style="padding:32px 0">
                        <div class="empty-ic"><x-icon name="check" /></div>
                        <b>Semua stok aman</b>
                        <p class="t-sub" style="margin-top:4px">Tidak ada barang kritis saat ini</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Aktivitas Terbaru --}}
        <div class="card">
            <div class="card-h">
                <h3>Aktivitas Terbaru</h3>
                <a href="{{ route('permintaan.index') }}" class="btn btn-ghost btn-sm">Lihat semua</a>
            </div>
            <div class="card-b" style="padding:6px 0">
                @foreach ($recentReqs as $req)
                    <div style="padding:10px 18px;border-bottom:1px solid var(--line)">
                        <div style="display:flex;gap:8px;align-items:center">
                            <a href="{{ route('permintaan.show', $req) }}" class="code">{{ $req->request_no }}</a>
                            <span class="badge b-primary">{{ $req->department->code }}</span>
                            <x-status-badge :status="$req->status" />
                            @if ($req->is_flagged)
                                <span class="badge b-danger" style="font-size:10px"><x-icon name="alert" width="11"
                                        height="11" style="vertical-align:-1px" /> Over</span>
                            @endif
                        </div>
                        <div class="t-sub" style="margin-top:3px">
                            {{ $req->requester->name }} · {{ $req->request_date->isoFormat('D MMM Y') }}
                        </div>
                    </div>
                @endforeach
                @if ($recentReqs->isEmpty())
                    <div class="empty" style="padding:24px 0">
                        <div class="empty-ic"><x-icon name="rows" /></div><b>Belum ada permintaan</b>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Konsumsi per Bidang --}}
    <div class="card mt">
        <div class="card-h">
            <h3>Aktivitas per Bidang</h3>
            <span class="hint">periode {{ $period }}</span>
        </div>
        <div class="card-b" style="padding:0">
            <table>
                <thead>
                    <tr>
                        <th>Bidang</th>
                        <th class="num">Total</th>
                        <th class="num">Disetujui</th>
                        <th class="num">Ditolak</th>
                        <th class="num">Selesai</th>
                        <th class="num">Over-Request</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deptStats as $ds)
                        <tr>
                            <td>
                                <span class="badge b-primary" style="margin-right:8px">{{ $ds['dept']->code }}</span>
                                <span class="t-name">{{ $ds['dept']->name }}</span>
                            </td>
                            <td class="num">{{ $ds['total'] }}</td>
                            <td class="num">{{ $ds['disetujui'] }}</td>
                            <td class="num">
                                @if ($ds['ditolak'] > 0)
                                    <span style="color:var(--danger);font-weight:700">{{ $ds['ditolak'] }}</span>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="num">{{ $ds['selesai'] }}</td>
                            <td class="num">
                                @if ($ds['flagged'] > 0)
                                    <span style="color:var(--warn);font-weight:700">{{ $ds['flagged'] }}</span>
                                @else
                                    <span class="t-sub">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty">
                                    <div class="empty-ic"><x-icon name="rows" /></div><b>Belum ada data bidang</b>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
