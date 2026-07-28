
@php
  $flash = [
      'success' => array_values(array_filter([session('success')])),
      'error'   => array_values(array_filter([session('error')])),
      'warning' => array_values(array_filter([session('warning')])),
      'info'    => array_values(array_filter([session('info')])),
  ];

  // Form yang menampilkan errornya sendiri secara inline (di dalam modal)
  // menandai session 'inline_errors' — jangan digandakan sebagai toast.
  if ($errors->any() && ! session('inline_errors')) {
      $flash['error'] = array_merge($flash['error'], $errors->all());
  }
@endphp

@if(array_filter($flash))
  <script type="application/json" id="flash-data">@json($flash)</script>
@endif
