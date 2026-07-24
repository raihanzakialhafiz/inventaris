@extends('layouts.app')
@section('title', 'Sisa Kuota Bidang')
@section('page-title', 'Sisa Kuota Bidang')
@section('page-crumb', 'Permintaan')

@section('content')
<div class="page-head">
  <h2>Sisa Kuota Bidang{{ $dept ? ' · '.$dept->name : '' }}</h2>
  <p>Pemakaian &amp; sisa kuota permintaan ATK bidang Anda — periode {{ \Illuminate\Support\Carbon::parse($period.'-01')->isoFormat('MMMM YYYY') }}.</p>
</div>

<div class="card">
  <div class="card-h">
    <h3>Kuota per Barang</h3>
    <span class="hint">kuota/bulan · terpakai + outstanding</span>
  </div>
  <div class="card-b" style="padding:0">
    <div style="padding:8px 18px">
    @forelse($quotaItems as $qi)
      @php
        $pct      = $qi['quota'] > 0 ? min(100, round($qi['total'] / $qi['quota'] * 100)) : 0;
        $cls      = $pct >= 100 ? 'over' : ($pct >= 80 ? 'warn' : '');
        $barColor = $pct >= 100 ? 'var(--danger)' : ($pct >= 80 ? 'var(--warn)' : 'var(--primary)');
      @endphp
      <div style="padding:10px 0;border-bottom:1px solid var(--line)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;gap:10px">
          <span class="t-name" style="font-size:13px">
            {{ $qi['item']->name }} <span class="t-sub">· {{ $qi['item']->code }}</span>
            @if($qi['configured'])
              <span class="badge {{ $qi['policy'] === 'block' ? 'b-danger' : 'b-warn' }}" style="font-size:10px" title="Kebijakan bila terlampaui">{{ $qi['policy'] === 'block' ? 'Blokir' : 'Peringatan' }}</span>
            @else
              <span class="badge b-neutral" style="font-size:10px" title="Belum ada kuota resmi, memakai perkiraan otomatis">Default</span>
            @endif
          </span>
          <div style="text-align:right;white-space:nowrap">
            <span class="t-sub" style="font-size:12px">{{ $qi['used'] }} dipakai · {{ $qi['outstanding'] }} pending</span><br>
            <span style="font-size:12px;font-weight:700;color:{{ $barColor }}">
              sisa {{ $qi['remaining'] }} dari {{ $qi['quota'] }} {{ $qi['item']->unit }}
            </span>
          </div>
        </div>
        <div class="meter-row">
          <div class="meter {{ $cls }}"><i style="width:{{ $pct }}%"></i></div>
          <span class="mtxt">{{ $pct }}%</span>
        </div>
      </div>
    @empty
      <div class="empty" style="padding:36px 0">
        <div class="empty-ic"><x-icon name="target" /></div>
        <b>Belum ada data kuota</b>
        <p>Kuota akan muncul begitu ada barang terdaftar.</p>
      </div>
    @endforelse
    </div>
    @if($quotaItems->total())
      <div class="pg-bar">
        <span class="pg-info">Menampilkan {{ $quotaItems->firstItem() }}–{{ $quotaItems->lastItem() }} dari {{ $quotaItems->total() }} barang</span>
        {{ $quotaItems->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
