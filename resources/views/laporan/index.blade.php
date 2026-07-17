@extends('layouts.app')
@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('page-crumb', 'Inventaris ATK')

@section('content')
@php
  $tabs = ['stok'=>'Stok Barang','masuk'=>'Barang Masuk','keluar'=>'Barang Keluar','permintaan'=>'Permintaan','bidang'=>'Per Bidang'];
@endphp

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
  <div class="tab-nav">
    @foreach($tabs as $val => $lbl)
      <a href="{{ route('laporan.index', ['type'=>$val, 'period'=>$period]) }}"
         class="tab-item {{ $type === $val ? 'active' : '' }}">{{ $lbl }}</a>
    @endforeach
  </div>

  <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="month" name="period" value="{{ $period }}" onchange="this.form.submit()" style="width:160px">
    @if(!in_array($type, ['stok','bidang']) && auth()->user()->role !== 'kepala_bidang')
      <select name="dept" class="inp" style="width:auto" onchange="this.form.submit()">
        <option value="">Semua Bidang</option>
        @foreach($departments as $dept)
          <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
        @endforeach
      </select>
    @endif
  </form>

  @php $exportParams = ['type'=>$type, 'period'=>$period, 'dept'=>$deptId]; @endphp
  <span class="filter-spacer"></span>
  <x-export-ttd :pdf="route('laporan.export.pdf', $exportParams)"
                :excel="route('laporan.export.excel', $exportParams)" />
</div>

