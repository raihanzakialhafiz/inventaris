<table>
    <thead>
        {{-- Kop: urutan baris mengikuti template PDF. --}}
        @if($government)<tr><th colspan="{{ count($headers) }}">{{ $government }}</th></tr>@endif
        <tr><th colspan="{{ count($headers) }}" style="font-weight:bold">{{ $institution }}</th></tr>
        @if($address)<tr><th colspan="{{ count($headers) }}">{{ $address }}</th></tr>@endif
        @if($email)<tr><th colspan="{{ count($headers) }}">email : {{ $email }}</th></tr>@endif
        <tr></tr>
        <tr><th colspan="{{ count($headers) }}" style="font-weight:bold">{{ strtoupper($title) }}</th></tr>
        @if($period)<tr><th colspan="{{ count($headers) }}">Periode: {{ $period }}@if($deptName) · Bidang: {{ $deptName }}@endif</th></tr>@endif
        <tr><th colspan="{{ count($headers) }}">Dicetak: {{ now()->format('d/m/Y H:i') }}@if($exporter) · Oleh: {{ $exporter['name'] }}@endif</th></tr>
        <tr></tr>
        <tr>
            @foreach($headers as $h)
                <th style="font-weight:bold;background:#0f766e;color:#ffffff">{{ $h }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($headers) }}">Tidak ada data.</td></tr>
        @endforelse
    </tbody>

    {{-- Blok tanda tangan pimpinan, didorong ke kolom kanan. Ekspor lewat
         FromView (HTML) tidak bisa mengatur tinggi baris atau merge sel, jadi
         ruang tanda tangan dibentuk dengan baris kosong dan posisinya lewat
         sel kosong ber-colspan. Urutan barisnya sengaja disamakan dengan PDF:
         tanggal · jabatan · ruang tanda basah · nama (di atas garis) · NIP.

         border-top pada sel nama = garis tanda tangan. Pembaca HTML Maatwebsite
         tidak selalu memetakan border; kalau diabaikan, hasilnya tetap rapi —
         nama tebal dengan ruang kosong di atasnya. Jadi ini peningkatan yang
         aman, bukan taruhan. --}}
    @if($signer)
        @php
            // Sel kosong kiri mengisi kolom selain kolom terakhir.
            $kiri = max(1, count($headers) - 1);
        @endphp
        <tr></tr>
        <tr>
            <td colspan="{{ $kiri }}"></td>
            <td>{{ $place ? $place . ', ' : '' }}{{ now()->isoFormat('D MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td colspan="{{ $kiri }}"></td>
            <td>{{ $signer['jabatan'] }}</td>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr>
            <td colspan="{{ $kiri }}"></td>
            <td style="font-weight:bold;border-top:1px solid #000000">{{ $signer['name'] }}</td>
        </tr>
        <tr>
            <td colspan="{{ $kiri }}"></td>
            <td>NIP. {{ $signer['nip'] ?: '-' }}</td>
        </tr>
    @endif
</table>
