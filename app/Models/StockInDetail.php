<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInDetail extends Model
{
    protected $fillable = ['stock_in_id', 'item_id', 'quantity'];

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }

    public function item(): BelongsTo
    {
        // withTrashed: riwayat barang masuk tetap tampil walau barang di-soft-delete.
        return $this->belongsTo(Item::class)->withTrashed();
    }
}
