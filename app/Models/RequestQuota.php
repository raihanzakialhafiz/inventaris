<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestQuota extends Model
{
    use Auditable;

    protected $fillable = [
        'department_id', 'item_id', 'category_id',
        'period_type', 'quota_quantity', 'threshold_percent',
        'cooldown_days', 'policy', 'effective_from', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'is_active'      => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
