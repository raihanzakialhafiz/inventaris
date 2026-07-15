# Sistem Inventaris ATK

Aplikasi pengelolaan inventaris Alat Tulis Kantor: pencatatan stok, permintaan barang antar bidang, persetujuan, distribusi, stock opname, dan pelaporan — dengan hak akses berjenjang per peran.

Dibangun dengan **Laravel 13** + **MySQL**. Tanpa langkah build frontend (aset sudah statis).

---

## Kebutuhan Sistem

| Kebutuhan | Versi |
|---|---|
| PHP | **8.3** atau lebih baru |
| Composer | 2.x |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `fileinfo` |

> Laragon, XAMPP, atau Herd sudah menyertakan semuanya. **Node.js/npm tidak diperlukan.**

---

## Instalasi

### 1. Ambil kode & masuk foldernya

```bash
git clone <url-repository> inventaris-atk
cd inventaris-atk
```

Bila memakai Laragon, letakkan folder ini di `C:\laragon\www\` agar otomatis dapat domain `http://inventaris-atk.test`.

### 2. Pasang dependensi

```bash
composer install
```

### 3. Buat database kosong

Buat database bernama **`inventaris_atk`** (lewat phpMyAdmin/HeidiSQL, atau perintah berikut):

```bash
mysql -u root -e "CREATE DATABASE inventaris_atk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Siapkan berkas konfigurasi

```bash
cp .env.example .env
php artisan key:generate
```

Buka `.env`, sesuaikan bila perlu:

```env
APP_URL=http://inventaris-atk.test

DB_DATABASE=inventaris_atk
DB_USERNAME=root
DB_PASSWORD=            # isi bila MySQL Anda berpassword
```

### 5. Jalankan migrasi & data awal

```bash
php artisan migrate --seed
```

Perintah ini membuat seluruh tabel sekaligus mengisi data contoh (pengguna, bidang, kategori, barang, dan beberapa transaksi).

### 6. Hubungkan storage publik

```bash
php artisan storage:link
```

Diperlukan agar logo/favicon yang diunggah lewat menu Pengaturan bisa tampil.

---

## Menjalankan Aplikasi

**Dengan Laragon/XAMPP** — cukup nyalakan Apache + MySQL, lalu buka:

```
http://inventaris-atk.test
```

**Atau dengan server bawaan Laravel:**

```bash
php artisan serve
```

Lalu buka `http://127.0.0.1:8000`.

### Akun untuk Masuk

Semua akun contoh memakai password **`password`**:

| Peran | Email | Hak akses utama |
|---|---|---|
| Administrator | `admin@siatk.test` | Seluruh sistem, master data, pengguna, pengaturan |
| Kasubag Umum | `kasubag@siatk.test` | Menyetujui / menolak permintaan |
| Petugas Gudang | `gudang@siatk.test` | Barang masuk, distribusi, stock opname |
| Kepala Bidang | `kabid.tik@siatk.test` | Mengajukan permintaan untuk bidangnya |
| Pimpinan | `pimpinan@siatk.test` | Melihat laporan & data barang |

> Tersedia juga `kabid.keu@`, `kabid.sdm@`, dan `kabid.umum@siatk.test` untuk bidang lain.
> **Ganti semua password ini sebelum dipakai sungguhan.**

---

## Penjadwalan Tugas Otomatis

Beberapa fitur berjalan terjadwal: pengingat stok menipis, pembersihan Kotak Sampah, dan backup database. Agar aktif, jalankan penjadwal setiap menit.

**Windows** — Task Scheduler → buat Basic Task, ulangi tiap 1 menit, jalankan `php.exe` dengan path lengkap:

```
"<path-php>\php.exe" C:\laragon\www\inventaris-atk\artisan schedule:run
```

Cari path PHP Anda lewat Command Prompt: `where php`
(contoh hasil: `C:\php8.3\php.exe` atau `C:\laragon\bin\php\php-8.3.16-nts-Win32-vs16-x64\php.exe`)

