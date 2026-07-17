{{--
  Tombol ekspor PDF/Excel dengan opsi menyertakan tanda tangan pimpinan.
  Checkbox hanya tampil bila pejabat penanda tangan sudah diatur di
  Pengaturan › Penanda Tangan Laporan.

  Pakai: <x-export-ttd :pdf="route(...)" :excel="route(...)" />
--}}
@props(['pdf' => null, 'excel' => null, 'pdfLabel' => 'PDF'])

@php
  // URL ttd dihitung di server: sebagian URL ekspor sudah membawa query
  // (format/type) dan sebagian polos, jadi pemisahnya ikut menyesuaikan.
  $ttdUrl = fn (?string $url) => $url ? $url . (str_contains($url, '?') ? '&' : '?') . 'ttd=1' : null;
@endphp

<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap"
     x-data='{ ttd: false, pdf: @json($pdf), pdfTtd: @json($ttdUrl($pdf)), xls: @json($excel), xlsTtd: @json($ttdUrl($excel)) }'>
  @if(setting('signer_user_id'))
    <label style="display:flex;gap:6px;align-items:center;font-size:13px;white-space:nowrap;cursor:pointer">
      <input type="checkbox" x-model="ttd"> Sertakan tanda tangan pimpinan
    </label>
  @endif
  @if($pdf)
    <a href="{{ $pdf }}" :href="ttd ? pdfTtd : pdf" class="btn btn-ghost btn-sm"><x-icon name="printer" width="14" height="14" /> {{ $pdfLabel }}</a>
  @endif
  @if($excel)
    <a href="{{ $excel }}" :href="ttd ? xlsTtd : xls" class="btn btn-ghost btn-sm"><x-icon name="download" width="14" height="14" /> Excel</a>
  @endif
</div>
