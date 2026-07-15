@extends('layouts.app')
@section('title', 'Satuan Barang')
@section('page-title', 'Satuan Barang')
@section('page-crumb', 'Master Data')

@section('content')
<div x-data="{ showModal: false, editData: {} }">

  <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
    <div><h2>Satuan Barang</h2><p>Daftar satuan (rim, lusin, buah, …) untuk pilihan pada form barang.</p></div>
    <button class="btn btn-pri" @click="showModal=true; editData={}"><x-icon name="plus" width="15" height="15" /> Tambah Satuan</button>
  </div>

  <div class="card">
    <div class="card-b" style="padding:0">
      @if($satuan->count())
        <table>
          <thead><tr>
            <th class="num">No</th><th>Nama</th><th>Keterangan</th><th class="num">Dipakai</th><th>Aksi</th>
          </tr></thead>
          <tbody>
            @foreach($satuan as $s)
              <tr>
                <td class="num t-sub">{{ $loop->iteration }}</td>
                <td><span class="code">{{ $s->name }}</span></td>
                <td class="t-sub">{{ $s->description ?: '—' }}</td>
                <td class="num">
                  @if($s->items_using > 0)<span class="badge b-primary">{{ $s->items_using }} barang</span>
                  @else <span class="t-sub">0</span>@endif
                </td>
                <td style="white-space:nowrap">
                  <button class="btn btn-ghost btn-sm"
                    @click="showModal=true; editData={{ json_encode(['id'=>$s->id,'name'=>$s->name,'description'=>$s->description]) }}">
                    Edit
                  </button>
                  @if($s->items_using === 0)
                    <form method="POST" action="{{ route('satuan.destroy', $s) }}" style="display:inline"
                          data-confirm="Hapus satuan {{ $s->name }}?"
                          data-confirm-variant="danger" data-confirm-ok="Hapus">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-danger btn-sm" style="margin-left:4px">Hapus</button>
                    </form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        @if($satuan->hasPages())
          <div class="pg-bar">
            <span class="pg-info">Menampilkan {{ $satuan->firstItem() }}–{{ $satuan->lastItem() }} dari {{ $satuan->total() }} satuan</span>
            {{ $satuan->links() }}
          </div>
        @endif
      @else
        <div class="empty">
          <div class="empty-ic">⊚</div>
          <b>Belum ada satuan</b>
          <p>Tambah satuan agar bisa dipilih saat menambah barang.</p>
        </div>
      @endif
    </div>
  </div>

  <template x-if="showModal">
    <div>
      <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
      <div class="modal" style="display:flex;max-width:460px">
        <div class="modal-head">
          <h3 x-text="editData.id ? 'Edit Satuan' : 'Tambah Satuan'"></h3>
          <button class="close-btn" @click="showModal=false">✕</button>
        </div>
        <div class="modal-body">
          <form :action="editData.id ? '/satuan/'+editData.id : '/satuan'" method="POST">
            @csrf
            <template x-if="editData.id"><input type="hidden" name="_method" value="PUT"></template>
            <div class="field">
              <label>Nama Satuan <span style="color:var(--danger)">*</span></label>
              <input type="text" name="name" :value="editData.name||''" required maxlength="30" placeholder="mis. rim, lusin, buah">
            </div>
            <div class="field" style="margin:0">
              <label>Keterangan</label>
              <input type="text" name="description" :value="editData.description||''" maxlength="100" placeholder="Opsional">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
              <button type="button" class="btn btn-ghost" @click="showModal=false">Batal</button>
              <button type="submit" class="btn btn-pri">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

</div>
@endsection
