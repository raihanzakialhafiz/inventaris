@extends('layouts.app')
@section('title', 'Stock Opname')
@section('page-title', 'Stock Opname')
@section('page-crumb', 'Inventaris')

@section('content')
<div x-data="{ showModal: false, q: '', match(t){ return !this.q || t.includes(this.q.toLowerCase()); } }">

  <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
    <div><h2>Stock Opname</h2><p>Cocokkan stok sistem dengan hitungan fisik, lalu sesuaikan otomatis.</p></div>
    <button class="btn btn-pri" @click="showModal=true"><x-icon name="plus" width="15" height="15" /> Mulai Opname</button>
  </div>

  <div class="card">
    <div class="card-b" style="padding:0">
      @if($opnames->count())
        <table>
          <thead><tr>
            <th class="num">No</th><th>No. Opname</th><th>Tanggal</th>
            <th class="num">Barang Diperiksa</th><th class="num">Disesuaikan</th><th>Petugas</th><th>Aksi</th>
          </tr></thead>
          <tbody>
            @foreach($opnames as $o)
              <tr>
                <td class="num t-sub">{{ $opnames->firstItem() + $loop->index }}</td>
                <td><span class="code">{{ $o->opname_no }}</span></td>
                <td>{{ $o->date->isoFormat('D MMM Y') }}</td>
                <td class="num">{{ $o->items_count }}</td>
                <td class="num">
                  @if($o->adjusted_count > 0)
                    <span class="badge b-warn">{{ $o->adjusted_count }}</span>
                  @else
                    <span class="t-sub">0</span>
                  @endif
                </td>
                <td class="t-sub">{{ $o->createdBy->name ?? '—' }}</td>
                <td>
                  <a href="{{ route('stock-opname.show', $o) }}" class="btn btn-ghost btn-sm">Detail <x-icon name="arrow-right" width="13" height="13" style="vertical-align:-2px" /></a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="pg-bar">
          <span class="pg-info">Menampilkan {{ $opnames->firstItem() }}–{{ $opnames->lastItem() }} dari {{ $opnames->total() }} sesi</span>
          {{ $opnames->links() }}
        </div>
      @else
        <div class="empty">
          <div class="empty-ic"><x-icon name="clipboard" /></div>
          <b>Belum ada stock opname</b>
          <p>Mulai opname untuk mencocokkan stok fisik dengan sistem.</p>
        </div>
      @endif
    </div>
  </div>

  {{-- Modal Mulai Opname --}}
  <template x-if="showModal">
    <div>
      <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
      <div class="modal" style="display:flex;max-width:780px">
        <div class="modal-head">
          <h3>Mulai Stock Opname</h3>
          <button type="button" class="close-btn" @click="showModal=false"><x-icon name="x" width="16" height="16" /></button>
        </div>
        <div class="modal-body">
          <p class="t-sub" style="margin-bottom:12px">Isi jumlah <b>fisik</b> hasil hitungan. Stok sistem otomatis disesuaikan bila berbeda.</p>
          <form method="POST" action="{{ route('stock-opname.store') }}"
                data-confirm="Simpan stock opname? Stok sistem akan disesuaikan dengan hitungan fisik." data-confirm-ok="Simpan &amp; Sesuaikan">
            @csrf
            <div class="field" style="margin:0 0 12px">
              <label>Catatan</label>
              <input type="text" name="note" placeholder="Opsional — mis. opname akhir bulan">
            </div>
            <input type="search" class="inp" placeholder="Cari barang untuk difilter…" x-model="q" style="margin-bottom:10px">

            <div class="scroll-box" style="max-height:46vh;overflow-y:auto;border:1px solid var(--line);border-radius:10px">
              <table>
                <thead><tr>
                  <th>Kode</th><th>Barang</th><th class="num">Stok Sistem</th><th class="num">Fisik</th>
                </tr></thead>
                <tbody>
                  @foreach($items as $item)
                    <tr x-show="match(@js(strtolower($item->code.' '.$item->name)))">
                      <td><span class="code">{{ $item->code }}</span></td>
                      <td class="t-name">{{ $item->name }} <span class="t-sub">· {{ $item->unit }}</span></td>
                      <td class="num t-sub">{{ $item->stock }}</td>
                      <td class="num">
                        <input type="number" name="counts[{{ $item->id }}]" value="{{ $item->stock }}"
                               min="0" max="1000000" class="qty-inp" style="width:90px">
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
              <button type="button" class="btn btn-ghost" @click="showModal=false">Batal</button>
              <button type="submit" class="btn btn-pri">Simpan Opname</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

</div>
@endsection
