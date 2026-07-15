<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Melengkapi model SoftDeletes dengan pencatatan SIAPA yang menghapus.
 * Kolom `deleted_by` diisi otomatis saat soft delete (bukan force delete).
 * Wajib: tabel punya kolom `deleted_by` (lihat migrasi terkait).
 */
trait TracksDeleter
{
    public static function bootTracksDeleter(): void
    {
        static::deleting(function ($model) {
            // Abaikan force delete (baris akan hilang) & saat tanpa user (mis. seeder/CLI).
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }
            if (auth()->check()) {
                $model->newQueryWithoutScopes()
                    ->where($model->getKeyName(), $model->getKey())
                    ->update(['deleted_by' => auth()->id()]);
            }
        });
    }

    /** Pengguna yang melakukan penghapusan (soft delete). */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
