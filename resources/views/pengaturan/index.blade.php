@extends('layouts.app')
@section('title', 'Pengaturan Sistem')
@section('page-title', 'Pengaturan Sistem')
@section('page-crumb', 'Sistem')

@section('content')
<style>
  .settings { max-width: 900px; }
  .set-row {
    display: grid; grid-template-columns: 250px 1fr; gap: 28px;
    padding: 26px 0; border-bottom: 1px solid var(--line);
  }
  .set-row:first-child { padding-top: 8px; }
  .set-side h3 { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
  .set-side p  { font-size: 12.5px; color: var(--muted); line-height: 1.5; }
  .set-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .set-grid .full { grid-column: 1 / -1; }
  /* Kotak unggah gambar */
  .img-box {
    display: flex; align-items: center; gap: 14px; padding: 12px;
    border: 1px solid var(--line); border-radius: 12px; background: var(--surface-2); margin-bottom: 10px;
  }
  .img-box .prev { border-radius: 8px; border: 1px solid var(--line); background: #fff; object-fit: contain; flex: 0 0 auto; }
  .img-box .meta { min-width: 0; flex: 1; }
  .img-box .meta b { font-size: 13px; }
  .img-box .meta span { display: block; font-size: 12px; color: var(--muted); }
  .file-drop { display: block; }
  .save-bar {
    position: sticky; bottom: 0; margin-top: 22px; padding: 14px 0;
    background: linear-gradient(to top, var(--surface) 60%, transparent);
    display: flex; justify-content: flex-end; gap: 10px;
  }
  @media (max-width: 720px) { .set-row { grid-template-columns: 1fr; gap: 12px; } .set-grid { grid-template-columns: 1fr; } }
</style>

<div class="settings">
  <div class="page-head">
    <h2>Pengaturan Sistem</h2>
    <p>Identitas, tampilan, dan keamanan aplikasi. Perubahan langsung berlaku di seluruh sistem.</p>
  </div>

  @if($errors->any())
    <div class="notice warn" style="margin-bottom:16px"><span class="ic">⚠</span><div>{{ $errors->first() }}</div></div>
  @endif

  <form method="POST" action="{{ route('pengaturan.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Identitas --}}
    <div class="set-row">
      <div class="set-side">
        <h3>Identitas Aplikasi</h3>
        <p>Nama aplikasi tampil di sidebar &amp; judul tab. Tiga isian lainnya menyusun kop laporan, berurutan dari atas ke bawah.</p>
      </div>
      <div class="set-main">
        <div class="set-grid">
          <div class="field" style="margin:0">
            <label>Nama Aplikasi <span style="color:var(--danger)">*</span></label>
            <input type="text" name="app_name" value="{{ old('app_name', $settings['app_name'] ?? 'SIIB') }}" required>
            <span class="help">Singkat, mis. “BKD”.</span>
          </div>
          <div class="field" style="margin:0">
            <label>Induk Pemerintahan <span class="help">baris 1 kop</span></label>
            <input type="text" name="government_name" value="{{ old('government_name', $settings['government_name'] ?? '') }}" placeholder="mis. PEMERINTAH PROVINSI SUMATERA BARAT">
          </div>
          <div class="field full" style="margin:0">
            <label>Nama Instansi <span class="help">baris 2 kop — juga tampil di sidebar, halaman login &amp; email</span></label>
            <input type="text" name="institution_name" value="{{ old('institution_name', $settings['institution_name'] ?? '') }}" placeholder="mis. BADAN KEPEGAWAIAN DAERAH">
          </div>
          <div class="field full" style="margin:0">
            <label>Alamat Instansi <span class="help">baris 3 kop — tulis utuh termasuk telepon, fax, dan kota</span></label>
            <input type="text" name="address" value="{{ old('address', $settings['address'] ?? '') }}" placeholder="mis. Jalan Batang Antokan No. 4 Telepon (0751) 7054124 Fax. (0751) 7054804 Padang">
          </div>
        </div>
      </div>
    </div>

    {{-- Penanda tangan laporan --}}
    <div class="set-row">
      <div class="set-side">
        <h3>Penanda Tangan Laporan</h3>
        <p>Pejabat yang tanda tangannya dapat disertakan saat ekspor PDF/Excel. Nama, NIP, dan jabatan diambil dari data pengguna — cukup perbarui di Manajemen Pengguna bila berubah.</p>
      </div>
      <div class="set-main">
        <div class="set-grid">
          <div class="field" style="margin:0">
            <label>Pejabat Penanda Tangan</label>
            <x-searchable-select name="signer_user_id" :options="$signers"
                                 :selected="old('signer_user_id', $settings['signer_user_id'] ?? '')"
                                 placeholder="— Tidak ada —" search-placeholder="Cari pengguna…" />
            <span class="help">Kosongkan bila laporan tidak pernah perlu tanda tangan pimpinan.</span>
          </div>
          <div class="field" style="margin:0">
            <label>Tempat Penandatanganan</label>
            <input type="text" name="signature_place" value="{{ old('signature_place', $settings['signature_place'] ?? '') }}" placeholder="mis. Padang">
            <span class="help">Mendahului tanggal, mis. “Padang, 16 Juli 2026”.</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Logo & Favicon --}}
    <div class="set-row">
      <div class="set-side">
        <h3>Logo &amp; Favicon</h3>
        <p>Logo tampil di sidebar & login. Favicon adalah ikon kecil di tab browser.</p>
      </div>
      <div class="set-main">
        <div class="set-grid">
          <div class="field" style="margin:0">
            <label>Logo</label>
            @if(!empty($settings['logo']))
              <div class="img-box">
                <img class="prev" src="{{ asset('storage/'.$settings['logo']) }}" alt="Logo" style="height:44px;width:44px;padding:4px">
                <div class="meta"><b>Logo aktif</b><span>Unggah baru untuk mengganti</span></div>
                <button type="submit" form="del-logo-form" class="btn btn-danger btn-sm">✕ Hapus</button>
              </div>
            @endif
            <input type="file" name="logo" accept="image/*" class="inp file-drop">
            <span class="help">PNG / JPG / WEBP, maks 1 MB</span>
          </div>
          <div class="field" style="margin:0">
            <label>Favicon</label>
            @if(!empty($settings['favicon']))
              <div class="img-box">
                <img class="prev" src="{{ asset('storage/'.$settings['favicon']) }}" alt="Favicon" style="height:32px;width:32px;padding:3px">
                <div class="meta"><b>Favicon aktif</b><span>Unggah baru untuk mengganti</span></div>
                <button type="submit" form="del-favicon-form" class="btn btn-danger btn-sm">✕ Hapus</button>
              </div>
            @endif
            <input type="file" name="favicon" accept="image/*" class="inp file-drop">
            <span class="help">PNG / ICO, maks 256 KB</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Tampilan Login --}}
    <div class="set-row">
      <div class="set-side">
        <h3>Tampilan Halaman Login</h3>
        <p>Gambar latar di sisi kanan halaman login (hero). Kosongkan untuk memakai gradasi default.</p>
      </div>
      <div class="set-main">
        <div class="field" style="margin:0;max-width:460px">
          <label>Gambar Latar Login</label>
          @if(!empty($settings['login_image']))
            <div style="margin-bottom:10px">
              <img src="{{ asset('storage/'.$settings['login_image']) }}" alt="Gambar Login"
                   style="width:100%;height:160px;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
              <div style="margin-top:8px">
                <button type="submit" form="del-login_image-form" class="btn btn-danger btn-sm">✕ Hapus Gambar</button>
              </div>
            </div>
          @endif
          <input type="file" name="login_image" accept="image/*" class="inp file-drop">
          <span class="help">PNG / JPG / WEBP, maks 3 MB · rasio landscape disarankan</span>
        </div>
      </div>
    </div>

    {{-- Kontak & Footer --}}
    <div class="set-row">
      <div class="set-side">
        <h3>Kontak &amp; Footer</h3>
        <p>Informasi kontak instansi dan teks footer laporan.</p>
      </div>
      <div class="set-main">
        <div class="set-grid">
          <div class="field" style="margin:0">
            <label>Email Kontak</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}">
          </div>
          <div class="field" style="margin:0">
            <label>Telepon Kontak</label>
            <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}">
          </div>
          <div class="field full" style="margin:0">
            <label>Teks Footer</label>
            <input type="text" name="footer_text" value="{{ old('footer_text', $settings['footer_text'] ?? '') }}">
          </div>
        </div>
      </div>
    </div>

    {{-- Keamanan --}}
    <div class="set-row">
      <div class="set-side">
        <h3>Keamanan Sesi</h3>
        <p>Durasi tidak aktif sebelum pengguna otomatis keluar demi keamanan.</p>
      </div>
      <div class="set-main">
        <div class="field" style="margin:0;max-width:220px">
          <label>Batas Waktu Sesi (menit) <span style="color:var(--danger)">*</span></label>
          <input type="number" name="session_timeout" min="1" max="1440" step="1"
                 value="{{ old('session_timeout', $settings['session_timeout'] ?? 30) }}" required>
          <span class="help">Rentang 1–1440 menit.</span>
        </div>
      </div>
    </div>

    <div class="save-bar">
      <button type="submit" class="btn btn-pri">Simpan Pengaturan</button>
    </div>
  </form>

  {{-- Form tersembunyi untuk hapus gambar (dipicu tombol via atribut form=…) --}}
  @foreach(['logo' => 'logo', 'favicon' => 'favicon', 'login_image' => 'gambar login'] as $key => $label)
    <form id="del-{{ $key }}-form" method="POST" action="{{ route('pengaturan.deleteImage', $key) }}" style="display:none"
          data-confirm="Hapus {{ $label }}?" data-confirm-variant="danger" data-confirm-ok="Hapus">
      @csrf @method('DELETE')
    </form>
  @endforeach
</div>
@endsection
