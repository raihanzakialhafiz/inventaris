@extends('layouts.app')
@section('title', 'Detail Barang Masuk')
@section('page-title', $stockIn->transaction_no)
@section('page-crumb', 'Barang Masuk')

@section('content')
<div style="max-width:700px">
  <div class="card">
    <div class="card-h">
      <h3>Detail Barang Masuk</h3>
      <a href="{{ route('barang-masuk.index') }}" class="btn btn-ghost btn-sm" style="margin-left:auto">← Kembali</a>
    </div>
    <div class="card-b">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">
        <div><span class="t-sub">No. Transaksi</span><div class="t-name code">{{ $stockIn->transaction_no }}</div></div>
        <div><span class="t-sub">Tanggal</span><div>{{ $stockIn->date->isoFormat('D MMM Y') }}</div></div>
        <div><span class="t-sub">Supplier</span><div>{{ $stockIn->supplier->name ?? '—' }}</div></div>
        <div><span class="t-sub">Dicatat oleh</span><div>{{ $stockIn->createdBy->name }}</div></div>
        @if($stockIn->note)
          <div style="grid-column:1/-1"><span class="t-sub">Catatan</span><div>{{ $stockIn->note }}</div></div>
        @endif
      </div>

      <table>
        <thead><tr>
          <th>Barang</th><th>Satuan</th><th class="num">Jumlah</th>
        </tr></thead>
        <tbody>
          @foreach($stockIn->details as $d)
            <tr>
              <td><span class="code">{{ $d->item->code }}</span> {{ $d->item->name }}</td>
              <td>{{ $d->item->unit }}</td>
              <td class="num"><b>{{ $d->quantity }}</b></td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <th colspan="2" style="text-align:right;padding:11px 14px">Total Unit Diterima</th>
            <th class="num" style="padding:11px 14px">{{ $stockIn->totalQuantity() }}</th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection
