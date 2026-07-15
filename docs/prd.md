# PRD — SiATK: Sistem Informasi Inventaris Alat Tulis Kantor

**Versi:** 2.2  
**Tanggal:** 2 Juli 2026  
**Status:** Aktif / Dalam Pengembangan

---

## 1. Ringkasan Eksekutif

SiATK adalah sistem manajemen inventaris berbasis web untuk pengelolaan Alat Tulis Kantor (ATK) di lingkungan perkantoran/instansi. Sistem ini mengotomasi seluruh siklus hidup ATK — mulai dari penerimaan barang dari supplier, pengajuan permintaan oleh tiap bidang, persetujuan oleh kasubag, distribusi oleh petugas gudang, hingga pelaporan kepada pimpinan — menggantikan proses manual berbasis spreadsheet atau form kertas. Selain barang habis pakai, sistem juga mengelola aset non-habis-pakai (barang permanen) secara terpisah.

**Masalah yang diselesaikan:**
- Tidak ada visibilitas real-time atas stok gudang
- Proses permintaan ATK tidak terstruktur dan tidak bisa dilacak
- Tidak ada kontrol atas jumlah permintaan yang wajar per bidang (over-request)
- Tidak ada penanggung jawab yang jelas untuk setiap transaksi distribusi
- Laporan sulit didistribusikan karena belum ada ekspor ke format dokumen formal

---

## 2. Tujuan Produk

| # | Tujuan | Indikator Keberhasilan |
|---|--------|------------------------|
| 1 | Visibilitas stok real-time | Stok ter-update otomatis saat barang masuk dan terdistribusi |
| 2 | Alur permintaan terstruktur | Setiap permintaan memiliki status yang dapat dilacak (pending → selesai) |
| 3 | Pencegahan over-request | Deteksi otomatis permintaan yang melebihi ambang wajar |
| 4 | Akuntabilitas distribusi | Setiap transaksi tercatat dengan nomor unik dan penanggung jawab |
| 5 | Laporan berbasis data & siap ekspor | Laporan stok, masuk, keluar, dan per-bidang tersedia setiap saat serta dapat diekspor ke PDF/Excel |
| 6 | Keamanan akses berbasis peran | Setiap pengguna hanya melihat dan melakukan apa yang diizinkan perannya |
| 7 | Pengelolaan aset permanen | Aset non-habis-pakai terdata beserta kondisi, lokasi, dan penanggung jawabnya |

---

## 3. Pengguna dan Peran (Roles)

Sistem memiliki **5 peran** dengan hak akses berbeda.

### 3.1 Tabel Peran

| Peran | Label | Deskripsi |
|-------|-------|-----------|
| `admin` | Administrator | Akses penuh ke seluruh sistem |
| `pimpinan` | Pimpinan | Dashboard eksekutif dan laporan (read-only) |
| `kasubag_umum` | Kasubag Umum | Menyetujui/menolak permintaan ATK |
| `kepala_bidang` | Kepala Bidang | Mengajukan permintaan atas nama bidangnya |
| `petugas_gudang` | Petugas Gudang | Input barang masuk, proses distribusi, dan kelola aset |

### 3.2 Matrix Hak Akses

| Modul / Aksi | Admin | Pimpinan | Kasubag | Kepala Bidang | Gudang |
|---|:---:|:---:|:---:|:---:|:---:|
| Dashboard (view) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Data Barang (view) | ✅ | — | — | ✅ | ✅ |
| Data Barang (CRUD) | ✅ | — | — | — | — |
| Barang Masuk (view+create) | ✅ | — | — | — | ✅ |
| Manajemen Aset (view) | ✅ | ✅ | — | — | ✅ |
| Manajemen Aset (CRUD) | ✅ | — | — | — | ✅* |
| Permintaan (view semua) | ✅ | — | ✅ | ✅** | ✅ |
| Permintaan (buat, multi-item) | ✅ | — | — | ✅ | — |
| Persetujuan (setuju/tolak) | ✅ | — | ✅ | — | — |
| Distribusi (proses) | ✅ | — | — | — | ✅ |
| Laporan (+ ekspor PDF/Excel) | ✅ | ✅ | ✅ | ✅** | — |
| Master Data (CRUD) | ✅ | — | — | — | — |
| Pengaturan Sistem | ✅ | — | — | — | — |

*) Gudang dapat menambah aset dan memperbarui kondisi/status; penghapusan aset hanya oleh Admin.  
**) Kepala Bidang hanya dapat melihat data milik bidangnya sendiri.

---

## 4. Arsitektur Sistem

### 4.1 Stack Teknologi

| Layer | Teknologi |
|-------|-----------|
| Framework | Laravel 13 |
| Database | MySQL |
| Frontend Templating | Laravel Blade |
| Reaktivitas UI | Alpine.js 3.x (CDN) |
| Autentikasi | Laravel built-in Auth (session-based) |
| Penjadwalan | Laravel Scheduler + Queue (untuk reminder stok) |
| Ekspor Dokumen | Library PDF & Excel (mis. DomPDF / Maatwebsite Excel) |

