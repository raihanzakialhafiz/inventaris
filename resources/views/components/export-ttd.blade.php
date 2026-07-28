{{--
  Toolbar ekspor PDF/Excel dengan opsi menyertakan tanda tangan pimpinan.
  Toggle tanda tangan hanya tampil bila pejabat penanda tangan sudah diatur di
  Pengaturan › Penanda Tangan Laporan. Tombol format tergabung (segmented) agar
  seluruh kontrol terbaca sebagai satu unit, bukan tiga item terpisah.

  Pakai: <x-export-ttd :pdf="route(...)" :excel="route(...)" />
--}}
@props(['pdf' => null, 'excel' => null, 'pdfLabel' => 'PDF'])

@php
  // URL ttd dihitung di server: sebagian URL ekspor sudah membawa query
  // (format/type) dan sebagian polos, jadi pemisahnya ikut menyesuaikan.
  $ttdUrl = fn (?string $url) => $url ? $url . (str_contains($url, '?') ? '&' : '?') . 'ttd=1' : null;
@endphp

<div class="export-tools"
     x-data='{ ttd: false, pdf: @json($pdf), pdfTtd: @json($ttdUrl($pdf)), xls: @json($excel), xlsTtd: @json($ttdUrl($excel)) }'>
  @if(setting('signer_user_id'))
    <label class="export-sign" title="Tambahkan blok tanda tangan pejabat pada dokumen">
      <input type="checkbox" x-model="ttd">
      <span>Tanda tangan pimpinan</span>
    </label>
    <span class="export-div" aria-hidden="true"></span>
  @endif

  <div class="export-actions">
    @if($pdf)
      <a href="{{ $pdf }}" :href="ttd ? pdfTtd : pdf" class="export-btn">
        <x-icon name="printer" width="14" height="14" /> {{ $pdfLabel }}
      </a>
    @endif
    @if($excel)
      <a href="{{ $excel }}" :href="ttd ? xlsTtd : xls" class="export-btn">
        <x-icon name="download" width="14" height="14" /> Excel
      </a>
    @endif
  </div>
</div>
