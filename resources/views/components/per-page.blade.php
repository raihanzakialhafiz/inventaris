@props([
    'perPage' => 15,
    'options' => [10, 25, 50, 100],
])

{{-- Selektor "Tampilkan N data" untuk diletakkan di dalam pagination bar (kiri-bawah).
     Form mini yang mempertahankan seluruh query string aktif (search/filter/sort). --}}
<form method="GET" class="per-page">
    @foreach(request()->except(['per_page', 'page']) as $key => $value)
        @if(is_array($value))
            @foreach($value as $v)
                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <span>Tampilkan</span>
    <select name="per_page" class="inp" onchange="this.form.submit()">
        @foreach($options as $n)
            <option value="{{ $n }}" {{ (int) $perPage === $n ? 'selected' : '' }}>{{ $n }}</option>
        @endforeach
    </select>
    <span>data</span>
</form>
