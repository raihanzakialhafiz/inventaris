@extends('layouts.app')
@section('title', 'Pengaturan Email')
@section('page-title', 'Pengaturan Email')
@section('page-crumb', 'Sistem')

@section('content')
@php
  // Prefill dari DB; bila kosong pakai nilai .env (config) sebagai acuan.
  $val = fn ($key, $default = '') => old($key, $settings[$key] ?? $default);
@endphp

<div style="max-width:820px">
  <div class="page-head">
    <h2>Pengaturan Email</h2>
    <p>Atur akun email pengirim (SMTP) dan jadwal pengingat stok menipis.</p>
  </div>

  {{-- ── Konfigurasi SMTP ── --}}
  <form method="POST" action="{{ route('pengaturan-email.update') }}">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:18px">
      <div class="card-h"><h3>Akun Email Pengirim</h3></div>
      <div class="card-b">
        <p class="t-sub" style="margin:0 0 14px">Akun Gmail yang mengirim email sistem. Host, port &amp; enkripsi mengikuti konfigurasi server (.env).</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="field" style="margin:0">
            <label>Email Pengirim</label>
            <input type="email" name="mail_from_address" value="{{ $val('mail_from_address', config('mail.from.address')) }}" placeholder="akun@gmail.com" autocomplete="off">
            <span class="help">Akun Gmail pengirim — juga dipakai untuk login SMTP.</span>
          </div>
          <div class="field" style="margin:0">
            <label>Nama Pengirim</label>
            <input type="text" name="mail_from_name" value="{{ $val('mail_from_name', config('mail.from.name')) }}" placeholder="{{ setting('app_name', 'SIIB') }}">
          </div>
          <div class="field" style="margin:0;grid-column:1/-1">
            <label style="display:flex;align-items:center;gap:6px">
              App Password
              {{-- Ikon bantuan cara mendapatkan password --}}
              <span x-data="{ open:false }" @click.outside="open=false" style="position:relative;display:inline-flex">
                <button type="button" class="help-icon" @click="open=!open" aria-label="Cara mendapatkan password">?</button>
                <div x-show="open" x-cloak class="help-pop" x-transition>
                  <b>Cara mendapat password (Gmail):</b>
                  <ol style="margin:8px 0 0;padding-left:18px;line-height:1.6">
                    <li>Buka <b>myaccount.google.com</b> → aktifkan <b>Verifikasi 2 Langkah</b>.</li>
                    <li>Buka <b>myaccount.google.com/apppasswords</b>.</li>
                    <li>Buat sandi aplikasi (pilih <b>Mail</b>), salin 16 karakter.</li>
                    <li>Tempel di sini <b>tanpa spasi</b> (bukan password login Google Anda).</li>
                  </ol>
                </div>
              </span>
            </label>
            <x-password-input name="mail_password" autocomplete="new-password"
                              placeholder="{{ $hasPassword ? '•••••••• (tersimpan — isi untuk mengganti)' : 'Masukkan app password (16 karakter)' }}" />
          </div>
        </div>
      </div>
    </div>

    {{-- ── Jadwal Email Stok Menipis ── --}}
    <div class="card" style="margin-bottom:18px">
      <div class="card-h"><h3>Jadwal Email Stok Menipis</h3></div>
      <div class="card-b">
        <p class="t-sub" style="margin:0 0 12px">Sistem mengecek stok & mengirim ulang email pengingat ke Admin, Gudang, dan Kasubag sesuai jadwal ini.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;max-width:420px">
          <div class="field" style="margin:0">
            <label>Setiap (hari) <span style="color:var(--danger)">*</span></label>
            <input type="number" name="stock_alert_interval_days" value="{{ $val('stock_alert_interval_days', 1) }}" min="1" max="30" required>
            <span class="help">1 = setiap hari.</span>
          </div>
          <div class="field" style="margin:0">
            <label>Pada Jam <span style="color:var(--danger)">*</span></label>
            <select name="stock_alert_hour" class="inp">
              @for($h = 0; $h < 24; $h++)
                <option value="{{ $h }}" @selected((int) $val('stock_alert_hour', 7) === $h)>{{ sprintf('%02d:00', $h) }}</option>
              @endfor
            </select>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:10px">
      <button type="submit" class="btn btn-pri">Simpan Pengaturan Email</button>
    </div>
  </form>

  {{-- ── Kirim Email Uji ── --}}
  <div class="card" style="margin-top:18px">
    <div class="card-h"><h3>Kirim Email Uji</h3></div>
    <div class="card-b">
      <p class="t-sub" style="margin:0 0 12px">Simpan pengaturan di atas dulu, lalu kirim email uji untuk memastikan konfigurasi benar.</p>
      <form method="POST" action="{{ route('pengaturan-email.test') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        @csrf
        <div class="field" style="margin:0;flex:1;min-width:240px">
          <label>Kirim ke Email</label>
          <input type="email" name="test_email" value="{{ auth()->user()->email }}" required placeholder="tujuan@email.com">
        </div>
        <button type="submit" class="btn btn-ghost">✉ Kirim Email Uji</button>
      </form>
    </div>
  </div>
</div>
@endsection
