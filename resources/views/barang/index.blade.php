@extends('layouts.app')
@section('title', 'Data Barang')
@section('page-title', 'Data Barang')
@section('page-crumb', 'Inventaris')

@section('content')
@php
  $role    = auth()->user()->role;
  $isAdmin = $role === 'admin';                                  // tambah + hapus
  $canEdit = in_array($role, ['admin', 'kasubag_umum']);        // edit
@endphp

{{-- Saat validasi gagal, modal dibuka kembali dengan isian old() — tanpa ini
     halaman reload menutup modal dan seluruh ketikan pengguna hilang. --}}
<div x-data="{
       showModal: {{ $errors->any() ? 'true' : 'false' }},
       editData: {{ json_encode($errors->any() ? [
         'id'            => old('editing_id') ?: null,
         'code'          => old('code'),
         'name'          => old('name'),
         'category_id'   => old('category_id'),
         'unit'          => old('unit'),
         'stock'         => old('stock'),
         'minimum_stock' => old('minimum_stock'),
         'reorder_point' => old('reorder_point'),
         'location'      => old('location'),
         'description'   => old('description'),
       ] : new \stdClass) }}
     }">

  <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
    <div><h2>Master Barang</h2><p>Data barang ATK yang dikelola gudang.</p></div>
    @if($isAdmin)
      <button class="btn btn-pri" @click="showModal=true; editData={ code: {{ json_encode($nextCode) }} }"><x-icon name="plus" width="15" height="15" /> Tambah Barang</button>
    @endif
  </div>

  <form method="GET" class="filter-bar">
    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
    @if(request('dir'))<input type="hidden" name="dir" value="{{ request('dir') }}">@endif

    <input type="search" name="search" value="{{ request('search') }}"
           placeholder="Cari nama atau kode barang…"
           x-data @input.debounce.400ms="$el.form.submit()">

    <div style="min-width:180px">
      <x-searchable-select name="category" :options="$categories" :selected="request('category')"
                           placeholder="Semua Kategori" search-placeholder="Cari kategori…" :submit-on-change="true" />
    </div>

    <div style="min-width:160px">
      <x-searchable-select name="status" :selected="request('status')"
                           :options="['ok' => 'Stok Aman', 'low' => 'Stok Menipis', 'out' => 'Stok Habis']"
                           placeholder="Semua Status" search-placeholder="Cari status…" :submit-on-change="true" />
    </div>

    <span class="filter-spacer"></span>

    @if(request()->hasAny(['search','category','status']))
      <a href="{{ route('barang.index') }}" class="btn btn-ghost btn-sm">✕ Reset</a>
    @endif
  </form>

  <div class="card">
    <div class="card-b" style="padding:0">
      @if($items->count())
        <table>
          <thead><tr>
            <th class="num">No</th>
            <x-sort-th col="code"          label="Kode" />
            <x-sort-th col="name"          label="Nama Barang" />
            <th>Kategori</th>
            <th>Lokasi</th>
            <x-sort-th col="stock"         label="Stok" class="num" />
            <x-sort-th col="minimum_stock" label="Min Stok" class="num" />
            <th>Status</th>
            @if($canEdit)<th>Aksi</th>@endif
          </tr></thead>
          <tbody>
            @foreach($items as $item)
              @php
                $st = $item->stock <= 0
                  ? ['b-danger','Habis']
                  : ($item->stock <= $item->minimum_stock ? ['b-warn','Menipis'] : ['b-ok','Aman']);
              @endphp
              <tr>
                <td class="num t-sub">{{ $items->firstItem() + $loop->index }}</td>
                <td><span class="code">{{ $item->code }}</span></td>
                <td class="t-name">{{ $item->name }}</td>
                <td><span class="t-sub">{{ $item->category->name }}</span></td>
                <td class="t-sub">{{ $item->location ?: '—' }}</td>
                <td class="num"><b>{{ $item->stock }}</b> <span class="t-sub">{{ $item->unit }}</span></td>
                <td class="num t-sub">{{ $item->minimum_stock }}</td>
                <td><span class="badge {{ $st[0] }}">{{ $st[1] }}</span></td>
                @if($canEdit)
                  <td style="white-space:nowrap">
                    <button class="btn btn-ghost btn-sm"
                      @click="showModal=true; editData={{ json_encode([
                        'id' => $item->id, 'code' => $item->code, 'name' => $item->name,
                        'category_id' => $item->category_id, 'unit' => $item->unit,
                        'stock' => $item->stock, 'minimum_stock' => $item->minimum_stock,
                        'reorder_point' => $item->reorder_point, 'location' => $item->location,
                        'description' => $item->description,
                      ]) }}">Edit</button>
                    @if($isAdmin)
                      <form method="POST" action="{{ route('barang.destroy', $item) }}" style="display:inline"
                            data-confirm="Hapus barang {{ $item->name }}? Tindakan ini tidak dapat dibatalkan."
                            data-confirm-variant="danger" data-confirm-ok="Hapus">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" style="margin-left:4px">Hapus</button>
                      </form>
                    @endif
                  </td>
                @endif
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="pg-bar">
          <x-per-page :per-page="request('per_page', 20)" />
          <span class="pg-info">
            Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $items->total() }} barang
          </span>
          {{ $items->links() }}
        </div>
      @else
        <div class="empty">
          <div class="empty-ic">☷</div>
          <b>Tidak ada barang ditemukan</b>
          <p>Coba ubah kata kunci pencarian atau reset filter.</p>
        </div>
      @endif
    </div>
  </div>

  {{-- Modal Tambah/Edit (kasubag hanya bisa Edit; Tambah dipicu tombol khusus admin) --}}
  @if($canEdit)
    <template x-if="showModal">
      <div>
        <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
        <div class="modal" style="display:flex">
          <div class="modal-head">
            <h3 x-text="editData.id ? 'Edit Barang' : 'Tambah Barang'"></h3>
            <button class="close-btn" @click="showModal=false">✕</button>
          </div>
          <div class="modal-body">
            <form :action="editData.id ? '/barang/'+editData.id : '/barang'" method="POST">
              @csrf
              <template x-if="editData.id"><input type="hidden" name="_method" value="PUT"></template>
              {{-- Ikut terkirim agar modal bisa dibuka ulang dalam mode yang sama
                   (tambah/edit) saat validasi gagal. --}}
              <input type="hidden" name="editing_id" :value="editData.id||''">

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="field" style="margin:0">
                  <label>Kode Barang <span style="color:var(--danger)">*</span></label>
                  <input type="text" name="code" :value="editData.code||''" required placeholder="ATK-XXX">
                  <span class="help" x-show="!editData.id">Terisi otomatis — boleh diubah.</span>
                  @error('code')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field" style="margin:0">
                  <label>Nama Barang <span style="color:var(--danger)">*</span></label>
                  <input type="text" name="name" :value="editData.name||''" required>
                  @error('name')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field" style="margin:0">
                  <label>Kategori <span style="color:var(--danger)">*</span></label>
                  <x-searchable-select name="category_id" :options="$categories"
                                       x-model="editData.category_id"
                                       placeholder="— Pilih Kategori —" search-placeholder="Cari kategori…" required />
                  @error('category_id')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field" style="margin:0">
                  <label>Satuan <span style="color:var(--danger)">*</span></label>
                  <x-searchable-select name="unit" :options="$units" x-model="editData.unit"
                                       placeholder="— Pilih Satuan —" search-placeholder="Cari satuan…" required />
                  @error('unit')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field" style="margin:0" x-show="!editData.id">
                  <label>Stok Awal</label>
                  <input type="number" name="stock" :value="editData.stock||0" min="0">
                  @error('stock')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field" style="margin:0">
                  <label>Stok Minimum <span style="color:var(--danger)">*</span></label>
                  <input type="number" name="minimum_stock" :value="editData.minimum_stock ?? 5" min="0" required>
                  @error('minimum_stock')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field" style="margin:0">
                  <label>Reorder Point</label>
                  <input type="number" name="reorder_point" :value="editData.reorder_point||''" min="0" placeholder="Opsional">
                </div>
                <div class="field" style="margin:0">
                  <label>Lokasi Gudang</label>
                  <input type="text" name="location" :value="editData.location||''" placeholder="Rak A1">
                </div>
                <div class="field" style="margin:0;grid-column:1/-1">
                  <label>Deskripsi</label>
                  <textarea name="description" x-text="editData.description||''"></textarea>
                </div>
              </div>

              <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
                <button type="button" class="btn btn-ghost" @click="showModal=false">Batal</button>
                <button type="submit" class="btn btn-pri">Simpan Barang</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </template>
  @endif

</div>
@endsection
