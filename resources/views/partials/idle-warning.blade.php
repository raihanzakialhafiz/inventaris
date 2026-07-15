{{--
  Peringatan sesi hampir berakhir (idle timeout) — dikendalikan public/js/app-shell.js.
  Muncul WARN_MS sebelum logout otomatis; hanya tombol "Tetap Masuk" yang memperpanjang.
--}}
<div id="idle-warning" hidden>
  <div class="modal-overlay" style="display:block"></div>
  <div class="modal" style="display:flex" role="alertdialog" aria-modal="true" aria-labelledby="idle-title">
    <div class="modal-head">
      <h3 id="idle-title">Sesi Hampir Berakhir</h3>
    </div>
    <div class="modal-body">
      <p class="cf-msg">
        Tidak ada aktivitas beberapa saat. Anda akan keluar otomatis dalam
        <b><span id="idle-countdown">60</span> detik</b> demi keamanan.
      </p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn btn-ghost"
        onclick="document.getElementById('idle-logout-form').submit()">Keluar Sekarang</button>
      <button type="button" class="btn btn-pri" data-idle-stay>Tetap Masuk</button>
    </div>
  </div>
</div>
