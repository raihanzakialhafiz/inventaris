<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; margin: 0; }
    /* Kop surat dinas: teks terpusat pada halaman dengan logo di kiri, garis
       tebal sebagai penutup. Semua hitam — dokumen resmi kerap dicetak B/W. */
    .kop { border-bottom: 3px solid #000; padding-bottom: 6px; margin-bottom: 12px; color: #000; }
    .kop table { width: 100%; border: none; }
    .kop td { border: none; vertical-align: middle; padding: 0; }
    /* Lebar sel logo & sel penyeimbang WAJIB sama, jika tidak teks kop lepas
       dari titik tengah halaman. Logo digeser lewat margin di dalam selnya,
       bukan dengan mengubah lebar sel. */
    .kop .logo { width: 140px; }
    .kop .logo img { width: 58px; height: 58px; margin-left: 75px; }
    .kop .teks { text-align: center; }
    .kop .pem { font-size: 11px; margin: 0; }
    .kop .inst { font-size: 15px; font-weight: bold; margin: 0; }
    .kop .addr { font-size: 8px; margin: 1px 0 0; }
    /* Judul berdiri sendiri di bawah kop, bukan bagian dari identitas instansi. */
    .judul { text-align: center; margin-bottom: 12px; color: #000; }
    .judul h1 { font-size: 13px; font-weight: bold; margin: 0; text-transform: uppercase; }
    .meta { font-size: 10px; color: #475569; margin-bottom: 10px; }
    .meta b { color: #1e293b; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #0f766e; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; }
    table.data td { border-bottom: 1px solid #e2e8f0; padding: 5px 8px; font-size: 10px; }
    table.data tr:nth-child(even) td { background: #f6f9f8; }
    .empty { text-align: center; padding: 24px; color: #94a3b8; }
    /* page-break-inside: jangan sampai nama terpisah dari jabatannya antar halaman. */
    table.ttd { width: 100%; margin-top: 36px; border: none; page-break-inside: avoid; }
    /* Semua hitam pekat — blok tanda tangan sering dicetak & difotokopi B/W. */
    table.ttd td { border: none; vertical-align: top; padding: 0; font-size: 10.5px; color: #000; line-height: 1.5; }
    /* Blok menyusut selebar baris terpanjangnya lalu menempel ke margin kanan,
       berapa pun panjang jabatannya. Lebar tetap (mis. 35%) tidak bisa: jabatan
       pendek menyisakan celah lebar di kanan, jabatan panjang terpotong.

       width:1% + nowrap wajib berpasangan. nowrap saja tidak cukup: pada tabel
       width:100% sisa lebar justru dilimpahkan ke sel ini (sel kosong punya
       max-content 0), sehingga blok malah melebar penuh dan jatuh ke kiri.
       width:1% memaksanya ke min-content; sel kosong menyerap sisanya.

       padding-right memberi jarak dari tepi kertas: sel menempel margin kanan,
       jadi padding-nya mendorong teks ke kiri tanpa mengunci lebar blok.

       Baris-baris dipecah jadi <div> (bukan <br>) agar tiap baris bisa diberi
       jarak sendiri — ini yang bikin bloknya rapi, bukan menempel padat. */
    table.ttd .blok { width: 1%; white-space: nowrap; padding-right: 34px; text-align: left; }
    table.ttd .tgl { margin-bottom: 1px; }
    table.ttd .jab { margin-bottom: 2px; }
    /* Ruang tanda basah; garis tipis di bawahnya jadi tempat membubuhkan tanda
       tangan, lalu nama tercetak persis di atas garis identitas. */
    table.ttd .ruang { height: 56px; border-bottom: 1px solid #000; }
    table.ttd .nama { font-weight: bold; padding-top: 3px; }
    table.ttd .nip  { color: #334155; font-size: 10px; }
    .foot { margin-top: 20px; padding-top: 6px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: right; }
  </style>
</head>
<body>
  @php $adaLogo = $logo && file_exists($logo); @endphp
  <div class="kop">
    <table>
      <tr>
        @if($adaLogo)<td class="logo"><img src="{{ $logo }}" alt="Logo"></td>@endif
        <td class="teks">
          @if($government)<p class="pem">{{ $government }}</p>@endif
          <p class="inst">{{ $institution }}</p>
          @if($address)<p class="addr">{{ $address }}</p>@endif
          @if($email)<p class="addr">email : {{ $email }}</p>@endif
        </td>
        {{-- Sel kosong penyeimbang selebar logo: tanpa ini teks terpusat pada
             sisa ruang di kanan logo, bukan pada halaman. --}}
        @if($adaLogo)<td class="logo"></td>@endif
      </tr>
    </table>
  </div>

  <div class="judul"><h1>{{ $title }}</h1></div>

  <div class="meta">
    @if($period)<b>Periode:</b> {{ $period }} &nbsp;&nbsp;@endif
    @if($deptName)<b>Bidang:</b> {{ $deptName }} &nbsp;&nbsp;@endif
    <b>Dicetak:</b> {{ now()->format('d/m/Y H:i') }}
    @if($exporter)&nbsp;&nbsp;<b>Oleh:</b> {{ $exporter['name'] }}@endif
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

  @if($signer)
    <table class="ttd">
      <tr>
        {{-- Sel kosong menyerap sisa lebar dan mendorong blok ke margin kanan;
             DomPDF tidak menangani float/margin-left:auto pada tabel seandal ini. --}}
        <td></td>
        <td class="blok">
          <div class="tgl">{{ $place ? $place . ', ' : '' }}{{ now()->isoFormat('D MMMM YYYY') }}</div>
          <div class="jab">{{ $signer['jabatan'] }}</div>
          <div class="ruang"></div>
          <div class="nama">{{ $signer['name'] }}</div>
          <div class="nip">NIP. {{ $signer['nip'] ?: '-' }}</div>
        </td>
      </tr>
    </table>
  @endif

  <div class="foot">{{ $appName }} · {{ setting('footer_text', 'Sistem Inventaris ATK') }}</div>
</body>
</html>
