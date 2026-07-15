@extends('layouts.app')
@section('title', $permintaan->request_no)
@section('page-title', $permintaan->request_no)
@section('page-crumb', 'Permintaan Barang')

@section('content')
<div x-data="{ showReject: false }">

  {{-- Header info --}}
  <div class="card" style="margin-bottom:16px">
    <div class="card-b">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <span class="code" style="font-size:15px">{{ $permintaan->request_no }}</span>
            <x-status-badge :status="$permintaan->status" />
            @if($permintaan->is_flagged)
              <span class="badge b-danger">⚠ Over-Request</span>
            @endif
          </div>
          <div class="t-sub">Pengaju: <b>{{ $permintaan->requester->name }}</b> · {{ $permintaan->department->name }}</div>
          <div class="t-sub">Tanggal: {{ $permintaan->request_date->isoFormat('D MMM Y') }}</div>
          @if($permintaan->approver)
            <div class="t-sub">Diproses oleh: {{ $permintaan->approver->name }} · {{ $permintaan->approved_date?->isoFormat('D MMM Y HH:mm') }}</div>
          @endif
        </div>

        {{-- Aksi Kasubag --}}
        @if($permintaan->status === 'pending' && in_array($user->role, ['kasubag_umum','admin']))
          <div style="display:flex;gap:8px">
            <button class="btn btn-danger btn-sm" @click="showReject=true">✕ Tolak</button>
            <button type="submit" form="form-approve" class="btn btn-ok btn-sm">✓ Setujui</button>
          </div>
        @endif
      </div>

      @if($permintaan->justification)
        <div class="notice info" style="margin-top:10px">
          <span class="ic">ℹ</span>
          <div><b>Justifikasi pengaju:</b> {{ $permintaan->justification }}</div>
        </div>
      @endif
      @if($permintaan->rejection_reason)
        <div class="notice warn" style="margin-top:10px">
          <span class="ic">⚠</span>
          <div><b>Alasan penolakan:</b> {{ $permintaan->rejection_reason }}</div>
        </div>
      @endif
      @if($permintaan->note)
        <div class="notice info" style="margin-top:10px">
          <span class="ic">•</span>
          <div>{{ $permintaan->note }}</div>
        </div>
      @endif
    </div>
  </div>

  {{-- Form Setujui (hidden, disubmit via tombol) --}}
  @if($permintaan->status === 'pending' && in_array($user->role, ['kasubag_umum','admin']))
    <form id="form-approve" method="POST" action="{{ route('permintaan.approve', $permintaan) }}"
          data-confirm="Setujui permintaan {{ $permintaan->request_no }} sesuai jumlah yang disetujui?" data-confirm-ok="Setujui">
      @csrf
  @endif

  {{-- Tabel detail --}}
  <div class="card">
    <div class="card-h"><h3>Daftar Barang</h3></div>
    <div class="card-b" style="padding:0">
      <table>
        <thead><tr>
          <th>Barang</th><th>Satuan</th><th class="num">Diminta</th>
          <th class="num">Disetujui</th><th class="num">Didistribusikan</th>
          @if($permintaan->details->first()?->reduction_reason)<th>Keterangan</th>@endif
        </tr></thead>
        <tbody>
          @foreach($permintaan->details as $d)
            <tr>
              <td>
                <span class="code">{{ $d->item->code }}</span>
                <span class="t-name" style="margin-left:6px">{{ $d->item->name }}</span>
              </td>
              <td>{{ $d->item->unit }}</td>
              <td class="num">{{ $d->quantity_requested }}</td>
              <td class="num">
                @if($permintaan->status === 'pending' && in_array($user->role, ['kasubag_umum','admin']))
                  <input type="number" name="approved_quantities[{{ $d->id }}]"
                         value="{{ $d->quantity_requested }}" min="0" max="{{ $d->quantity_requested }}"
                         class="qty-inp">
                @else
                  {{ $d->quantity_approved ?? '—' }}
                @endif
              </td>
              <td class="num">{{ $d->quantity_distributed > 0 ? $d->quantity_distributed : '—' }}</td>
              @if($permintaan->details->first()?->reduction_reason)
                <td class="t-sub">{{ $d->reduction_reason ?: '—' }}</td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  @if($permintaan->status === 'pending' && in_array($user->role, ['kasubag_umum','admin']))
    </form>
  @endif

  {{-- Distribusi info --}}
  @if($permintaan->stockOuts->count() > 0)
    <div class="card" style="margin-top:16px">
      <div class="card-h"><h3>Riwayat Distribusi</h3></div>
      <div class="card-b" style="padding:0">
        <table>
          <thead><tr><th>No. Distribusi</th><th>Barang</th><th class="num">Jml Distribusi</th><th>Tanggal</th></tr></thead>
          <tbody>
            @foreach($permintaan->stockOuts as $so)
              <tr>
                <td><span class="code">{{ $so->transaction_no }}</span></td>
                <td>{{ $so->item->name }}</td>
                <td class="num">{{ $so->quantity }}</td>
                <td>{{ $so->date->isoFormat('D MMM Y') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  {{-- Modal Tolak --}}
  @if($permintaan->status === 'pending' && in_array($user->role, ['kasubag_umum','admin']))
    <template x-if="showReject">
      <div>
        <div class="modal-overlay" style="display:block" @click="showReject=false"></div>
        <div class="modal" style="display:flex">
          <div class="modal-head">
            <h3>Tolak Permintaan {{ $permintaan->request_no }}</h3>
            <button class="close-btn" @click="showReject=false">✕</button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('permintaan.reject', $permintaan) }}">
              @csrf
              <div class="field">
                <label>Alasan Penolakan <span style="color:var(--danger)">*</span></label>
                <textarea name="rejection_reason" required placeholder="Jelaskan alasan penolakan…" rows="4"></textarea>
              </div>
              <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-ghost" @click="showReject=false">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Permintaan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </template>
  @endif

  <div style="margin-top:16px">
    <a href="{{ route('permintaan.index') }}" class="btn btn-ghost">← Kembali</a>
    @if($permintaan->status === 'disetujui' && in_array($user->role, ['petugas_gudang','admin']))
      <a href="{{ route('distribusi.index', ['proses' => $permintaan->id]) }}" class="btn btn-pri" style="margin-left:8px">Proses Distribusi →</a>
    @endif
  </div>

</div>
@endsection
