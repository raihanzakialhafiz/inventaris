<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksDeleter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use Auditable, SoftDeletes, TracksDeleter;

    /** Stok tidak diaudit di sini — pergerakannya sudah terekam di transaksi. */
    protected $auditExclude = ['stock'];

    protected $fillable = [
        'code', 'category_id', 'name', 'unit',
        'stock', 'minimum_stock', 'reorder_point',
        'location', 'description',
    ];

    public function category(): BelongsTo
    {
        // withTrashed: barang tetap tampil utuh walau kategorinya di-soft-delete.
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function stockInDetails(): HasMany
    {
        return $this->hasMany(StockInDetail::class);
    }

    public function stockOuts(): HasMany
    {
        return $this->hasMany(StockOut::class);
    }

    public function requestDetails(): HasMany
    {
        return $this->hasMany(RequestDetail::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->minimum_stock;
    }

    /** Ambang pemesanan ulang: reorder_point bila diisi, jika tidak stok minimum. */
    public function reorderLevel(): int
    {
        return $this->reorder_point > 0 ? (int) $this->reorder_point : (int) $this->minimum_stock;
    }

    /** Perlu dipesan ulang bila stok sudah menyentuh/di bawah ambang reorder. */
    public function needsReorder(): bool
    {
        return $this->stock <= $this->reorderLevel();
    }

    /** Saran jumlah pemesanan agar stok kembali ke level aman (2× ambang reorder). */
    public function suggestedReorderQty(): int
    {
        return max(1, $this->reorderLevel() * 2 - (int) $this->stock);
    }

    /**
     * Kuota permintaan bulanan per bidang untuk barang ini (heuristik PRD):
     * 2× stok minimum, minimal 10. Satu-satunya sumber aturan ini —
     * dipakai flag over-request, dashboard kabid, dan halaman sisa kuota.
     */
    public function monthlyQuota(): int
    {
        return ($this->minimum_stock * 2) ?: 10;
    }
}
