@extends('layouts.app')
@section('title', 'Dashboard Kepala Bidang')
@section('page-title', 'Halo, ' . explode(' ', auth()->user()->name)[0])
@section('page-crumb', ($dept->name ?? '') . ' · periode ' . $period . ' · ' . now()->isoFormat('D MMM Y'))

@section('content')

{{-- Stat Cards --}}
<div class="grid g-4">
  <div class="stat">
    <div class="accent"></div>
    <div class="lbl">Permintaan Periode Ini</div>
    <div class="val">{{ $myReqs->count() }}</div>
    <div class="tag badge b-neutral">{{ $period }}</div>
  </div>
  <div class="stat {{ $pending > 0 ? 'warn' : 'ok' }}">
    <div class="accent"></div>
    <div class="lbl">Menunggu Kasubag</div>
    <div class="val">{{ $pending }}</div>
    <div class="tag badge {{ $pending > 0 ? 'b-warn' : 'b-ok' }}">
      {{ $pending > 0 ? 'Belum diproses' : 'Semua diproses' }}
    </div>
  </div>
  <div class="stat {{ $selesai > 0 ? 'ok' : '' }}">
    <div class="accent"></div>
    <div class="lbl">Selesai Didistribusikan</div>
    <div class="val">{{ $selesai }}</div>
    <div class="tag badge b-ok">Diterima bidang</div>
  </div>
  <div class="stat ok">
    <div class="accent"></div>
    <div class="lbl">Total Item Diterima</div>
    <div class="val">{{ $totalDiterima }}</div>
    <div class="tag badge b-ok">unit ATK periode ini</div>
  </div>
</div>

<div class="grid g-2 mt">
  {{-- Kuota Pemakaian --}}
  <div class="card">
    <div class="card-h">
      <h3>Sisa Kuota Bidang</h3>
      <span class="hint">kuota/bulan · terpakai + outstanding</span>
    </div>
    <div class="card-b" style="padding:8px 18px">
      @forelse($quotaItems as $qi)
        @php
          $pct = $qi['quota'] > 0 ? min(100, round($qi['total'] / $qi['quota'] * 100)) : 0;
          $cls = $pct >= 100 ? 'over' : ($pct >= 80 ? 'warn' : '');
          $barColor = $pct >= 100 ? 'var(--danger)' : ($pct >= 80 ? 'var(--warn)' : 'var(--primary)');
        @endphp
        <div style="padding:10px 0;border-bottom:1px solid var(--line)">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <span class="t-name" style="font-size:13px">{{ $qi['item']->name }}</span>
            <div style="text-align:right">
              <span class="t-sub" style="font-size:12px">{{ $qi['used'] }} dipakai · {{ $qi['outstanding'] }} pending</span><br>
              <span style="font-size:12px;font-weight:600;color:{{ $barColor }}">{{ $qi['total'] }}/{{ $qi['quota'] }} {{ $qi['item']->unit }}</span>
            </div>
          </div>
          <div class="meter-row">
            <div class="meter {{ $cls }}"><i style="width:{{ $pct }}%"></i></div>
            <span class="mtxt">{{ $pct }}%</span>
          </div>
        </div>
      @empty
        <div class="empty" style="padding:32px 0">
          <div class="ic" style="font-size:22px">✓</div>
          <b>Belum ada pemakaian bulan ini</b>
        </div>
      @endforelse
    </div>
  </div>

  {{-- Permintaan Terbaru --}}
  <div class="card">
    <div class="card-h">
      <h3>Permintaan Terbaru</h3>
      <a href="{{ route('permintaan.index') }}" class="btn btn-ghost btn-sm">Semua</a>
    </div>
    <div class="card-b" style="padding:6px 0">
      @forelse($recent as $req)
        <div style="padding:11px 18px;border-bottom:1px solid var(--line)">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <a href="{{ route('permintaan.show', $req) }}" class="code">{{ $req->request_no }}</a>
            <x-status-badge :status="$req->status" />
            @if($req->is_flagged)<span class="badge b-danger" style="font-size:10px">⚠ Over-Request</span>@endif
            <span class="t-sub" style="margin-left:auto;white-space:nowrap">{{ $req->request_date->format('d M') }}</span>
          </div>
          <div class="t-sub" style="margin-top:5px;font-size:12px">
            {{ $req->details->map(fn($d) => $d->item->name . ' ×' . $d->quantity_requested)->implode(', ') }}
          </div>
        </div>
      @empty
        <div class="empty" style="padding:24px 0"><div class="ic">☷</div><b>Belum ada permintaan</b></div>
      @endforelse
      <div style="padding:12px 18px">
        <a href="{{ route('permintaan.index') }}" class="btn btn-pri" style="width:100%;justify-content:center">
          <x-icon name="plus" width="15" height="15" /> Buat Permintaan Baru
        </a>
      </div>
    </div>
  </div>
</div>

@endsection
