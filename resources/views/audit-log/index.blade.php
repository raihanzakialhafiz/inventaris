@extends('layouts.app')
@section('title', 'Audit Log')
@section('page-title', 'Audit Log Sistem')
@section('page-crumb', 'Administrasi')

@section('content')

<div class="page-head" style="display:flex;align-items:center;justify-content:space-between;gap:10px">
  <div><h2>Audit Log Sistem</h2><p>Rekaman seluruh aktivitas pengguna dalam sistem.</p></div>
  <div style="display:flex;gap:8px;flex:0 0 auto">
    <x-export-ttd :pdf="route('audit-log.export', ['format' => 'pdf'] + request()->query())"
                  :excel="route('audit-log.export', ['format' => 'excel'] + request()->query())" />
  </div>
</div>

<form method="GET" x-data x-ref="filterForm" class="filter-bar">
  <input type="search" name="q" value="{{ request('q') }}"
         placeholder="Cari aktivitas, entitas, IP…"
         @input.debounce.400ms="$refs.filterForm.submit()">

  <div style="min-width:180px">
    <x-searchable-select name="user_id" :options="$users" :selected="request('user_id')"
                         placeholder="Semua Pengguna" search-placeholder="Cari pengguna…" :submit-on-change="true" />
  </div>

  <div style="min-width:160px">
    <x-searchable-select name="entity_type" :selected="request('entity_type')"
                         :options="collect($entities)->mapWithKeys(fn($e) => [$e => $e])"
                         placeholder="Semua Entitas" search-placeholder="Cari entitas…" :submit-on-change="true" />
  </div>

  <input type="date" name="date" value="{{ request('date') }}"
         title="Filter tanggal" onchange="this.form.submit()" style="width:150px">

  <span class="filter-spacer"></span>

  @if(request()->anyFilled(['q','user_id','entity_type','date']))
    <a href="{{ route('audit-log.index') }}" class="btn btn-ghost btn-sm">✕ Reset</a>
  @endif
</form>

<div class="card">
  <div class="card-b" style="padding:0">
    @if($logs->count())
      <table>
        <thead><tr>
          <th>Waktu</th>
          <th>Pengguna</th>
          <th>Aktivitas</th>
          <th>Entitas</th>
          <th>ID</th>
          <th>IP</th>
          <th>Detail</th>
        </tr></thead>
        <tbody>
          @foreach($logs as $log)
            <tr>
              <td style="white-space:nowrap;font-size:12px">
                {{ $log->created_at->isoFormat('D MMM Y') }}<br>
                <span class="t-sub">{{ $log->created_at->format('H:i:s') }}</span>
              </td>
              <td>
                @if($log->user)
                  <span class="t-name" style="font-size:13px">{{ $log->user->name }}</span><br>
                  <span class="badge b-neutral" style="font-size:10px">{{ $log->user->role }}</span>
                @else
                  <span class="t-sub">Sistem</span>
                @endif
              </td>
              <td>
                @php
                  $actColor = match(true) {
                    str_contains($log->activity, 'login')    => 'b-primary',
                    str_contains($log->activity, 'logout')   => 'b-neutral',
                    str_contains($log->activity, 'delete') || str_contains($log->activity, 'reject') => 'b-danger',
                    str_contains($log->activity, 'create') || str_contains($log->activity, 'distribusi') => 'b-ok',
                    str_contains($log->activity, 'update') || str_contains($log->activity, 'approve') => 'b-warn',
                    default => 'b-neutral',
                  };
                @endphp
                <span class="badge {{ $actColor }}">{{ $log->activity }}</span>
              </td>
              <td class="t-sub">{{ $log->entity_type ?? '—' }}</td>
              <td class="t-sub">{{ $log->entity_id ?? '—' }}</td>
              <td style="font-size:12px">{{ $log->ip_address ?? '—' }}</td>
              <td>
                @php
                  $detail = '';
                  if ($log->old_values) $detail .= "Before:\n" . json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                  if ($log->new_values) $detail .= "After:\n" . json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                @endphp
                @if($detail !== '')
                  <div x-data="{ open: false }">
                    <button type="button" class="btn btn-ghost btn-sm" @click="open=!open"
                            x-text="open ? '▼ Tutup' : '▶ Detail'" style="font-size:11px">▶ Detail</button>
                    <pre x-show="open" x-cloak
                         style="margin-top:6px;font-size:11px;background:var(--bg);border:1px solid var(--line);border-radius:6px;padding:8px;max-width:360px;overflow:auto;text-align:left;white-space:pre-wrap;word-break:break-all">{{ $detail }}</pre>
                  </div>
                @else
                  <span class="t-sub">—</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div class="pg-bar">
        <x-per-page :per-page="request('per_page', 50)" :options="[25, 50, 100]" />
        <span class="pg-info">
          Menampilkan {{ $logs->firstItem() }}–{{ $logs->lastItem() }} dari {{ number_format($logs->total()) }} entri
        </span>
        {{ $logs->links() }}
      </div>
    @else
      <div class="empty">
        <div class="empty-ic">≡</div>
        <b>Tidak ada log yang ditemukan</b>
        <p>Coba ubah kata kunci pencarian atau filter untuk melihat data lain.</p>
      </div>
    @endif
  </div>
</div>
@endsection
