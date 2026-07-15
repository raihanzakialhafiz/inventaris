<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOut extends Model
{
    protected $fillable = [
        'transaction_no', 'item_id', 'quantity',
        'department_id', 'request_id', 'type',
        'date', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function item(): BelongsTo
    {
        // withTrashed: riwayat distribusi tetap tampil walau master di-soft-delete.
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
