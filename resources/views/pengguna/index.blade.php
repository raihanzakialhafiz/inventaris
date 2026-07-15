@extends('layouts.app')
@section('title', 'Pengguna')
@section('page-title', 'Manajemen Pengguna')
@section('page-crumb', 'Master Data')

@section('content')
@php
  $roles = [
    'admin'          => 'Administrator',
    'kasubag_umum'   => 'Kasubag Umum',
    'petugas_gudang' => 'Petugas Gudang',
    'kepala_bidang'  => 'Kepala Bidang',
    'pimpinan'       => 'Pimpinan',
  ];
@endphp
<div x-data="{ showModal: false, editData: {} }">

  <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
    <div><h2>Manajemen Pengguna</h2><p>Kelola akun dan hak akses pengguna sistem.</p></div>
    <button class="btn btn-pri" @click="showModal=true; editData={ role:'', department_id:'', is_active:'1' }"><x-icon name="plus" width="15" height="15" /> Tambah Pengguna</button>
  </div>

  <form method="GET" x-data x-ref="filterForm" class="filter-bar">
    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
    @if(request('dir'))<input type="hidden" name="dir" value="{{ request('dir') }}">@endif

    <input type="search" name="search" value="{{ request('search') }}"
           placeholder="Cari nama atau email…"
           @input.debounce.400ms="$refs.filterForm.submit()">

    <div style="min-width:170px">
      <x-searchable-select name="role" :options="$roles" :selected="request('role')"
                           placeholder="Semua Role" search-placeholder="Cari role…" :submit-on-change="true" />
    </div>

    <span class="filter-spacer"></span>

    @if(request()->hasAny(['search','role']))
      <a href="{{ route('pengguna.index') }}" class="btn btn-ghost btn-sm">✕ Reset</a>
    @endif
  </form>

  <div class="card">
    <div class="card-b" style="padding:0">
      @if($users->count())
        <table>
          <thead><tr>
            <x-sort-th col="name"       label="Nama" />
            <x-sort-th col="email"      label="Email" />
            <x-sort-th col="role"       label="Role" />
            <th>Bidang</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr></thead>
          <tbody>
            @foreach($users as $u)
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:30px;height:30px;border-radius:8px;background:{{ $u->roleColor() }};display:grid;place-items:center;font-weight:700;color:#fff;font-size:11px;flex:0 0 auto">{{ $u->initials() }}</div>
                    <div>
                      <div class="t-name">{{ $u->name }}</div>
                      @if($u->isLocked())<div style="font-size:11px;color:var(--danger)">Terkunci</div>@endif
                    </div>
                  </div>
                </td>
                <td class="t-sub">{{ $u->email }}</td>
                <td>
                  <span class="badge" style="background:{{ $u->roleColor() }}1A;color:{{ $u->roleColor() }}">{{ $u->roleLabel() }}</span>
                </td>
                <td class="t-sub">{{ $u->department->name ?? '—' }}</td>
                <td>
                  <span class="badge {{ $u->is_active ? 'b-ok' : 'b-danger' }}">
                    {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td style="white-space:nowrap">
                  <button class="btn btn-ghost btn-sm"
                    @click="showModal=true; editData={{ json_encode(['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'role'=>$u->role,'department_id'=>$u->department_id ? (string)$u->department_id : '','is_active'=>$u->is_active ? '1' : '0']) }}">
                    Edit
                  </button>
                  @if($u->id !== auth()->id())
                    <form method="POST" action="{{ route('pengguna.destroy', $u) }}" style="display:inline"
                          data-confirm="Hapus pengguna {{ $u->name }}?"
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
        <div class="pg-bar">
          <x-per-page :per-page="request('per_page', 20)" />
          <span class="pg-info">
            Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} pengguna
          </span>
          {{ $users->links() }}
        </div>
      @else
        <div class="empty">
          <div class="empty-ic">◯</div>
          <b>Tidak ada pengguna ditemukan</b>
          <p>Coba ubah kata kunci pencarian atau filter role.</p>
        </div>
      @endif
    </div>
  </div>

  <template x-if="showModal">
    <div>
      <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
      <div class="modal" style="display:flex">
        <div class="modal-head">
          <h3 x-text="editData.id ? 'Edit Pengguna' : 'Tambah Pengguna'"></h3>
          <button class="close-btn" @click="showModal=false">✕</button>
        </div>
        <div class="modal-body">
          <form :action="editData.id ? '/pengguna/'+editData.id : '/pengguna'" method="POST"
                x-effect="if (editData.role !== 'kepala_bidang') editData.department_id = ''">
            @csrf
            <template x-if="editData.id"><input type="hidden" name="_method" value="PUT"></template>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
              <div class="field" style="margin:0">
                <label>Nama Lengkap <span style="color:var(--danger)">*</span></label>
                <input type="text" name="name" :value="editData.name||''" required>
              </div>
              <div class="field" style="margin:0">
                <label>Email <span style="color:var(--danger)">*</span></label>
                <input type="email" name="email" :value="editData.email||''" required>
              </div>
              <div class="field" style="margin:0">
                <label>Role <span style="color:var(--danger)">*</span></label>
                <x-searchable-select name="role" :options="$roles" x-model="editData.role"
                                     placeholder="— Pilih Role —" search-placeholder="Cari role…" required />
              </div>
              <div class="field" style="margin:0">
                <label>Status</label>
                <x-searchable-select name="is_active" :options="['1' => 'Aktif', '0' => 'Nonaktif']" x-model="editData.is_active"
                                     placeholder="— Pilih —" search-placeholder="" />
              </div>

              {{-- Bidang hanya relevan untuk Kepala Bidang --}}
              <div class="field" style="margin:0;grid-column:1/-1" x-show="editData.role === 'kepala_bidang'" x-cloak>
                <label>Bidang <span style="color:var(--danger)">*</span></label>
                <x-searchable-select name="department_id" :options="$departments" x-model="editData.department_id"
                                     placeholder="— Pilih Bidang —" search-placeholder="Cari bidang…" />
              </div>

              <div class="field" style="margin:0;grid-column:1/-1">
                <label>Password <span x-show="!editData.id" style="color:var(--danger)">*</span></label>
                <x-password-input name="password" placeholder="Isi untuk mengubah password" autocomplete="new-password" :meter="true" />
                <span class="help" x-show="editData.id">Kosongkan jika tidak ingin mengubah.</span>
              </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
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
