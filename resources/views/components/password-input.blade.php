{{--
  Input kata sandi dengan tombol tampil/sembunyi (ikon mata).
  Meneruskan semua atribut (name, placeholder, required, :value, x-model, id, …).
  Butuh: gaya .pw-wrap/.pw-toggle (siatk.css / auth-head) + fungsi togglePassword().
  Prop `meter` = tampilkan indikator kekuatan (JS di app-shell.js).
--}}
@props(['meter' => false])
<div class="pw-wrap">
  <input type="password" {{ $attributes }} @if($meter) data-pw-meter @endif>
  <button type="button" class="pw-toggle" tabindex="-1" onclick="togglePassword(this)"
          aria-label="Tampilkan atau sembunyikan kata sandi">
    <svg class="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
    <svg class="pw-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
  </button>
</div>
@if($meter)
  <div class="pw-meter" aria-hidden="true"><span></span></div>
  <div class="pw-hint t-sub" data-pw-hint aria-live="polite">Minimal 8 karakter, kombinasi huruf &amp; angka.</div>
@endif
