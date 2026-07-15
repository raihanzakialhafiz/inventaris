{{--
  Ikon garis monokrom (gaya Lucide, stroke = currentColor) — dipakai sidebar & UI.
  Mewarisi warna teks induknya, jadi otomatis ikut redup/aktif lewat CSS.
  Pakai: <x-icon name="box" /> · ukuran & kelas via atribut biasa.
--}}
@props(['name' => 'dot'])

@php
  // Setiap ikon: satu atau beberapa elemen SVG (tanpa <svg> pembungkus).
  $paths = [
    'home'        => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h3v-6h6v6h3a1 1 0 0 0 1-1V9.5"/>',
    'box'         => '<path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/>',
    'inbox-in'    => '<path d="M4 13v5a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"/><path d="M8 12l4 4 4-4"/><path d="M12 3v13"/>',
    'send-up'     => '<path d="M4 13v5a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"/><path d="M8 8l4-4 4 4"/><path d="M12 4v12"/>',
    'clipboard'   => '<rect x="8" y="3" width="8" height="4" rx="1"/><path d="M8 5H6a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2"/><path d="m9 14 2 2 4-4"/>',
    'file-text'   => '<path d="M14 3H7a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7Z"/><path d="M14 3v4h4"/><path d="M9 12h6"/><path d="M9 16h6"/>',
    'check-circle'=> '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
    'gauge'       => '<path d="M12 13l4-4"/><path d="M3.5 18a9 9 0 1 1 17 0"/><circle cx="12" cy="13" r="1.4"/>',
    'bar-chart'   => '<path d="M4 20V4"/><path d="M4 20h16"/><rect x="7" y="11" width="3" height="6"/><rect x="12" y="7" width="3" height="10"/><rect x="17" y="13" width="3" height="4"/>',
    'tag'         => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z"/><circle cx="7.5" cy="7.5" r="1.4"/>',
    'ruler'       => '<path d="M3 15 15 3l6 6L9 21Z"/><path d="M7 11l2 2"/><path d="M11 7l2 2"/><path d="M15 11l2 2"/>',
    'building'    => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h2"/><path d="M13 7h2"/><path d="M9 11h2"/><path d="M13 11h2"/><path d="M10 21v-4h4v4"/>',
    'truck'       => '<path d="M3 6a1 1 0 0 1 1-1h9v10H3Z"/><path d="M13 8h4l3 3v3h-7Z"/><circle cx="7" cy="17" r="2"/><circle cx="16" cy="17" r="2"/>',
    'target'      => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1.2"/>',
    'users'       => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 5.6"/><path d="M17.5 14.4A5.5 5.5 0 0 1 21 20"/>',
    'scroll'      => '<path d="M6 4h11a1 1 0 0 1 1 1v12a2 2 0 0 0 2 2H9a2 2 0 0 1-2-2V6a2 2 0 0 0-2-2Z"/><path d="M10 9h5"/><path d="M10 13h5"/>',
    'trash'       => '<path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/><path d="M10 11v6"/><path d="M14 11v6"/>',
    'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/>',
    'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
    'bell'        => '<path d="M6 9.5a6 6 0 0 1 12 0c0 4.5 1.8 5.5 1.8 5.5H4.2S6 14 6 9.5Z"/><path d="M10 19a2 2 0 0 0 4 0"/>',
    'user'        => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20a7 7 0 0 1 14 0"/>',
    'plus'        => '<path d="M12 5v14"/><path d="M5 12h14"/>',
    'printer'     => '<path d="M7 9V3h10v6"/><path d="M7 18H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><rect x="7" y="14" width="10" height="7" rx="1"/>',
    'download'    => '<path d="M12 3v12"/><path d="m8 11 4 4 4-4"/><path d="M5 21h14"/>',
    'dot'         => '<circle cx="12" cy="12" r="3"/>',
  ];
  $body = $paths[$name] ?? $paths['dot'];
@endphp

<svg {{ $attributes->merge(['width' => 20, 'height' => 20]) }} viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">{!! $body !!}</svg>