{{-- ===== STOK ===== --}}
@if($type === 'stok')
  <div class="card">
    <div class="card-h">
      <h3>Laporan Stok Barang</h3>
      <span class="hint">{{ $items->count() }} jenis barang</span>
    </div>
    <div class="card-b" style="padding:0">
      <table>
        <thead><tr>
          <th>Kode</th><th>Nama Barang</th><th>Kategori</th>
          <th class="num">Stok</th><th class="num">Min</th><th>Status</th>
        </tr></thead>
        <tbody>
          @foreach($items as $item)
            @php $st = $item['status_label']; $b = match($st){ 'Habis'=>'b-danger','Menipis'=>'b-warn',default=>'b-ok' }; @endphp
            <tr>
              <td><span class="code">{{ $item['code'] }}</span></td>
              <td class="t-name">{{ $item['name'] }}</td>
              <td class="t-sub">{{ $item['category']['name'] ?? '—' }}</td>
              <td class="num"><b>{{ $item['stock'] }}</b> <span class="t-sub">{{ $item['unit'] }}</span></td>
              <td class="num">{{ $item['minimum_stock'] }}</td>
              <td><span class="badge {{ $b }}">{{ $st }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

{{-- ===== BARANG MASUK ===== --}}
@if($type === 'masuk')
  <div class="card">
    <div class="card-h"><h3>Laporan Barang Masuk</h3><span class="hint">{{ $period }}</span></div>
    <div class="card-b" style="padding:0">
      @if($stockIns->count())
        <table>
          <thead><tr>
            <th>No. Transaksi</th><th>Tanggal</th><th>Supplier</th>
            <th class="num">Jenis</th><th class="num">Total Unit</th>
          </tr></thead>
          <tbody>
            @foreach($stockIns as $si)
              <tr>
                <td><span class="code">{{ $si->transaction_no }}</span></td>
                <td>{{ $si->date->isoFormat('D MMM Y') }}</td>
                <td>{{ $si->supplier->name ?? '—' }}</td>
                <td class="num">{{ $si->details->count() }}</td>
                <td class="num"><b>{{ $si->totalQuantity() }}</b></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <div class="empty"><div class="ic">↓</div><b>Tidak ada data</b></div>
      @endif
    </div>
  </div>
@endif

{{-- ===== BARANG KELUAR ===== --}}
@if($type === 'keluar')
  <div class="card">
    <div class="card-h"><h3>Laporan Distribusi / Barang Keluar</h3><span class="hint">{{ $period }}</span></div>
    <div class="card-b" style="padding:0">
      @if($stockOuts->count())
        <table>
          <thead><tr>
            <th>No. Transaksi</th><th>Barang</th><th>Bidang</th>
            <th class="num">Jml</th><th>Tanggal</th><th>Tipe</th>
          </tr></thead>
          <tbody>
            @foreach($stockOuts as $so)
              <tr>
                <td><span class="code">{{ $so->transaction_no }}</span></td>
                <td>{{ $so->item->name }}</td>
                <td>{{ $so->department?->code ?? '—' }}</td>
                <td class="num">{{ $so->quantity }} {{ $so->item->unit }}</td>
                <td>{{ $so->date->isoFormat('D MMM Y') }}</td>
                <td><span class="badge b-neutral">{{ $so->type }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <div class="empty"><div class="ic">↑</div><b>Tidak ada data</b></div>
      @endif
    </div>
  </div>
@endif

{{-- ===== PERMINTAAN ===== --}}
@if($type === 'permintaan')
  <div class="card">
    <div class="card-h"><h3>Laporan Permintaan Barang</h3><span class="hint">{{ $period }}</span></div>
    <div class="card-b" style="padding:0">
      @if($requests->count())
        <table>
          <thead><tr>
            <th>No. Permintaan</th><th>Bidang</th><th>Pengaju</th>
            <th>Tanggal</th><th>Status</th><th>Flag</th>
          </tr></thead>
          <tbody>
            @foreach($requests as $req)
              <tr>
                <td><a href="{{ route('permintaan.show', $req) }}" class="code">{{ $req->request_no }}</a></td>
                <td><span class="badge b-primary">{{ $req->department->code }}</span></td>
                <td class="t-sub">{{ $req->requester->name }}</td>
                <td>{{ $req->request_date->isoFormat('D MMM Y') }}</td>
                <td><x-status-badge :status="$req->status" /></td>
                <td>@if($req->is_flagged)<span class="badge b-danger">⚠</span>@else<span class="t-sub">—</span>@endif</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <div class="empty"><div class="ic">✉</div><b>Tidak ada data</b></div>
      @endif
    </div>
  </div>
@endif

{{-- ===== PER BIDANG ===== --}}
@if($type === 'bidang')
  <div class="card">
    <div class="card-h"><h3>Laporan Permintaan per Bidang</h3><span class="hint">{{ $period }}</span></div>
    <div class="card-b" style="padding:0">
      <table>
        <thead><tr>
          <th>Bidang</th><th class="num">Total</th><th class="num">Disetujui</th>
          <th class="num">Ditolak</th><th class="num">Selesai</th>
          <th class="num">Over-Request</th><th class="num">Unit Distribusi</th>
        </tr></thead>
        <tbody>
          @foreach($deptStats as $ds)
            <tr>
              <td>
                <span class="badge b-primary" style="margin-right:6px">{{ $ds['dept']->code }}</span>
                <span class="t-name">{{ $ds['dept']->name }}</span>
              </td>
              <td class="num">{{ $ds['total'] }}</td>
              <td class="num">{{ $ds['disetujui'] }}</td>
              <td class="num">{!! $ds['ditolak'] > 0 ? '<span style="color:var(--danger);font-weight:700">'.$ds['ditolak'].'</span>' : '0' !!}</td>
              <td class="num">{{ $ds['selesai'] }}</td>
              <td class="num">{!! $ds['flagged'] > 0 ? '<span style="color:var(--warn);font-weight:700">'.$ds['flagged'].'</span>' : '0' !!}</td>
              <td class="num"><b>{{ $ds['totalQty'] }}</b></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Bar chart distribusi per bidang berdasarkan jumlah unit --}}
  @php $maxQty = $deptStats->max('totalQty') ?: 1; @endphp
  <div class="card" style="margin-top:16px">
    <div class="card-h"><h3>Perbandingan Unit Distribusi per Bidang</h3><span class="hint">{{ $period }}</span></div>
    <div class="card-b">
      @foreach($deptStats->sortByDesc('totalQty') as $ds)
        <div class="bar-row">
          <span style="font-size:13px;font-weight:600;min-width:60px">{{ $ds['dept']->code }}</span>
          <div class="bar-track"><i style="width:{{ $maxQty > 0 ? round($ds['totalQty']/$maxQty*100) : 0 }}%"></i></div>
          <span class="bar-val">{{ $ds['totalQty'] }} unit</span>
        </div>
      @endforeach
    </div>
  </div>
@endif
@endsection
