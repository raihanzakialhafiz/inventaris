@extends('layouts.app')
@section('title', 'Pengaturan Email')
@section('page-title', 'Pengaturan Email')
@section('page-crumb', 'Sistem')

@section('content')
@php
  $val = fn ($key, $default = '') => old($key, $settings[$key] ?? $default);

  // Satu sumber teks status — dipakai strip di puncak halaman.
  [$statusClass, $statusTitle, $statusNote] = match ($status) {
      'ok'   => ['is-ok',  'Email aktif',
                 'Email uji terakhir berhasil pada ' . $lastTest->isoFormat('D MMM YYYY, HH:mm') . '.'],
      'warn' => ['is-warn', $lastTest ? 'Email uji terakhir gagal' : 'Belum diverifikasi',
                 $lastTest
                    ? 'Percobaan pada ' . $lastTest->isoFormat('D MMM YYYY, HH:mm') . ' ditolak server. Periksa App Password, lalu uji lagi.'
                    : 'Akun sudah diisi tapi belum pernah diuji. Kirim email uji untuk memastikan kredensialnya benar.'],
      default => ['is-off', 'Belum dikonfigurasi',
                 'Sistem belum bisa mengirim email apa pun. Isi Email Pengirim dan App Password di bawah, simpan, lalu uji.'],
  };
@endphp

<div style="max-width:760px" x-data="{ dirty: false, guide: false }">
  <div class="page-head">
    <h2>Pengaturan Email</h2>
    <p>Akun pengirim (SMTP) dan jadwal pengingat stok menipis.</p>
  </div>

  {{-- Pertanyaan utama halaman ini cuma satu: "email jalan atau tidak?" — jadi
       itu yang dijawab lebih dulu, lengkap dengan tombol pembuktiannya.
       Form uji sengaja DI LUAR form pengaturan (form bersarang ilegal). --}}
  <div class="status-strip {{ $statusClass }}">
    <span class="status-strip__dot"></span>
    <div class="status-strip__text">
      <b>{{ $statusTitle }}</b>
      <span>{{ $statusNote }}</span>
    </div>
    <form method="POST" action="{{ route('pengaturan-email.test') }}"
          style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      @csrf
      <input type="email" name="test_email" value="{{ auth()->user()->email }}" required
             placeholder="tujuan@email.com" style="min-width:200px;flex:1">
      <button type="submit" class="btn btn-ghost" style="flex:0 0 auto">
        <x-icon name="send-up" width="14" height="14" /> Kirim Uji
      </button>
    </form>
  </div>

  <div class="notice warn" style="margin:0 0 18px" x-show="dirty" x-cloak>
    <span class="ic"><x-icon name="alert" /></span>
    <div>Ada perubahan yang belum disimpan. Email uji memakai konfigurasi <b>tersimpan</b>, bukan yang sedang diketik — simpan dulu.</div>
  </div>

  <form method="POST" action="{{ route('pengaturan-email.update') }}" @input="dirty = true" @change="dirty = true">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:16px">
      <div class="card-h mc-h">
        <span class="mc-ic"><x-icon name="mail" width="17" height="17" /></span>
        <div>
          <h3>Akun Email Pengirim</h3>
          <p>Akun Gmail yang mengirim seluruh email sistem. Host, port &amp; enkripsi mengikuti konfigurasi server (<code>.env</code>).</p>
        </div>
      </div>
      <div class="card-b">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="field" style="margin:0">
            <label>Email Pengirim</label>
            <input type="email" name="mail_from_address" value="{{ $val('mail_from_address', config('mail.from.address')) }}" placeholder="akun@gmail.com" autocomplete="off">
            <span class="help">Sekaligus dipakai untuk login SMTP.</span>
            @error('mail_from_address')<span class="err">{{ $message }}</span>@enderror
          </div>
          <div class="field" style="margin:0">
            <label>Nama Pengirim</label>
            <input type="text" name="mail_from_name" value="{{ $val('mail_from_name', config('mail.from.name')) }}" placeholder="{{ setting('app_name', 'SIIB') }}">
            <span class="help">Nama yang tampil di kotak masuk penerima.</span>
            @error('mail_from_name')<span class="err">{{ $message }}</span>@enderror
          </div>
          <div class="field" style="margin:0;grid-column:1/-1">
            <label>App Password</label>
            <x-password-input name="mail_password" autocomplete="new-password"
                              placeholder="{{ $hasPassword ? '•••••••• (tersimpan — isi untuk mengganti)' : 'Masukkan app password (16 karakter)' }}" />
            @error('mail_password')<span class="err">{{ $message }}</span>@enderror
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
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;max-width:420px">
          <div class="field" style="margin:0">
            <label>Setiap (hari) <span class="req" aria-hidden="true">*</span></label>
            <input type="number" name="stock_alert_interval_days" value="{{ $val('stock_alert_interval_days', 1) }}" min="1" max="30" required>
            <span class="help">1 = setiap hari.</span>
            @error('stock_alert_interval_days')<span class="err">{{ $message }}</span>@enderror
          </div>
          <div class="field" style="margin:0">
            <label>Pada Jam <span class="req" aria-hidden="true">*</span></label>
            <select name="stock_alert_hour" class="inp">
              @for($h = 0; $h < 24; $h++)
                <option value="{{ $h }}" @selected((int) $val('stock_alert_hour', 7) === $h)>{{ sprintf('%02d:00', $h) }}</option>
              @endfor
            </select>
            @error('stock_alert_hour')<span class="err">{{ $message }}</span>@enderror
          </div>
        </div>
      </div>
    </div>

    <div class="save-bar">
      <button type="submit" class="btn btn-pri">Simpan Pengaturan Email</button>
    </div>
  </form>
</div>
@endsection
