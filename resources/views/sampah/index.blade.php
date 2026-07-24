@extends('layouts.app')
@section('title', 'Kotak Sampah')
@section('page-title', 'Kotak Sampah')
@section('page-crumb', 'Sistem')

@section('content')
<div class="page-head">
  <h2>Kotak Sampah</h2>
  <p>Data terhapus dapat dipulihkan. Otomatis dihapus permanen setelah {{ $retention }} hari sejak dihapus.</p>
</div>

<div class="card">
  <div class="card-b" style="padding:0">
    @if($rows->count())
      <table>
        <thead><tr>
          <th class="num">No</th>
          <th>Jenis</th>
          <th>Data Dihapus</th>
          <th>Dihapus Oleh</th>
          <th>Tanggal Hapus</th>
          <th>Hapus Permanen</th>
          <th>Aksi</th>
        </tr></thead>
        <tbody>
          @foreach($rows as $row)
            @php
              $elapsed  = (int) $row['deleted_at']->diffInDays(now());
              $daysLeft = max(0, $retention - $elapsed);
            @endphp
            <tr>
              <td class="num t-sub">{{ $rows->firstItem() + $loop->index }}</td>
              <td><span class="badge b-neutral">{{ $row['type'] }}</span></td>
              <td class="t-name">{{ $row['name'] }}</td>
              <td class="t-sub">{{ $row['deleted_by'] }}</td>
              <td class="t-sub">{{ $row['deleted_at']?->isoFormat('D MMM YYYY · HH:mm') }}</td>
              <td><span class="badge {{ $daysLeft <= 7 ? 'b-danger' : 'b-warn' }}">{{ $daysLeft }} hari lagi</span></td>
              <td style="white-space:nowrap">
                <div class="act-group">
                  {{-- Pulihkan --}}
                  <form method="POST" action="{{ route('sampah.restore', [$row['slug'], $row['id']]) }}" style="display:inline"
                        data-confirm="Pulihkan {{ $row['type'] }} “{{ $row['name'] }}”?" data-confirm-ok="Pulihkan">
                    @csrf
                    <button type="submit" class="icon-act ok" title="Pulihkan">
                      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                    </button>
                  </form>
                  {{-- Hapus permanen --}}
                  <form method="POST" action="{{ route('sampah.destroy', [$row['slug'], $row['id']]) }}" style="display:inline"
                        data-confirm="Hapus permanen {{ $row['type'] }} “{{ $row['name'] }}”? Tindakan ini tidak dapat dibatalkan."
                        data-confirm-variant="danger" data-confirm-ok="Hapus Permanen">
                    @csrf @method('DELETE')
                    <button type="submit" class="icon-act danger" title="Hapus permanen">
                      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      @if($rows->total())
        <div class="pg-bar">
          <span class="pg-info">Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $rows->total() }} data</span>
          {{ $rows->links() }}
        </div>
      @endif
    @else
      <div class="empty">
        <div class="empty-ic"><x-icon name="trash" /></div>
        <b>Kotak sampah kosong</b>
        <p>Data yang dihapus akan muncul di sini dan bisa dipulihkan dalam {{ $retention }} hari.</p>
      </div>
    @endif
  </div>
</div>
@endsection
