{{-- Sengaja TANPA @if($paginator->hasPages()): saat data muat satu halaman,
     nav tetap tampil («1» + panah nonaktif). Disembunyikan total membuat
     pengguna mengira pagination rusak. --}}
@php
    $current = $paginator->currentPage();
    $last    = $paginator->lastPage();
    $window  = 1; // jumlah nomor di kiri & kanan halaman aktif

    // Bangun daftar nomor ringkas: selalu halaman 1 & terakhir + sekitar aktif.
    $pages = collect([1, $last]);
    for ($i = $current - $window; $i <= $current + $window; $i++) {
        if ($i >= 1 && $i <= $last) {
            $pages->push($i);
        }
    }
    $pages = $pages->unique()->sort()->values();
  @endphp
  <nav>
    <ul class="pagination">
      {{-- Sebelumnya --}}
      @if ($paginator->onFirstPage())
        <li class="disabled"><span>‹</span></li>
      @else
        <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">‹</a></li>
      @endif

      {{-- Nomor halaman (dengan elipsis) --}}
      @php $prev = 0; @endphp
      @foreach ($pages as $page)
        @if ($page - $prev > 1)
          <li class="disabled"><span>…</span></li>
        @endif
        @if ($page == $current)
          <li class="active"><span>{{ $page }}</span></li>
        @else
          <li><a href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
        @endif
        @php $prev = $page; @endphp
      @endforeach

      {{-- Berikutnya --}}
      @if ($paginator->hasMorePages())
        <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">›</a></li>
      @else
        <li class="disabled"><span>›</span></li>
      @endif
    </ul>
  </nav>
