@extends('layouts.app')
@section('title', 'Distribusi')
@section('page-title', 'Distribusi Barang')
@section('page-crumb', 'Barang Keluar')

@section('content')
{{-- openId menyimpan id permintaan yang modalnya sedang terbuka (null = tertutup).
     Nilai awal dari ?proses agar tautan dari halaman lain langsung membuka modal. --}}
<div x-data="{ openId: {{ request('proses') ? (int) request('proses') : 'null' }} }">

  <div class="page-head">
    <h2>Distribusi Barang</h2>
    <p>Proses permintaan yang sudah disetujui, lalu pantau riwayat barang keluar.</p>
  </div>

  {{-- Ringkasan sekilas-pandang: beban kerja & aktivitas tanpa harus membaca tabel. --}}
  <div class="grid g-3" style="margin-bottom:16px">
    <div class="stat {{ $approved->total() > 0 ? 'warn' : 'ok' }}">
      <div class="accent"></div>
      <div class="lbl">Menunggu Diproses</div>
      <div class="val">{{ $approved->total() }} <small>permintaan</small></div>
    </div>
    <div class="stat">
      <div class="accent"></div>
      <div class="lbl">Distribusi Hari Ini</div>
      <div class="val">{{ $stats['hariIni'] }} <small>transaksi</small></div>
    </div>
    <div class="stat">
      <div class="accent"></div>
      <div class="lbl">Unit Keluar Bulan Ini</div>
      <div class="val">{{ number_format($stats['unitBulan'], 0, ',', '.') }} <small>unit</small></div>
    </div>
  </div>

  @if($approved->total() > 0)
    <div class="card" style="margin-bottom:16px">
      <div class="card-h mc-h">
        <span class="mc-ic"><x-icon name="send-up" width="17" height="17" /></span>
        <div>
          <h3>Antrean Distribusi</h3>
          <p>Cek kolom <b>Kesiapan Stok</b> dulu — permintaan berstok kurang tetap bisa diproses dengan mengurangi jumlah &amp; mengisi alasan.</p>
        </div>
      </div>
      <div class="card-b" style="padding:0">
        <table>
          <thead><tr>
            <th class="num">No</th>
            <th>No. Permintaan</th><th>Bidang</th><th>Pengaju</th>
            <th class="num">Barang</th><th>Kesiapan Stok</th><th>Disetujui</th><th>Aksi</th>
          </tr></thead>
          <tbody>
            @foreach($approved as $req)
              @php
                // Dihitung dari relasi yang sudah dimuat — tanpa query tambahan.
                $kurang = $req->details->filter(
                  fn ($d) => $d->item->stock < ($d->quantity_approved ?? 0)
                )->count();
              @endphp
              <tr>
                <td class="num t-sub">{{ $approved->firstItem() + $loop->index }}</td>
                <td><span class="code">{{ $req->request_no }}</span></td>
                <td><span class="badge b-primary">{{ $req->department->code }}</span> {{ $req->department->name }}</td>
                <td class="t-sub">{{ $req->requester->name }}</td>
                <td class="num">{{ $req->details->count() }} jenis</td>
                <td>
                  @if($kurang)
                    <span class="badge b-warn">⚠ {{ $kurang }} barang kurang</span>
                  @else
                    <span class="badge b-ok">✓ Semua siap</span>
                  @endif
                </td>
                <td class="t-sub">{{ $req->approved_date?->isoFormat('D MMM Y') }}</td>
                <td><button type="button" class="btn btn-pri btn-sm" @click="openId = {{ $req->id }}">Proses →</button></td>
              </tr>
            @endforeach
          </tbody>
        </table>
        @if($approved->total())
          <div class="pg-bar">
            <span class="pg-info">Menampilkan {{ $approved->firstItem() }}–{{ $approved->lastItem() }} dari {{ $approved->total() }} antrean</span>
            {{ $approved->links() }}
          </div>
        @endif
      </div>
    </div>
  @else
    <div class="notice info" style="margin-bottom:16px">
      <span class="ic">✓</span>
      <div><b>Semua beres.</b> Tidak ada permintaan yang menunggu distribusi — antrean baru muncul di sini setelah Kasubag menyetujui permintaan.</div>
    </div>
  @endif

  <div class="card">
    <div class="card-h mc-h">
      <span class="mc-ic"><x-icon name="clipboard" width="17" height="17" /></span>
      <div>
        <h3>Riwayat Distribusi Terbaru</h3>
        <p>Barang keluar yang sudah diserahkan, berikut rujukan permintaannya.</p>
      </div>
    </div>
    <div class="card-b" style="padding:0">
      @if($history->count())
        <table>
          <thead><tr>
            <th class="num">No</th>
            <th>No. Transaksi</th><th>Barang</th><th>Bidang</th>
            <th class="num">Jml</th><th>Tanggal</th><th>Ref. Permintaan</th>
          </tr></thead>
          <tbody>
            @foreach($history as $so)
              <tr>
                <td class="num t-sub">{{ $history->firstItem() + $loop->index }}</td>
                <td><span class="code">{{ $so->transaction_no }}</span></td>
                <td>{{ $so->item->name }}</td>
                <td><span class="badge b-primary">{{ $so->department->code ?? '—' }}</span></td>
                <td class="num">{{ $so->quantity }}</td>
                <td>{{ $so->date->isoFormat('D MMM Y') }}</td>
                <td>
                  @if($so->request)
                    <a href="{{ route('permintaan.show', $so->request) }}" class="code">{{ $so->request->request_no }}</a>
                  @else —
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="pg-bar">
          <x-per-page :per-page="request('per_page', 15)" />
          <span class="pg-info">
            Menampilkan {{ $history->firstItem() }}–{{ $history->lastItem() }} dari {{ $history->total() }} distribusi
          </span>
          {{ $history->links() }}
        </div>
      @else
        <div class="empty">
          <div class="empty-ic">↑</div>
          <b>Belum ada distribusi</b>
          <p>Riwayat distribusi barang akan muncul di sini setelah permintaan diproses.</p>
        </div>
      @endif
    </div>
  </div>

  {{-- Modal proses distribusi (satu per permintaan; dirender saat openId cocok) --}}
  @foreach($approved as $req)
    <template x-if="openId === {{ $req->id }}">
      <div>
        <div class="modal-overlay" style="display:block" @click="openId = null"></div>
        <div class="modal" style="display:flex;max-width:760px">
          <div class="modal-head">
            <h3>Proses Distribusi · {{ $req->request_no }}</h3>
            <button type="button" class="close-btn" @click="openId = null">✕</button>
          </div>

          <div class="modal-body">
            <div class="notice info" style="margin:0 0 14px">
              <span class="ic">ℹ</span>
              <div>
                <b>{{ $req->requester->name }}</b> · {{ $req->department->name }}.
                Kurangi jumlah distribusi jika stok tidak mencukupi (wajib isi alasan).
              </div>
            </div>

            <form id="dist-form-{{ $req->id }}" method="POST" action="{{ route('distribusi.store', $req) }}"
                  data-confirm="Proses distribusi {{ $req->request_no }}? Stok akan berkurang sesuai jumlah yang diisi."
                  data-confirm-ok="Proses">
              @csrf
              <table>
                <thead><tr>
                  <th>Barang</th><th class="num">Stok</th><th class="num">Disetujui</th>
                  <th class="num">Distribusikan</th><th>Alasan (jika dikurangi)</th>
                </tr></thead>
                <tbody>
                  @foreach($req->details as $d)
                    @php $approvedQty = $d->quantity_approved ?? 0; @endphp
                    {{-- qty & approved di-track Alpine agar alasan WAJIB saat qty < disetujui --}}
                    <tr x-data="{ qty: {{ old("distributions.{$d->id}.qty", min($approvedQty, $d->item->stock)) }}, approved: {{ $approvedQty }} }">
                      <td>
                        <div class="t-name">{{ $d->item->name }}</div>
                        <div class="t-sub">{{ $d->item->unit }}{{ $d->item->location ? ' · '.$d->item->location : '' }}</div>
                      </td>
                      <td class="num">
                        <b style="{{ $d->item->stock < $approvedQty ? 'color:var(--danger)' : '' }}">{{ $d->item->stock }}</b>
                        @if($d->item->stock < $approvedQty)
                          <span class="badge b-danger" style="font-size:10px;margin-left:4px">Kurang</span>
                        @endif
                      </td>
                      <td class="num"><b>{{ $approvedQty }}</b></td>
                      <td class="num">
                        <input type="number" name="distributions[{{ $d->id }}][qty]" x-model.number="qty"
                               min="0" max="{{ $approvedQty }}" class="qty-inp" required>
                      </td>
                      <td>
                        <input type="text" name="distributions[{{ $d->id }}][reduction_reason]"
                               value="{{ old("distributions.{$d->id}.reduction_reason") }}"
                               :required="qty < approved"
                               :placeholder="qty < approved ? 'Wajib diisi (jumlah dikurangi)' : 'Alasan (jika dikurangi)'"
                               :style="qty < approved ? 'width:100%;border-color:var(--danger)' : 'width:100%'">
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </form>
          </div>

          <div class="modal-foot">
            <button type="button" class="btn btn-ghost" @click="openId = null">Batal</button>
            <button type="submit" form="dist-form-{{ $req->id }}" class="btn btn-pri">✓ Proses Distribusi</button>
          </div>
        </div>
      </div>
    </template>
  @endforeach

</div>
@endsection
