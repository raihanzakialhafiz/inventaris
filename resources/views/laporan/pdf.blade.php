<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; margin: 0; }
    .kop { border-bottom: 2.5px solid #0f766e; padding-bottom: 10px; margin-bottom: 14px; }
    .kop table { width: 100%; border: none; }
    .kop td { border: none; vertical-align: middle; padding: 0; }
    .kop .logo { width: 56px; }
    .kop .logo img { width: 50px; height: 50px; object-fit: contain; }
    .kop h1 { font-size: 17px; margin: 0; color: #0f766e; }
    .kop .inst { font-size: 12px; font-weight: bold; margin: 0; }
    .kop .addr { font-size: 10px; color: #64748b; margin: 2px 0 0; }
    .meta { font-size: 10px; color: #475569; margin-bottom: 10px; }
    .meta b { color: #1e293b; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #0f766e; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; }
    table.data td { border-bottom: 1px solid #e2e8f0; padding: 5px 8px; font-size: 10px; }
    table.data tr:nth-child(even) td { background: #f6f9f8; }
    .empty { text-align: center; padding: 24px; color: #94a3b8; }
    .foot { margin-top: 18px; font-size: 9px; color: #94a3b8; text-align: right; }
  </style>
</head>
<body>
  <div class="kop">
    <table>
      <tr>
        @if($logo && file_exists($logo))
          <td class="logo"><img src="{{ $logo }}" alt="Logo"></td>
        @endif
        <td>
          <p class="inst">{{ $institution }}</p>
          <h1>{{ $title }}</h1>
          @if($address)<p class="addr">{{ $address }}</p>@endif
        </td>
      </tr>
    </table>
  </div>

  <div class="meta">
    @if($period)<b>Periode:</b> {{ $period }} &nbsp;&nbsp;@endif
    @if($deptName)<b>Bidang:</b> {{ $deptName }} &nbsp;&nbsp;@endif
    <b>Dicetak:</b> {{ now()->format('d/m/Y H:i') }}
  </div>

  @if(count($rows))
    <table class="data">
      <thead>
        <tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
      </thead>
      <tbody>
        @foreach($rows as $row)
          <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
        @endforeach
      </tbody>
    </table>
  @else
    <div class="empty">Tidak ada data untuk parameter ini.</div>
  @endif

  <div class="foot">{{ $appName }} · {{ setting('footer_text', 'Sistem Inventaris ATK') }}</div>
</body>
</html>
