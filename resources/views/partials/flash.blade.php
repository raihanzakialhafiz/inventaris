{{--
  Menyuntikkan pesan flash server (success/error/warning/info + error validasi)
  sebagai JSON agar ditampilkan sebagai toast oleh public/js/ui-toast.js.
--}}
@php
  $flash = [
      'success' => array_values(array_filter([session('success')])),
      'error'   => array_values(array_filter([session('error')])),
      'warning' => array_values(array_filter([session('warning')])),
      'info'    => array_values(array_filter([session('info')])),
  ];

  if ($errors->any()) {
      $flash['error'] = array_merge($flash['error'], $errors->all());
  }
@endphp

@if(array_filter($flash))
  <script type="application/json" id="flash-data">@json($flash)</script>
@endif
