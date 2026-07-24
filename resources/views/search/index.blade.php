@extends('layouts.app')
@section('title', 'Pencarian')
@section('page-title', 'Pencarian')
@section('page-crumb', 'Global')

@section('content')
<div style="max-width:920px">

  <div class="page-head">
    <h2>Hasil Pencarian</h2>
    <p>
      @if($q === '')
        Ketik kata kunci untuk menelusuri seluruh menu sekaligus.
      @else
        Menemukan <b>{{ $total }}</b> hasil untuk “<b>{{ $q }}</b>”.
      @endif
    </p>
  </div>

  <form method="GET" action="{{ route('search.index') }}" class="filter-bar" style="margin-bottom:18px">
    <input type="search" name="q" value="{{ $q }}" autofocus
           placeholder="Cari barang, permintaan, pengguna, supplier…">
    <button type="submit" class="btn btn-pri btn-sm">Cari</button>
    @if($q !== '')
      <a href="{{ route('search.index') }}" class="btn btn-ghost btn-sm"><x-icon name="x" width="13" height="13" /> Reset</a>
    @endif
  </form>

  @forelse($groups as $group)
    <div class="card" style="margin-bottom:16px">
      <div class="card-h" style="display:flex;align-items:center;gap:10px">
        <span class="ic" style="font-size:16px">{{ $group['icon'] }}</span>
        <h3 style="margin:0">{{ $group['label'] }}</h3>
        <span class="nav-badge" style="position:static">{{ count($group['items']) }}</span>
      </div>
      <div class="card-b" style="padding:0">
        @foreach($group['items'] as $item)
          <a href="{{ $item['url'] }}"
             style="display:flex;flex-direction:column;gap:2px;padding:12px 16px;border-bottom:1px solid var(--line);text-decoration:none;color:inherit;transition:background .12s"
             onmouseover="this.style.background='var(--bg-soft,#f7fafa)'" onmouseout="this.style.background=''">
            <span style="font-weight:600;color:var(--text,#0f172a)">{{ $item['title'] }}</span>
            <span style="font-size:12.5px;color:var(--muted,#64748b)">{{ $item['subtitle'] }}</span>
          </a>
        @endforeach
      </div>
      @if(!empty($group['allUrl']))
        <a href="{{ $group['allUrl'] }}" class="notif-foot" style="display:block">Lihat semua di {{ $group['label'] }} <x-icon name="arrow-right" width="13" height="13" style="vertical-align:-2px" /></a>
      @endif
    </div>
  @empty
    <div class="card">
      <div class="card-b" style="text-align:center;padding:48px 24px;color:var(--muted,#64748b)">
        @if($q === '')
          <div style="font-size:34px;margin-bottom:8px">🔎</div>
          <b>Mulai mencari</b>
          <p style="margin-top:4px">Gunakan kolom di atas atau kotak pencarian di header untuk menelusuri seluruh data.</p>
        @else
          <div style="font-size:34px;margin-bottom:8px">🗂</div>
          <b>Tidak ada hasil</b>
          <p style="margin-top:4px">Tidak ditemukan data yang cocok dengan “{{ $q }}”. Coba kata kunci lain.</p>
        @endif
      </div>
    </div>
  @endforelse

</div>
@endsection
