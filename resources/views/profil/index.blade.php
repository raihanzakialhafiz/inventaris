@extends('layouts.app')
@section('title', 'Pengaturan Akun')

@section('content')
    <div style="max-width:760px">
        <div class="page-head">
            <h2>Pengaturan Akun</h2>
            <p>Kelola informasi profil dan keamanan akun Anda.</p>
        </div>

        {{-- Ringkasan identitas --}}
        <div class="card" style="margin-bottom:18px">
            <div class="card-b" style="display:flex;align-items:center;gap:16px">
                <span class="role-ava lg"
                    style="background:{{ $user->roleColor() }};width:60px;height:60px;border-radius:16px;font-size:20px;flex:0 0 auto;color:var(--on-accent);display:grid;place-items:center;font-weight:700">
                    {{ $user->initials() }}
                </span>
                <div style="min-width:0">
                    <div style="font-size:18px;font-weight:800">{{ $user->name }}</div>
                    <div class="t-sub" style="font-family:var(--mono)">{{ $user->email }}</div>
                    <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap">
                        <span class="badge"
                            style="background:{{ $user->roleColor() }}1A;color:{{ $user->roleColor() }}">{{ $user->roleLabel() }}</span>
                        @if ($user->department)
                            <span class="badge b-neutral">{{ $user->department->name }}</span>
                        @endif
                        <span
                            class="badge {{ $user->is_active ? 'b-ok' : 'b-danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Data Profil --}}
        <div class="card" style="margin-bottom:18px">
            <div class="card-h">
                <h3>Informasi Profil</h3>
            </div>
            <div class="card-b">
                <form method="POST" action="{{ route('profil.update') }}">
                    @csrf
                    @method('PUT')
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="field" style="margin:0">
                            <label>Nama Lengkap <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="field" style="margin:0">
                            <label>Email <span style="color:var(--danger)">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </div>
                    </div>
                    <div class="field" style="margin:14px 0 0">
                        <label>Role &amp; Bidang <span class="help">hanya dapat diubah oleh Administrator</span></label>
                        <input type="text"
                            value="{{ $user->roleLabel() }}{{ $user->department ? ' · ' . $user->department->name : '' }}"
                            disabled>
                    </div>
                    <div class="field" style="margin:14px 0 0">
                        <label>NIP &amp; Jabatan <span class="help">dipakai tanda tangan laporan — hanya dapat diubah oleh
                                Administrator</span></label>
                        <input type="text" value="{{ $user->nip ?: 'NIP belum diisi' }} · {{ $user->jabatanLabel() }}"
                            disabled>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                        <button type="submit" class="btn btn-pri">Simpan Profil</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Ganti Password --}}
        <div class="card">
            <div class="card-h">
                <h3>Ubah Password</h3>
            </div>
            <div class="card-b">
                <form method="POST" action="{{ route('profil.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="field">
                        <label>Password Saat Ini <span style="color:var(--danger)">*</span></label>
                        <x-password-input name="current_password" required autocomplete="current-password"
                            placeholder="Masukkan password saat ini" />
                        @error('current_password')
                            <div class="field-err">{{ $message }}</div>
                        @enderror
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px">
                        <div class="field" style="margin:0">
                            <label>Password Baru <span style="color:var(--danger)">*</span></label>
                            <x-password-input name="password" required autocomplete="new-password"
                                placeholder="Min. 8 karakter, huruf &amp; angka" :meter="true" />
                            @error('password')
                                <div class="field-err">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="field" style="margin:0">
                            <label>Konfirmasi Password Baru <span style="color:var(--danger)">*</span></label>
                            <x-password-input name="password_confirmation" required autocomplete="new-password"
                                placeholder="Ulangi password baru" />
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                        <button type="submit" class="btn btn-pri">Ubah Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
