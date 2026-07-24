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
            <th class="num">Kuota</th><th class="num">Threshold</th><th class="num">Cooldown</th>
            <th>Kebijakan</th><th>Status</th><th>Aksi</th>
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
                <td class="num">{{ $k->threshold_percent }}%</td>
                <td class="num">{{ $k->cooldown_days }} hari</td>
                <td><span class="badge {{ $k->policy === 'block' ? 'b-danger' : 'b-warn' }}">{{ ucfirst($k->policy) }}</span></td>
                <td><span class="badge {{ $k->is_active ? 'b-ok' : 'b-neutral' }}">{{ $k->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td style="white-space:nowrap">
                  <button class="btn btn-ghost btn-sm"
                    @click="showModal=true; editData={{ json_encode(['id'=>$k->id,'quota_quantity'=>$k->quota_quantity,'threshold_percent'=>$k->threshold_percent,'cooldown_days'=>$k->cooldown_days,'policy'=>$k->policy,'is_active'=>$k->is_active]) }}">
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

            <template x-if="!editData.id">
              <div>
                <div class="field">
                  <label>Bidang</label>
                  <x-searchable-select name="department_id" :options="$departments" x-model="editData.department_id"
                                       placeholder="Semua Bidang" search-placeholder="Cari bidang…" />
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                  <div class="field" style="margin:0">
                    <label>Barang (opsional)</label>
                    <x-searchable-select name="item_id" :options="$items" x-model="editData.item_id"
                                         placeholder="Semua barang" search-placeholder="Cari barang…" />
                  </div>
                  <div class="field" style="margin:0">
                    <label>Kategori (opsional)</label>
                    <x-searchable-select name="category_id" :options="$categories" x-model="editData.category_id"
                                         placeholder="Semua kategori" search-placeholder="Cari kategori…" />
                  </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px">
                  <div class="field" style="margin:0">
                    <label>Periode</label>
                    <select name="period_type" class="inp">
                      <option value="monthly">Bulanan</option>
                      <option value="quarterly">Triwulan</option>
                      <option value="yearly">Tahunan</option>
                    </select>
                  </div>
                  <div class="field" style="margin:0">
                    <label>Berlaku Mulai</label>
                    <input type="date" name="effective_from" value="{{ today()->format('Y-m-d') }}" required>
                  </div>
                </div>
              </div>
            </template>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px">
              <div class="field" style="margin:0">
                <label>Kuota Maksimum</label>
                <input type="number" name="quota_quantity" :value="editData.quota_quantity||10" min="1" required>
              </div>
              <div class="field" style="margin:0">
                <label>Threshold Anomali (%)</label>
                <input type="number" name="threshold_percent" :value="editData.threshold_percent||150" min="100" max="500">
              </div>
              <div class="field" style="margin:0">
                <label>Cooldown (hari)</label>
                <input type="number" name="cooldown_days" :value="editData.cooldown_days||14" min="0">
              </div>
              <div class="field" style="margin:0">
                <label>Kebijakan Pelanggaran</label>
                <select name="policy" class="inp">
                  <option value="warn" :selected="editData.policy==='warn'">Peringatan (Warn)</option>
                  <option value="block" :selected="editData.policy==='block'">Blokir (Block)</option>
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
