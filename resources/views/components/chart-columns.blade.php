{{--
  Grafik kolom satu seri (SVG server-rendered, tanpa JS/CDN — aman CSP).
  Spec: kolom 22px ujung-data membulat 4px (siku di baseline), gridline hairline,
  label nilai hanya pada kolom terakhir (bulan berjalan), tooltip native <title>.

  Pakai: <x-chart-columns :data="['Jan' => 10, ...]" satuan="pcs" />
--}}
@props(['data' => [], 'satuan' => ''])

@php
  $labels = array_keys($data);
  $vals   = array_map(fn ($v) => max(0, (int) $v), array_values($data));
  $n      = count($vals);
  $maxVal = $n ? max($vals) : 0;
@endphp

@if($n === 0 || $maxVal < 1)
  <div class="empty" style="padding:26px 0"><div class="empty-ic"><x-icon name="bar-chart" /></div><b>Belum ada data untuk ditampilkan</b></div>
@else
  @php
    // Skala "nice": bulatkan puncak ke 1/2/2.5/5/10 × 10^k agar tick enak dibaca.
    $pow     = 10 ** floor(log10($maxVal));
    $niceMax = (int) ceil(collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $pow)->first(fn ($v) => $v >= $maxVal));
    $mid     = (int) round($niceMax / 2);

    $H = 190; $padT = 16; $padB = 26; $padL = 44; $padR = 10;
    $plotH = $H - $padT - $padB;
    $slot  = 56; $barW = 22;
    $W     = $padL + $padR + $slot * $n;
    $y     = fn ($v) => $padT + $plotH * (1 - $v / $niceMax);
    $fmt   = fn ($v) => number_format($v, 0, ',', '.');
  @endphp
  <svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" height="{{ $H }}" preserveAspectRatio="xMidYMid meet"
       role="img" aria-label="{{ collect($labels)->map(fn ($l, $i) => "$l {$vals[$i]}")->implode(', ') }}">
    {{-- Gridline hairline + tick sumbu Y (angka bulat) --}}
    @foreach([$mid, $niceMax] as $t)
      <line x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ $y($t) }}" y2="{{ $y($t) }}" stroke="#E7ECF0" stroke-width="1"/>
      <text x="{{ $padL - 8 }}" y="{{ $y($t) + 3.5 }}" text-anchor="end" font-size="10.5" fill="#94A3B8">{{ $fmt($t) }}</text>
    @endforeach

    @foreach($vals as $i => $v)
      @php
        $x     = $padL + $slot * $i + ($slot - $barW) / 2;
        $yTop  = $y($v);
        $yBase = $y(0);
        $h     = $yBase - $yTop;
        $r     = min(4, $h / 2);
      @endphp
      <g class="ch-mark">
        <title>{{ $labels[$i] }}: {{ $fmt($v) }} {{ $satuan }}</title>
        {{-- Target hover selebar slot (lebih besar dari mark) --}}
        <rect x="{{ $padL + $slot * $i }}" y="{{ $padT }}" width="{{ $slot }}" height="{{ $plotH }}" fill="transparent"/>
        @if($h >= 1)
          <path fill="#0D9488" d="M{{ $x }},{{ $yBase }} V{{ $yTop + $r }} Q{{ $x }},{{ $yTop }} {{ $x + $r }},{{ $yTop }} H{{ $x + $barW - $r }} Q{{ $x + $barW }},{{ $yTop }} {{ $x + $barW }},{{ $yTop + $r }} V{{ $yBase }} Z"/>
        @endif
        @if($i === $n - 1)
          <text x="{{ $x + $barW / 2 }}" y="{{ max(11, $yTop - 6) }}" text-anchor="middle"
                font-size="11" font-weight="700" fill="#334155">{{ $fmt($v) }}</text>
        @endif
        <text x="{{ $padL + $slot * $i + $slot / 2 }}" y="{{ $H - 8 }}" text-anchor="middle"
              font-size="11" fill="#64748B">{{ $labels[$i] }}</text>
      </g>
    @endforeach

    {{-- Baseline --}}
    <line x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ $y(0) }}" y2="{{ $y(0) }}" stroke="#CBD5E1" stroke-width="1"/>
  </svg>
@endif
