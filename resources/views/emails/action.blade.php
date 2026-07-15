<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;color:#0f172a">
  <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0">
    <div style="background:linear-gradient(150deg,#0f766e,#0d9488);padding:20px 24px;color:#fff">
      <div style="font-size:16px;font-weight:800;letter-spacing:-.3px">{{ setting('app_name', 'SIIB') }}</div>
      <div style="font-size:12px;opacity:.85">{{ setting('institution_name', 'Sistem Inventaris Barang') }}</div>
    </div>
    <div style="padding:24px">
      <p style="margin:0 0 12px;font-size:14px">Halo <b>{{ $name }}</b>,</p>
      <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#334155">{{ $body }}</p>
      @if($url)
        <a href="{{ $url }}" style="display:inline-block;background:#0d9488;color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:10px 20px;border-radius:10px">
          {{ $urlLabel ?? 'Buka Sistem' }}
        </a>
      @endif
    </div>
    <div style="padding:14px 24px;border-top:1px solid #e2e8f0;font-size:11.5px;color:#94a3b8">
      Email otomatis dari {{ setting('app_name', 'SIIB') }}. Mohon tidak membalas email ini.
    </div>
  </div>
</body>
</html>
