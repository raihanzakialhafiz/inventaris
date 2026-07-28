<!DOCTYPE html>
<html lang="id">

<head>
    @include('auth.partials.auth-head', ['title' => 'Lupa Sandi'])
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

        <h1 class="title">Lupa Kata Sandi</h1>
        <p class="desc">Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>

        @if (session('status'))
            <div class="alert alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label class="lbl" for="email">Alamat Email</label>
            <input type="email" id="email" name="email" class="inp" value="{{ old('email') }}"
                placeholder="nama@instansi.go.id" required autofocus>
            <button type="submit" class="btn-main">Kirim Tautan Reset</button>
        </form>

        <a href="{{ route('login') }}" class="back-link"><x-icon name="arrow-left" width="14" height="14"
                style="vertical-align:-2px" /> Kembali ke halaman masuk</a>
    </div>
</body>

</html>