### 4.2 Struktur Direktori Utama

```
inventaris-atk/
├── app/
│   ├── Console/Commands/
│   │   └── CheckMinimumStock.php        (scheduled job reminder stok)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/LoginController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── BarangController.php
│   │   │   ├── BarangMasukController.php
│   │   │   ├── AsetController.php
│   │   │   ├── PermintaanController.php
│   │   │   ├── PersetujuanController.php
│   │   │   ├── DistribusiController.php
│   │   │   ├── LaporanController.php
│   │   │   ├── KategoriController.php
│   │   │   ├── BidangController.php
│   │   │   ├── SupplierController.php
│   │   │   ├── PenggunaController.php
│   │   │   ├── KuotaController.php
│   │   │   ├── NotifikasiController.php
│   │   │   └── PengaturanController.php
│   │   └── Middleware/RoleMiddleware.php
│   └── Models/
│       ├── User.php
│       ├── Department.php
│       ├── Category.php
│       ├── Supplier.php
│       ├── Item.php
│       ├── Asset.php
│       ├── StockIn.php / StockInDetail.php
│       ├── StockOut.php
│       ├── Request.php / RequestDetail.php
│       ├── RequestQuota.php
│       ├── Notification.php
│       └── Setting.php
├── resources/views/
│   ├── layouts/app.blade.php
│   ├── auth/login.blade.php
│   ├── dashboard/ (admin, kasubag, kabid, gudang)
│   ├── barang/index.blade.php           (tambah/edit via modal)
│   ├── barang-masuk/index.blade.php     (tambah via modal)
│   ├── aset/index.blade.php             (tambah/edit via modal)
│   ├── permintaan/ (index, show)        (buat via modal, multi-item)
│   ├── distribusi/ (index, show)
│   ├── laporan/index.blade.php
│   ├── kategori/, bidang/, supplier/, pengguna/, kuota/  (modal)
│   ├── pengaturan/index.blade.php
│   └── components/ (sort-th, status-badge, modal, toast, searchable-select)
└── public/
```

---

## 5. Alur Kerja Utama (Business Flow)

### 5.1 Alur Permintaan ATK

Permintaan bersifat **multi-item**: dalam satu pengajuan, Kepala Bidang dapat menambahkan lebih dari satu barang beserta qty masing-masing.

```
[Kepala Bidang]
      │
      ▼ Buat Permintaan (PRM-YYMM-NNN) — via modal
      │   • Tambah beberapa baris barang & qty (multi-item)
      │   • Isi justifikasi (jika perlu)
      │   ↳ Sistem deteksi over-request (qty > kuota × 1.5 → flag ⚠)
      │
      ▼ Notifikasi + toast dikirim ke Kasubag Umum
      │
[Kasubag Umum]
      │
      ├─ Setujui → input qty disetujui per item (boleh parsial)
      │            status: pending → disetujui
      │            Notifikasi ke: pemohon + semua petugas gudang
      │
      └─ Tolak  → wajib isi alasan penolakan
                  status: pending → ditolak
                  Notifikasi ke: pemohon
      │
[Petugas Gudang] (hanya jika disetujui)
      │
      ▼ Proses Distribusi (BKL-YYMM-NNN per item)
      │   • Input qty aktual yang didistribusikan (≤ qty disetujui)
      │   • Jika dikurangi, wajib isi alasan pengurangan
      │   • Stok barang otomatis berkurang
      │   ↳ Jika semua item terpenuhi: status → selesai
      │   ↳ Jika ada yang dikurangi: status → selesai_sebagian
      │
      ▼ Notifikasi ke pemohon (Kepala Bidang)
```

### 5.2 Alur Barang Masuk

```
[Petugas Gudang / Admin]
      │
      ▼ Input Penerimaan Barang (BMS-YYMM-NNN) — via modal
      │   • Pilih supplier (dropdown dengan pencarian)
      │   • Tambah baris item (barang + qty diterima)
      │   • Stok barang otomatis bertambah
      └─ Rekap tersimpan di riwayat barang masuk
```

Setiap aksi penting (buat/setuju/tolak/distribusi/simpan) memunculkan **notifikasi toast** di pojok kanan atas serta mencatat notifikasi in-app bagi pihak terkait.

---

## 6. Spesifikasi Fitur Per Modul

### 6.1 Autentikasi & Keamanan Akun

**Halaman:** `/login`

