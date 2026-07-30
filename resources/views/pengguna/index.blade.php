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
  // Ringkasan hak akses per peran — diturunkan dari grup middleware di routes/web.php.
  $roleHints = [
    'admin'          => 'Akses penuh: master data, pengguna, pengaturan, dan seluruh transaksi.',
    'kasubag_umum'   => 'Menyetujui atau menolak permintaan, mengubah barang, dan membuka laporan.',
    'petugas_gudang' => 'Mencatat barang masuk, stock opname, dan mendistribusikan permintaan.',
    'kepala_bidang'  => 'Mengajukan permintaan atas nama bidangnya. Wajib dipasangkan ke satu Bidang.',
    'pimpinan'       => 'Hanya melihat: laporan, dashboard, dan data barang.',
  ];
@endphp
{{-- Saat validasi gagal, modal dibuka kembali dengan isian old() — tanpa ini
     halaman reload menutup modal dan seluruh ketikan pengguna hilang. --}}
<div x-data="{
       showModal: {{ $errors->any() ? 'true' : 'false' }},
       editData: {{ json_encode($errors->any() ? [
         'id'            => old('editing_id') ?: null,
         'name'          => old('name'),
         'email'         => old('email'),
         'nip'           => old('nip'),
         'jabatan'       => old('jabatan'),
         'role'          => old('role', ''),
         'department_id' => old('department_id', ''),
         'is_active'     => old('is_active', '1'),
       ] : new \stdClass) }},
       roleHints: {{ json_encode($roleHints) }},
       showDetail: false, detailData: {}
     }">

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
      <a href="{{ route('pengguna.index') }}" class="btn btn-ghost btn-sm"><x-icon name="x" width="13" height="13" /> Reset</a>
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
                    <div style="width:30px;height:30px;border-radius:8px;background:{{ $u->roleColor() }};display:grid;place-items:center;font-weight:700;color:var(--on-accent);font-size:11px;flex:0 0 auto">{{ $u->initials() }}</div>
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
                  {{-- Detail memuat data yang tidak muat di tabel: NIP, jabatan,
                       dan status keamanan akun. --}}
                  <button class="btn btn-ghost btn-sm"
                    @click="showDetail=true; detailData={{ json_encode([
                      'name'      => $u->name,
                      'email'     => $u->email,
                      'nip'       => $u->nip,
                      'jabatan'   => $u->jabatan,
                      'jabatanFallback' => $u->jabatanLabel(),
                      'initials'  => $u->initials(),
                      'roleLabel' => $u->roleLabel(),
                      'roleColor' => $u->roleColor(),
                      'dept'      => $u->department->name ?? null,
                      'active'    => (bool) $u->is_active,
                      'locked'    => $u->isLocked(),
                      'lockedUntil' => $u->isLocked() ? $u->locked_until->isoFormat('D MMM YYYY, HH:mm') : null,
                      'failed'    => (int) $u->failed_login_count,
                      'joined'    => optional($u->created_at)->isoFormat('D MMMM YYYY'),
                      'isSelf'    => $u->id === auth()->id(),
                    ]) }}">
                    Detail
                  </button>
                  <button class="btn btn-ghost btn-sm" style="margin-left:4px"
                    @click="showModal=true; editData={{ json_encode(['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'nip'=>$u->nip,'jabatan'=>$u->jabatan,'role'=>$u->role,'department_id'=>$u->department_id ? (string)$u->department_id : '','is_active'=>$u->is_active ? '1' : '0']) }}">
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
          <div class="empty-ic"><x-icon name="users" /></div>
          <b>Tidak ada pengguna ditemukan</b>
          <p>Coba ubah kata kunci pencarian atau filter role.</p>
        </div>
      @endif
    </div>
  </div>


  <template x-if="showDetail">
    <div @keydown.escape.window="showDetail=false">
      <div class="modal-overlay" style="display:block" @click="showDetail=false"></div>
      <div class="modal" style="display:flex">
        <div class="modal-head">
          <h3>Detail Pengguna</h3>
          <button class="close-btn" @click="showDetail=false"><x-icon name="x" width="16" height="16" /></button>
        </div>
        <div class="modal-body">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
            <div style="width:44px;height:44px;border-radius:10px;display:grid;place-items:center;font-weight:700;color:var(--on-accent);font-size:15px;flex:0 0 auto"
                 :style="'background:' + detailData.roleColor" x-text="detailData.initials"></div>
            <div style="min-width:0">
              <div style="font-size:16px;font-weight:700" x-text="detailData.name"></div>
              <div class="t-sub" style="font-family:var(--mono)" x-text="detailData.email"></div>
            </div>
            <span style="margin-left:auto;display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end">
              <span class="badge" :style="'background:' + detailData.roleColor + '1A;color:' + detailData.roleColor" x-text="detailData.roleLabel"></span>
              <span class="badge" :class="detailData.active ? 'b-ok' : 'b-danger'" x-text="detailData.active ? 'Aktif' : 'Nonaktif'"></span>
            </span>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="field" style="margin:0">
              <label>NIP</label>
              <input type="text" :value="detailData.nip || '— belum diisi'" disabled>
            </div>
            <div class="field" style="margin:0">
              <label>Jabatan</label>
              <input type="text" :value="detailData.jabatanFallback" disabled>
              <span class="help" x-show="!detailData.jabatan" x-cloak>Belum diisi — memakai label peran.</span>
            </div>
            <div class="field" style="margin:0">
              <label>Bidang</label>
              <input type="text" :value="detailData.dept || '—'" disabled>
            </div>
            <div class="field" style="margin:0">
              <label>Bergabung Sejak</label>
              <input type="text" :value="detailData.joined || '—'" disabled>
            </div>
          </div>

          {{-- Status keamanan hanya relevan bila memang ada masalah. --}}
          <template x-if="detailData.locked || detailData.failed > 0">
            <div class="notice warn" style="margin-top:16px">
              <span class="ic"><x-icon name="alert" /></span>
              <div>
                <template x-if="detailData.locked">
                  <div>Akun terkunci sampai <b x-text="detailData.lockedUntil"></b>.</div>
                </template>
                <div x-show="detailData.failed > 0">
                  Percobaan login gagal: <b x-text="detailData.failed"></b>. Terhapus otomatis setelah login berhasil.
                </div>
              </div>
            </div>
          </template>

          <template x-if="!detailData.nip">
            <div class="notice warn" style="margin-top:12px">
              <span class="ic"><x-icon name="alert" /></span>
              <div>NIP kosong. Bila pengguna ini menandatangani laporan, NIP akan tercetak sebagai “-”.</div>
            </div>
          </template>

          <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
            <button type="button" class="btn btn-ghost" @click="showDetail=false">Tutup</button>
          </div>
        </div>
      </div>
    </div>
  </template>

  <template x-if="showModal">
    <div @keydown.escape.window="showModal=false">
      <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
      <div class="modal" style="display:flex">
        <div class="modal-head">
          <h3 x-text="editData.id ? 'Edit Pengguna' : 'Tambah Pengguna'"></h3>
          <button class="close-btn" @click="showModal=false"><x-icon name="x" width="16" height="16" /></button>
        </div>
        <div class="modal-body">
          <form :action="editData.id ? '/pengguna/'+editData.id : '/pengguna'" method="POST"
                x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <template x-if="editData.id"><input type="hidden" name="_method" value="PUT"></template>
            {{-- Ikut terkirim agar modal terbuka ulang dalam mode yang sama saat validasi gagal. --}}
            <input type="hidden" name="editing_id" :value="editData.id||''">

            <div class="form-sec">Identitas</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
              <div class="field" style="margin:0">
                <label>Nama Lengkap <span class="req" aria-hidden="true">*</span></label>
                <input type="text" name="name" :value="editData.name||''" required
                       x-init="$nextTick(() => $el.focus())">
                @error('name')<span class="err">{{ $message }}</span>@enderror
              </div>
              <div class="field" style="margin:0">
                <label>Email <span class="req" aria-hidden="true">*</span></label>
                <input type="email" name="email" :value="editData.email||''" required>
                @error('email')<span class="err">{{ $message }}</span>@enderror
              </div>
            </div>

            {{-- Selebar modal, bukan dua kolom: keterangan hak akses di bawah Role
                 butuh ruang, dan sel kanan akan menganggur untuk 4 dari 5 peran. --}}
            <div class="form-sec">Akses</div>
            <div class="field" style="margin:0">
              <label>Role <span class="req" aria-hidden="true">*</span></label>
              <x-searchable-select name="role" :options="$roles" x-model="editData.role"
                                   placeholder="— Pilih Role —" search-placeholder="Cari role…" required />
              <span class="help" x-text="roleHints[editData.role] || 'Menentukan menu dan hak akses pengguna.'"></span>
              @error('role')<span class="err">{{ $message }}</span>@enderror
            </div>

            {{-- x-if, bukan x-show: field keluar dari DOM saat peran lain dipilih.
                 Dengan x-show, input tersembunyi tetap ikut terkirim dan `required`
                 pada field tak terlihat membuat browser gagal submit diam-diam. --}}
            <template x-if="editData.role === 'kepala_bidang'">
              <div class="field" style="margin:14px 0 0">
                <label>Bidang <span class="req" aria-hidden="true">*</span></label>
                <x-searchable-select name="department_id" :options="$departments" x-model="editData.department_id"
                                     placeholder="— Pilih Bidang —" search-placeholder="Cari bidang…" required />
                <span class="help">Permintaan barang yang diajukan akan tercatat atas nama bidang ini.</span>
                @error('department_id')<span class="err">{{ $message }}</span>@enderror
              </div>
            </template>

            {{-- Status bukan isian data melainkan saklar akses: nonaktif memblokir
                 login (LoginController) sekaligus reset password. Keterangannya
                 ikut berubah supaya konsekuensinya terbaca saat dipilih. --}}
            <div class="switch-row" :class="{ 'is-off': editData.is_active === '0' }">
              <div class="switch-row__text">
                <b>Status akun</b>
                <span x-text="editData.is_active === '0'
                        ? 'Tidak bisa login maupun meminta reset password. Data lamanya tetap tersimpan.'
                        : 'Bisa masuk dan memakai sistem sesuai perannya.'"></span>
              </div>
              <div class="seg">
                <input type="radio" id="ua-on"  name="is_active" value="1" x-model="editData.is_active">
                <label for="ua-on">Aktif</label>
                <input type="radio" id="ua-off" name="is_active" value="0" x-model="editData.is_active">
                <label for="ua-off">Nonaktif</label>
              </div>
            </div>
            @error('is_active')<span class="err" style="display:block;margin-top:5px">{{ $message }}</span>@enderror

            <div class="form-sec">Kredensial</div>
            <div class="field" style="margin:0">
              <label>Password <span class="req" x-show="!editData.id" x-cloak aria-hidden="true">*</span></label>
              <x-password-input name="password" autocomplete="new-password" :meter="true"
                                x-bind:required="!editData.id"
                                x-bind:placeholder="editData.id ? 'Kosongkan bila tidak diubah' : 'Minimal 8 karakter, huruf dan angka'" />
              @error('password')<span class="err">{{ $message }}</span>@enderror
            </div>

            {{-- NIP & jabatan hanya dipakai blok tanda tangan laporan PDF/Excel —
                 dilipat agar modal tidak jadi dinding isian. Terbuka sendiri bila
                 sudah terisi (mode edit / isian yang gagal validasi). --}}
            <details class="fold" :open="!!(editData.nip || editData.jabatan)">
              <summary>Tanda tangan laporan (opsional)</summary>
              <div class="fold__body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="field" style="margin:0">
                  <label>NIP</label>
                  <input type="text" name="nip" :value="editData.nip||''" placeholder="mis. 19801231 200604 1 002">
                  @error('nip')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field" style="margin:0">
                  <label>Jabatan</label>
                  <input type="text" name="jabatan" :value="editData.jabatan||''" placeholder="mis. Kepala Dinas Kominfo">
                  <span class="help">Kosong → memakai label peran.</span>
                  @error('jabatan')<span class="err">{{ $message }}</span>@enderror
                </div>
              </div>
            </details>

            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" @click="showModal=false">Batal</button>
              <button type="submit" class="btn btn-pri" :disabled="saving"
                      x-text="saving ? 'Menyimpan…' : 'Simpan'">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

</div>
@endsection
