{{--
  Kerangka halaman error (403/404/419/500/503) — berdiri sendiri:
  tanpa query DB, tanpa aset eksternal, agar tetap tampil walau aplikasi bermasalah.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('code') · @yield('title') — {{ config('app.name', 'SIATK') }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: linear-gradient(160deg, #f0fdfa 0%, #f8fafc 55%, #ecfdf5 100%);
      color: #0f172a; min-height: 100vh;
      display: flex; align-items: center; justify-content: center; padding: 24px;
    }
    .card {
      background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
      box-shadow: 0 10px 40px rgba(13, 148, 136, .08);
      max-width: 460px; width: 100%; padding: 40px 36px; text-align: center;
    }
    .code {
      font-size: 64px; font-weight: 800; line-height: 1; letter-spacing: -2px;
      color: #0d9488; margin-bottom: 14px;
    }
    h1 { font-size: 20px; font-weight: 700; margin-bottom: 10px; }
    p  { font-size: 14px; line-height: 1.65; color: #64748b; margin-bottom: 26px; }
    .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .btn {
      display: inline-block; padding: 10px 22px; border-radius: 10px;
      font-size: 13.5px; font-weight: 700; text-decoration: none; cursor: pointer;
      border: 1px solid transparent; transition: filter .15s;
    }
    .btn:hover { filter: brightness(.95); }
    .btn-pri   { background: #0d9488; color: #fff; }
    .btn-ghost { background: #fff; color: #334155; border-color: #cbd5e1; }
    .brand { margin-top: 26px; font-size: 12px; color: #94a3b8; font-weight: 600; }
  </style>
</head>
<body>
  <div class="card">
    <div class="code">@yield('code')</div>
    <h1>@yield('title')</h1>
    <p>@yield('desc')</p>
    <div class="actions">@yield('actions')</div>
    <div class="brand">{{ config('app.name', 'SIATK') }}</div>
  </div>
</body>
</html>
