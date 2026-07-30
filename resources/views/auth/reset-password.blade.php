<!DOCTYPE html>
<html lang="id">

<head>
    @include('auth.partials.auth-head', ['title' => 'Reset Sandi'])
</head>

<body>
    <div class="auth-card">
        <div class="brand-row">
            <div class="brand-mark">{{ strtoupper(substr(setting('app_name', 'SIIB'), 0, 2)) }}</div>
            <div>
                <div class="brand-name">{{ setting('app_name', 'SIIB') }}</div>
                <div class="brand-sub">{{ setting('institution_name', 'Inventaris Barang') }}</div>
            </div>
        </div>

        <h1 class="title">Atur Ulang Kata Sandi</h1>
        <p class="desc">Buat kata sandi baru (minimal 8 karakter, kombinasi huruf dan angka).</p>

        @if ($errors->any())
            <div class="alert alert-err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label class="lbl" for="email">Alamat Email</label>
            <input type="email" id="email" name="email" class="inp" value="{{ old('email', $email) }}"
                placeholder="nama@gmail.com" required>

            <label class="lbl" for="password">Kata Sandi Baru</label>
            <x-password-input id="password" name="password" class="inp" placeholder="••••••••" required autofocus />

            <label class="lbl" for="password_confirmation">Ulangi Kata Sandi</label>
            <x-password-input id="password_confirmation" name="password_confirmation" class="inp"
                placeholder="••••••••" required />

            <button type="submit" class="btn-main">Simpan Kata Sandi Baru</button>
        </form>

        <a href="{{ route('login') }}" class="back-link"><x-icon name="arrow-left" width="14" height="14"
                style="vertical-align:-2px" /> Kembali ke halaman masuk</a>
    </div>
</body>

</html>
