<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksDeleter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use Auditable, SoftDeletes, TracksDeleter;

    protected $fillable = ['name', 'address', 'phone', 'email'];

    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }
}