| Fitur | Detail |
|-------|--------|
| Login | Email + password, opsi "Ingat Saya" |
| Validasi | Format email, field wajib |
| Rate limiting | Throttle percobaan login per email + IP |
| Keamanan | 5 gagal login → kunci akun 15 menit |
| Pesan error | Menampilkan sisa percobaan (misal: "3 percobaan tersisa") |
| Redirect | Setelah login → `/dashboard`, setelah logout → `/login` |
| Status akun | Akun nonaktif tidak bisa login |
| Sesi | Regenerasi session ID saat login; idle timeout otomatis logout |

**Format pesan kunci:** `"Akun terkunci. Coba lagi dalam {N} menit."`

---

### 6.2 Dashboard (Per Role)

#### Dashboard Admin & Pimpinan
- **Stat cards (4):** Stok Perlu Perhatian, Permintaan Pending, Total Jenis Barang, Penerimaan Bulan Ini
- **Alert over-request:** Banner peringatan jika ada permintaan flagged yang belum diproses
- **5 Permintaan terbaru:** Tabel ringkas no. permintaan, bidang, pengaju, status
- **Statistik per bidang:** Tabel total/disetujui/ditolak/selesai/flagged per departemen bulan ini

#### Dashboard Kasubag Umum
- **Antrean permintaan pending:** Semua permintaan belum diproses, dengan indikator flag
- **Ringkasan per bidang:** Pending, total, flagged per bidang bulan ini
- **5 keputusan terbaru:** Permintaan yang sudah disetujui/ditolak bulan ini

#### Dashboard Kepala Bidang
- **Stat cards (4):** Total permintaan bulan ini, sedang diproses, selesai, total unit diterima
- **Penggunaan kuota per barang:** Progress penggunaan vs kuota per item untuk bidangnya
- **4 permintaan terbaru:** Dari bidangnya sendiri

#### Dashboard Petugas Gudang
- **Stat cards (4):** Total jenis barang, stok menipis, stok habis, total unit di gudang
- **Antrean distribusi:** Permintaan berstatus "disetujui" yang belum didistribusikan
- **Daftar stok kritis:** Barang dengan stok ≤ minimum

---

### 6.3 Data Barang (Master Inventaris — Habis Pakai)

**URL:** `/barang`  
**Akses view:** Admin, Kepala Bidang, Petugas Gudang  
**Akses CRUD:** Admin saja (tambah/edit **via modal**, tanpa halaman terpisah)

| Field | Tipe | Keterangan |
|-------|------|------------|
| `code` | VARCHAR(30) UNIQUE | Kode unik barang (contoh: ATK-001) |
| `category_id` | FK → categories | Kategori pengelompokan |
| `name` | VARCHAR | Nama barang ATK |
| `unit` | VARCHAR(30) | Satuan (rim, buah, pak, dll.) |
| `stock` | UINT | Stok saat ini (real-time) |
| `minimum_stock` | UINT | Batas minimum stok (trigger alert & reminder) |
| `reorder_point` | UINT nullable | Titik reorder (opsional) |
| `location` | VARCHAR(100) nullable | Lokasi penyimpanan di gudang |
| `description` | TEXT nullable | Keterangan tambahan |

**Status stok:**
- `Aman`: stock > minimum_stock
- `Menipis`: 0 < stock ≤ minimum_stock
- `Habis`: stock = 0

**Fitur tampilan:**
- Filter: kategori, status stok, pencarian (nama/kode)
- Sorting: kode, nama, stok, minimum stok
- Pagination + per-page: 10/25/50/100
- Soft delete (data tidak hilang dari database)

---

### 6.4 Barang Masuk (Penerimaan Stok)

**URL:** `/barang-masuk`  
**Akses:** Admin, Petugas Gudang  
**Input:** tambah penerimaan **via modal** (multi-item)

**Header transaksi:**

| Field | Detail |
|-------|--------|
| `transaction_no` | Auto-generate: `BMS-YYMM-NNN` (contoh: BMS-2406-001) |
| `supplier_id` | FK → suppliers (opsional jika tanpa supplier) |
| `date` | Tanggal penerimaan |
| `note` | Catatan tambahan |
| `created_by` | User yang menginput |

**Detail transaksi (per baris item):**

| Field | Detail |
|-------|--------|
| `item_id` | FK → items |
| `quantity` | Jumlah unit diterima |

**Efek:** Setiap item yang diinput menambah `items.stock` secara atomik.

**Fitur tampilan:**
- Filter: pencarian no. transaksi/supplier, filter rentang tanggal (date_from–date_to)
- Sorting: no. transaksi, tanggal
- Pagination + per-page: 10/25/50/100

---

### 6.5 Permintaan ATK

**URL:** `/permintaan`  
**Akses buat:** Kepala Bidang, Admin (buat **via modal**)  
**Akses lihat:** Admin, Kasubag, Kepala Bidang (bidangnya saja), Petugas Gudang

Satu permintaan dapat memuat **lebih dari satu barang** (multi-item): pemohon menambahkan beberapa baris item dengan qty masing-masing dalam satu pengajuan.

**Header permintaan:**

