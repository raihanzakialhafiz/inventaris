@extends('layouts.app')
@section('title', 'Barang Masuk')
@section('page-title', 'Barang Masuk')
@section('page-crumb', 'Inventaris')

@section('content')
    @php
        // Opsi barang untuk dropdown searchable (dipakai tiap baris input barang masuk).
        $itemOptions = $items
            ->map(
                fn($i) => [
                    'value' => (string) $i->id,
                    'label' => $i->code . ' — ' . $i->name . ' (stok: ' . $i->stock . ' ' . $i->unit . ')',
                    'disabled' => false,
                ],
            )
            ->values();
    @endphp

    <div x-data="{
        showModal: false,
        detailId: null,
        lines: [{ qty: 1, item_id: '' }],
        openModal() { this.lines = [{ qty: 1, item_id: '' }];
            this.showModal = true; },
        addLine() { this.lines.push({ qty: 1, item_id: '' }); },
        removeLine(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
        async saveMasuk(e) {
            e.preventDefault();
            if (this.lines.some(l => !l.item_id)) { window.toast('Pilih barang untuk setiap baris.', 'warn'); return; }
            if (await window.uiConfirm({ title: 'Konfirmasi Barang Masuk', message: 'Proses barang masuk? Stok akan bertambah sesuai jumlah yang diisi.', okLabel: 'Simpan & Perbarui Stok' })) {
                e.target.submit();
            }
        },
    }">

        <div class="page-head" style="display:flex;align-items:center;justify-content:space-between">
            <div>
                <h2>Barang Masuk</h2>
                <p>Pencatatan penerimaan barang dari supplier.</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <x-export-ttd :pdf="route('barang-masuk.export', ['format' => 'pdf'] + request()->query())" :excel="route('barang-masuk.export', ['format' => 'excel'] + request()->query())" />
                <button class="btn btn-pri" @click="openModal()"><x-icon name="plus" width="15" height="15" /> Input
                    Barang Masuk</button>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            @if (request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if (request('dir'))
                <input type="hidden" name="dir" value="{{ request('dir') }}">
            @endif

            <input type="search" name="search" value="{{ request('search') }}"
                placeholder="Cari no. transaksi atau supplier…" x-data @input.debounce.400ms="$el.form.submit()">

            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Tanggal dari"
                onchange="this.form.submit()" style="width:150px">
            <span class="t-sub" style="padding:0 2px">—</span>
            <input type="date" name="date_to" value="{{ request('date_to') }}" title="Tanggal sampai"
                onchange="this.form.submit()" style="width:150px">

            <span class="filter-spacer"></span>

            @if (request()->hasAny(['search', 'date_from', 'date_to']))
                <a href="{{ route('barang-masuk.index') }}" class="btn btn-ghost btn-sm"><x-icon name="x"
                        width="13" height="13" /> Reset</a>
            @endif
        </form>

        <div class="card">
            <div class="card-b" style="padding:0">
                @if ($stockIns->count())
                    <table>
                        <thead>
                            <tr>
                                <x-sort-th col="transaction_no" label="No. Transaksi" />
                                <x-sort-th col="date" label="Tanggal" />
                                <th>Supplier</th>
                                <th class="num">Jenis Barang</th>
                                <th class="num">Total Unit</th>
                                <th>Petugas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockIns as $si)
                                <tr>
                                    <td><span class="code">{{ $si->transaction_no }}</span></td>
                                    <td>{{ $si->date->isoFormat('D MMM Y') }}</td>
                                    <td>{{ $si->supplier->name ?? 'Tanpa supplier' }}</td>
                                    <td class="num t-sub">{{ $si->details->count() }} jenis</td>
                                    <td class="num"><b>{{ $si->totalQuantity() }}</b></td>
                                    <td class="t-sub">{{ $si->createdBy->name }}</td>
                                    <td style="white-space:nowrap">
                                        <button type="button" class="icon-act" title="Lihat detail"
                                            @click="detailId = {{ $si->id }}">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </button>
                                        <form method="POST" action="{{ route('barang-masuk.destroy', $si) }}"
                                            style="display:inline"
                                            data-confirm="Hapus transaksi {{ $si->transaction_no }}? Stok semua barang di dalamnya akan dikurangi kembali."
                                            data-confirm-variant="danger" data-confirm-ok="Hapus">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="icon-act" title="Hapus transaksi"
                                                style="color:var(--danger)">
                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path d="M4 7h16" />
                                                    <path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                    <path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13" />
                                                    <path d="M10 11v6" />
                                                    <path d="M14 11v6" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="pg-bar">
                        <x-per-page :per-page="request('per_page', 20)" />
                        <span class="pg-info">
                            Menampilkan {{ $stockIns->firstItem() }}–{{ $stockIns->lastItem() }} dari
                            {{ $stockIns->total() }} transaksi
                        </span>
                        {{ $stockIns->links() }}
                    </div>
                @else
                    <div class="empty">
                        <div class="empty-ic"><x-icon name="inbox-in" /></div>
                        <b>Belum ada data barang masuk</b>
                        <p>Data penerimaan barang dari supplier akan tampil di sini.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Modal Input Barang Masuk (multi-item) --}}
        <template x-if="showModal">
            <div>
                <div class="modal-overlay" style="display:block" @click="showModal=false"></div>
                <div class="modal" style="display:flex;max-width:680px">
                    <div class="modal-head">
                        <h3>Input Barang Masuk</h3>
                        <button class="close-btn" @click="showModal=false"><x-icon name="x" width="16"
                                height="16" /></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('barang-masuk.store') }}" @submit="saveMasuk($event)">
                            @csrf
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                                <div class="field" style="margin:0">
                                    <label>Tanggal <span style="color:var(--danger)">*</span></label>
                                    <input type="date" name="date" value="{{ today()->format('Y-m-d') }}"
                                        required>
                                </div>
                                <div class="field" style="margin:0">
                                    <label>Supplier</label>
                                    <x-searchable-select name="supplier_id" :options="$suppliers"
                                        placeholder="— Tanpa supplier —" search-placeholder="Cari supplier…" />
                                </div>
                                <div class="field" style="margin:0;grid-column:1/-1">
                                    <label>Catatan</label>
                                    <input type="text" name="note" placeholder="Opsional">
                                </div>
                            </div>

                            <div
                                style="display:flex;align-items:center;justify-content:space-between;margin:18px 0 10px;padding-top:14px;border-top:1px solid var(--line)">
                                <b style="font-size:13.5px">Daftar Barang</b>
                                <button type="button" class="btn btn-ghost btn-sm" @click="addLine()"><x-icon
                                        name="plus" width="15" height="15" /> Tambah Baris</button>
                            </div>

                            <template x-for="(line, idx) in lines" :key="idx">
                                <div
                                    style="display:grid;grid-template-columns:1fr 92px auto;gap:10px;align-items:end;margin-bottom:10px">
                                    <div class="field" style="margin:0">
                                        <label x-show="idx===0" style="font-size:12px">Barang</label>
                                        <x-item-picker options-var="window.__barangMasukItems" />
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

                            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
                                <button type="button" class="btn btn-ghost" @click="showModal=false">Batal</button>
                                <button type="submit" class="btn btn-pri">Simpan &amp; Perbarui Stok</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        {{-- Modal Detail Barang Masuk (tetap di halaman ini, tanpa pindah halaman) --}}
        @foreach ($stockIns as $si)
            <template x-if="detailId === {{ $si->id }}">
                <div>
                    <div class="modal-overlay" style="display:block" @click="detailId = null"></div>
                    <div class="modal" style="display:flex;max-width:640px">
                        <div class="modal-head">
                            <h3>Detail Barang Masuk · {{ $si->transaction_no }}</h3>
                            <button type="button" class="close-btn" @click="detailId = null"><x-icon name="x"
                                    width="16" height="16" /></button>
                        </div>
                        <div class="modal-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;margin-bottom:16px">
                                <div><span class="t-sub">No. Transaksi</span>
                                    <div class="t-name code">{{ $si->transaction_no }}</div>
                                </div>
                                <div><span class="t-sub">Tanggal</span>
                                    <div>{{ $si->date->isoFormat('D MMM Y') }}</div>
                                </div>
                                <div><span class="t-sub">Supplier</span>
                                    <div>{{ $si->supplier->name ?? '—' }}</div>
                                </div>
                                <div><span class="t-sub">Dicatat oleh</span>
                                    <div>{{ $si->createdBy->name }}</div>
                                </div>
                                @if ($si->note)
                                    <div style="grid-column:1/-1"><span class="t-sub">Catatan</span>
                                        <div>{{ $si->note }}</div>
                                    </div>
                                @endif
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Satuan</th>
                                        <th class="num">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($si->details as $d)
                                        <tr>
                                            <td><span class="code">{{ $d->item->code }}</span> {{ $d->item->name }}
                                            </td>
                                            <td>{{ $d->item->unit }}</td>
                                            <td class="num"><b>{{ $d->quantity }}</b></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" style="text-align:right;padding:11px 14px">Total Unit Diterima
                                        </th>
                                        <th class="num" style="padding:11px 14px">{{ $si->totalQuantity() }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn btn-pri" @click="detailId = null">Tutup</button>
                        </div>
                    </div>
                </div>
            </template>
        @endforeach

    </div>
@endsection

@push('scripts')
    <script>
        // Data barang untuk dropdown searchable pada form "Input Barang Masuk".
        window.__barangMasukItems = @json($itemOptions);
    </script>
@endpush
