<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Dashboard') · {{ setting('app_name', 'SIIB') }}</title>
  @if(setting('favicon'))<link rel="icon" href="{{ asset('storage/'.setting('favicon')) }}">@endif
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/siatk.css') }}?v={{ filemtime(public_path('css/siatk.css')) }}">
  {{-- Alpine di-self-host (versi terkunci 3.15.0) — tanpa dependensi CDN --}}
  <script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<div id="pg-loading"></div>

<div class="app show" x-data="appShell()" :class="{ 'sb-mini': mini, 'sb-open': mobileOpen }">
  {{-- Mobile sidebar backdrop --}}
  <div class="sb-backdrop" @click="mobileOpen = false"></div>

  @include('partials.sidebar')

  {{-- MAIN --}}
  <div class="main">
    @include('partials.topbar')

    <main class="content">
      @yield('content')
    </main>
  </div>
</div>

<div id="toasts"></div>

{{-- Modal konfirmasi global + peringatan idle + payload pesan flash untuk toast --}}
@include('partials.confirm-modal')
@include('partials.idle-warning')
@include('partials.flash')

{{-- Form tersembunyi untuk logout otomatis saat sesi habis (idle timeout) --}}
<form method="POST" action="{{ route('logout') }}" id="idle-logout-form" style="display:none">
  @csrf
  <input type="hidden" name="timeout" value="1">
</form>

{{-- Konfigurasi runtime untuk app-shell.js --}}
<script>
  window.__APP = {
    idleMinutes: {{ max(1, (int) setting('session_timeout', 30)) }},
    pingUrl: @json(route('session.ping')),
    notifCountUrl: @json(route('notifikasi.count')),
  };
</script>
<script src="{{ asset('js/app-shell.js') }}?v={{ filemtime(public_path('js/app-shell.js')) }}"></script>
<script src="{{ asset('js/ui-toast.js') }}?v={{ filemtime(public_path('js/ui-toast.js')) }}"></script>
<script src="{{ asset('js/ui-confirm.js') }}?v={{ filemtime(public_path('js/ui-confirm.js')) }}"></script>
<script src="{{ asset('js/item-picker.js') }}?v={{ filemtime(public_path('js/item-picker.js')) }}"></script>

@stack('scripts')
</body>
</html>
