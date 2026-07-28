@extends('layouts.app')
@section('title', 'Pengaturan Sistem')
@section('page-title', 'Pengaturan Sistem')
@section('page-crumb', 'Sistem')

@section('content')
<style>

  .set-wrap { display: grid; grid-template-columns: 230px 1fr; gap: 22px; align-items: start; }
  .set-nav {
    position: sticky; top: 16px; display: flex; flex-direction: column; gap: 4px;
    padding: 10px; background: var(--surface); border: 1px solid var(--line);
    border-radius: var(--radius-lg);
  }
  .set-nav button {
    display: flex; align-items: center; gap: 10px; width: 100%; text-align: left;
    padding: 10px 12px; border: 0; border-radius: 10px; cursor: pointer;
    background: transparent; color: var(--muted);
    font: inherit; font-size: 13.5px; font-weight: 600;
  }
  .set-nav button:hover { background: var(--surface-2); color: var(--ink); }
  .set-nav button.on { background: var(--primary-soft); color: var(--primary-dark); }
  .set-nav button svg { flex: 0 0 auto; }
  .settings { max-width: 780px; min-width: 0; }

  .set-card { margin-bottom: 18px; }
  .set-card .card-h h3 { margin-bottom: 3px; }
  .set-card .card-h p { font-size: 12.5px; color: var(--muted); line-height: 1.5; margin: 0; }
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
  .kop-prev {
    margin-top: 16px; padding: 16px; border: 1px solid var(--line);
    border-radius: 12px; background: #fff; color: #000;
  }
  .kop-prev-lbl {
    font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    color: var(--muted); margin-bottom: 10px;
  }
  .kp-row { display: flex; align-items: center; border-bottom: 3px solid #000; padding-bottom: 6px; }
  .kp-side { width: 96px; flex: 0 0 auto; }
  .kp-side img { width: 40px; height: 40px; object-fit: contain; margin-left: 52px; display: block; }
  .kp-teks { flex: 1; min-width: 0; text-align: center; }
  .kp-teks p { margin: 0; overflow-wrap: anywhere; }
  .kp-g { font-size: 11px; }
  .kp-i { font-size: 15px; font-weight: 700; }
  .kp-a { font-size: 8.5px; }
  .kp-judul { text-align: center; font-size: 12px; font-weight: 700; margin: 12px 0 0; }

  @media (max-width: 900px) {
    .set-wrap { grid-template-columns: 1fr; }
    .set-nav { position: static; flex-direction: row; overflow-x: auto; }
    .set-nav button { width: auto; white-space: nowrap; }
  }
  @media (max-width: 720px) { .set-grid { grid-template-columns: 1fr; } }
</style>

@php
  $tabs = [
    'identitas' => ['Identitas', 'tag'],
    'kop'       => ['Kop Surat', 'file-text'],
    'ttd'       => ['Penanda Tangan', 'scroll'],
    'logo'      => ['Logo & Favicon', 'image'],
    'login'     => ['Halaman Login', 'user'],
    'kontak'    => ['Footer', 'file-text'],
    'keamanan'  => ['Keamanan Sesi', 'shield'],
  ];
@endphp


<div class="page-head">
  <h2>Pengaturan Sistem</h2>
  <p>Konfigurasi global aplikasi {{ setting('app_name', 'SIIB') }} — perubahan langsung berlaku di seluruh sistem.</p>
</div>

<div class="set-wrap" x-data="{ tab: 'identitas' }">
<nav class="set-nav">
  @foreach($tabs as $id => [$label, $ikon])
    <button type="button" :class="{ 'on': tab === '{{ $id }}' }" @click="tab = '{{ $id }}'">
      <x-icon name="{{ $ikon }}" width="16" height="16" /> {{ $label }}
    </button>
  @endforeach
</nav>

<div class="settings">

  @if($errors->any())
    <div class="notice warn" style="margin-bottom:16px"><span class="ic"><x-icon name="alert" /></span><div>{{ $errors->first() }}</div></div>
  @endif


  <form method="POST" action="{{ route('pengaturan.update') }}" enctype="multipart/form-data"
        @invalid.capture="const t = $event.target, k = t.closest('[data-tab]')?.dataset.tab;
                          if (k && k !== tab) { tab = k; $nextTick(() => t.reportValidity()); }">
    @csrf
    @method('PUT')

    {{-- Identitas --}}
    <div class="card set-card" data-tab="identitas" x-show="tab === 'identitas'" x-cloak>
      <div class="card-h">
        <h3>Informasi Aplikasi</h3>
        <p>Nama singkat yang tampil di sidebar dan judul tab browser.</p>
      </div>
      <div class="card-b">
        <div class="field" style="margin:0;max-width:280px">
          <label>Nama Aplikasi <span style="color:var(--danger)">*</span></label>
          <input type="text" name="app_name" value="{{ old('app_name', $settings['app_name'] ?? 'SIIB') }}" required>
          <span class="help">Singkat, mis. “BKD”.</span>
        </div>
      </div>
    </div>


    <div class="card set-card" data-tab="kop" x-show="tab === 'kop'" x-cloak
         x-data='{
           gov:  @json(old('government_name', $settings['government_name'] ?? '')),
           inst: @json(old('institution_name', $settings['institution_name'] ?? '')),
           addr: @json(old('address', $settings['address'] ?? '')),
           mail: @json(old('contact_email', $settings['contact_email'] ?? ''))
         }'>
      <div class="card-h">
        <h3>Kop Surat Laporan</h3>
        <p>Empat baris identitas pada kop laporan PDF &amp; Excel, urut dari atas ke bawah. Kosongkan baris yang tidak dipakai.</p>
      </div>
      <div class="card-b">
        <div class="set-grid">
          <div class="field full" style="margin:0">
            <label>Baris 1 — Induk Pemerintahan</label>
            <input type="text" name="government_name" x-model="gov" placeholder="mis. PEMERINTAH PROVINSI SUMATERA BARAT">
          </div>
          <div class="field full" style="margin:0">
            <label>Baris 2 — Nama Instansi <span class="help">juga tampil di sidebar, halaman login &amp; email</span></label>
            <input type="text" name="institution_name" x-model="inst" placeholder="mis. BADAN KEPEGAWAIAN DAERAH">
          </div>
          <div class="field full" style="margin:0">
            <label>Baris 3 — Alamat <span class="help">tulis utuh termasuk telepon, fax, dan kota</span></label>
            <input type="text" name="address" x-model="addr" placeholder="mis. Jalan Batang Antokan No. 4 Telepon (0751) 7054124 Fax. (0751) 7054804 Padang">
          </div>
          <div class="field full" style="margin:0">
            <label>Baris 4 — Email</label>
            <input type="email" name="contact_email" x-model="mail" placeholder="mis. bkd@sumbarprov.go.id">
          </div>
        </div>

        <div class="kop-prev">
          <div class="kop-prev-lbl">Pratinjau</div>
          <div class="kp-row">
            <div class="kp-side">
              @if(!empty($settings['logo']))
                <img src="{{ asset('storage/'.$settings['logo']) }}" alt="Logo">
              @endif
            </div>
            <div class="kp-teks">
              <p class="kp-g" x-show="gov.trim()" x-text="gov" x-cloak></p>
              <p class="kp-i" x-text="inst.trim() || 'Nama Instansi'"></p>
              <p class="kp-a" x-show="addr.trim()" x-text="addr" x-cloak></p>
              <p class="kp-a" x-show="mail.trim()" x-text="'email : ' + mail" x-cloak></p>
            </div>
            <div class="kp-side"></div>
          </div>
          <p class="kp-judul">JUDUL LAPORAN</p>
        </div>
      </div>
    </div>

    {{-- Penanda tangan laporan --}}
    <div class="card set-card" data-tab="ttd" x-show="tab === 'ttd'" x-cloak>
      <div class="card-h">
        <h3>Penanda Tangan Laporan</h3>
        <p>Pejabat yang tanda tangannya dapat disertakan saat ekspor PDF/Excel. Nama, NIP, dan jabatan diambil dari data pengguna — cukup perbarui di Manajemen Pengguna bila berubah.</p>
      </div>
      <div class="card-b">
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
    <div class="card set-card" data-tab="logo" x-show="tab === 'logo'" x-cloak>
      <div class="card-h">
        <h3>Logo &amp; Favicon</h3>
        <p>Logo tampil di sidebar, halaman login, dan kop laporan. Favicon adalah ikon kecil di tab browser.</p>
      </div>
      <div class="card-b">
        <div class="set-grid">
          <div class="field" style="margin:0">
            <label>Logo</label>
            @if(!empty($settings['logo']))
              <div class="img-box">
                <img class="prev" src="{{ asset('storage/'.$settings['logo']) }}" alt="Logo" style="height:44px;width:44px;padding:4px">
                <div class="meta"><b>Logo aktif</b><span>Unggah baru untuk mengganti</span></div>
                <button type="submit" form="del-logo-form" class="btn btn-danger btn-sm"><x-icon name="x" width="13" height="13" /> Hapus</button>
              </div>
            @endif
            <x-file-drop name="logo" accept="image/png,image/jpeg,image/webp" hint="PNG / JPG / WEBP · maks 1 MB" />
          </div>
          <div class="field" style="margin:0">
            <label>Favicon</label>
            @if(!empty($settings['favicon']))
              <div class="img-box">
                <img class="prev" src="{{ asset('storage/'.$settings['favicon']) }}" alt="Favicon" style="height:32px;width:32px;padding:3px">
                <div class="meta"><b>Favicon aktif</b><span>Unggah baru untuk mengganti</span></div>
                <button type="submit" form="del-favicon-form" class="btn btn-danger btn-sm"><x-icon name="x" width="13" height="13" /> Hapus</button>
              </div>
            @endif
            <x-file-drop name="favicon" accept="image/png,image/x-icon,.ico" hint="PNG / ICO · maks 256 KB" />
          </div>
        </div>
      </div>
    </div>

    {{-- Tampilan Login --}}
    <div class="card set-card" data-tab="login" x-show="tab === 'login'" x-cloak>
      <div class="card-h">
        <h3>Tampilan Halaman Login</h3>
        <p>Gambar latar di sisi kanan halaman login (hero). Kosongkan untuk memakai gradasi default.</p>
      </div>
      <div class="card-b">
        <div class="field" style="margin:0;max-width:460px">
          <label>Gambar Latar Login</label>
          @if(!empty($settings['login_image']))
            <div style="margin-bottom:10px">
              <img src="{{ asset('storage/'.$settings['login_image']) }}" alt="Gambar Login"
                   style="width:100%;height:160px;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
              <div style="margin-top:8px">
                <button type="submit" form="del-login_image-form" class="btn btn-danger btn-sm"><x-icon name="x" width="13" height="13" /> Hapus Gambar</button>
              </div>
            </div>
          @endif
          <x-file-drop name="login_image" accept="image/png,image/jpeg,image/webp"
                       hint="PNG / JPG / WEBP · maks 3 MB · rasio landscape disarankan" />
        </div>
      </div>
    </div>


    <div class="card set-card" data-tab="kontak" x-show="tab === 'kontak'" x-cloak>
      <div class="card-h">
        <h3>Footer Laporan</h3>
        <p>Teks yang tampil di kaki setiap laporan PDF.</p>
      </div>
      <div class="card-b">
        <div class="field" style="margin:0;max-width:460px">
          <label>Teks Footer</label>
          <input type="text" name="footer_text" value="{{ old('footer_text', $settings['footer_text'] ?? '') }}">
        </div>
      </div>
    </div>

    {{-- Keamanan --}}
    <div class="card set-card" data-tab="keamanan" x-show="tab === 'keamanan'" x-cloak>
      <div class="card-h">
        <h3>Keamanan Sesi</h3>
        <p>Durasi tidak aktif sebelum pengguna otomatis keluar demi keamanan.</p>
      </div>
      <div class="card-b">
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
</div>
@endsection
