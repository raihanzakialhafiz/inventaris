{{-- SIDEBAR — brand, navigasi per role, footer --}}
<aside class="sidebar">
  <div class="brand">
    @if(setting('logo'))
      <div class="brand-mark" style="background:#fff;padding:4px">
        <img src="{{ asset('storage/'.setting('logo')) }}" alt="Logo" style="width:100%;height:100%;object-fit:contain">
      </div>
    @else
      <div class="brand-mark">{{ strtoupper(substr(setting('app_name', 'SIIB'), 0, 2)) }}</div>
    @endif
    <div>
      <h1>{{ setting('app_name', 'SIIB') }}</h1>
      <p>{{ setting('institution_name', 'Inventaris Barang') }}</p>
    </div>
  </div>

  @php $role = auth()->user()->role; @endphp

  {{--
    Navigasi dibangun dari satu sumber data agar rapi & tidak berulang.
    - `active`  : pola route untuk state aktif (mis. 'barang.*').
    - `icon`    : nama ikon SVG (lihat components/icon.blade.php).
    - `variant` : warna badge sesuai makna — danger (perlu keputusan),
                  warn (peringatan), info (antrian/informasi).
  --}}
  @php
    $navGroups = [
      ['label' => null, 'items' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'home', 'roles' => ['*']],
      ]],
      ['label' => 'Inventaris', 'items' => [
        ['label' => 'Data Barang',  'route' => 'barang.index',       'active' => 'barang.*',       'icon' => 'box',       'roles' => ['admin', 'petugas_gudang', 'kasubag_umum', 'pimpinan'], 'badge' => $lowStockCount, 'variant' => 'warn'],
        ['label' => 'Barang Masuk', 'route' => 'barang-masuk.index', 'active' => 'barang-masuk.*', 'icon' => 'inbox-in',  'roles' => ['admin', 'petugas_gudang']],
        ['label' => 'Distribusi',   'route' => 'distribusi.index',   'active' => 'distribusi.*',   'icon' => 'send-up',   'roles' => ['admin', 'petugas_gudang'], 'badge' => $queueCount, 'variant' => 'info'],
        ['label' => 'Stock Opname', 'route' => 'stock-opname.index', 'active' => 'stock-opname.*', 'icon' => 'clipboard', 'roles' => ['admin', 'petugas_gudang']],
      ]],
      ['label' => 'Permintaan', 'items' => [
        ['label' => 'Permintaan Barang', 'route' => 'permintaan.index', 'active' => 'permintaan.*', 'icon' => 'file-text', 'roles' => ['admin', 'kepala_bidang', 'petugas_gudang'], 'badge' => $pendingCount, 'variant' => 'danger'],
        // Kasubag: dipisah jadi dua submenu (perlu diproses / sudah diproses).
        ['label' => 'Perlu Diproses', 'route' => 'permintaan.index', 'params' => ['view' => 'masuk'],    'active' => 'permintaan.*', 'activeView' => 'masuk',    'icon' => 'inbox-in',     'roles' => ['kasubag_umum'], 'badge' => $pendingCount, 'variant' => 'danger'],
        ['label' => 'Sudah Diproses', 'route' => 'permintaan.index', 'params' => ['view' => 'diproses'], 'active' => 'permintaan.*', 'activeView' => 'diproses', 'icon' => 'check-circle', 'roles' => ['kasubag_umum']],
        ['label' => 'Sisa Kuota Bidang', 'route' => 'kuota-bidang.index', 'active' => 'kuota-bidang.*', 'icon' => 'gauge', 'roles' => ['kepala_bidang']],
      ]],
      ['label' => 'Laporan', 'items' => [
        ['label' => 'Laporan', 'route' => 'laporan.index', 'active' => 'laporan.*', 'icon' => 'bar-chart', 'roles' => ['admin', 'pimpinan', 'kasubag_umum']],
      ]],
      ['label' => 'Master Data', 'items' => [
        ['label' => 'Kategori',     'route' => 'kategori.index',  'active' => 'kategori.*',  'icon' => 'tag',      'roles' => ['admin']],
        ['label' => 'Satuan',       'route' => 'satuan.index',    'active' => 'satuan.*',    'icon' => 'ruler',    'roles' => ['admin']],
        ['label' => 'Bidang',       'route' => 'bidang.index',    'active' => 'bidang.*',    'icon' => 'building', 'roles' => ['admin']],
        ['label' => 'Supplier',     'route' => 'supplier.index',  'active' => 'supplier.*',  'icon' => 'truck',    'roles' => ['admin']],
        ['label' => 'Kuota Bidang', 'route' => 'kuota.index',     'active' => 'kuota.*',     'icon' => 'target',   'roles' => ['admin']],
      ]],
      ['label' => 'Sistem', 'items' => [
        ['label' => 'Pengguna',     'route' => 'pengguna.index',   'active' => 'pengguna.*',   'icon' => 'users',    'roles' => ['admin']],
        ['label' => 'Audit Log',    'route' => 'audit-log.index',  'active' => 'audit-log.*',  'icon' => 'scroll',   'roles' => ['admin']],
        ['label' => 'Kotak Sampah',    'route' => 'sampah.index',           'active' => 'sampah.*',          'icon' => 'trash',    'roles' => ['admin']],
        ['label' => 'Pengaturan',      'route' => 'pengaturan.index',       'active' => 'pengaturan.index',  'icon' => 'settings', 'roles' => ['admin']],
        ['label' => 'Pengaturan Email','route' => 'pengaturan-email.index', 'active' => 'pengaturan-email.*','icon' => 'mail',     'roles' => ['admin']],
      ]],
      ['label' => 'Akun', 'items' => [
        ['label' => 'Notifikasi',      'route' => 'notifikasi.index', 'active' => 'notifikasi.*', 'icon' => 'bell', 'roles' => ['*'], 'badge' => $unreadNotifCount, 'variant' => 'info'],
        ['label' => 'Pengaturan Akun', 'route' => 'profil.edit',      'active' => 'profil.*',     'icon' => 'user', 'roles' => ['*']],
      ]],
    ];
  @endphp

  <nav class="nav-scroll" aria-label="Menu utama">
    @foreach ($navGroups as $group)
      @php
        $items = array_filter(
          $group['items'],
          fn ($item) => in_array('*', $item['roles'], true) || in_array($role, $item['roles'], true)
        );
      @endphp
      @continue(empty($items))

      <div class="nav-group">
        @if ($group['label'])
          <div class="nav-label">{{ $group['label'] }}</div>
        @endif

        @foreach ($items as $item)
          @php
            $isActive = request()->routeIs($item['active']);
            // Submenu berbasis query (?view=) — bedakan aktif via nilai view.
            if (isset($item['activeView'])) {
                $isActive = $isActive && request('view', 'masuk') === $item['activeView'];
            }
            $href    = route($item['route'], $item['params'] ?? []);
            $badge   = $item['badge'] ?? 0;
            $variant = $item['variant'] ?? 'danger';
          @endphp
          <a href="{{ $href }}"
             class="nav-item {{ $isActive ? 'active' : '' }}"
             @if($isActive) aria-current="page" @endif
             title="{{ $item['label'] }}">
            <span class="ic"><x-icon :name="$item['icon']" width="19" height="19" /></span>
            <span class="nav-text">{{ $item['label'] }}</span>
            {{-- Badge selalu dirender (hidden saat 0) agar polling bisa memunculkannya --}}
            <span class="nav-badge" data-badge-for="{{ $item['route'] }}" data-variant="{{ $variant }}"
                  aria-label="{{ $badge }} perlu perhatian" title="{{ $item['label'] }}: {{ $badge }}"
                  @if ($badge < 1) hidden @endif>{{ $badge > 99 ? '99+' : $badge }}</span>
          </a>
        @endforeach
      </div>
    @endforeach
  </nav>{{-- /.nav-scroll --}}

  <div class="sidebar-foot">
    <span class="foot-ava" style="background:{{ auth()->user()->roleColor() }}">{{ auth()->user()->initials() }}</span>
    <div class="foot-meta">
      <b>{{ auth()->user()->name }}</b>
      <span>{{ auth()->user()->roleLabel() }}</span>
    </div>
  </div>
</aside>