| Field | Detail |
|-------|--------|
| `request_no` | Auto-generate: `PRM-YYMM-NNN` (contoh: PRM-2406-005) |
| `user_id` | Pengaju (Kepala Bidang) |
| `department_id` | Bidang pengaju |
| `request_date` | Tanggal pengajuan |
| `status` | Lihat alur status di bawah |
| `approver_id` | FK → users (diisi saat persetujuan) |
| `approved_date` | Timestamp persetujuan/penolakan |
| `rejection_reason` | Alasan jika ditolak |
| `is_flagged` | Boolean: true jika terdeteksi over-request |
| `justification` | Alasan pengajuan (opsional) |
| `note` | Catatan tambahan |

**Detail permintaan (per baris item):**

| Field | Detail |
|-------|--------|
| `item_id` | FK → items |
| `quantity_requested` | Qty diminta oleh Kepala Bidang |
| `quantity_approved` | Qty disetujui oleh Kasubag (boleh < requested) |
| `quantity_distributed` | Qty aktual didistribusikan oleh Gudang |
| `reduction_reason` | Alasan jika distribusi dikurangi dari yang disetujui |

**Alur Status:**
```
pending → disetujui → selesai
                    → selesai_sebagian
        → ditolak
```

**Deteksi Over-Request:**
- Kuota per item = `minimum_stock × 2` (minimum 10)
- Flagged jika ada item dengan `qty_requested > kuota × 1.5`
- Request yang flagged tetap bisa diajukan namun diberi tanda ⚠ di seluruh tampilan

**Fitur tampilan:**
- Filter: pencarian no. permintaan, status, bidang, checkbox "Over-Request"
- Sorting: no. permintaan, tanggal, status
- Pagination + per-page: 10/25/50/100
- Kepala Bidang tidak melihat filter bidang (langsung ke bidangnya saja)

---

### 6.6 Persetujuan

**URL:** `POST /permintaan/{id}/approve` atau `/reject`  
**Akses:** Kasubag Umum, Admin

**Approve:**
- Input `quantity_approved` per item (0 hingga `quantity_requested`)
- Validasi: qty_approved tidak boleh melebihi qty_requested
- Status berubah: `pending → disetujui`
- Notifikasi + toast dikirim ke: pemohon + semua petugas_gudang

**Reject:**
- Wajib isi `rejection_reason` (minimal 5 karakter)
- Status berubah: `pending → ditolak`
- Notifikasi + toast dikirim ke: pemohon

---

### 6.7 Distribusi

**URL:** `/distribusi`  
**Akses:** Petugas Gudang, Admin

**Halaman Index:**
- **Antrean distribusi:** Semua permintaan berstatus `disetujui`, belum didistribusikan
- **Riwayat 20 distribusi terbaru:** Transaksi keluar yang sudah diproses

**Proses Distribusi:**
- Input qty distribusi per item (≤ `quantity_approved`)
- Jika qty distribusi < qty_approved: wajib isi `reduction_reason`
- Setiap distribusi item → record `StockOut` baru dengan no. `BKL-YYMM-NNN`
- Stok item dikurangi atomik (dalam DB transaction)
- Status permintaan:
  - Semua item terpenuhi penuh → `selesai`
  - Ada item yang dikurangi → `selesai_sebagian`
- Notifikasi + toast ke pemohon setelah distribusi selesai

---

### 6.8 Laporan

**URL:** `/laporan`  
**Akses:** Admin, Pimpinan, Kasubag Umum, Kepala Bidang

**5 Jenis Laporan:**

| Tipe | Parameter | Isi |
|------|-----------|-----|
| `stok` | — | Daftar semua barang + status stok saat ini |
| `masuk` | periode (Y-m) | Semua penerimaan barang dalam periode |
| `keluar` | periode, bidang | Semua distribusi dalam periode, filter per bidang |
| `permintaan` | periode, bidang | Semua permintaan dalam periode, filter per bidang |
| `bidang` | periode | Ringkasan statistik per bidang (total/disetujui/ditolak/selesai/flagged/totalQty) |

**Filter:**
- Periode: format `YYYY-MM` (default bulan ini)
- Bidang: dropdown dengan pencarian (Kepala Bidang di-lock ke bidangnya sendiri)

**Ekspor:**
- Setiap laporan dapat diekspor ke **PDF** (siap cetak, ber-kop dinamis mengikuti Pengaturan Sistem) dan **Excel (.xlsx)**
- Ekspor menghormati filter periode/bidang yang sedang aktif

---

### 6.9 Master Data Kategori

**URL:** `/kategori` (Admin only) — tambah/edit **via modal**

| Field | Detail |
|-------|--------|
| `name` | Nama kategori (contoh: Kertas, Alat Tulis, Tinta) |
| `description` | Keterangan singkat (opsional) |

- Tombol hapus hanya muncul jika `items_count = 0` (tidak ada barang dalam kategori)

