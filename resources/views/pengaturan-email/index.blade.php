@extends('layouts.app')
@section('title', 'Pengaturan Email')
@section('page-title', 'Pengaturan Email')
@section('page-crumb', 'Sistem')

@section('content')
@php
  $val = fn ($key, $default = '') => old($key, $settings[$key] ?? $default);
@endphp

<style>
  {{-- .mc-h/.mc-ic/.mc-side dan .save-bar didefinisikan di siatk.css —
       dipakai bersama halaman Pengaturan Sistem. --}}
  .mail-wrap { max-width: 760px; }
  .guide-btn {
    display: inline-flex; align-items: center; gap: 5px; border: 0; background: none;
    padding: 0; cursor: pointer; color: var(--primary-dark);
    font: inherit; font-size: 11.5px; font-weight: 700;
  }
  .guide-btn:hover { text-decoration: underline; }
  .guide {
    margin-top: 8px; padding: 12px 14px; border: 1px solid var(--line);
    border-radius: var(--radius); background: var(--surface-2);
    font-size: 12.5px; line-height: 1.6; color: var(--ink);
  }
  .guide ol { margin: 6px 0 0; padding-left: 18px; }
  @media (max-width: 720px) { .mail-grid { grid-template-columns: 1fr !important; } }
</style>

<div class="mail-wrap" x-data="{ dirty: false, guide: false }">
  <div class="page-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div>
      <h2>Pengaturan Email</h2>
      <p>Akun pengirim (SMTP) dan jadwal pengingat stok menipis.</p>
    </div>
    <span class="badge {{ $configured ? 'b-ok' : 'b-danger' }}" style="flex:0 0 auto">
      {{ $configured ? '● Email aktif' : '● Belum dikonfigurasi' }}
    </span>
  </div>

  @unless($configured)
    <div class="notice warn" style="margin-bottom:16px">
      <span class="ic"><x-icon name="alert" /></span>
      <div>Email sistem belum bisa terkirim. Isi <b>Email Pengirim</b> dan <b>App Password</b> di bawah, simpan, lalu kirim email uji.</div>
    </div>
  @endunless

  <form method="POST" action="{{ route('pengaturan-email.update') }}" @input="dirty = true" @change="dirty = true">
    @csrf
    @method('PUT')

    {{-- ── Akun pengirim ── --}}
    <div class="card" style="margin-bottom:16px">
      <div class="card-h mc-h">
        <span class="mc-ic"><x-icon name="mail" width="17" height="17" /></span>
        <div>
          <h3>Akun Email Pengirim</h3>
          <p>Akun Gmail yang mengirim seluruh email sistem. Host, port &amp; enkripsi mengikuti konfigurasi server (<code>.env</code>).</p>
        </div>
      </div>
      <div class="card-b">
        <div class="mail-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="field" style="margin:0">
            <label>Email Pengirim</label>
            <input type="email" name="mail_from_address" value="{{ $val('mail_from_address', config('mail.from.address')) }}" placeholder="akun@gmail.com" autocomplete="off">
            <span class="help">Sekaligus dipakai untuk login SMTP.</span>
          </div>
          <div class="field" style="margin:0">
            <label>Nama Pengirim</label>
            <input type="text" name="mail_from_name" value="{{ $val('mail_from_name', config('mail.from.name')) }}" placeholder="{{ setting('app_name', 'SIIB') }}">
            <span class="help">Nama yang tampil di kotak masuk penerima.</span>
          </div>
          <div class="field" style="margin:0;grid-column:1/-1">
            <label>App Password</label>
            <x-password-input name="mail_password" autocomplete="new-password"
                              placeholder="{{ $hasPassword ? '•••••••• (tersimpan — isi untuk mengganti)' : 'Masukkan app password (16 karakter)' }}" />
            <button type="button" class="guide-btn" style="margin-top:6px" @click="guide = ! guide">
              <span x-text="guide ? '▾' : '▸'"></span> Cara mendapatkan App Password Gmail
            </button>

            <div class="guide" x-show="guide" x-cloak x-transition.opacity.duration.150ms>
              <b>Ini bukan password akun Google Anda.</b>
              <ol>
                <li>Buka <b>myaccount.google.com</b> → aktifkan <b>Verifikasi 2 Langkah</b>.</li>
                <li>Buka <b>myaccount.google.com/apppasswords</b>.</li>
                <li>Buat sandi aplikasi (pilih <b>Mail</b>), salin 16 karakter.</li>
                <li>Tempel di kolom di atas <b>tanpa spasi</b>.</li>
              </ol>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ── Jadwal ── --}}
    <div class="card">
      <div class="card-h mc-h">
        <span class="mc-ic"><x-icon name="bell" width="17" height="17" /></span>
        <div>
          <h3>Jadwal Email Stok Menipis</h3>
          <p>Pengingat otomatis ke Admin, Petugas Gudang, dan Kasubag Umum.</p>
        </div>
        <div class="mc-side">
          @if($lastSent)
            Terakhir dikirim<br><b style="color:var(--ink)">{{ $lastSent->isoFormat('D MMM YYYY') }}</b>
          @else
            Belum pernah<br>terkirim
          @endif
        </div>
      </div>
      <div class="card-b">
        <div class="mail-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;max-width:420px">
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

    <div class="save-bar">
      <button type="submit" class="btn btn-pri">Simpan Pengaturan Email</button>
    </div>
  </form>

  {{-- ── Kirim email uji ── --}}
  <div class="card">
    <div class="card-h mc-h">
      <span class="mc-ic"><x-icon name="send-up" width="17" height="17" /></span>
      <div>
        <h3>Kirim Email Uji</h3>
        <p>Memakai konfigurasi yang sudah <b>tersimpan</b>, bukan yang sedang diketik.</p>
      </div>
    </div>
    <div class="card-b">

      <div class="notice warn" style="margin:0 0 12px" x-show="dirty" x-cloak>
        <span class="ic"><x-icon name="alert" /></span>
        <div>Ada perubahan yang belum disimpan. Klik <b>Simpan Pengaturan Email</b> dulu — jika tidak, email uji masih memakai konfigurasi lama.</div>
      </div>

      <form method="POST" action="{{ route('pengaturan-email.test') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        @csrf
        <div class="field" style="margin:0;flex:1;min-width:240px">
          <label>Kirim ke Email</label>
          <input type="email" name="test_email" value="{{ auth()->user()->email }}" required placeholder="tujuan@email.com">
        </div>
        <button type="submit" class="btn btn-ghost"><x-icon name="mail" width="14" height="14" /> Kirim Email Uji</button>
      </form>
    </div>
  </div>
</div>
@endsection
