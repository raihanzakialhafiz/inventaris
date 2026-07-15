@extends('layouts.app')
@section('title', 'Supplier')
@section('page-title', 'Supplier')
@section('page-crumb', 'Master Data')

@section('content')
<div x-data="supplierData()">

  <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
    <div><h2>Supplier</h2><p>Daftar pemasok barang ATK.</p></div>
    <button class="btn btn-pri" @click="openModal({})"><x-icon name="plus" width="15" height="15" /> Tambah Supplier</button>
  </div>

  <form method="GET" x-ref="filterForm" class="filter-bar">
    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
    @if(request('dir'))<input type="hidden" name="dir" value="{{ request('dir') }}">@endif

    <input type="search" name="search" value="{{ request('search') }}"
           placeholder="Cari nama, telepon, atau email…"
           @input.debounce.400ms="$refs.filterForm.submit()">

    <span class="filter-spacer"></span>

    @if(request()->filled('search'))
      <a href="{{ route('supplier.index') }}" class="btn btn-ghost btn-sm">✕ Reset</a>
    @endif

  </form>

  <div class="card">
    <div class="card-b" style="padding:0">
      @if($suppliers->count())
        <table>
          <thead><tr>
            <th style="width:40px">#</th>
            <x-sort-th col="name"  label="Nama Supplier" />
            <x-sort-th col="phone" label="Telepon" />
            <x-sort-th col="email" label="Email" />
            <th>Alamat</th>
            <th class="num">Transaksi</th>
            <th>Aksi</th>
          </tr></thead>
          <tbody>
            @foreach($suppliers as $sup)
              <tr>
                <td class="t-sub">{{ ($suppliers->currentPage()-1)*$suppliers->perPage()+$loop->iteration }}</td>
                <td class="t-name">{{ $sup->name }}</td>
                <td>{{ $sup->phone ?: '—' }}</td>
                <td class="t-sub">{{ $sup->email ?: '—' }}</td>
                <td class="t-sub">{{ $sup->address ?: '—' }}</td>
                <td class="num"><b>{{ $sup->stock_ins_count }}</b></td>
                <td style="white-space:nowrap">
                  <button class="btn btn-ghost btn-sm"
                    @click="openModal({{ json_encode(['id'=>$sup->id,'name'=>$sup->name,'address'=>$sup->address,'phone'=>$sup->phone,'email'=>$sup->email]) }})">
                    Edit
                  </button>
                  <form method="POST" action="{{ route('supplier.destroy', $sup) }}" style="display:inline"
                        data-confirm="Hapus supplier {{ $sup->name }}?"
                        data-confirm-variant="danger" data-confirm-ok="Hapus">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="margin-left:4px">Hapus</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="pg-bar">
          <x-per-page :per-page="request('per_page', 15)" />
          <span class="pg-info">
            Menampilkan {{ $suppliers->firstItem() }}–{{ $suppliers->lastItem() }} dari {{ $suppliers->total() }} supplier
          </span>
          {{ $suppliers->links() }}
        </div>
      @else
        <div class="empty">
          <div class="empty-ic">⊡</div>
          <b>Tidak ada supplier ditemukan</b>
          <p>Coba ubah kata kunci pencarian atau tambah supplier baru.</p>
        </div>
      @endif
    </div>
  </div>

  <template x-if="showModal">
    <div>
      <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
      <div class="modal" style="display:flex">
        <div class="modal-head">
          <h3 x-text="editData.id ? 'Edit Supplier' : 'Tambah Supplier'"></h3>
          <button class="close-btn" @click="showModal=false">✕</button>
        </div>
        <div class="modal-body">
          <form :action="editData.id ? '/supplier/'+editData.id : '/supplier'" method="POST">
            @csrf
            <template x-if="editData.id"><input type="hidden" name="_method" value="PUT"></template>
            <div class="field">
              <label>Nama Supplier <span style="color:var(--danger)">*</span></label>
              <input type="text" name="name" :value="editData.name||''" required placeholder="PT / CV / UD ...">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
              <div class="field" style="margin:0">
                <label>Telepon</label>
                <input type="text" name="phone" :value="editData.phone||''" placeholder="021-...">
              </div>
              <div class="field" style="margin:0">
                <label>Email</label>
                <input type="email" name="email" :value="editData.email||''" placeholder="info@...">
              </div>
            </div>
            <div class="field" style="margin-top:14px">
              <label>Alamat</label>
              <textarea name="address" placeholder="Alamat lengkap supplier" x-model="editAddress"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
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

@push('scripts')
<script>
function supplierData() {
  return {
    showModal: false,
    editData: {},
    editAddress: '',
    openModal(data) {
      this.editData    = data;
      this.editAddress = data.address || '';
      this.showModal   = true;
    },
  };
}
</script>
@endpush
