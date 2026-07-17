<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** audit:prune — masa simpan berbeda untuk event autentikasi vs jejak perubahan. */
class PruneAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function log(string $activity, int $hariLalu): AuditLog
    {
        $log = AuditLog::create([
            'user_id'     => $this->makeUser('admin')->id,
            'activity'    => $activity,
            'entity_type' => 'users',
            'entity_id'   => 1,
            'ip_address'  => '127.0.0.1',
        ]);
        // Mundurkan waktu buat: created_at tak bisa lewat create() (guarded timestamp).
        $log->timestamps = false;
        $log->created_at = now()->subDays($hariLalu);
        $log->save();

        return $log;
    }

    public function test_prune_memakai_masa_simpan_berbeda(): void
    {
        $loginLama   = $this->log('login', 91);          // > 90 hari → terhapus
        $loginBaru   = $this->log('login', 89);          // < 90 hari → bertahan
        $crudLama    = $this->log('create_stock_in', 731); // > 730 hari → terhapus
        $crudSedang  = $this->log('create_stock_in', 91);  // < 730 hari → bertahan walau > 90
        $timeoutLama = $this->log('session_timeout', 91);  // ikut aturan autentikasi

        $this->artisan('audit:prune')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $loginLama->id]);
        $this->assertDatabaseMissing('audit_logs', ['id' => $timeoutLama->id]);
        $this->assertDatabaseMissing('audit_logs', ['id' => $crudLama->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $loginBaru->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $crudSedang->id]);
    }

    public function test_masa_simpan_bisa_diatur_lewat_opsi(): void
    {
        $login = $this->log('login', 10);

        $this->artisan('audit:prune', ['--auth-days' => 7])->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $login->id]);
    }
}
