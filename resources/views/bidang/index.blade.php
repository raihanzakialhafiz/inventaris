@extends('layouts.app')
@section('title', 'Bidang/Departemen')
@section('page-title', 'Bidang / Departemen')
@section('page-crumb', 'Master Data')

@section('content')
    <div x-data="{ showModal: false, editData: {} }">

        <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
            <div>
                <h2>Bidang / Departemen</h2>
                <p>Unit kerja sebagai dasar pengelompokan permintaan dan kuota.</p>
            </div>
            <button class="btn btn-pri" @click="showModal=true; editData={}"><x-icon name="plus" width="15"
                    height="15" /> Tambah Bidang</button>
        </div>

        <div class="card">
            <div class="card-b" style="padding:0">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Bidang</th>
                            <th>Kepala Bidang</th>
                            <th class="num">Anggota</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bidang as $b)
                            <tr>
                                <td><span class="badge b-primary">{{ $b->code }}</span></td>
                                <td class="t-name">{{ $b->name }}</td>
                                <td>{{ $b->headUser->name ?? '—' }}</td>
                                <td class="num">{{ $b->users_count }}</td>
                                <td style="white-space:nowrap">
                                    <button class="btn btn-ghost btn-sm"
                                        @click="showModal=true; editData={{ json_encode(['id' => $b->id, 'code' => $b->code, 'name' => $b->name, 'head_user_id' => $b->head_user_id, 'description' => $b->description]) }}">
                                        Edit
                                    </button>
                                    {{-- Tombol selalu tampil; bidang yang masih punya pegawai atau
                     permintaan ditolak server dengan pesan jelas (BidangController). --}}
                                    <form method="POST" action="{{ route('bidang.destroy', $b) }}" style="display:inline"
                                        data-confirm="Hapus bidang {{ $b->name }}?{{ $b->users_count > 0 ? ' Bidang ini masih punya ' . $b->users_count . ' pegawai dan hanya bisa dihapus setelah kosong.' : ' Bisa dipulihkan dari Sampah.' }}"
                                        data-confirm-variant="danger" data-confirm-ok="Hapus">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm"
                                            style="margin-left:4px">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty">
                                        <div class="empty-ic"><x-icon name="building" /></div>
                                        <b>Belum ada bidang</b>
                                        <p>Tambah unit kerja untuk mengelompokkan permintaan dan kuota.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($bidang->total())
                    <div class="pg-bar">
                        <span class="pg-info">Menampilkan {{ $bidang->firstItem() }}–{{ $bidang->lastItem() }} dari
                            {{ $bidang->total() }} bidang</span>
                        {{ $bidang->links() }}
                    </div>
                @endif
            </div>
        </div>

        <template x-if="showModal">
            <div>
                <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
                <div class="modal" style="display:flex">
                    <div class="modal-head">
                        <h3 x-text="editData.id ? 'Edit Bidang' : 'Tambah Bidang'"></h3>
                        <button class="close-btn" @click="showModal=false"><x-icon name="x" width="16"
                                height="16" /></button>
                    </div>
                    <div class="modal-body">
                        <form :action="editData.id ? '/bidang/' + editData.id : '/bidang'" method="POST">
                            @csrf
                            <template x-if="editData.id"><input type="hidden" name="_method" value="PUT"></template>
                            <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px">
                                <div class="field" style="margin:0">
                                    <label>Kode Bidang <span style="color:var(--danger)">*</span></label>
                                    <input type="text" name="code" :value="editData.code || ''" required
                                        placeholder="KEU" style="text-transform:uppercase">
                                </div>
                                <div class="field" style="margin:0">
                                    <label>Nama Bidang <span style="color:var(--danger)">*</span></label>
                                    <input type="text" name="name" :value="editData.name || ''" required
                                        placeholder="Bidang Keuangan">
                                </div>
                            </div>
                            <div class="field" style="margin-top:14px">
                                <label>Kepala Bidang</label>
                                <x-searchable-select name="head_user_id" :options="$kabiList" x-model="editData.head_user_id"
                                    placeholder="— Pilih Kepala Bidang —" search-placeholder="Cari pengguna…" />
                            </div>
                            <div class="field">
                                <label>Keterangan</label>
                                <input type="text" name="description" :value="editData.description || ''"
                                    placeholder="Opsional">
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
