<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Kotak Sampah — data yang di-soft-delete dari entitas master.
 * Admin dapat memulihkan atau menghapus permanen. Data lebih tua dari
 * RETENTION_DAYS dibersihkan otomatis oleh command `sampah:purge`.
 */
class SampahController extends Controller
{
    public const RETENTION_DAYS = 30;

    /** slug => [model, label, kolom nama]. */
    private const TYPES = [
        'barang'   => [Item::class,       'Barang',   'name'],
        'kategori' => [Category::class,   'Kategori', 'name'],
        'satuan'   => [Unit::class,       'Satuan',   'name'],
        'bidang'   => [Department::class, 'Bidang',   'name'],
        'supplier' => [Supplier::class,   'Supplier', 'name'],
    ];

    public function index()
    {
        $rows = collect();

        foreach (self::TYPES as $slug => [$model, $label, $field]) {
            $model::onlyTrashed()->with('deleter')->get()->each(function ($rec) use ($rows, $slug, $label, $field) {
                $rows->push([
                    'slug'       => $slug,
                    'type'       => $label,
                    'id'         => $rec->getKey(),
                    'name'       => $rec->{$field},
                    'deleted_by' => $rec->deleter?->name ?? 'Sistem',
                    'deleted_at' => $rec->deleted_at,
                ]);
            });
        }

        $rows = $rows->sortByDesc('deleted_at')->values();

        // Koleksi gabungan lintas-model → paginasi manual.
        $perPage = 15;
        $page    = LengthAwarePaginator::resolveCurrentPage();
        $rows    = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('sampah.index', ['rows' => $rows, 'retention' => self::RETENTION_DAYS]);
    }

    public function restore(string $type, int $id)
    {
        // Jejak audit dicatat otomatis oleh trait Auditable pada model.
        $rec = $this->find($type, $id);
        $rec->restore();

        return back()->with('success', 'Data berhasil dipulihkan.');
    }

    public function destroy(string $type, int $id)
    {
        $rec = $this->find($type, $id);

        try {
            $rec->forceDelete();
        } catch (\Illuminate\Database\QueryException) {
            // FK restrictOnDelete: data masih direferensikan riwayat transaksi.
            return back()->with('error', 'Data tidak dapat dihapus permanen karena masih dipakai riwayat transaksi. Data tetap berada di Kotak Sampah.');
        }

        return back()->with('success', 'Data dihapus permanen.');
    }

    private function find(string $type, int $id)
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type][0]::onlyTrashed()->findOrFail($id);
    }
}
