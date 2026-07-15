@extends('layouts.app')
@section('title', 'Kategori Barang')
@section('page-title', 'Kategori Barang')
@section('page-crumb', 'Master Data')

@section('content')
<div x-data="katData()">

  <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
    <div><h2>Kategori Barang</h2><p>Kelola kategori untuk pengelompokan barang ATK.</p></div>
    <button class="btn btn-pri" @click="openModal(null,'','')">
      <x-icon name="plus" width="15" height="15" /> Tambah Kategori
    </button>
  </div>

  <div class="card">
    <div class="card-b" style="padding:0">
      <table>
        <thead><tr>
          <th>#</th><th>Nama Kategori</th><th>Deskripsi</th><th class="num">Jumlah Barang</th><th>Aksi</th>
        </tr></thead>
        <tbody>
          @forelse($kategori as $kat)
            <tr>
              <td class="t-sub">{{ $loop->iteration }}</td>
              <td class="t-name">{{ $kat->name }}</td>
              <td class="t-sub">{{ $kat->description ?: '—' }}</td>
              <td class="num">{{ $kat->items_count }}</td>
              <td style="white-space:nowrap">
                <button class="btn btn-ghost btn-sm"
                  @click="openModal({{ $kat->id }}, '{{ addslashes($kat->name) }}', '{{ addslashes($kat->description) }}')">
                  Edit
                </button>
                @if($kat->items_count === 0)
                  <form method="POST" action="{{ route('kategori.destroy', $kat) }}" style="display:inline"
                        data-confirm="Hapus kategori {{ $kat->name }}?"
                        data-confirm-variant="danger" data-confirm-ok="Hapus">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="margin-left:4px">Hapus</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5">
              <div class="empty">
                <div class="empty-ic">⊟</div>
                <b>Belum ada kategori</b>
                <p>Tambah kategori untuk mengelompokkan barang ATK.</p>
              </div>
            </td></tr>
          @endforelse
        </tbody>
      </table>
      @if($kategori->hasPages())
        <div class="pg-bar">
          <span class="pg-info">Menampilkan {{ $kategori->firstItem() }}–{{ $kategori->lastItem() }} dari {{ $kategori->total() }} kategori</span>
          {{ $kategori->links() }}
        </div>
      @endif
    </div>
  </div>

  {{-- Modal Tambah / Edit --}}
  <template x-if="showModal">
    <div>
      <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
      <div class="modal" style="display:flex">
        <div class="modal-head">
          <h3 x-text="editId ? 'Edit Kategori' : 'Tambah Kategori'"></h3>
          <button class="close-btn" @click="showModal=false">✕</button>
        </div>
        <div class="modal-body">
          <form :action="editId ? '/kategori/'+editId : '/kategori'" method="POST">
            @csrf
            <template x-if="editId"><input type="hidden" name="_method" value="PUT"></template>
            <div class="field">
              <label>Nama Kategori <span style="color:var(--danger)">*</span></label>
              <input type="text" name="name" x-model="editName" required placeholder="Cth: Kertas, Alat Tulis">
            </div>
            <div class="field">
              <label>Deskripsi</label>
              <textarea name="description" x-model="editDesc" placeholder="Keterangan singkat (opsional)"></textarea>
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
function katData() {
  return {
    showModal: false, editId: null, editName: '', editDesc: '',
    openModal(id, name, desc) {
      this.editId = id; this.editName = name; this.editDesc = desc;
      this.showModal = true;
    }
  };
}
</script>
@endpush
