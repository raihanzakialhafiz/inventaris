<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use Auditable;

    protected $fillable = ['key', 'value', 'type'];

    /** Nilai rahasia (mis. password SMTP terenkripsi) tidak boleh masuk audit log. */
    protected function auditMasked(): array
    {
        return $this->type === 'secret' ? ['value'] : [];
    }

    private const CACHE_KEY = 'settings.all';

    /** Peta key => value dari seluruh pengaturan (di-cache untuk performa). */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::pluck('value', 'key')->all());
    }

    /** Ambil satu nilai pengaturan dengan fallback default. */
    public static function get(string $key, $default = null)
    {
        return self::map()[$key] ?? $default;
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }
}
