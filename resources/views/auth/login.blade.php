<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · {{ setting('app_name', 'SIIB') }}</title>
    @if (setting('favicon'))
        <link rel="icon" href="{{ asset('storage/' . setting('favicon')) }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- JetBrains Mono tidak lagi dimuat: satu-satunya pemakainya adalah kolom
         email, yang kini ikut sans seperti seluruh input lain di aplikasi.
         Satu unduhan font yang memblokir render jadi hilang dari halaman
         pertama yang dilihat pengguna. --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>

        :root {
            --surface: #FFFFFF;
            --ink: #0F172A;
            --ink-2: #334155;
            --ink-3: #475569;
            --muted: #64748B;
            --primary: #0D9488;
            --primary-dark: #0F766E;
            --primary-darker: #0B5F58;
            --primary-deep: #0B4F4A;
            /* henti bawah gradien panel hero */
            --primary-soft: #F0FDFA;
            --on-accent: #FFFFFF;
            --line: #E2E8F0;
            --line-soft: #EEF2F6;
            --warn: #B45309;
            --warn-2: #92400E;
            --warn-bg: #FEF3C7;
            --warn-line: #FDE68A;
            --danger: #DC2626;
            --danger-2: #B91C1C;
            --danger-bg: #FEE2E2;
            --danger-line: #FECACA;
            /* Disalin PERSIS dari --sans di siatk.css. Halaman ini berdiri
               sendiri (tidak memuat siatk.css) supaya paint pertama cepat, jadi
               stack-nya harus dikutip utuh — versi lama memotong Roboto/
               Helvetica/Arial, sehingga saat Google Fonts gagal dimuat, login
               jatuh ke sans-serif generik sementara sisa aplikasi jatuh ke
               Roboto. Dua rupa huruf berbeda di mesin yang sama. */
            --sans: 'Plus Jakarta Sans', system-ui, -apple-system, Roboto, Helvetica, Arial, sans-serif;
            --radius-xs: 8px;
            --radius-sm: 10px;
            --radius: 12px;
            --ease-out: cubic-bezier(.16, 1, .3, 1);
            --dur-1: 90ms;
            --dur-2: 140ms;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
        }

        /* font-size & line-height mengikuti dasar siatk.css — sebelumnya tidak
           diset, jadi apa pun yang tidak diberi ukuran sendiri memakai 16px/
           normal bawaan browser, bukan ritme aplikasi. */
        body {
            font-family: var(--sans);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            background: var(--surface);
            color: var(--ink);
        }

        /* Sejajar dengan `h1,h2,h3,h4` di siatk.css. */
        h1,
        h2 {
            letter-spacing: -.01em;
        }

        :focus-visible {
            outline: 2px solid var(--primary-dark);
            outline-offset: 2px;
            border-radius: 6px;
        }

        .login-shell {
            position: fixed;
            inset: 0;
            display: flex;
            background: var(--surface);
            overflow: hidden;
        }

        .login-left {
            flex: 1;
            min-width: 0;
            display: flex;
            overflow-y: auto;
            padding: 30px 34px;
            background: var(--surface);
        }

        .login-form {
            width: 392px;
            max-width: 100%;
            margin: auto;
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: var(--radius);
            background: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--on-accent);
            font-weight: 800;
            font-size: 18px;
            flex: 0 0 auto;
        }

        /* Tracking dalam em, bukan px: pada breakpoint 420px .login-title turun
           25px → 23px, dan nilai px membuat jaraknya ikut melonggar relatif
           terhadap huruf. em ikut mengecil sendiri. */
        .brand-name {
            font-weight: 800;
            font-size: 17px;
            letter-spacing: -.02em;
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 11.5px;
            color: var(--muted);
            font-weight: 600;
            line-height: 1.1;
            margin-top: 2px;
        }

        .login-title {
            margin: 0;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .login-desc {
            margin: 7px 0 18px;
            font-size: 13.5px;
            color: var(--muted);
        }

        .lbl {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--ink-2);
            margin-bottom: 6px;
        }

        .inp-wrap {
            position: relative;
            margin-bottom: 13px;
        }

        /* Tanpa transisi: border + cincin di sini adalah penanda fokus. */
        .inp {
            width: 100%;
            height: 44px;
            padding: 0 14px;
            border: 1.5px solid var(--line);
            border-radius: var(--radius);
            background: var(--surface);
            font-size: 14px;
            outline: none;
            font-family: inherit;
            color: var(--ink);
        }

        .inp.has-toggle {
            padding-right: 46px;
        }

        .inp:focus {
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px var(--primary-soft);
        }

        .inp::placeholder {
            color: var(--muted);
        }

        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            padding: 4px;
            line-height: 0;
            display: inline-flex;
            border-radius: 6px;
        }

        .toggle-pw:hover {
            color: var(--primary-dark);
        }

        .toggle-pw .pw-eye-off {
            display: none;
        }

        .toggle-pw.is-visible .pw-eye {
            display: none;
        }

        .toggle-pw.is-visible .pw-eye-off {
            display: inline;
        }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: var(--ink-3);
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }

        .remember input {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .forgot {
            font-size: 12.5px;
            color: var(--primary-dark);
            font-weight: 700;
            cursor: pointer;
            background: none;
            border: none;
            border-radius: 6px;
        }

        .btn-login {
            width: 100%;
            height: 46px;
            border: none;
            border-radius: var(--radius);
            background: var(--primary-dark);
            color: var(--on-accent);
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: background-color var(--dur-1) var(--ease-out);
        }

        .btn-login:hover {
            background: var(--primary-darker);
        }

        .btn-login:active {
            background: var(--primary-darker);
        }

        .btn-login:disabled {
            background: var(--muted);
            cursor: not-allowed;
        }

        .help-line {
            margin: 15px 0 0;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }

        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
            border-radius: var(--radius);
            padding: 12px 14px;
            margin-bottom: 18px;
            font-size: 13px;
            color: var(--danger-2);
        }

        .alert-ic {
            flex: 0 0 auto;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--danger);
            color: var(--on-accent);
            display: grid;
            place-items: center;
            margin-top: 1px;
        }

        .alert-ic svg {
            display: block;
        }

        .alert-error.alert-warn {
            background: var(--warn-bg);
            border-color: var(--warn-line);
            color: var(--warn-2);
        }

        .alert-error.alert-warn .alert-ic {
            background: var(--warn);
        }

        /* demo accounts */
        .demo-sec {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--line-soft);
        }

        .demo-lbl {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .demo-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border: 1.5px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            cursor: pointer;
            font-family: inherit;
            text-align: left;
            transition: background-color var(--dur-1) var(--ease-out), border-color var(--dur-1) var(--ease-out);
        }

        .demo-btn:hover {
            border-color: var(--primary-dark);
            background: var(--primary-soft);
        }

        .demo-ava {
            width: 26px;
            height: 26px;
            border-radius: var(--radius-xs);
            display: grid;
            place-items: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--on-accent);
            flex: 0 0 auto;
        }

        .demo-meta {
            min-width: 0;
        }

        .demo-meta b {
            display: block;
            font-size: 11.5px;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .demo-meta span {
            font-size: 10.5px;
            color: var(--muted);
        }
        .login-right {
            flex: 1.12;
            min-width: 0;
            position: relative;
            overflow: hidden;
            background: linear-gradient(155deg, var(--primary-dark) 0%, var(--primary-deep) 100%);
        }

        .hero-content {
            position: absolute;
            left: 48px;
            right: 48px;
            bottom: 46px;
            color: var(--on-accent);
        }
        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .03em;
            background: rgba(255, 255, 255, .16);
            padding: 6px 12px;
            border-radius: 20px;
            margin-bottom: 20px;
        }

        .hero-title {
            margin: 0;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -.03em;
            line-height: 1.14;
            max-width: 460px;
        }

        .hero-desc {
            margin: 16px 0 0;
            font-size: 14.5px;
            line-height: 1.6;
            max-width: 440px;
            color: rgba(255, 255, 255, .88);
        }

        /* ── responsive ── */
        @media (max-width: 900px) {
            .login-right {
                display: none;
            }

            .login-left {
                background: var(--surface);
            }
        }

        @media (max-width: 420px) {
            .login-left {
                padding: 28px 20px;
            }

            .demo-grid {
                grid-template-columns: 1fr;
            }

            .login-title {
                font-size: 23px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                transition-duration: 1ms !important;
                animation-duration: 1ms !important;
            }
        }
    </style>
