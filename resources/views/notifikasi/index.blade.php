@extends('layouts.app')
@section('title', 'Notifikasi')

@section('content')
@php
  // [label, kelas-badge, warna aksen, ikon] per jenis notifikasi.
  $typeMeta = [
    'new_request'         => ['Permintaan Baru',      'b-primary', '#0D9488', '✉'],
    'request_approved'    => ['Permintaan Disetujui', 'b-ok',      '#16A34A', '✓'],
    'ready_to_distribute' => ['Siap Distribusi',      'b-primary', '#2563EB', '↑'],
    'request_rejected'    => ['Permintaan Ditolak',   'b-danger',  '#DC2626', '✕'],
    'request_distributed' => ['Distribusi Selesai',   'b-ok',      '#0D9488', '📦'],
    'low_stock'           => ['Stok Menipis',         'b-warn',    '#EA580C', '📉'],
    'reorder'             => ['Perlu Restok',         'b-warn',    '#CA8A04', '🛒'],
  ];
@endphp

<div class="page-head" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
  <div><h2>Notifikasi</h2><p>Semua pemberitahuan terkait aktivitas akun Anda.</p></div>
  <div style="display:flex;gap:8px">
    @if($notifications->where('is_read', false)->count())
      <form method="POST" action="{{ route('notifikasi.readAll') }}">
        @csrf
        <button type="submit" class="btn btn-ghost btn-sm">✓ Tandai semua dibaca</button>
      </form>
    @endif
    @if($notifications->total())
      <form method="POST" action="{{ route('notifikasi.destroyAll') }}"
            data-confirm="Hapus semua notifikasi? Tindakan ini tidak dapat dibatalkan."
            data-confirm-variant="danger" data-confirm-ok="Hapus Semua">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm"><x-icon name="trash" width="14" height="14" /> Hapus semua</button>
      </form>
    @endif
  </div>
</div>

<div class="card">
  <div class="card-b" style="padding:6px 0">
    @forelse($notifications as $n)
      @php $meta = $typeMeta[$n->type] ?? [ucfirst(str_replace('_',' ',$n->type)), 'b-neutral', '#64748B', '•']; @endphp
      <div class="notif-row {{ $n->is_read ? '' : 'unread' }}" style="border-left:3px solid {{ $meta[2] }}">
        <a href="{{ route('notifikasi.read', $n) }}" class="notif-row-main">
          <span class="notif-ic" style="background:{{ $meta[2] }}1a;color:{{ $meta[2] }}">{{ $meta[3] }}</span>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <span class="badge {{ $meta[1] }}">{{ $meta[0] }}</span>
              @unless($n->is_read)<span class="t-sub" style="font-weight:700;color:var(--primary-dark)">• baru</span>@endunless
            </div>
            <div class="notif-row-msg">{{ $n->message }}</div>
            <div class="t-sub" style="margin-top:2px">{{ optional($n->created_at)->translatedFormat('d M Y H:i') }} · {{ optional($n->created_at)->diffForHumans() }}</div>
          </div>
        </a>
        <div class="notif-row-del">
          <form method="POST" action="{{ route('notifikasi.destroy', $n) }}"
                data-confirm="Hapus notifikasi ini?"
                data-confirm-variant="danger" data-confirm-ok="Hapus">
            @csrf @method('DELETE')
            <button type="submit" class="icon-act danger" title="Hapus notifikasi">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </form>
        </div>
      </div>
    @empty
      <div class="empty">
        <div class="empty-ic">🔔</div>
        <b>Belum ada notifikasi</b>
        <p>Pemberitahuan aktivitas akan muncul di sini.</p>
      </div>
    @endforelse
  </div>
  @if($notifications->total())
    <div class="pg-bar">
      <span class="pg-info">Menampilkan {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} dari {{ $notifications->total() }} notifikasi</span>
      {{ $notifications->links() }}
    </div>
  @endif
</div>
@endsection
