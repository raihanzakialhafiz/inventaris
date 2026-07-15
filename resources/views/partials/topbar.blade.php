{{-- TOPBAR — toggle sidebar, pencarian global, notifikasi, profil --}}
<header class="topbar">
  {{-- Sidebar toggle --}}
  <button class="topbar-toggle"
          @click="toggle()"
          title="Buka/tutup menu samping" aria-label="Buka atau tutup menu samping">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
      <line x1="3" y1="7"  x2="21" y2="7"/>
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="17" x2="21" y2="17"/>
    </svg>
  </button>

  {{-- Global search — menelusuri seluruh menu --}}
  <form class="topbar-search" method="GET" action="{{ route('search.index') }}" role="search">
    <span class="search-ic">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
    </span>
    <input type="search" name="q" value="{{ request('q') }}"
           placeholder="Cari apa saja — barang, permintaan, pengguna…" x-ref="globalSearch" aria-label="Cari">
    <span class="search-kbd">/</span>
  </form>

  <div class="topbar-right">
    {{-- Notifications --}}
    <div class="dd" x-data="{ open:false }" @click.outside="open=false" @keydown.escape.window="open=false">
      <button type="button" class="icon-btn" @click="open=!open" title="Notifikasi" aria-label="Notifikasi">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9.5a6 6 0 0 1 12 0c0 4.5 1.8 5.5 1.8 5.5H4.2S6 14 6 9.5z"/><path d="M10 19a2 2 0 0 0 4 0"/></svg>
        {{-- Selalu dirender (hidden saat 0) agar bisa disegarkan polling app-shell.js --}}
        <span class="icon-badge" id="notif-badge" @if($unreadNotifCount < 1) hidden @endif>{{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}</span>
      </button>
      <div class="dd-panel notif-panel" x-show="open" x-cloak x-transition.origin.top.right>
        <div class="dd-head">
          <span>Notifikasi</span>
          @if($unreadNotifCount > 0)
            <form method="POST" action="{{ route('notifikasi.readAll') }}">
              @csrf
              <button type="submit" class="dd-count-btn">Tandai semua dibaca</button>
            </form>
          @endif
        </div>
        <div class="notif-list">
          {{-- Hanya notifikasi belum dibaca — semuanya bergaya "unread". --}}
          @forelse($recentNotifs as $n)
            <a href="{{ route('notifikasi.read', $n) }}" class="notif-item unread">
              <span class="notif-dot"></span>
              <div style="min-width:0">
                <div class="notif-msg">{{ $n->message }}</div>
                <div class="notif-time">{{ optional($n->created_at)->diffForHumans() }}</div>
              </div>
            </a>
          @empty
            <div class="notif-empty">Tidak ada notifikasi baru.</div>
          @endforelse
        </div>
        <a href="{{ route('notifikasi.index') }}" class="notif-foot">Lihat semua notifikasi →</a>
      </div>
    </div>

    {{-- Profile --}}
    <div class="dd" x-data="{ open:false }" @click.outside="open=false" @keydown.escape.window="open=false">
      <button type="button" class="profile-btn" @click="open=!open">
        <span class="role-ava" style="background:{{ auth()->user()->roleColor() }}">{{ auth()->user()->initials() }}</span>
        <span class="profile-meta">
          <b>{{ auth()->user()->name }}</b>
          <span>{{ auth()->user()->roleLabel() }}</span>
        </span>
        <span class="chev" :class="{ 'flip': open }">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        </span>
      </button>
      <div class="dd-panel profile-panel" x-show="open" x-cloak x-transition.origin.top.right>
        <div class="profile-card">
          <span class="role-ava lg" style="background:{{ auth()->user()->roleColor() }}">{{ auth()->user()->initials() }}</span>
          <div class="profile-info">
            <b>{{ auth()->user()->name }}</b>
            <span class="mono">{{ auth()->user()->email }}</span>
            <span class="role-badge" style="color:{{ auth()->user()->roleColor() }}">{{ auth()->user()->roleLabel() }}</span>
          </div>
        </div>
        <div class="dd-div"></div>
        <a href="{{ route('profil.edit') }}" class="dd-item">
          <span class="dd-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h9"/><circle cx="16" cy="7" r="2.4"/><path d="M20 7h.5"/><path d="M4 17h.5"/><circle cx="8" cy="17" r="2.4"/><path d="M11 17h9"/></svg></span>
          Pengaturan Akun
        </a>
        <div class="dd-div"></div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="dd-item danger">
            <span class="dd-ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3"/><path d="M10 12h9"/><path d="M15 8l4 4-4 4"/></svg></span>
            Keluar
          </button>
        </form>
      </div>
    </div>
  </div>
</header>
