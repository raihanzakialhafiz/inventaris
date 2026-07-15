<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * Jejak audit otomatis: mencatat create/update/delete/restore/force_delete
 * model ke audit_logs beserta nilai lama → baru, tanpa perlu menulis
 * AuditLog::create() di tiap controller.
 *
 * Opsi per model:
 *  - protected array $auditExclude — atribut yang diabaikan sepenuhnya
 *    (perubahan yang hanya menyentuh atribut ini tidak dicatat sama sekali);
 *  - auditMasked(): array — atribut yang dicatat sebagai '[dirahasiakan]'.
 *
 * Aksi transaksional (permintaan, barang masuk, distribusi, opname) tetap
 * dicatat manual oleh controller-nya dengan nama aktivitas yang spesifik.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($m) => $m->writeAudit('create', null, $m->auditClean($m->getAttributes())));

        static::updated(function ($m) {
            $baru = $m->auditClean($m->getChanges());
            if ($baru === []) {
                return; // hanya atribut yang dikecualikan/timestamp yang berubah
            }
            $lama = $m->auditClean(array_intersect_key($m->getOriginal(), $m->getChanges()));
            $m->writeAudit('update', $lama, $baru);
        });

        static::deleted(function ($m) {
            // forceDelete ikut memicu 'deleted' — biar dicatat sekali oleh forceDeleted.
            if (method_exists($m, 'isForceDeleting') && $m->isForceDeleting()) {
                return;
            }
            $m->writeAudit('delete');
        });

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn ($m) => $m->writeAudit('restore'));
            static::forceDeleted(fn ($m) => $m->writeAudit('force_delete'));
        }
    }

    /** Atribut yang dicatat sebagai '[dirahasiakan]' — override per model bila perlu. */
    protected function auditMasked(): array
    {
        return [];
    }

    /** Buang atribut noise/rahasia dari nilai yang akan dicatat. */
    protected function auditClean(array $values): array
    {
        $excluded = array_merge(
            ['id', 'created_at', 'updated_at', 'deleted_at', 'deleted_by', 'remember_token'],
            $this->auditExclude ?? [],
        );

        $values = array_diff_key($values, array_flip($excluded));

        foreach ($this->auditMasked() as $attr) {
            if (array_key_exists($attr, $values)) {
                $values[$attr] = '[dirahasiakan]';
            }
        }

        return $values;
    }

    protected function writeAudit(string $activity, ?array $old = null, ?array $new = null): void
    {
        AuditLog::create([
            'user_id'     => auth()->id(),
            'activity'    => $activity,
            'entity_type' => $this->getTable(),
            'entity_id'   => $this->getKey(),
            'old_values'  => $old ?: null,
            'new_values'  => $new ?: null,
            'ip_address'  => request()->ip(),
        ]);
    }
}