---

### 6.10 Master Data Bidang/Departemen

**URL:** `/bidang` (Admin only) — tambah/edit **via modal**

| Field | Detail |
|-------|--------|
| `code` | Kode singkat bidang (contoh: KEU, SDM, TIK) |
| `name` | Nama bidang lengkap |
| `head_user_id` | FK → users (Kepala Bidang yang bertugas) |
| `description` | Keterangan (opsional) |

- Tombol hapus hanya muncul jika `users_count = 0`

---

### 6.11 Master Data Supplier

**URL:** `/supplier` (Admin only) — tambah/edit **via modal**

| Field | Detail |
|-------|--------|
| `name` | Nama perusahaan supplier |
| `phone` | Nomor telepon |
| `email` | Alamat email |
| `address` | Alamat lengkap |

- Menampilkan jumlah transaksi barang masuk per supplier (`stock_ins_count`)
- Filter: pencarian nama/telepon/email
- Sorting: nama, telepon, email
- Pagination + per-page: 10/25/50/100

---

### 6.12 Manajemen Pengguna

**URL:** `/pengguna` (Admin only) — tambah/edit **via modal**

| Field | Detail |
|-------|--------|
| `name` | Nama lengkap |
| `email` | Email (unique, untuk login) |
| `password` | Hash bcrypt, min 8 karakter, wajib kombinasi huruf & angka |
| `role` | Salah satu dari 5 peran |
| `department_id` | FK → departments (wajib untuk kepala_bidang) |
| `is_active` | Boolean: akun aktif/nonaktif |
| `failed_login_count` | Counter percobaan login gagal |
| `locked_until` | Timestamp akhir masa kunci akun |

- Avatar otomatis dari inisial nama
- Filter: pencarian nama/email, filter role (dropdown dengan pencarian)
- Sorting: nama, email, role
- Pagination + per-page: 10/25/50/100
- Admin tidak bisa menghapus akunnya sendiri

---

### 6.13 Kuota Bidang

**URL:** `/kuota` (Admin only) — tambah/edit **via modal**

Sistem kuota membatasi jumlah permintaan per bidang agar tidak over-request.

| Field | Detail |
|-------|--------|
| `department_id` | Bidang yang diatur |
| `item_id` | Barang spesifik (null = semua barang) |
| `category_id` | Kategori spesifik (null = semua kategori) |
| `period_type` | `monthly`, `quarterly`, `yearly` |
| `quota_quantity` | Batas maksimum qty per periode |
| `threshold_percent` | Persen anomali (default 150%, artinya 1.5× kuota) |
| `cooldown_days` | Jeda hari sebelum bisa minta lagi |
| `policy` | `warn` (hanya peringatan) atau `block` (blokir) |
| `effective_from` | Tanggal mulai berlaku |
| `is_active` | Status aktif/nonaktif |

---

### 6.14 Manajemen Aset (Non-Habis-Pakai)

**URL:** `/aset`  
**Akses view:** Admin, Pimpinan, Petugas Gudang  
**Akses kelola:** Admin & Petugas Gudang (tambah/edit **via modal**); hapus hanya Admin

Modul untuk barang permanen/tidak habis pakai (mis. printer, meja, dispenser) yang tidak masuk siklus stok ATK.

| Field | Detail |
|-------|--------|
| `code` | Auto-generate: `AST-YYMM-NNN` |
| `name` | Nama aset |
| `category_id` | FK → categories (opsional) |
| `department_id` | FK → departments (lokasi/pemegang, nullable) |
| `acquisition_date` | Tanggal perolehan |
| `acquisition_value` | Nilai perolehan (nullable) |
| `condition` | `baik`, `rusak_ringan`, `rusak_berat` |
| `status` | `tersedia`, `digunakan`, `perbaikan`, `dihapus` |
| `location` | Lokasi fisik aset |
| `note` | Keterangan tambahan |

**Fitur tampilan:**
- Filter: pencarian nama/kode, kategori, kondisi, status, bidang (dropdown dengan pencarian)
- Sorting: kode, nama, kondisi, status, tanggal perolehan
- Pagination + per-page: 10/25/50/100
- Soft delete (aset dinonaktifkan, tidak dihapus permanen)

---

### 6.15 Pengaturan Sistem (Konten Dinamis)

**URL:** `/pengaturan` (Admin only)

Modul untuk mengatur konten yang tidak permanen agar dinamis, tanpa mengubah kode.

| Kunci | Detail |
|-------|--------|
| `app_name` | Nama aplikasi (tampil di topbar & judul tab) |
| `institution_name` | Nama instansi |
| `logo` | Upload berkas logo (validasi tipe gambar + batas ukuran) — tampil di sidebar, login, dan kop laporan |
| `favicon` | Upload favicon (opsional) |
| `address` | Alamat instansi (untuk kop laporan) |
| `footer_text` | Teks footer aplikasi |
| `contact_email` / `contact_phone` | Kontak instansi |

