@extends('layouts.app')
@section('title', 'Pengaturan Akun')
@section('page-title', 'Pengaturan Akun')
@section('page-crumb', 'Akun')

@section('content')
<div style="max-width:760px">
  <div class="page-head">
    <h2>Pengaturan Akun</h2>
    <p>Kelola informasi profil dan keamanan akun Anda.</p>
  </div>

  {{-- Identitas: satu tempat untuk SEMUA data yang tidak bisa kamu ubah sendiri.
       Sebelumnya peran/bidang/NIP/jabatan ditaruh di <input disabled> — kolom
       isian yang tidak bisa diisi terbaca seperti form rusak, dan peran/bidang
       jadi tampil dua kali. --}}
  <div class="card" style="margin-bottom:18px">
    <div class="card-b" style="display:flex;align-items:flex-start;gap:16px">
      <span class="role-ava" style="background:{{ $user->roleColor() }};width:56px;height:56px;border-radius:15px;font-size:19px">
        {{ $user->initials() }}
      </span>
      <div style="min-width:0;flex:1">
        <div style="font-size:18px;font-weight:800">{{ $user->name }}</div>
        <div class="t-sub" style="font-family:var(--mono)">{{ $user->email }}</div>

        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
          <span class="badge" style="background:{{ $user->roleColor() }}1A;color:{{ $user->roleColor() }}">{{ $user->roleLabel() }}</span>
          @if($user->department)<span class="badge b-neutral">{{ $user->department->name }}</span>@endif
          <span class="badge {{ $user->is_active ? 'b-ok' : 'b-danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        </div>

        <div class="t-sub" style="margin-top:9px;font-size:12px;line-height:1.6">
          NIP {{ $user->nip ?: '— belum diisi' }} · {{ $user->jabatanLabel() }}
          <br>
          <span style="color:var(--muted)">Peran, bidang, NIP &amp; jabatan hanya dapat diubah oleh Administrator.</span>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Yang bisa kamu ubah sendiri ── --}}
  <div class="card" style="margin-bottom:18px">
    <div class="card-h mc-h">
      <span class="mc-ic"><x-icon name="user" width="17" height="17" /></span>
      <div>
        <h3>Informasi Profil</h3>
        <p>Nama yang tampil di sistem dan email untuk login serta pemberitahuan.</p>
      </div>
    </div>
    <div class="card-b">
      <form method="POST" action="{{ route('profil.update') }}">
        @csrf
        @method('PUT')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="field" style="margin:0">
            <label>Nama Lengkap <span class="req" aria-hidden="true">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name')<span class="err">{{ $message }}</span>@enderror
          </div>
          <div class="field" style="margin:0">
            <label>Email <span class="req" aria-hidden="true">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            <span class="help">Dipakai untuk login. Mengubahnya mengubah cara Anda masuk.</span>
            @error('email')<span class="err">{{ $message }}</span>@enderror
          </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
          <button type="submit" class="btn btn-pri">Simpan Profil</button>
        </div>
      </form>
    </div>
  </div>

  {{-- ── Keamanan ── --}}
  <div class="card">
    <div class="card-h mc-h">
      <span class="mc-ic"><x-icon name="shield" width="17" height="17" /></span>
      <div>
        <h3>Ubah Password</h3>
        <p>Minimal 8 karakter dan harus memuat huruf sekaligus angka.</p>
      </div>
    </div>
    <div class="card-b">
      <form method="POST" action="{{ route('profil.password') }}">
        @csrf
        @method('PUT')
        <div class="field">
          <label>Password Saat Ini <span class="req" aria-hidden="true">*</span></label>
          <x-password-input name="current_password" required autocomplete="current-password"
                            placeholder="Masukkan password saat ini" />
          @error('current_password')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px">
          <div class="field" style="margin:0">
            <label>Password Baru <span class="req" aria-hidden="true">*</span></label>
            <x-password-input name="password" required autocomplete="new-password"
                              placeholder="Min. 8 karakter, huruf dan angka" :meter="true" />
            @error('password')<span class="err">{{ $message }}</span>@enderror
          </div>
          <div class="field" style="margin:0">
            <label>Konfirmasi Password Baru <span class="req" aria-hidden="true">*</span></label>
            <x-password-input name="password_confirmation" required autocomplete="new-password"
                              placeholder="Ulangi password baru" />
          </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
          <button type="submit" class="btn btn-pri">Ubah Password</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
