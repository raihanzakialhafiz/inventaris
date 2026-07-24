@extends('layouts.app')
@section('title', 'Detail Opname')
@section('page-title', $opname->opname_no)
@section('page-crumb', 'Stock Opname')

@section('content')
<div style="max-width:820px">
  <div class="card">
    <div class="card-h">
      <h3>Detail Stock Opname</h3>
      <div style="margin-left:auto;display:flex;gap:8px">
        <x-export-ttd :pdf="route('stock-opname.export', $opname)" pdf-label="Cetak PDF" />
        <a href="{{ route('stock-opname.index') }}" class="btn btn-ghost btn-sm"><x-icon name="arrow-left" width="13" height="13" style="vertical-align:-2px" /> Kembali</a>
      </div>
    </div>
    <div class="card-b">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;margin-bottom:16px">
        <div><span class="t-sub">No. Opname</span><div class="t-name code">{{ $opname->opname_no }}</div></div>
        <div><span class="t-sub">Tanggal</span><div>{{ $opname->date->isoFormat('D MMM Y') }}</div></div>
        <div><span class="t-sub">Petugas</span><div>{{ $opname->createdBy->name ?? '—' }}</div></div>
        <div><span class="t-sub">Disesuaikan</span><div>{{ $opname->adjusted_count }} dari {{ $opname->items_count }} barang</div></div>
        @if($opname->note)
          <div style="grid-column:1/-1"><span class="t-sub">Catatan</span><div>{{ $opname->note }}</div></div>
        @endif
      </div>

      <table>
        <thead><tr>
          <th>Kode</th><th>Barang</th>
          <th class="num">Stok Sistem</th><th class="num">Fisik</th><th class="num">Selisih</th>
        </tr></thead>
        <tbody>
          @foreach($opname->details as $d)
            <tr>
              <td><span class="code">{{ $d->item->code ?? '—' }}</span></td>
              <td class="t-name">{{ $d->item->name ?? '—' }}</td>
              <td class="num t-sub">{{ $d->system_stock }}</td>
              <td class="num"><b>{{ $d->physical_stock }}</b></td>
              <td class="num">
                @if($d->difference > 0)
                  <span class="badge b-ok">+{{ $d->difference }}</span>
                @elseif($d->difference < 0)
                  <span class="badge b-danger">{{ $d->difference }}</span>
                @else
                  <span class="t-sub">0</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
