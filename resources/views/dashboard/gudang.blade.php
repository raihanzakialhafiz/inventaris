@extends('layouts.app')
@section('title', 'Dashboard Petugas Gudang')
@section('page-title', 'Halo, ' . explode(' ', auth()->user()->name)[0])
@section('page-crumb', 'Petugas Gudang · Kelola stok dan distribusi ATK · ' . now()->isoFormat('D MMM Y'))

@section('content')

{{-- Stat Cards --}}
<div class="grid g-4">
  <div class="stat {{ $approved->count() > 0 ? 'danger' : 'ok' }}">
    <div class="lbl">Antrean Distribusi</div>
    <div class="val">{{ $approved->count() }}</div>
    <div class="tag badge {{ $approved->count() > 0 ? 'b-danger' : 'b-ok' }}">
      {{ $approved->count() > 0 ? 'Perlu dikeluarkan' : 'Kosong' }}
    </div>
  </div>
  <div class="stat {{ $low->count() > 0 ? 'warn' : 'ok' }}">
    <div class="lbl">Stok Menipis</div>
    <div class="val">{{ $low->count() }}<small> barang</small></div>
    <div class="tag badge {{ $low->count() > 0 ? 'b-warn' : 'b-ok' }}">stok ≤ minimum</div>
  </div>
  <div class="stat {{ $out->count() > 0 ? 'danger' : 'ok' }}">
    <div class="lbl">Stok Habis</div>
    <div class="val">{{ $out->count() }}<small> barang</small></div>
    <div class="tag badge {{ $out->count() > 0 ? 'b-danger' : 'b-ok' }}">Segera restock</div>
  </div>
  <div class="stat ok">
    <div class="lbl">Total Unit Stok</div>
    <div class="val">{{ number_format($totalStok) }}</div>
    <div class="tag badge b-ok">{{ $items->count() }} jenis barang</div>
  </div>
</div>

{{-- Alert antrean distribusi --}}
@if($approved->count() > 0)
  <div class="notice warn mt">
    <span class="ic"><x-icon name="inbox-in" /></span>
    <div>
      <b>{{ $approved->count() }} permintaan</b> menunggu distribusi.
      <a href="{{ route('distribusi.index') }}" style="margin-left:8px;font-weight:600">Lihat antrean <x-icon name="arrow-right" width="13" height="13" style="vertical-align:-2px" /></a>
    </div>
  </div>
@endif

<div class="grid g-2 mt">
  {{-- Stok Kritis --}}
  <div class="card">
    <div class="card-h">
      <h3>Stok Perlu Perhatian</h3>
      <span class="hint">≤ minimum stok</span>
    </div>
    <div class="card-b" style="padding:6px 0">
      @forelse($low->merge($out) as $item)
        @php $st = $item->stock <= 0 ? ['b-danger','Habis'] : ['b-warn','Menipis']; @endphp
        <div style="display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid var(--line)">
          <span class="code" style="flex:0 0 auto">{{ $item->code }}</span>
          <div style="flex:1;min-width:0">
            <div class="t-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item->name }}</div>
            <div class="t-sub">min {{ $item->minimum_stock }} · {{ $item->location ?: 'lokasi ??' }}</div>
          </div>
          <span style="font-weight:700;color:{{ $item->stock <= 0 ? 'var(--danger)' : 'var(--warn)' }}">
            {{ $item->stock }} {{ $item->unit }}
          </span>
          <span class="badge {{ $st[0] }}">{{ $st[1] }}</span>
        </div>
      @empty
        <div class="empty" style="padding:32px 0">
          <div class="empty-ic"><x-icon name="check" /></div>
          <b>Semua stok aman</b>
          <p class="t-sub" style="margin-top:4px">Tidak ada barang perlu perhatian</p>
        </div>
      @endforelse
    </div>
    @if($low->merge($out)->count() > 0)
      <div style="padding:10px 18px;border-top:1px solid var(--line)">
        <a href="{{ route('barang-masuk.index') }}" class="btn btn-pri btn-sm">+ Input Barang Masuk</a>
      </div>
    @endif
  </div>

  {{-- Antrean Distribusi --}}
  <div class="card">
    <div class="card-h">
      <h3>Antrean Distribusi</h3>
      <span class="hint">sudah disetujui Kasubag</span>
    </div>
    <div class="card-b" style="padding:6px 0">
      @forelse($approved->take(6) as $req)
        <div style="display:flex;align-items:center;gap:12px;padding:11px 18px;border-bottom:1px solid var(--line)">
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px">
              <span class="code">{{ $req->request_no }}</span>
              <span class="badge b-primary">{{ $req->department->code }}</span>
            </div>
            <div class="t-sub" style="margin-top:2px">{{ $req->details->count() }} jenis barang · {{ $req->department->name }}</div>
          </div>
          <a href="{{ route('distribusi.index', ['proses' => $req->id]) }}" class="btn btn-pri btn-sm" style="flex:0 0 auto">Proses <x-icon name="arrow-right" width="13" height="13" style="vertical-align:-2px" /></a>
        </div>
      @empty
        <div class="empty" style="padding:32px 0">
          <div class="empty-ic"><x-icon name="inbox-in" /></div>
          <b>Tidak ada antrean distribusi</b>
          <p class="t-sub" style="margin-top:4px">Semua permintaan sudah diproses</p>
        </div>
      @endforelse
      @if($approved->count() > 6)
        <div style="padding:10px 18px;text-align:center;border-top:1px solid var(--line)">
          <a href="{{ route('distribusi.index') }}" class="btn btn-ghost btn-sm">
            Lihat semua {{ $approved->count() }} antrean <x-icon name="arrow-right" width="13" height="13" style="vertical-align:-2px" />
          </a>
        </div>
      @endif
    </div>
  </div>
</div>

@endsection
