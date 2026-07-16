<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\ExportTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blok tanda tangan pada ekspor laporan: hanya pimpinan, di pojok kanan, dan
 * hanya bila diminta lewat parameter ttd DAN pejabatnya sudah diatur. Nama
 * pengekspor tidak ikut menandatangani — hanya tercatat sebagai "Oleh:".
 */
class TandaTanganLaporanTest extends TestCase
{
    use RefreshDatabase;

    private function aturPenandaTangan(User $user): void
    {
        Setting::updateOrCreate(['key' => 'signer_user_id'], ['value' => $user->id, 'type' => 'number']);
    }

    private function tabel(bool $ttd): array
    {
        return ExportTable::make('Uji', ['A'], [['1']], 'uji', withSignerSignature: $ttd);
    }

    public function test_pengekspor_selalu_ikut_tanpa_perlu_diminta(): void
    {
        $user = $this->makeUser('petugas_gudang');
        $user->update(['nip' => '199001012015031001', 'jabatan' => 'Petugas Gudang ATK']);

        $this->actingAs($user);

        $table = $this->tabel(false);

        $this->assertSame('199001012015031001', $table['exporter']['nip']);
        $this->assertSame('Petugas Gudang ATK', $table['exporter']['jabatan']);
        $this->assertNull($table['signer'], 'Tanpa parameter ttd, tanda tangan pimpinan tidak boleh ikut.');
    }

    public function test_jabatan_kosong_jatuh_ke_label_peran(): void
    {
        $this->actingAs($this->makeUser('kasubag_umum'));

        $this->assertSame('Kasubag Umum', $this->tabel(false)['exporter']['jabatan']);
    }

    public function test_pimpinan_ikut_hanya_bila_diminta(): void
    {
        $pimpinan = $this->makeUser('pimpinan');
        $pimpinan->update(['nip' => '198203152006041002', 'jabatan' => 'Kepala Dinas']);
        $this->aturPenandaTangan($pimpinan);

        $this->actingAs($this->makeUser('admin'));

        $this->assertNull($this->tabel(false)['signer']);

        $signer = $this->tabel(true)['signer'];
        $this->assertSame($pimpinan->name, $signer['name']);
        $this->assertSame('198203152006041002', $signer['nip']);
        $this->assertSame('Kepala Dinas', $signer['jabatan']);
    }

    public function test_pejabat_nonaktif_tidak_dicetak_sebagai_penanda_tangan(): void
    {
        $pimpinan = $this->makeUser('pimpinan');
        $this->aturPenandaTangan($pimpinan);
        $pimpinan->update(['is_active' => false]);

        $this->actingAs($this->makeUser('admin'));

        // Dokumen tetap terbit dengan satu tanda tangan, bukan mencetak nama
        // pejabat yang sudah tidak menjabat.
        $this->assertNull($this->tabel(true)['signer']);
    }

    public function test_tanpa_pengaturan_penanda_tangan_ekspor_tetap_jalan(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $this->assertNull($this->tabel(true)['signer']);
    }

