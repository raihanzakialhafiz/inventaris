@php
$map = [
    'pending'          => ['b-warn',    'Menunggu'],
    'disetujui'        => ['b-primary', 'Disetujui'],
    'ditolak'          => ['b-danger',  'Ditolak'],
    'selesai'          => ['b-ok',      'Selesai'],
    'selesai_sebagian' => ['b-warn',    'Selesai Sebagian'],
];
[$cls, $lbl] = $map[$status] ?? ['b-neutral', $status];
@endphp
<span class="badge {{ $cls }}">{{ $lbl }}</span>