- Nilai disimpan sebagai key–value dan di-cache untuk performa
- Perubahan logo/nama langsung tercermin di seluruh aplikasi dan pada ekspor laporan PDF

---

### 6.16 Sistem Notifikasi

Notifikasi in-app berbasis database, dilengkapi **notifikasi toast** di pojok kanan atas untuk setiap aksi penting.

**Notifikasi in-app (pusat notifikasi / bell icon):**

| Tipe | Dikirim Ke | Trigger |
|------|-----------|---------|
| `new_request` | Semua Kasubag Umum | Kepala Bidang membuat permintaan baru |
| `request_approved` | Pemohon (Kepala Bidang) | Kasubag menyetujui permintaan |
| `ready_to_distribute` | Semua Petugas Gudang | Kasubag menyetujui permintaan |
| `request_rejected` | Pemohon (Kepala Bidang) | Kasubag menolak permintaan |
| `request_distributed` | Pemohon (Kepala Bidang) | Petugas Gudang menyelesaikan distribusi |
| `low_stock` | Admin + Petugas Gudang | Reminder stok minimum (scheduled job) |

- Ikon lonceng menampilkan badge jumlah notifikasi belum dibaca
- Klik notifikasi → tandai terbaca + arahkan ke sumber terkait

**Notifikasi toast (pojok kanan atas):**
- Muncul otomatis pada aksi penting (simpan, setuju, tolak, distribusi, hapus, error validasi/otorisasi)
- Tipe: `success`, `error`, `warning`, `info`
- Auto-dismiss (mis. 4–5 detik) dan dapat ditutup manual
- Diimplementasikan via Alpine.js + session flash

---

### 6.17 Reminder Stok Minimum (Scheduled Job)

- Perintah terjadwal (`CheckMinimumStock`) berjalan harian melalui Laravel Scheduler
- Memindai barang dengan `stock ≤ minimum_stock`
- Membuat notifikasi `low_stock` untuk Admin dan seluruh Petugas Gudang
- Dijalankan melalui queue agar tidak memblokir proses lain
- Dapat dikembangkan untuk mengirim notifikasi email (lihat roadmap)

---

## 7. Model Data (Entity Relationship)

### 7.1 Tabel dan Atribut Lengkap

```
users
  id, name, email, password,
  role (enum: admin|pimpinan|kasubag_umum|kepala_bidang|petugas_gudang),
  department_id (FK), is_active, failed_login_count, locked_until,
  remember_token, email_verified_at, timestamps

departments
  id, code, name, head_user_id (FK→users nullable),
  description, timestamps

categories
  id, name, description, timestamps

suppliers
  id, name, phone, email, address, timestamps

items
  id, code (unique), category_id (FK), name, unit,
  stock (uint, default 0), minimum_stock (uint),
  reorder_point (uint nullable), location (varchar 100),
  description (text), timestamps, deleted_at (soft delete)

assets
  id, code (AST-YYMM-NNN unique), name, category_id (FK nullable),
  department_id (FK nullable), acquisition_date (date),
  acquisition_value (decimal nullable),
  condition (enum: baik|rusak_ringan|rusak_berat),
  status (enum: tersedia|digunakan|perbaikan|dihapus),
  location (varchar nullable), note (text nullable),
  timestamps, deleted_at (soft delete)

stock_ins
  id, transaction_no (BMS-YYMM-NNN), supplier_id (FK nullable),
  date, note, created_by (FK→users), timestamps

stock_in_details
  id, stock_in_id (FK→stock_ins cascade), item_id (FK→items),
  quantity (uint), timestamps

requests
  id, request_no (PRM-YYMM-NNN unique), user_id (FK),
  department_id (FK), request_date,
  status (enum: pending|disetujui|ditolak|selesai|selesai_sebagian),
  approver_id (FK→users nullable), approved_date (timestamp nullable),
  rejection_reason (text nullable), is_flagged (boolean),
  justification (text nullable), note (text nullable), timestamps

request_details
  id, request_id (FK→requests cascade), item_id (FK),
  quantity_requested (uint), quantity_approved (uint nullable),
  quantity_distributed (uint default 0),
  reduction_reason (text nullable), timestamps

stock_outs
  id, transaction_no (BKL-YYMM-NNN), item_id (FK), quantity (uint),
  department_id (FK), request_id (FK nullable),
  type (varchar: request/manual), date,
  note (text nullable), created_by (FK→users), timestamps

request_quotas
  id, department_id (FK), item_id (FK nullable),
  category_id (FK nullable), period_type (monthly|quarterly|yearly),
  quota_quantity (uint), threshold_percent (uint default 150),
  cooldown_days (uint), policy (warn|block),
  effective_from (date), is_active (boolean), timestamps

notifications
  id, user_id (FK), type (varchar), message (text),
  reference_id (uint nullable), reference_type (varchar nullable),
  is_read (boolean default false), read_at (timestamp nullable), timestamps

settings
  id, key (varchar unique), value (text nullable),
  type (varchar: text|image|email|phone), timestamps
```

