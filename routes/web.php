<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\BidangController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangMasukController;
use App\Http\Controllers\PermintaanController;
use App\Http\Controllers\PersetujuanController;
use App\Http\Controllers\DistribusiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\KuotaController;
use App\Http\Controllers\KuotaBidangController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PengaturanEmailController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\SampahController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StockOpnameController;
use Illuminate\Support\Facades\Route;

/* ─── Auth ─────────────────────────────────────────── */
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
// Rate limit per email+IP (limiter 'login' di AppServiceProvider) — anti brute force,
// tanpa mengunci satu kantor yang berbagi IP. Melengkapi lockout per-akun.
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/* ─── Lupa / Reset Kata Sandi (bawaan Password broker) ─────────── */
Route::get('/lupa-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/lupa-password', [PasswordResetController::class, 'sendResetLink'])
    ->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
    ->middleware('throttle:5,1')->name('password.update');
Route::redirect('/', '/login');

/* ─── Protected ─────────────────────────────────────── */
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* Keep-alive — menyegarkan waktu aktivitas sesi selama pengguna aktif di halaman */
    Route::get('/sesi/ping', fn () => response()->noContent())->name('session.ping');

    /* Pencarian global — semua pengguna terautentikasi (hasil dibatasi role) */
    Route::get('/cari', [SearchController::class, 'index'])->name('search.index');

    /* Profil / Pengaturan Akun — semua pengguna terautentikasi */
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');

    /* Notifikasi — semua pengguna terautentikasi.
       Rute statis (hitung/baca-semua) HARUS sebelum wildcard {notifikasi}. */
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/hitung', [NotifikasiController::class, 'count'])->name('notifikasi.count');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'readAll'])->name('notifikasi.readAll');
    Route::delete('/notifikasi/hapus-semua', [NotifikasiController::class, 'destroyAll'])->name('notifikasi.destroyAll');
    Route::delete('/notifikasi/{notifikasi}', [NotifikasiController::class, 'destroy'])->name('notifikasi.destroy');
    Route::get('/notifikasi/{notifikasi}', [NotifikasiController::class, 'read'])->name('notifikasi.read');

    /* Barang — lihat: semua kecuali Kepala Bidang (menunya memang dihapus);
       Admin CRUD penuh, Kasubag boleh edit */
    Route::middleware('role:admin,petugas_gudang,kasubag_umum,pimpinan')->group(function () {
        Route::get('/barang', [BarangController::class, 'index'])->name('barang.index');
    });
    Route::middleware('role:admin,kasubag_umum')->group(function () {
        Route::put('/barang/{barang}', [BarangController::class, 'update'])->name('barang.update');
    });
    Route::middleware('role:admin')->group(function () {
        // Tambah/hapus lewat modal di halaman index (tanpa halaman terpisah)
        Route::post('/barang', [BarangController::class, 'store'])->name('barang.store');
        Route::delete('/barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');
    });

    /* Master Data — Admin only */
    Route::middleware('role:admin')->group(function () {
        Route::resource('kategori', KategoriController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('satuan', SatuanController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('bidang', BidangController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('supplier', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('pengguna', PenggunaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('kuota', KuotaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
        Route::get('/audit-log/export/{format}', [AuditLogController::class, 'export'])->name('audit-log.export');
        Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
        Route::delete('/pengaturan/gambar/{key}', [PengaturanController::class, 'deleteImage'])->name('pengaturan.deleteImage');

        // Pengaturan Email (SMTP + jadwal stok menipis)
        Route::get('/pengaturan/email', [PengaturanEmailController::class, 'index'])->name('pengaturan-email.index');
        Route::put('/pengaturan/email', [PengaturanEmailController::class, 'update'])->name('pengaturan-email.update');
        Route::post('/pengaturan/email/test', [PengaturanEmailController::class, 'test'])
            ->middleware('throttle:3,1')->name('pengaturan-email.test');

        // Kotak Sampah — pulihkan / hapus permanen data soft-deleted
        Route::get('/sampah', [SampahController::class, 'index'])->name('sampah.index');
        Route::post('/sampah/{type}/{id}/restore', [SampahController::class, 'restore'])->name('sampah.restore');
        Route::delete('/sampah/{type}/{id}', [SampahController::class, 'destroy'])->name('sampah.destroy');
    });

    /* Barang Masuk & Stock Opname — Admin & Gudang */
    Route::middleware('role:admin,petugas_gudang')->group(function () {
        // Rute ekspor didaftarkan sebelum resource agar tidak tertelan wildcard {id}.
        Route::get('/barang-masuk/export/{format}', [BarangMasukController::class, 'export'])->name('barang-masuk.export');
        Route::get('/stock-opname/{stock_opname}/export', [StockOpnameController::class, 'export'])->name('stock-opname.export');
        Route::resource('barang-masuk', BarangMasukController::class)->only(['index', 'store', 'show']);
        Route::resource('stock-opname', StockOpnameController::class)->only(['index', 'store', 'show']);
    });

    /* Permintaan — semua bisa lihat; hanya Kabid bisa buat (permintaan
       selalu atas nama bidang; admin tidak punya bidang) */
    Route::get('/permintaan', [PermintaanController::class, 'index'])->name('permintaan.index');
    Route::middleware('role:kepala_bidang')->group(function () {
        // Ajukan permintaan lewat modal di halaman index
        Route::post('/permintaan', [PermintaanController::class, 'store'])->name('permintaan.store');
    });
    Route::get('/permintaan/{permintaan}', [PermintaanController::class, 'show'])->name('permintaan.show');

    /* Persetujuan — Kasubag & Admin */
    Route::middleware('role:kasubag_umum,admin')->group(function () {
        Route::post('/permintaan/{permintaan}/approve', [PersetujuanController::class, 'approve'])->name('permintaan.approve');
        Route::post('/permintaan/{permintaan}/reject', [PersetujuanController::class, 'reject'])->name('permintaan.reject');
    });

    /* Distribusi — Gudang & Admin */
    Route::middleware('role:petugas_gudang,admin')->group(function () {
        Route::get('/distribusi', [DistribusiController::class, 'index'])->name('distribusi.index');
        Route::post('/distribusi/{permintaan}', [DistribusiController::class, 'store'])->name('distribusi.store');
    });

    /* Laporan — Admin, Pimpinan, Kasubag */
    Route::middleware('role:admin,pimpinan,kasubag_umum')->group(function () {
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/export/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.export.pdf');
        Route::get('/laporan/export/excel', [LaporanController::class, 'exportExcel'])->name('laporan.export.excel');
    });

    /* Sisa Kuota Bidang — Kepala Bidang */
    Route::middleware('role:kepala_bidang')->group(function () {
        Route::get('/kuota-bidang', [KuotaBidangController::class, 'index'])->name('kuota-bidang.index');
    });
});
