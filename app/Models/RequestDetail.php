<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDetail extends Model
{
    protected $fillable = [
        'request_id', 'item_id',
        'quantity_requested', 'quantity_approved',
        'quantity_distributed', 'reduction_reason',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function item(): BelongsTo
    {
        // withTrashed: riwayat permintaan tetap tampil walau barang di-soft-delete.
        return $this->belongsTo(Item::class)->withTrashed();
    }
}
