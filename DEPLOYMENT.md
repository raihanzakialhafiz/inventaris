# Checklist Deploy Produksi — Inventaris ATK

## 1. Konfigurasi lingkungan
- [ ] Salin `.env.production.example` → `.env`, isi semua nilai `<...>`.
- [ ] `php artisan key:generate --force` (APP_KEY baru, jangan pakai punya lokal).
- [ ] Pastikan `APP_DEBUG=false` dan `APP_ENV=production`.
- [ ] `APP_URL` memakai `https://` — aplikasi otomatis memaksa skema HTTPS di produksi.
- [ ] Buat user MySQL khusus aplikasi (bukan root) dengan hak hanya ke database ini.

## 2. Instalasi
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force        # hanya instalasi pertama
php artisan storage:link
```

## 3. Optimasi & cache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> Setelah mengubah `.env`, ulangi `php artisan config:cache`.

## 4. Scheduler (wajib — pengingat stok, purge sampah, backup)
Jadwalkan perintah berikut setiap menit:
```
php artisan schedule:run
```
- **Windows**: Task Scheduler → Basic Task → tiap 1 menit → `php.exe C:\path\artisan schedule:run`.
- **Linux**: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`

Jadwal bawaan: pengingat stok (jam sesuai Pengaturan Email), `sampah:purge` 02:00, `db:backup` 01:30 (retensi 14 hari, tersimpan di `storage/app/backups`).

## 5. Keamanan server
- [ ] HTTPS aktif (sertifikat valid); redirect 80 → 443 di web server.
- [ ] `SESSION_SECURE_COOKIE=true` di `.env`.
- [ ] Document root mengarah ke folder `public/` (bukan root proyek).
- [ ] Folder `storage/` & `bootstrap/cache/` writable oleh web server, tidak bisa diakses publik.
- [ ] Uji `php artisan db:backup` manual sekali; bila `mysqldump` tidak dikenali, isi `MYSQLDUMP_PATH` di `.env`.
- [ ] Salin berkas backup secara berkala ke media lain (backup di server yang sama tidak melindungi dari kegagalan disk).

## 6. Verifikasi akhir
- [ ] Login tiap role, cek dasbor masing-masing.
- [ ] Kirim email uji dari Pengaturan > Email.
- [ ] Buka halaman yang tidak ada → tampil halaman 404 kustom.
- [ ] Ganti semua password akun bawaan seeder.