**Linux/macOS** — tambahkan ke crontab (`crontab -e`):

```cron
* * * * * cd /path/ke/inventaris-atk && php artisan schedule:run >> /dev/null 2>&1
```

Jadwal bawaan:

| Perintah | Waktu | Fungsi |
|---|---|---|
| `stock:check-minimum` | sesuai Pengaturan Email (default 07:00) | Notifikasi + email stok menipis |
| `sampah:purge` | 02:00 | Hapus permanen isi Kotak Sampah > 30 hari |
| `db:backup` | 01:30 | Backup database (retensi 14 hari) |

Ketiganya juga bisa dijalankan manual, misalnya:

```bash
php artisan stock:check-minimum
php artisan db:backup
```

> **Catatan `db:backup`:** perintah ini memakai `mysqldump`. Bila muncul pesan *"mysqldump is not recognized"*, isi `MYSQLDUMP_PATH` di `.env` (contohnya sudah tersedia di `.env.example`).

---

## Pengaturan Email (opsional)

Notifikasi email (permintaan baru, persetujuan, distribusi, stok menipis) aktif setelah SMTP diisi. Ada dua cara:

1. **Lewat aplikasi** — masuk sebagai admin → **Pengaturan › Email**, isi alamat & password aplikasi, lalu klik **Kirim Email Uji**. Password disimpan terenkripsi dan menimpa nilai di `.env`.
2. **Lewat `.env`** — isi `MAIL_USERNAME`, `MAIL_PASSWORD`, dan `MAIL_FROM_ADDRESS`.

> Untuk Gmail, gunakan **App Password** (bukan password akun biasa).

---

## Menjalankan Pengujian

```bash
php artisan test
```

Pengujian memakai SQLite di memori, jadi **tidak menyentuh database asli**.

---

## Perintah yang Sering Dipakai

```bash
php artisan migrate:fresh --seed   # reset database + isi ulang data contoh
php artisan optimize:clear         # bersihkan semua cache (config, route, view)
php artisan view:clear             # bersihkan cache tampilan saja
```

> Bila tampilan terlihat berantakan setelah pembaruan, biasanya karena cache browser — muat ulang paksa dengan **Ctrl + Shift + R**.

---

## Struktur Peran

| Peran | Ringkasan |
|---|---|
| **Administrator** | Akses penuh: master data, pengguna, kuota, audit log, pengaturan, Kotak Sampah |
| **Kasubag Umum** | Menyetujui/menolak permintaan, melihat laporan & data barang |
| **Petugas Gudang** | Barang masuk, distribusi, stock opname, data barang |
| **Kepala Bidang** | Mengajukan permintaan & memantau sisa kuota bidangnya sendiri |
| **Pimpinan** | Hanya melihat laporan dan data barang |

---

## Menyiapkan untuk Produksi

Jangan pakai konfigurasi lokal di server. Ikuti checklist lengkap di **[DEPLOYMENT.md](DEPLOYMENT.md)** — mencakup `.env` produksi (`APP_DEBUG=false`, HTTPS, cookie aman), optimasi cache, penjadwal, dan keamanan server.

---

## Pemecahan Masalah

| Gejala | Penyebab & solusi |
|---|---|
| `SQLSTATE[HY000] [1049] Unknown database` | Database `inventaris_atk` belum dibuat — ulangi langkah 3 |
| `No application encryption key has been specified` | Jalankan `php artisan key:generate` |
| Logo/gambar tidak muncul | Jalankan `php artisan storage:link` |
| Tampilan berantakan setelah update | Cache browser — tekan **Ctrl + Shift + R** |
| `mysqldump is not recognized` saat backup | Isi `MYSQLDUMP_PATH` di `.env` |
| Peringatan unduhan "not secure" di Chrome | Akses lewat `https://` (aktifkan SSL Laragon: klik kanan Laragon → Apache → SSL) |
| Email tidak terkirim | Cek Pengaturan › Email, gunakan App Password untuk Gmail |