</head>

<body>
    <div class="login-shell">

        <!-- LEFT: form -->
        <div class="login-left">
            <div class="login-form">
                <div class="brand-row">
                    @if (setting('logo'))
                        <div class="brand-mark" style="background:var(--surface);padding:5px">
                            <img src="{{ asset('storage/' . setting('logo')) }}" alt="Logo"
                                style="width:100%;height:100%;object-fit:contain">
                        </div>
                    @else
                        <div class="brand-mark">{{ strtoupper(substr(setting('app_name', 'SIIB'), 0, 2)) }}</div>
                    @endif
                    <div>
                        <div class="brand-name">{{ setting('app_name', 'SIIB') }}</div>
                        <div class="brand-sub">{{ setting('institution_name', 'Sistem Inventaris Barang') }}</div>
                    </div>
                </div>

                <h2 class="login-title">Masuk ke akun Anda</h2>
                <p class="login-desc">Gunakan akun pegawai Anda untuk mengakses sistem inventaris.</p>

                @if (request('timeout') && !$errors->any())
                    <div class="alert-error alert-warn">
                        <div class="alert-ic">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 7v5l3 2" />
                            </svg>
                        </div>
                        <div>Sesi Anda berakhir karena tidak ada aktivitas. Silakan masuk kembali.</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-error">
                        <div class="alert-ic">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 7v6" />
                                <path d="M12 17h.01" />
                            </svg>
                        </div>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf

                    <label class="lbl" for="email">Alamat Email</label>
                    <div class="inp-wrap">
                        <input type="email" id="email" name="email" class="inp" value="{{ old('email') }}"
                            placeholder="nama@gmail.com" required autofocus autocomplete="email">
                    </div>

                    <label class="lbl" for="password">Kata Sandi</label>
                    <div class="inp-wrap">
                        <input type="password" id="password" name="password" class="inp has-toggle"
                            placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="toggle-pw" onclick="togglePw()"
                            title="Tampilkan / sembunyikan sandi" aria-label="Tampilkan atau sembunyikan sandi">
                            <svg class="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg class="pw-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        </button>
                    </div>

                    <div class="form-row">
                        <label class="remember"><input type="checkbox" name="remember"> Ingat saya</label>
                        <a class="forgot" href="{{ route('password.request') }}" style="text-decoration:none">Lupa
                            sandi?</a>
                    </div>

                    <button type="submit" class="btn-login" id="btn-submit">Masuk ke Sistem</button>
                    <p class="help-line">Butuh bantuan akses akun? Hubungi Administrator Sistem.</p>
                </form>

                {{-- Akun demo hanya untuk pengembangan — otomatis hilang saat APP_DEBUG=false --}}
                @if (config('app.debug'))
                    <div class="demo-sec">
                        <div class="demo-lbl">Akun demo — klik untuk isi otomatis</div>
                        <div class="demo-grid">
                            @foreach ([['admin', 'AD', 'Administrator', 'admin@siatk.test', false], ['kepala_bidang', 'KB', 'Kepala Bidang', 'kabid.tik@siatk.test', false], ['kasubag_umum', 'KS', 'Kasubag Umum', 'kasubag@siatk.test', false], ['petugas_gudang', 'GD', 'Petugas Gudang', 'gudang@siatk.test', false], ['pimpinan', 'PM', 'Pimpinan', 'pimpinan@siatk.test', true]] as [$peran, $inisial, $label, $surel, $lebar])
                                <button type="button" class="demo-btn"
                                    @if ($lebar) style="grid-column:1/-1" @endif
                                    onclick="fillDemo('{{ $surel }}')">
                                    <span class="demo-ava"
                                        style="background:{{ \App\Models\User::colorForRole($peran) }}">{{ $inisial }}</span>
                                    <span class="demo-meta"><b>{{ $label }}</b><span>{{ $surel }}
                                            @if ($lebar)
                                                · sandi: password
                                            @endif
                                        </span></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- RIGHT: hero -->
        <div class="login-right"
            @if (setting('login_image')) style="background-image: linear-gradient(150deg, rgba(15,118,110,.72) 0%, rgba(13,148,136,.62) 42%, rgba(17,94,89,.82) 100%), url('{{ asset('storage/' . setting('login_image')) }}'); background-size: cover; background-position: center;" @endif>
            <div class="hero-content">
                <div class="hero-pill">GUDANG PUSAT · PERIODE 2026</div>
                <h1 class="hero-title">Kelola stok &amp; permintaan barang dalam satu alur.</h1>
                <p class="hero-desc">Pencatatan stok, permintaan antar bidang, persetujuan berjenjang, hingga kartu
                    stok — transparan dan terpantau.</p>
            </div>
        </div>

    </div>

    <script>
        function togglePw() {
            const pw = document.getElementById('password');
            const btn = document.querySelector('.toggle-pw');
            const show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            btn.classList.toggle('is-visible', show);
        }

        function fillDemo(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'password';
            document.getElementById('password').focus();
        }
        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.getElementById('btn-submit');
            btn.textContent = 'Memproses…';
            btn.disabled = true;
        });
    </script>
</body>

</html>
