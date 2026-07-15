@props(['col', 'label', 'class' => ''])
@php
  $s      = request('sort');
  $d      = request('dir');
  $active = $s === $col;

  if (!$active) {
      [$ns, $nd] = [$col, 'asc'];
  } elseif ($d === 'asc') {
      [$ns, $nd] = [$col, 'desc'];
  } else {
      [$ns, $nd] = [null, null];
  }

  $base   = request()->except(['sort', 'dir', 'page']);
  $params = $ns ? array_merge($base, ['sort' => $ns, 'dir' => $nd]) : $base;
  $qs     = http_build_query($params);
  $url    = request()->url() . ($qs ? '?' . $qs : '');

  $cls = $active ? ($d === 'asc' ? 'sort-asc' : 'sort-desc') : '';
@endphp
<th class="{{ $class }}">
  <a href="{{ $url }}" class="th-sort {{ $cls }} active-{{ $active ? 'yes' : 'no' }}" style="display:inline-flex;align-items:center;gap:4px">
    {{ $label }}
    <span class="sort-ic">
      <span class="si-up"></span>
      <span class="si-dn"></span>
    </span>
  </a>
</th>