    public function test_endpoint_ekspor_pdf_dengan_ttd_menghasilkan_pdf_utuh(): void
    {
        $pimpinan = $this->makeUser('pimpinan');
        $pimpinan->update(['nip' => '198203152006041002', 'jabatan' => 'Kepala Dinas']);
        $this->aturPenandaTangan($pimpinan);

        $response = $this->actingAs($this->makeUser('admin'))
            ->get('/laporan/export/pdf?type=stok&period=2026-07&ttd=1')
            ->assertOk();

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_hanya_menandatangani_pimpinan_bukan_pengekspor(): void
    {
        $pimpinan = $this->makeUser('pimpinan');
        $pimpinan->update(['name' => 'Dewi Anggraini', 'nip' => '198203152006041002', 'jabatan' => 'Kepala Dinas']);
        $this->aturPenandaTangan($pimpinan);

        $admin = $this->makeUser('admin');
        $admin->update(['name' => 'Budi Santoso', 'nip' => '199001012015031001']);
        $this->actingAs($admin);

        $html = view('laporan.pdf', $this->tabel(true))->render();

        $this->assertStringContainsString('Dewi Anggraini', $html);
        $this->assertStringContainsString('NIP. 198203152006041002', $html);
        $this->assertStringContainsString('Kepala Dinas', $html);

        // Pengekspor hanya tercatat sebagai keterangan "Oleh:", tidak pernah
        // punya blok tanda tangan sendiri dan NIP-nya tidak dicetak.
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringNotContainsString('NIP. 199001012015031001', $html);
    }

    public function test_tanpa_ttd_tidak_ada_blok_tanda_tangan_sama_sekali(): void
    {
        $pimpinan = $this->makeUser('pimpinan');
        $pimpinan->update(['name' => 'Dewi Anggraini']);
        $this->aturPenandaTangan($pimpinan);

        $admin = $this->makeUser('admin');
        $admin->update(['name' => 'Budi Santoso']);
        $this->actingAs($admin);

        $html = view('laporan.pdf', $this->tabel(false))->render();

        $this->assertStringNotContainsString('class="ttd"', $html);
        $this->assertStringNotContainsString('Dewi Anggraini', $html);
        // Keterangan pencetak tetap ada meski tanpa tanda tangan.
        $this->assertStringContainsString('Budi Santoso', $html);
    }

    public function test_kop_menyusun_empat_baris_identitas_instansi(): void
    {
        Setting::updateOrCreate(['key' => 'government_name'], ['value' => 'PEMERINTAH PROVINSI SUMATERA BARAT', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'institution_name'], ['value' => 'BADAN KEPEGAWAIAN DAERAH', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'address'], ['value' => 'Jalan Batang Antokan No. 4 Padang', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'contact_email'], ['value' => 'bkd@sumbarprov.go.id', 'type' => 'email']);

        $this->actingAs($this->makeUser('admin'));
        $html = view('laporan.pdf', $this->tabel(false))->render();

        $this->assertStringContainsString('PEMERINTAH PROVINSI SUMATERA BARAT', $html);
        $this->assertStringContainsString('BADAN KEPEGAWAIAN DAERAH', $html);
        $this->assertStringContainsString('Jalan Batang Antokan No. 4 Padang', $html);
        $this->assertStringContainsString('email : bkd@sumbarprov.go.id', $html);
    }

    public function test_judul_laporan_berada_di_luar_kop(): void
    {
        $this->actingAs($this->makeUser('admin'));
        $html = view('laporan.pdf', $this->tabel(false))->render();

        // Judul harus berdiri sendiri setelah kop ditutup, bukan di dalamnya.
        $posKopTutup = strpos($html, '</div>', strpos($html, 'class="kop"'));
        $posJudul    = strpos($html, 'class="judul"');

        $this->assertNotFalse($posJudul, 'Blok judul tidak ditemukan.');
        $this->assertGreaterThan($posKopTutup, $posJudul, 'Judul masih berada di dalam kop.');
    }

    public function test_baris_kop_kosong_tidak_menyisakan_baris_hampa(): void
    {
        Setting::where('key', 'government_name')->delete();
        Setting::updateOrCreate(['key' => 'contact_email'], ['value' => '', 'type' => 'email']);

        $this->actingAs($this->makeUser('admin'));
        $html = view('laporan.pdf', $this->tabel(false))->render();

        $this->assertStringNotContainsString('class="pem"', $html);
        $this->assertStringNotContainsString('email :', $html);
    }

    public function test_tempat_mendahului_tanggal_bila_diatur(): void
    {
        $pimpinan = $this->makeUser('pimpinan');
        $this->aturPenandaTangan($pimpinan);
        Setting::updateOrCreate(['key' => 'signature_place'], ['value' => 'Padang', 'type' => 'text']);

        $this->actingAs($this->makeUser('admin'));

        $this->assertStringContainsString(
            'Padang, ' . now()->isoFormat('D MMMM YYYY'),
            view('laporan.pdf', $this->tabel(true))->render(),
        );
    }

    public function test_tanpa_tempat_hanya_tanggal_tanpa_koma_menggantung(): void
    {
        $pimpinan = $this->makeUser('pimpinan');
        $this->aturPenandaTangan($pimpinan);

        $this->actingAs($this->makeUser('admin'));
        $html = view('laporan.pdf', $this->tabel(true))->render();

        $this->assertStringContainsString(now()->isoFormat('D MMMM YYYY'), $html);
        $this->assertStringNotContainsString(', ' . now()->isoFormat('D MMMM YYYY'), $html);
    }

    public function test_nip_pimpinan_kosong_ditandai_strip_bukan_baris_kosong(): void
    {
        $pimpinan = $this->makeUser('pimpinan');
        $pimpinan->update(['name' => 'Tanpa Nip']);
        $this->aturPenandaTangan($pimpinan);

        $this->actingAs($this->makeUser('admin'));

        $this->assertStringContainsString('NIP. -', view('laporan.pdf', $this->tabel(true))->render());
    }
}
