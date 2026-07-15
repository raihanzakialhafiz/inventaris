@extends('layouts.app')
@section('title', 'Dashboard Kasubag Umum')
@section('page-title', 'Halo, ' . explode(' ', auth()->user()->name)[0])
@section('page-crumb', 'Kasubag Umum · Persetujuan permintaan ATK · ' . now()->isoFormat('D MMM Y'))

@section('content')

{{-- Stat Cards --}}
<div class="grid g-4">
  <div class="stat {{ $pending->count() > 0 ? 'danger' : 'ok' }}">
    <div class="accent"></div>
    <div class="lbl">Menunggu Persetujuan</div>
    <div class="val">{{ $pending->count() }}</div>
    <div class="tag badge {{ $pending->count() > 0 ? 'b-danger' : 'b-ok' }}">
      {{ $pending->count() > 0 ? 'Harus diproses' : 'Semua bersih' }}
    </div>
  </div>
  <div class="stat {{ $flagged > 0 ? 'warn' : 'ok' }}">
    <div class="accent"></div>
    <div class="lbl">Over-Request</div>
    <div class="val">{{ $flagged }}</div>
    <div class="tag badge {{ $flagged > 0 ? 'b-warn' : 'b-ok' }}">
      {{ $flagged > 0 ? 'Butuh justifikasi' : 'Tidak ada' }}
    </div>
  </div>
  <div class="stat">
    <div class="accent"></div>
    <div class="lbl">Total Bidang</div>
    <div class="val">{{ $deptSummary->count() }}</div>
    <div class="tag badge b-neutral">unit kerja aktif</div>
  </div>
  <div class="stat ok">
    <div class="accent"></div>
    <div class="lbl">Diproses Bulan Ini</div>
    <div class="val">{{ $recentApproved->count() }}</div>
    <div class="tag badge b-ok">selesai diproses</div>
  </div>
</div>

{{-- Pemberitahuan ringkas (tanpa tabel) — detail ada di menu Permintaan --}}
@if($pending->count() > 0)
  <div class="notice warn mt" style="align-items:center;gap:12px">
    <span class="ic">🔔</span>
    <div style="flex:1">
      <b>{{ $pending->count() }} permintaan menunggu persetujuan.</b>
      @if($flagged > 0)<span class="t-sub"> · {{ $flagged }} di antaranya over-request.</span>@endif
    </div>
    <a href="{{ route('permintaan.index', ['view' => 'masuk']) }}" class="btn btn-pri btn-sm" style="flex:0 0 auto">Lihat &amp; Proses →</a>
  </div>
@else
  <div class="notice ok-notice mt" style="align-items:center">
    <span class="ic">✓</span>
    <div><b>Tidak ada permintaan pending.</b> Semua permintaan sudah diproses.</div>
  </div>
@endif

{{-- Grafik tren permintaan --}}
<div class="card mt">
  <div class="card-h">
    <h3>Tren Permintaan Masuk</h3>
    <span class="hint">jumlah pengajuan · 6 bulan terakhir</span>
  </div>
  <div class="card-b">
    <x-chart-columns :data="$reqTrend" satuan="permintaan" />
  </div>
</div>

<div class="grid g-2 mt">
  {{-- Status per Bidang --}}
  <div class="card">
    <div class="card-h">
      <h3>Status per Bidang</h3>
      <span class="hint">periode {{ $period }}</span>
    </div>
    <div class="card-b" style="padding:0">
      <table>
        <thead><tr>
          <th>Bidang</th><th class="num">Total</th>
          <th class="num">Pending</th><th class="num">Flag</th>
        </tr></thead>
        <tbody>
          @foreach($deptSummary as $ds)
            <tr>
              <td>
                <span class="badge b-primary" style="margin-right:6px">{{ $ds['dept']->code }}</span>
                {{ $ds['dept']->name }}
              </td>
              <td class="num">{{ $ds['total'] }}</td>
              <td class="num">
                {!! $ds['pending'] > 0
                  ? '<span style="color:var(--danger);font-weight:700">' . $ds['pending'] . '</span>'
                  : '<span class="t-sub">0</span>' !!}
              </td>
              <td class="num">
                {!! $ds['flagged'] > 0
                  ? '<span style="color:var(--warn);font-weight:700">' . $ds['flagged'] . '</span>'
                  : '<span class="t-sub">0</span>' !!}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Baru Diproses --}}
  <div class="card">
    <div class="card-h">
      <h3>Baru Diproses</h3>
      <span class="hint">disetujui / ditolak bulan ini</span>
    </div>
    <div class="card-b" style="padding:6px 0">
      @forelse($recentApproved as $req)
        @php
          $isApproved = $req->status === 'disetujui';
          $badge = $isApproved ? 'b-primary' : 'b-danger';
          $label = $isApproved ? 'Disetujui' : 'Ditolak';
        @endphp
        <div style="padding:10px 18px;border-bottom:1px solid var(--line)">
          <div style="display:flex;gap:8px;align-items:center">
            <a href="{{ route('permintaan.show', $req) }}" class="code">{{ $req->request_no }}</a>
            <span class="badge b-primary">{{ $req->department->code }}</span>
            <span class="badge {{ $badge }}">{{ $label }}</span>
          </div>
          <div class="t-sub" style="margin-top:3px">{{ $req->requester->name }}</div>
        </div>
      @empty
        <div class="empty" style="padding:32px 0">
          <div class="ic">✓</div>
          <b>Belum ada yang diproses bulan ini</b>
        </div>
      @endforelse
    </div>
  </div>
</div>

@endsection