### 7.2 Relasi Utama

```
users           ── belongs to ──▶ departments
departments     ── has many ───▶ users
departments     ── has many ───▶ requests
departments     ── has many ───▶ assets
categories      ── has many ───▶ items
categories      ── has many ───▶ assets
items           ── has many ───▶ stock_in_details
items           ── has many ───▶ stock_outs
items           ── has many ───▶ request_details
suppliers       ── has many ───▶ stock_ins
stock_ins       ── has many ───▶ stock_in_details
requests        ── has many ───▶ request_details
requests        ── has many ───▶ stock_outs
request_details ── belongs to ──▶ requests, items
stock_outs      ── belongs to ──▶ items, departments, requests (nullable), users
```

---

## 8. Aturan Bisnis

### 8.1 Nomor Transaksi
- Format seragam: `PREFIX-YYMM-NNN` (3 digit berurutan per bulan)
- Permintaan: `PRM-YYMM-NNN`
- Barang Masuk: `BMS-YYMM-NNN`
- Barang Keluar/Distribusi: `BKL-YYMM-NNN`
- Aset: `AST-YYMM-NNN`
- NNN dimulai dari 001 setiap bulan baru

### 8.2 Stok

| Aturan | Detail |
|--------|--------|
| Stok bertambah | Hanya saat barang masuk dikonfirmasi |
| Stok berkurang | Hanya saat distribusi dikonfirmasi (per item per distribusi) |
| Stok minimum | Tampilkan alert jika `stock ≤ minimum_stock` |
| Stok habis | Tampilkan alert danger jika `stock = 0` |
| Reminder | Scheduled job harian mengirim notifikasi saat `stock ≤ minimum_stock` |
| Atomisitas | Semua perubahan stok dalam DB transaction |

### 8.3 Over-Request
- Kuota efektif per item = `MAX(item.minimum_stock × 2, 10)`
- Flagged jika ada item dengan `qty_requested > kuota_efektif × 1.5`
- Flagged tidak memblokir pengajuan — hanya memberi tanda ⚠
- Kebijakan blokir sesungguhnya dikonfigurasi di modul Kuota Bidang

### 8.4 Permintaan Multi-Item & Persetujuan Parsial
- Satu permintaan boleh memuat banyak item (minimal 1 baris item)
- Kasubag boleh menyetujui qty < yang diminta (qty_approved < qty_requested)
- Gudang boleh mendistribusikan qty < yang disetujui (qty_distributed < qty_approved), dengan wajib mengisi alasan pengurangan

### 8.5 Keamanan Akun
- Gagal login 5× berturut-turut → akun dikunci 15 menit (`locked_until = now() + 15 min`)
- Lockout counter direset setelah login berhasil
- Percobaan login dibatasi throttle per email + IP

### 8.6 Pembatasan Akses Data
- Kepala Bidang: HANYA bisa melihat dan membuat permintaan untuk `department_id`-nya sendiri
- Laporan Keluar: Kepala Bidang hanya bisa filter ke bidangnya sendiri, tidak bisa pilih bidang lain

### 8.7 Penghapusan Data
- Item dan Aset menggunakan **soft delete** (`deleted_at`) — tidak terhapus dari DB
- Kategori tidak bisa dihapus jika masih ada item/aset terkait
- Bidang tidak bisa dihapus jika masih ada user terkait
- Admin tidak bisa menghapus akun dirinya sendiri

### 8.8 Aset
- Aset non-habis-pakai tidak memengaruhi stok ATK
- Perubahan kondisi/status aset dapat dilakukan Admin & Petugas Gudang
- Penghapusan aset hanya oleh Admin (soft delete → status `dihapus`)

### 8.9 Pengaturan Sistem
- Nilai pengaturan disimpan key–value dan di-cache
- Upload logo/favicon divalidasi tipe (image) dan ukuran maksimum

---

## 9. Antarmuka & Interaksi

### 9.1 Pola Umum
- **Modal untuk tambah/edit:** Semua aksi "tambah" dan "edit" pada tiap menu menggunakan modal, tanpa membuka halaman baru
- **Notifikasi toast:** Muncul di pojok kanan atas pada setiap aksi penting
- **Dropdown dengan pencarian:** Semua dropdown/select (pilih barang, supplier, bidang, kategori, user, filter) memiliki kolom pencarian di dalamnya

### 9.2 Komponen UI Utama (Perilaku Fungsional)

**Filter Bar** (semua halaman listing)
- Pencarian real-time dengan debounce (Alpine.js)
- Selector filter: status, bidang, tanggal, dll. (auto-submit onchange)
- Tombol reset (tampil hanya jika ada filter aktif)
- Per-page selector: 10/25/50/100 data

