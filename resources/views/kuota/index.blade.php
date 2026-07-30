@extends('layouts.app')
@section('title', 'Kuota Bidang')
@section('page-title', 'Kuota Permintaan per Bidang')
@section('page-crumb', 'Master Data')

@section('content')
<div x-data="{ showModal: false, editData: {} }">

  <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
    <div><h2>Kuota Permintaan</h2><p>Atur batas maksimum permintaan per bidang untuk mencegah over-request.</p></div>
    <button class="btn btn-pri" @click="showModal=true; editData={}"><x-icon name="plus" width="15" height="15" /> Tambah Kuota</button>
  </div>

  <div class="card">
    <div class="card-b" style="padding:0">
      @if($kuota->count())
        <table>
          <thead><tr>
            <th>Bidang</th><th>Barang / Kategori</th><th>Periode</th>
            <th class="num">Kuota</th><th>Bila Terlampaui</th><th>Status</th><th>Aksi</th>
          </tr></thead>
          <tbody>
            @foreach($kuota as $k)
              <tr>
                <td>
                  @if($k->department)<span class="badge b-primary">{{ $k->department->code }}</span>
                  @else <span class="badge b-neutral">Semua</span>@endif
                </td>
                <td class="t-sub">{{ $k->item?->name ?? ($k->category?->name ?? 'Semua barang') }}</td>
                <td>{{ ucfirst($k->period_type) }}</td>
                <td class="num">{{ $k->quota_quantity }}</td>
                <td><span class="badge {{ $k->policy === 'block' ? 'b-danger' : 'b-warn' }}">{{ $k->policy === 'block' ? 'Tolak' : 'Wajib justifikasi' }}</span></td>
                <td><span class="badge {{ $k->is_active ? 'b-ok' : 'b-neutral' }}">{{ $k->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td style="white-space:nowrap">
                  <button class="btn btn-ghost btn-sm"
                    @click="showModal=true; editData={{ json_encode(['id'=>$k->id,'quota_quantity'=>$k->quota_quantity,'policy'=>$k->policy,'is_active'=>$k->is_active]) }}">
                    Edit
                  </button>
                  <form method="POST" action="{{ route('kuota.destroy', $k) }}" style="display:inline"
                        data-confirm="Hapus kuota ini?"
                        data-confirm-variant="danger" data-confirm-ok="Hapus">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="margin-left:4px">Hapus</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        @if($kuota->total())
          <div class="pg-bar">
            <span class="pg-info">Menampilkan {{ $kuota->firstItem() }}–{{ $kuota->lastItem() }} dari {{ $kuota->total() }} kuota</span>
            {{ $kuota->links() }}
          </div>
        @endif
      @else
        <div class="empty">
          <div class="empty-ic"><x-icon name="target" /></div>
          <b>Belum ada kuota yang dikonfigurasi</b>
          <p>Tambah kuota untuk mengatur batas permintaan per bidang.</p>
        </div>
      @endif
    </div>
  </div>

  <template x-if="showModal">
    <div>
      <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
      <div class="modal" style="display:flex">
        <div class="modal-head">
          <h3 x-text="editData.id ? 'Edit Kuota' : 'Tambah Kuota'"></h3>
          <button class="close-btn" @click="showModal=false"><x-icon name="x" width="16" height="16" /></button>
        </div>
        <div class="modal-body">
          <form :action="editData.id ? '/kuota/'+editData.id : '/kuota'" method="POST">
            @csrf
            <template x-if="editData.id"><input type="hidden" name="_method" value="PUT"></template>

            {{-- Periode, berlaku-mulai, threshold & cooldown tidak lagi ditanyakan:
                 dikunci di StoreKuotaRequest agar modal cukup 4 isian. --}}
            <template x-if="!editData.id">
              <div x-data="{ scope: 'all' }">
                <div class="field">
                  <label>Bidang</label>
                  <x-searchable-select name="department_id" :options="$departments"
                                       placeholder="Semua Bidang" search-placeholder="Cari bidang…" />
                </div>
                <div class="field">
                  <label>Berlaku untuk</label>
                  <select class="inp" x-model="scope">
                    <option value="all">Semua barang</option>
                    <option value="category">Kategori tertentu</option>
                    <option value="item">Barang tertentu</option>
                  </select>
                </div>
                {{-- x-if, bukan x-show: pilihan yang tidak dipakai keluar dari DOM
                     sehingga tak ikut terkirim — item_id & category_id mustahil
                     terisi bersamaan (kombinasi itu membuat kategori diabaikan
                     diam-diam oleh DepartmentQuotaService::resolve()). --}}
                <template x-if="scope === 'category'">
                  <div class="field">
                    <label>Kategori</label>
                    <x-searchable-select name="category_id" :options="$categories" required
                                         placeholder="— Pilih kategori —" search-placeholder="Cari kategori…" />
                  </div>
                </template>
                <template x-if="scope === 'item'">
                  <div class="field">
                    <label>Barang</label>
                    <x-searchable-select name="item_id" :options="$items" required
                                         placeholder="— Pilih barang —" search-placeholder="Cari barang…" />
                  </div>
                </template>
              </div>
            </template>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px">
              <div class="field" style="margin:0">
                <label>Kuota per Bulan</label>
                <input type="number" name="quota_quantity" :value="editData.quota_quantity||10" min="1" required>
              </div>
              <div class="field" style="margin:0">
                <label>Bila Terlampaui</label>
                <select name="policy" class="inp">
                  <option value="warn" :selected="editData.policy==='warn'">Izinkan, wajib isi justifikasi</option>
                  <option value="block" :selected="editData.policy==='block'">Tolak permintaan</option>
                </select>
              </div>
            </div>
            <template x-if="editData.id">
              <div class="field" style="margin-top:14px">
                <label>Status</label>
                <select name="is_active" class="inp">
                  <option value="1" :selected="editData.is_active">Aktif</option>
                  <option value="0" :selected="!editData.is_active">Nonaktif</option>
                </select>
              </div>
            </template>

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
