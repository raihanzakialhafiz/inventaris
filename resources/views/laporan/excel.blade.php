<table>
    <thead>
        <tr><th colspan="{{ count($headers) }}" style="font-weight:bold">{{ $institution }} — {{ $title }}</th></tr>
        @if($period)<tr><th colspan="{{ count($headers) }}">Periode: {{ $period }}@if($deptName) · Bidang: {{ $deptName }}@endif</th></tr>@endif
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
</table>
