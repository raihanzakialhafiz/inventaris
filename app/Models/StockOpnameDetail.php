<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameDetail extends Model
{
    protected $fillable = [
        'stock_opname_id', 'item_id', 'system_stock', 'physical_stock', 'difference',
    ];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function item(): BelongsTo
    {
        // withTrashed: riwayat opname tetap tampil walau barang di-soft-delete.
        return $this->belongsTo(Item::class)->withTrashed();
    }
}
