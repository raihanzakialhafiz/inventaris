<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk · {{ setting('app_name', 'SIIB') }}</title>
  @if(setting('favicon'))<link rel="icon" href="{{ asset('storage/'.setting('favicon')) }}">@endif
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      -webkit-font-smoothing: antialiased;
      background: #faf9f5;
      color: #0f172a;
    }
    .login-shell { position: fixed; inset: 0; display: flex; background: #fff; overflow: hidden; }

    /* ── LEFT: form ──
       overflow-y:auto + margin:auto on the form keeps it centred when it fits
       AND lets it scroll (brand reachable) when the viewport is short — unlike
       justify-content:center which clips the top on overflow. */
    .login-left {
      flex: 1; min-width: 0; display: flex; overflow-y: auto;
      padding: 30px 34px; background: #fff;
    }
    .login-form { width: 392px; max-width: 100%; margin: auto; }
    .brand-row { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; }
    .brand-mark {
      width: 44px; height: 44px; border-radius: 12px;
      background: linear-gradient(150deg, #0f766e, #14b8a6);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 800; font-size: 18px;
      box-shadow: 0 6px 16px rgba(13,148,136,.3); flex: 0 0 auto;
    }
    .brand-name  { font-weight: 800; font-size: 17px; letter-spacing: -.3px; line-height: 1.1; }
    .brand-sub   { font-size: 11.5px; color: #64748b; font-weight: 600; line-height: 1.1; margin-top: 2px; }
    .login-title { margin: 0; font-size: 25px; font-weight: 800; letter-spacing: -.5px; }
    .login-desc  { margin: 7px 0 18px; font-size: 13.5px; color: #64748b; }

    .lbl { display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .inp-wrap { position: relative; margin-bottom: 13px; }
    .inp {
      width: 100%; height: 44px; padding: 0 14px; border: 1.5px solid #e2e8f0;
      border-radius: 12px; background: #fff; font-size: 14px; outline: none;
      font-family: inherit; color: #0f172a; transition: border-color .15s, box-shadow .15s;
    }
    .inp.mono { font-family: 'JetBrains Mono', monospace; }
    .inp.has-toggle { padding-right: 46px; }
    .inp:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
    .inp::placeholder { color: #94a3b8; }
    .toggle-pw {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: #94a3b8;
      padding: 4px; line-height: 0; display: inline-flex;
    }
    .toggle-pw:hover { color: #0d9488; }
    .toggle-pw .pw-eye-off { display: none; }
    .toggle-pw.is-visible .pw-eye { display: none; }
    .toggle-pw.is-visible .pw-eye-off { display: inline; }

    .form-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .remember { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #475569; font-weight: 600; cursor: pointer; user-select: none; }
    .remember input { width: 16px; height: 16px; accent-color: #0d9488; cursor: pointer; }
    .forgot { font-size: 12.5px; color: #0d9488; font-weight: 700; cursor: pointer; background: none; border: none; }

    .btn-login {
      width: 100%; height: 46px; border: none; border-radius: 13px;
      background: linear-gradient(150deg, #0f766e, #0d9488); color: #fff;
      font-size: 14.5px; font-weight: 700; cursor: pointer; font-family: inherit;
      box-shadow: 0 10px 24px rgba(13,148,136,.32); transition: filter .15s, transform .1s;
    }
    .btn-login:hover  { filter: brightness(1.05); }
    .btn-login:active { transform: translateY(1px); }
    .help-line { margin: 15px 0 0; text-align: center; font-size: 12px; color: #94a3b8; }

    .alert-error {
      display: flex; align-items: flex-start; gap: 10px;
      background: #fee2e2; border: 1px solid #fecaca; border-radius: 12px;
      padding: 12px 14px; margin-bottom: 18px; font-size: 13px; color: #b91c1c;
    }
    .alert-ic {
      flex: 0 0 auto; width: 20px; height: 20px; border-radius: 50%;
      background: #dc2626; color: #fff; display: grid; place-items: center;
      font-size: 12px; font-weight: 700; margin-top: 1px;
    }

    /* demo accounts */
    .demo-sec { margin-top: 16px; padding-top: 14px; border-top: 1px solid #eef2f6; }
    .demo-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 8px; }
    .demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .demo-btn {
      display: flex; align-items: center; gap: 8px; padding: 7px 10px;
      border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fff;
      cursor: pointer; transition: .12s; font-family: inherit; text-align: left;
    }
    .demo-btn:hover { border-color: #0d9488; background: #f0fdfa; }
    .demo-ava { width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; font-size: 11px; font-weight: 700; color: #fff; flex: 0 0 auto; }
    .demo-meta { min-width: 0; }
    .demo-meta b    { display: block; font-size: 11.5px; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .demo-meta span { font-size: 10.5px; color: #94a3b8; }

    /* ── RIGHT: hero ── */
    .login-right {
      flex: 1.12; min-width: 0; position: relative; overflow: hidden;
      background:
        radial-gradient(700px 420px at 78% 12%, rgba(20,184,166,.45) 0%, transparent 62%),
        linear-gradient(150deg, #0f766e 0%, #0d9488 42%, #115e59 100%);
    }
    .login-right::before {
      content: ''; position: absolute; width: 520px; height: 520px; border-radius: 50%;
      border: 1px solid rgba(255,255,255,.08); top: -140px; right: -120px;
    }
    .login-right::after {
      content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%;
      border: 1px solid rgba(255,255,255,.07); bottom: 60px; left: -80px;
    }
    .hero-content { position: absolute; left: 48px; right: 48px; bottom: 46px; color: #fff; }
    .hero-pill {
      display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 700;
      letter-spacing: .4px; background: rgba(255,255,255,.18); padding: 6px 12px;
      border-radius: 20px; margin-bottom: 20px; backdrop-filter: blur(4px);
    }
    .hero-title { margin: 0; font-size: 34px; font-weight: 800; letter-spacing: -1px; line-height: 1.14; max-width: 460px; }
    .hero-desc  { margin: 16px 0 0; font-size: 14.5px; line-height: 1.6; opacity: .92; max-width: 440px; }

    /* ── responsive ── */
    @media (max-width: 900px) {
      .login-right { display: none; }
      .login-left  { background: #fff; }
    }
    @media (max-width: 420px) {
      .login-left { padding: 28px 20px; }
      .demo-grid  { grid-template-columns: 1fr; }
      .login-title { font-size: 23px; }
    }
    @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
  </style>
</head>
<body>
  <div class="login-shell">

    <!-- LEFT: form -->
    <div class="login-left">
      <div class="login-form">
        <div class="brand-row">
          @if(setting('logo'))
            <div class="brand-mark" style="background:#fff;padding:5px">
              <img src="{{ asset('storage/'.setting('logo')) }}" alt="Logo" style="width:100%;height:100%;object-fit:contain">
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

        @if (request('timeout') && ! $errors->any())
          <div class="alert-error" style="background:#fef3c7;border-color:#fde68a;color:#92400e">
            <div class="alert-ic" style="background:#d97706">⏱</div>
            <div>Sesi Anda berakhir karena tidak ada aktivitas. Silakan masuk kembali.</div>
          </div>
        @endif

        @if ($errors->any())
          <div class="alert-error">
            <div class="alert-ic">!</div>
            <div>{{ $errors->first() }}</div>
          </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="login-form">
          @csrf

          <label class="lbl" for="email">Alamat Email</label>
          <div class="inp-wrap">
            <input type="email" id="email" name="email" class="inp mono"
                   value="{{ old('email') }}" placeholder="nama@instansi.go.id"
                   required autofocus autocomplete="email">
          </div>

          <label class="lbl" for="password">Kata Sandi</label>
          <div class="inp-wrap">
            <input type="password" id="password" name="password" class="inp has-toggle"
                   placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="toggle-pw" onclick="togglePw()" title="Tampilkan / sembunyikan sandi" aria-label="Tampilkan atau sembunyikan sandi">
              <svg class="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="pw-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>

          <div class="form-row">
            {{-- Tidak tercentang default: komputer kantor kerap dipakai bergantian. --}}
            <label class="remember"><input type="checkbox" name="remember"> Ingat saya</label>
            <a class="forgot" href="{{ route('password.request') }}" style="text-decoration:none">Lupa sandi?</a>
          </div>

          <button type="submit" class="btn-login" id="btn-submit">Masuk ke Sistem</button>
          <p class="help-line">Butuh bantuan akses akun? Hubungi Administrator Sistem.</p>
        </form>

        {{-- Akun demo hanya untuk pengembangan — otomatis hilang saat APP_DEBUG=false --}}
        @if(config('app.debug'))
        <div class="demo-sec">
          <div class="demo-lbl">Akun demo — klik untuk isi otomatis</div>
          <div class="demo-grid">
            <button type="button" class="demo-btn" onclick="fillDemo('admin@siatk.test')">
              <span class="demo-ava" style="background:#7C3AED">AD</span>
              <span class="demo-meta"><b>Administrator</b><span>admin@siatk.test</span></span>
            </button>
            <button type="button" class="demo-btn" onclick="fillDemo('kabid.tik@siatk.test')">
              <span class="demo-ava" style="background:#EA580C">KB</span>
              <span class="demo-meta"><b>Kepala Bidang</b><span>kabid.tik@siatk.test</span></span>
            </button>
            <button type="button" class="demo-btn" onclick="fillDemo('kasubag@siatk.test')">
              <span class="demo-ava" style="background:#0D9488">KS</span>
              <span class="demo-meta"><b>Kasubag Umum</b><span>kasubag@siatk.test</span></span>
            </button>
            <button type="button" class="demo-btn" onclick="fillDemo('gudang@siatk.test')">
              <span class="demo-ava" style="background:#2563EB">GD</span>
              <span class="demo-meta"><b>Petugas Gudang</b><span>gudang@siatk.test</span></span>
            </button>
            <button type="button" class="demo-btn" style="grid-column:1/-1" onclick="fillDemo('pimpinan@siatk.test')">
              <span class="demo-ava" style="background:#0891B2">PM</span>
              <span class="demo-meta"><b>Pimpinan</b><span>pimpinan@siatk.test · sandi: password</span></span>
            </button>
          </div>
        </div>
        @endif
      </div>
    </div>

    <!-- RIGHT: hero -->
    <div class="login-right"
         @if(setting('login_image'))
           style="background-image: linear-gradient(150deg, rgba(15,118,110,.72) 0%, rgba(13,148,136,.62) 42%, rgba(17,94,89,.82) 100%), url('{{ asset('storage/'.setting('login_image')) }}'); background-size: cover; background-position: center;"
         @endif>
      <div class="hero-content">
        <div class="hero-pill">GUDANG PUSAT · PERIODE 2026</div>
        <h1 class="hero-title">Kelola stok &amp; permintaan barang dalam satu alur.</h1>
        <p class="hero-desc">Pencatatan stok, permintaan antar bidang, persetujuan berjenjang, hingga kartu stok — transparan dan terpantau.</p>
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
    document.getElementById('login-form').addEventListener('submit', function () {
      const btn = document.getElementById('btn-submit');
      btn.textContent = 'Memproses…';
      btn.disabled = true;
    });
  </script>
</body>
</html>
