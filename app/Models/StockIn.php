<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIn extends Model
{
    protected $fillable = ['transaction_no', 'supplier_id', 'date', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function supplier(): BelongsTo
    {
        // withTrashed: riwayat barang masuk tetap tampil walau supplier di-soft-delete.
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function details(): HasMany
    {
        return $this->hasMany(StockInDetail::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalQuantity(): int
    {
        return $this->details->sum('quantity');
    }
}
