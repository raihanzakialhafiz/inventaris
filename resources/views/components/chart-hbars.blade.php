{{--
  Grafik bar horizontal satu hue (SVG server-rendered, tanpa JS/CDN — aman CSP).
  Perbandingan besaran antar kategori: bar 18px ujung membulat 4px (siku di
  baseline kiri), nilai di ujung bar, tooltip native <title>.

  Pakai: <x-chart-hbars :data="['TIK' => 12, ...]" satuan="permintaan" />
--}}
@props(['data' => [], 'satuan' => ''])

@php
  $rows   = collect($data)->map(fn ($v) => max(0, (int) $v));
  $n      = $rows->count();
  $maxVal = $n ? max(1, $rows->max()) : 0;
@endphp

@if($n === 0 || $rows->max() < 1)
  <div class="empty" style="padding:26px 0"><div class="ic">▹▹</div><b>Belum ada data untuk ditampilkan</b></div>
@else
  @php
    $barH = 18; $gap = 14; $padL = 104; $padR = 48; $padT = 6; $padB = 6;
    $H = $padT + $padB + $n * $barH + ($n - 1) * $gap;
    $W = 520;
    $plotW = $W - $padL - $padR;
    $fmt = fn ($v) => number_format($v, 0, ',', '.');
  @endphp
  <svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" height="{{ $H }}" preserveAspectRatio="xMidYMid meet"
       role="img" aria-label="{{ $rows->map(fn ($v, $l) => "$l $v")->implode(', ') }}">
    @foreach($rows as $label => $v)
      @php
        $i  = $loop->index;
        $yb = $padT + $i * ($barH + $gap);
        $w  = $plotW * $v / $maxVal;
        $r  = min(4, $w / 2);
      @endphp
      <g class="ch-mark">
        <title>{{ $label }}: {{ $fmt($v) }} {{ $satuan }}</title>
        {{-- Target hover satu baris penuh --}}
        <rect x="0" y="{{ $yb - $gap / 2 }}" width="{{ $W }}" height="{{ $barH + $gap }}" fill="transparent"/>
        <text x="{{ $padL - 10 }}" y="{{ $yb + $barH / 2 + 3.5 }}" text-anchor="end"
              font-size="11" fill="#64748B">{{ \Illuminate\Support\Str::limit($label, 14) }}</text>
        @if($w >= 1)
          <path fill="#0D9488" d="M{{ $padL }},{{ $yb }} H{{ $padL + $w - $r }} Q{{ $padL + $w }},{{ $yb }} {{ $padL + $w }},{{ $yb + $r }} V{{ $yb + $barH - $r }} Q{{ $padL + $w }},{{ $yb + $barH }} {{ $padL + $w - $r }},{{ $yb + $barH }} H{{ $padL }} Z"/>
        @endif
        <text x="{{ $padL + $w + 8 }}" y="{{ $yb + $barH / 2 + 3.5 }}" text-anchor="start"
              font-size="11" font-weight="700" fill="#334155">{{ $fmt($v) }}</text>
      </g>
    @endforeach
    {{-- Baseline kiri --}}
    <line x1="{{ $padL }}" x2="{{ $padL }}" y1="{{ $padT - 2 }}" y2="{{ $H - $padB + 2 }}" stroke="#CBD5E1" stroke-width="1"/>
  </svg>
@endif
