<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksDeleter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use Auditable, SoftDeletes, TracksDeleter;

    protected $fillable = ['name', 'description'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
