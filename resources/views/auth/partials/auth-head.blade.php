{{-- <head> + gaya bersama untuk halaman auth ringkas (lupa/reset password) --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'Akun' }} · {{ setting('app_name', 'SIIB') }}</title>
@if(setting('favicon'))<link rel="icon" href="{{ asset('storage/'.setting('favicon')) }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased;
         background: #faf9f5; color: #0f172a; min-height: 100vh; display: grid; place-items: center; padding: 24px; }
  .auth-card { width: 420px; max-width: 100%; background: #fff; border: 1px solid #e2e8f0;
               border-radius: 18px; box-shadow: 0 18px 50px rgba(15,23,42,.12); padding: 30px 30px 26px; }
  .brand-row { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
  .brand-mark { width: 42px; height: 42px; border-radius: 12px; flex: 0 0 auto;
                background: linear-gradient(150deg, #0f766e, #14b8a6); display: grid; place-items: center;
                color: #fff; font-weight: 800; font-size: 17px; box-shadow: 0 6px 16px rgba(13,148,136,.3); }
  .brand-name { font-weight: 800; font-size: 16px; letter-spacing: -.3px; }
  .brand-sub  { font-size: 11.5px; color: #64748b; font-weight: 600; }
  h1.title { font-size: 21px; font-weight: 800; letter-spacing: -.4px; margin-bottom: 6px; }
  p.desc { font-size: 13.5px; color: #64748b; margin-bottom: 18px; line-height: 1.5; }
  label.lbl { display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; }
  .inp { width: 100%; height: 44px; padding: 0 14px; border: 1.5px solid #e2e8f0; border-radius: 12px;
         background: #fff; font-size: 14px; outline: none; font-family: inherit; color: #0f172a;
         transition: border-color .15s, box-shadow .15s; margin-bottom: 14px; }
  .inp:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
  .btn-main { width: 100%; height: 46px; border: none; border-radius: 13px; cursor: pointer; font-family: inherit;
              background: linear-gradient(150deg, #0f766e, #0d9488); color: #fff; font-size: 14.5px; font-weight: 700;
              box-shadow: 0 10px 24px rgba(13,148,136,.3); transition: filter .15s; }
  .btn-main:hover { filter: brightness(1.05); }
  .back-link { display: inline-block; margin-top: 16px; font-size: 13px; color: #0d9488; font-weight: 700; text-decoration: none; }
  .back-link:hover { text-decoration: underline; }
  .alert { border-radius: 12px; padding: 11px 14px; margin-bottom: 16px; font-size: 13px; }
  .alert-ok  { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
  .alert-err { background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; }
  .pw-wrap { position: relative; }
  .pw-wrap > input { padding-right: 42px; }
  .pw-toggle { position: absolute; top: 22px; right: 10px; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; line-height: 0; display: inline-flex; }
  .pw-toggle:hover { color: #0d9488; }
  .pw-toggle .pw-eye-off { display: none; }
  .pw-toggle.is-visible .pw-eye { display: none; }
  .pw-toggle.is-visible .pw-eye-off { display: inline; }
</style>
<script>
  function togglePassword(btn) {
    const input = btn.parentElement.querySelector('input');
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.classList.toggle('is-visible', show);
  }
</script>
