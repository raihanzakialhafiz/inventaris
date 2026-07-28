{{-- TAB BAR BAWAH — navigasi cepat di ponsel (≤576px). Empat destinasi
     tersering + tombol Menu yang membuka laci sidebar untuk sisanya.
     Berada di dalam scope x-data="appShell()" agar bisa memanggil mobileOpen. --}}
@php $bnRole = auth()->user()->role; @endphp
<nav class="bottomnav" aria-label="Navigasi cepat">
  <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
    <span class="bn-ic"><x-icon name="home" width="20" height="20" /></span>
    Beranda
  </a>

  @if (in_array($bnRole, ['admin', 'petugas_gudang', 'kasubag_umum', 'pimpinan']))
    <a href="{{ route('barang.index') }}" class="{{ request()->routeIs('barang.*') ? 'active' : '' }}">
      <span class="bn-ic"><x-icon name="box" width="20" height="20" /></span>
      Barang
    </a>
  @else
    <a href="{{ route('permintaan.index') }}" class="{{ request()->routeIs('permintaan.*') ? 'active' : '' }}">
      <span class="bn-ic"><x-icon name="file-text" width="20" height="20" /></span>
      Permintaan
    </a>
  @endif

  <a href="{{ route('notifikasi.index') }}" class="{{ request()->routeIs('notifikasi.*') ? 'active' : '' }}">
    <span class="bn-ic"><x-icon name="bell" width="20" height="20" /></span>
    {{-- Badge awal dari server; polling app-shell.js menyegarkan #notif-badge di topbar. --}}
    <span class="bn-badge" @if (($unreadNotifCount ?? 0) < 1) hidden @endif>{{ ($unreadNotifCount ?? 0) > 9 ? '9+' : ($unreadNotifCount ?? 0) }}</span>
    Notifikasi
  </a>

  <button type="button" class="bn-menu" @click="mobileOpen = true" aria-label="Buka menu lengkap">
    <span class="bn-ic">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <line x1="3" y1="7" x2="21" y2="7" />
        <line x1="3" y1="12" x2="21" y2="12" />
        <line x1="3" y1="17" x2="21" y2="17" />
      </svg>
    </span>
    Menu
  </button>
</nav>