**Tabel** (berlaku untuk **semua** tabel)
- Pagination di setiap tabel
- Header sortable (3-state: ASC → DESC → reset) di setiap tabel
- Empty state informatif saat data kosong

**Pagination Bar**
- Informasi "Menampilkan X–Y dari Z item"
- Semua query string (sort, dir, search, filter) dipertahankan antar halaman

**Modal**
- Overlay background (dismiss on click)
- Digunakan untuk seluruh form tambah/edit dan konfirmasi hapus

**Status Badge** (komponen `<x-status-badge>`)
- pending → "Menunggu"
- disetujui → "Disetujui"
- ditolak → "Ditolak"
- selesai → "Selesai"
- selesai_sebagian → "Selesai Sebagian"

**Toast** (komponen `<x-toast>`)
- Posisi pojok kanan atas, tipe success/error/warning/info, auto-dismiss + tutup manual

### 9.3 Responsivitas (Desktop & Mobile)

Seluruh antarmuka — konten di dalam web maupun elemen navigasi — bersifat responsif dan menyesuaikan ukuran layar.

| Perangkat | Perilaku |
|-----------|----------|
| Desktop | Sidebar penuh, tabel tampil utuh, layout multi-kolom |
| Tablet | Sidebar dapat diciutkan/overlay, tabel dapat di-scroll horizontal |
| Mobile | Navigasi ringkas (hamburger), tabel scroll/stack, form & modal full-width, filter bar menumpuk agar mudah diakses |

- Grid konten, kartu statistik, dan modal menyesuaikan lebar layar
- Elemen interaktif tetap dapat diakses dengan sentuhan pada layar kecil

---

## 10. Keamanan

| Aspek | Implementasi |
|-------|-------------|
| Autentikasi | Laravel session-based auth, regenerasi session saat login |
| Otorisasi | Middleware `role:{peran1,peran2}` per route group + policy isolasi data |
| Rate Limiting | Throttle pada login dan endpoint sensitif |
| CSRF Protection | `@csrf` di setiap form, token di meta tag |
| XSS | Auto-escaping Blade; sanitasi input yang ditampilkan |
| SQL Injection | Eloquent ORM + query builder parameterized |
| Mass Assignment | `$fillable`/`$guarded` pada setiap model |
| Validasi Input | Form Request validation di semua aksi tulis |
| Brute Force | Account lockout 5 gagal → kunci 15 menit + throttle |
| Akun Nonaktif | Dicek saat login, tidak bisa masuk |
| Password | Hash bcrypt, minimal 8 karakter, kombinasi huruf & angka |
| Sesi | Idle timeout, cookie `HttpOnly` + `Secure` (produksi), `SameSite` |
| Upload Berkas | Validasi tipe (image) & ukuran untuk logo/favicon |
| Security Headers | X-Frame-Options, X-Content-Type-Options, Referrer-Policy, dan sejenis |
| Transport | Enforce HTTPS di lingkungan produksi |
| Data Isolation | Kepala Bidang hanya akses data departemennya |
| Soft Delete | Barang & aset tidak benar-benar dihapus dari DB |

---

## 11. Penomoran Dokumen & Referensi Teknis

### 11.1 Format Nomor Transaksi

| Jenis | Format | Contoh |
|-------|--------|--------|
| Permintaan | `PRM-YYMM-NNN` | PRM-2406-007 |
| Barang Masuk | `BMS-YYMM-NNN` | BMS-2406-003 |
| Barang Keluar | `BKL-YYMM-NNN` | BKL-2406-012 |
| Aset | `AST-YYMM-NNN` | AST-2406-005 |

`YY` = 2 digit tahun, `MM` = 2 digit bulan, `NNN` = urutan 3 digit (reset tiap bulan)

### 11.2 Sortable Columns per Tabel

| Halaman | Kolom yang Bisa Disort |
|---------|------------------------|
| Data Barang | code, name, stock, minimum_stock, created_at |
| Barang Masuk | transaction_no, date, created_at |
| Aset | code, name, condition, status, acquisition_date |
| Permintaan | request_no, request_date, status, created_at |
| Supplier | name, phone, email, created_at |
| Pengguna | name, email, role, created_at |

> Catatan: pagination dan sorting berlaku pada seluruh tabel listing, bukan hanya yang tercantum di atas.

### 11.3 View Composer (SidebarComposer)
Disuntikkan otomatis ke semua layout, menyediakan:
- `$lowStockCount`: Jumlah barang dengan stok rendah (untuk badge sidebar)
- `$pendingCount`: Jumlah permintaan pending (untuk badge sidebar)
- `$queueCount`: Jumlah permintaan siap distribusi (untuk badge sidebar)
- `$unreadNotifCount`: Jumlah notifikasi belum dibaca (untuk badge lonceng)

---

