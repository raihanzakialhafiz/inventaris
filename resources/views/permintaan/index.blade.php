@extends('layouts.app')
@section('title', 'Permintaan Barang')
@section('page-title', 'Permintaan Barang')
@section('page-crumb', 'Permintaan')

@section('content')
    @php
        $user = auth()->user();
        $canCreate = $user->role === 'kepala_bidang';
    @endphp

    <div x-data="{
        showModal: false,
        detailId: null,
        lines: [{ qty: 1, item_id: '' }],
        openModal() { this.lines = [{ qty: 1, item_id: '' }];
            this.showModal = true; },
        addLine() { this.lines.push({ qty: 1, item_id: '' }); },
        removeLine(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
        // Proyeksi pemakaian per barang: pemakaian berjalan (used) + qty diminta di form.
        projected(itemId) {
            const opts = window.__permintaanItems || [];
            const o = opts.find(x => String(x.value) === String(itemId));
            if (!o) return null;
            const inForm = this.lines
                .filter(l => String(l.item_id) === String(itemId))
                .reduce((s, l) => s + Number(l.qty || 0), 0);
            return { over: o.used + inForm > o.limit, blocked: o.policy === 'block' && o.configured, opt: o };
        },
        // Melebihi ambang kuota dgn kebijakan warn → wajib justifikasi.
        isOverRequest() {
            return this.lines.some(l => {
                const p = l.item_id && this.projected(l.item_id);
                return p && p.over && !p.blocked;
            });
        },
        // Melebihi ambang kuota dgn kebijakan block → tidak boleh diajukan.
        isBlocked() {
            return this.lines.some(l => {
                const p = l.item_id && this.projected(l.item_id);
                return p && p.over && p.blocked;
            });
        },
        rejectOpen: false,
        rejectAction: '',
        rejectNo: '',
        openReject(action, no) { this.rejectAction = action;
            this.rejectNo = no;
            this.rejectOpen = true; },
    }">

        @php
            $viewSubtitle = match (request('view')) {
                'masuk' => 'Permintaan yang perlu diproses.',
                'diproses' => 'Permintaan yang sudah diproses.',
                default => 'Riwayat dan status permintaan ATK.',
            };
        @endphp
        <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
            <div>
                <h2>Permintaan Barang</h2>
                <p>{{ $viewSubtitle }}</p>
            </div>
            @if ($canCreate)
                <button class="btn btn-pri" @click="openModal()"><x-icon name="plus" width="15" height="15" /> Ajukan
                    Permintaan</button>
            @endif
        </div>

        <form method="GET" class="filter-bar">
            @if (request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if (request('dir'))
                <input type="hidden" name="dir" value="{{ request('dir') }}">
            @endif
            @if (request('view'))
                <input type="hidden" name="view" value="{{ request('view') }}">
            @endif

            <input type="search" name="search" value="{{ request('search', '') }}" placeholder="Cari no. permintaan…"
                x-data @input.debounce.400ms="$el.form.submit()">

            <div style="min-width:170px">
                <x-searchable-select name="status" :selected="request('status')" :options="[
                    'pending' => 'Menunggu',
                    'disetujui' => 'Disetujui',
                    'ditolak' => 'Ditolak',
                    'selesai' => 'Selesai',
                    'selesai_sebagian' => 'Selesai Sebagian',
                ]" placeholder="Semua Status"
                    search-placeholder="Cari status…" :submit-on-change="true" />
            </div>

            @if ($user->role !== 'kepala_bidang')
                <div style="min-width:170px">
                    <x-searchable-select name="dept" :options="$departments" :selected="request('dept')" placeholder="Semua Bidang"
                        search-placeholder="Cari bidang…" :submit-on-change="true" />
                </div>
            @endif

            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;white-space:nowrap">
                <input type="checkbox" name="flagged" value="1" {{ request('flagged') ? 'checked' : '' }}
                    onchange="this.form.submit()">
                Over-Request
            </label>

            <span class="filter-spacer"></span>

            @if (request()->hasAny(['search', 'status', 'dept', 'flagged']))
                <a href="{{ route('permintaan.index', request('view') ? ['view' => request('view')] : []) }}"
                    class="btn btn-ghost btn-sm"><x-icon name="x" width="13" height="13" /> Reset</a>
            @endif
        </form>

        <div class="card">
            <div class="card-b" style="padding:0">
                @if ($permintaan->count())
                    <table class="tbl-cards">
                        <thead>
                            <tr>
                                <x-sort-th col="request_no" label="No. Permintaan" />
                                <th>Bidang</th>
                                <th>Pengaju</th>
                                <x-sort-th col="request_date" label="Tanggal" />
                                <th>Flag</th>
                                <x-sort-th col="status" label="Status" />
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permintaan as $req)
                                <tr>
                                    <td class="cell-title"><a href="#" @click.prevent="detailId = {{ $req->id }}" class="code"
                                            style="cursor:pointer">{{ $req->request_no }}</a></td>
                                    <td data-label="Bidang"><span class="badge b-primary">{{ $req->department->code }}</span></td>
                                    <td class="t-sub" data-label="Pengaju">{{ $req->requester->name }}</td>
                                    <td data-label="Tanggal">{{ $req->request_date->isoFormat('D MMM Y') }}</td>
                                    <td data-label="Flag">
                                        @if ($req->is_flagged)
                                            <span class="badge b-danger"><x-icon name="alert" width="11"
                                                    height="11" style="vertical-align:-1px" /> Over</span>
                                        @else
                                            <span class="t-sub">—</span>
                                        @endif
                                    </td>
                                    <td data-label="Status"><x-status-badge :status="$req->status" /></td>
                                    <td class="cell-actions">
                                        <div class="act-group">
                                            @if ($req->status === 'pending' && in_array($user->role, ['kasubag_umum', 'admin']))
                                                {{-- Setujui (sesuai jumlah diminta) --}}
                                                <form method="POST" action="{{ route('permintaan.approve', $req) }}"
                                                    style="display:inline"
                                                    data-confirm="Setujui {{ $req->request_no }} sesuai jumlah yang diminta?"
                                                    data-confirm-ok="Setujui">
                                                    @csrf
                                                    @foreach ($req->details as $d)
                                                        <input type="hidden"
                                                            name="approved_quantities[{{ $d->id }}]"
                                                            value="{{ $d->quantity_requested }}">
                                                    @endforeach
                                                    <button type="submit" class="icon-act ok"
                                                        title="Setujui (sesuai jumlah diminta)">
                                                        <svg width="17" height="17" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2.4"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="20 6 9 17 4 12" />
                                                        </svg>
                                                    </button>
                                                </form>
                                                {{-- Tolak (buka modal alasan) --}}
                                                <button type="button" class="icon-act danger" title="Tolak"
                                                    @click="openReject('{{ route('permintaan.reject', $req) }}', '{{ $req->request_no }}')">
                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2.4" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <line x1="18" y1="6" x2="6"
                                                            y2="18" />
                                                        <line x1="6" y1="6" x2="18"
                                                            y2="18" />
                                                    </svg>
                                                </button>
                                            @endif
                                            {{-- Detail (modal, tetap di halaman ini) --}}
                                            <button type="button" class="icon-act" title="Lihat detail"
                                                @click="detailId = {{ $req->id }}">
                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="pg-bar">
                        <x-per-page :per-page="request('per_page', 20)" />
                        <span class="pg-info">
                            Menampilkan {{ $permintaan->firstItem() }}–{{ $permintaan->lastItem() }} dari
                            {{ $permintaan->total() }} permintaan
                        </span>
                        {{ $permintaan->links() }}
                    </div>
                @else
                    <div class="empty">
                        <div class="empty-ic"><x-icon name="file-text" /></div>
                        <b>Tidak ada permintaan ditemukan</b>
                        <p>Coba ubah filter status atau bidang untuk melihat data lain.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Modal Tolak Permintaan (alasan) --}}
        @if (in_array($user->role, ['kasubag_umum', 'admin']))
            <template x-if="rejectOpen">
                <div>
                    <div class="modal-overlay" style="display:block" @click="rejectOpen=false"></div>
                    <div class="modal" style="display:flex;max-width:460px">
                        <div class="modal-head">
                            <h3>Tolak Permintaan</h3>
                            <button class="close-btn" @click="rejectOpen=false"><x-icon name="x" width="16"
                                    height="16" /></button>
                        </div>
                        <div class="modal-body">
                            <p class="t-sub" style="margin-bottom:12px">Menolak permintaan <b x-text="rejectNo"></b>.
                                Berikan alasan penolakan.</p>
                            <form :action="rejectAction" method="POST">
                                @csrf
                                <div class="field">
                                    <label>Alasan Penolakan <span style="color:var(--danger)">*</span></label>
                                    <textarea name="rejection_reason" rows="3" required minlength="5" placeholder="Minimal 5 karakter…"></textarea>
                                </div>
                                <div class="modal-actions">
                                    <button type="button" class="btn btn-ghost" @click="rejectOpen=false">Batal</button>
                                    <button type="submit" class="btn btn-danger">Tolak Permintaan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>
        @endif

        {{-- Modal Detail Permintaan (tetap di halaman ini, tanpa pindah halaman) --}}
        @foreach ($permintaan as $req)
            <template x-if="detailId === {{ $req->id }}">
                <div>
                    <div class="modal-overlay" style="display:block" @click="detailId = null"></div>
                    <div class="modal" style="display:flex;max-width:720px">
                        <div class="modal-head">
                            <h3>Detail Permintaan · {{ $req->request_no }}</h3>
                            <button type="button" class="close-btn" @click="detailId = null"><x-icon name="x"
                                    width="16" height="16" /></button>
                        </div>
                        <div class="modal-body">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px">
                                <x-status-badge :status="$req->status" />
                                @if ($req->is_flagged)
                                    <span class="badge b-danger"><x-icon name="alert" width="11" height="11"
                                            style="vertical-align:-1px" /> Over-Request</span>
                                @endif
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;margin-bottom:16px">
                                <div><span class="t-sub">Pengaju</span>
                                    <div class="t-name">{{ $req->requester->name }}</div>
                                </div>
                                <div><span class="t-sub">Bidang</span>
                                    <div>{{ $req->department->name }}</div>
                                </div>
                                <div><span class="t-sub">Tanggal</span>
                                    <div>{{ $req->request_date->isoFormat('D MMM Y') }}</div>
                                </div>
                                @if ($req->approver)
                                    <div><span class="t-sub">Diproses oleh</span>
                                        <div>{{ $req->approver->name }} ·
                                            {{ $req->approved_date?->isoFormat('D MMM Y HH:mm') }}</div>
                                    </div>
                                @endif
                            </div>

                            @if ($req->justification)
                                <div class="notice info" style="margin-bottom:10px"><span class="ic"><x-icon
                                            name="info" /></span>
                                    <div><b>Justifikasi:</b> {{ $req->justification }}</div>
                                </div>
                            @endif
                            @if ($req->rejection_reason)
                                <div class="notice danger" style="margin-bottom:10px"><span class="ic"><x-icon
                                            name="alert" /></span>
                                    <div><b>Alasan penolakan:</b> {{ $req->rejection_reason }}</div>
                                </div>
                            @endif
                            @if ($req->note)
                                <div class="notice info" style="margin-bottom:10px"><span class="ic"><x-icon
                                            name="dot" /></span>
                                    <div>{{ $req->note }}</div>
                                </div>
                            @endif

                            <table>
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Satuan</th>
                                        <th class="num">Diminta</th>
                                        <th class="num">Disetujui</th>
                                        <th class="num">Didistribusikan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($req->details as $d)
                                        <tr>
                                            <td><span class="code">{{ $d->item->code }}</span> {{ $d->item->name }}
                                            </td>
                                            <td>{{ $d->item->unit }}</td>
                                            <td class="num">{{ $d->quantity_requested }}</td>
                                            <td class="num">{{ $d->quantity_approved ?? '—' }}</td>
                                            <td class="num">
                                                {{ $d->quantity_distributed > 0 ? $d->quantity_distributed : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn btn-pri" @click="detailId = null">Tutup</button>
                        </div>
                    </div>
                </div>
            </template>
        @endforeach

        {{-- Modal Ajukan Permintaan (multi-item) --}}
        @if ($canCreate)
            <template x-if="showModal">
                <div>
                    <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
                    <div class="modal" style="display:flex;max-width:680px">
                        <div class="modal-head">
                            <h3>Ajukan Permintaan ATK</h3>
                            <button class="close-btn" @click="showModal=false"><x-icon name="x" width="16"
                                    height="16" /></button>
                        </div>
                        <div class="modal-body">
                            <p class="t-sub" style="margin-bottom:14px">Permintaan atas nama
                                <b>{{ $user->department->name ?? '—' }}</b>. Sistem memeriksa kuota otomatis.</p>
                            <form method="POST" action="{{ route('permintaan.store') }}"
                                @submit="
                  if (lines.some(l => !l.item_id)) { $event.preventDefault(); window.toast('Pilih barang untuk setiap baris.', 'warn'); return; }
                  if (isBlocked()) { $event.preventDefault(); window.toast('Sebagian barang melebihi kuota bidang (kebijakan blokir). Kurangi jumlah.', 'err'); }
                ">
                                @csrf

                                <div
                                    style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                                    <b style="font-size:13.5px">Daftar Barang yang Diminta</b>
                                    <button type="button" class="btn btn-ghost btn-sm" @click="addLine()"><x-icon
                                            name="plus" width="15" height="15" /> Tambah Baris</button>
                                </div>

                                <template x-for="(line, idx) in lines" :key="idx">
                                    <div
                                        style="display:grid;grid-template-columns:1fr 92px auto;gap:10px;align-items:end;margin-bottom:10px">
                                        <div class="field" style="margin:0">
                                            <label x-show="idx===0" style="font-size:12px">Barang <span
                                                    style="color:var(--danger)">*</span></label>
                                            <x-item-picker options-var="window.__permintaanItems" />
                                        </div>
                                        <div class="field" style="margin:0">
                                            <label x-show="idx===0" style="font-size:12px">Jumlah</label>
                                            <input type="number" :name="'items[' + idx + '][qty]'" x-model="line.qty"
                                                min="1" required class="qty-inp" style="width:100%">
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm" @click="removeLine(idx)"
                                            x-show="lines.length > 1" title="Hapus baris"><x-icon name="x"
                                                width="13" height="13" /></button>
                                    </div>
                                </template>

                                <div class="field" style="margin-top:14px">
                                    <label>Keperluan / Keterangan</label>
                                    <textarea name="note" rows="2" placeholder="Cth: untuk kebutuhan operasional bulan ini"></textarea>
                                </div>
                                {{-- Peringatan BLOKIR: kuota bidang berpolicy "block" terlampaui. --}}
                                <div class="notice danger" x-show="isBlocked()" x-cloak style="margin-top:12px">
                                    <span class="ic"><x-icon name="ban" /></span>
                                    <div>Sebagian barang melebihi kuota bidang dengan kebijakan <b>blokir</b>. Kurangi
                                        jumlah agar permintaan bisa diajukan.</div>
                                </div>

                                {{-- Justifikasi muncul & wajib saat over-request berkebijakan "warn". --}}
                                <div class="field" x-show="isOverRequest()" x-cloak
                                    style="border-left:3px solid var(--warn);padding-left:12px">
                                    <label>Justifikasi <span style="color:var(--danger)">*</span>
                                        <span class="help">Permintaan melebihi batas kuota bidang — wajib diisi</span>
                                    </label>
                                    <textarea name="justification" rows="2" :required="isOverRequest()"
                                        placeholder="Jelaskan alasan permintaan melebihi batas kuota"></textarea>
                                </div>

                                <div class="modal-actions">
                                    <button type="button" class="btn btn-ghost" @click="showModal=false">Batal</button>
                                    <button type="submit" class="btn btn-pri" :disabled="isBlocked()"
                                        :style="isBlocked() ? 'opacity:.5;cursor:not-allowed' : ''">Kirim Permintaan <x-icon
                                            name="arrow-right" width="14" height="14"
                                            style="vertical-align:-2px" /></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        // Data barang untuk dropdown searchable pada form "Ajukan Permintaan".
        // Komponen itemPicker() dimuat global dari public/js/item-picker.js.
        window.__permintaanItems = @json($itemOptions);
    </script>
@endpush
