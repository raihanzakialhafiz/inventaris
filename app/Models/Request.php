<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    protected $fillable = [
        'request_no', 'user_id', 'department_id', 'request_date',
        'status', 'approver_id', 'approved_date', 'rejection_reason',
        'is_flagged', 'justification', 'note',
    ];

    protected function casts(): array
    {
        return [
            'request_date'  => 'date',
            'approved_date' => 'datetime',
            'is_flagged'    => 'boolean',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        // withTrashed: permintaan lama tetap tampil walau bidang di-soft-delete.
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(RequestDetail::class);
    }

    public function stockOuts(): HasMany
    {
        return $this->hasMany(StockOut::class);
    }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isApproved(): bool   { return $this->status === 'disetujui'; }
    public function isRejected(): bool   { return $this->status === 'ditolak'; }
    public function isDone(): bool       { return in_array($this->status, ['selesai', 'selesai_sebagian']); }
}
