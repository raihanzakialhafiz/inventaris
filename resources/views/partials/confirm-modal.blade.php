{{--
  Modal konfirmasi global (satu instance, dikendalikan public/js/ui-confirm.js).
  Dipicu otomatis oleh <form data-confirm="…"> / <a data-confirm="…">,
  atau secara imperatif via window.uiConfirm({ message, title, okLabel, variant }).
--}}
<div id="confirm-modal" hidden aria-hidden="true">
  <div class="modal-overlay" style="display:block" data-confirm-cancel></div>
  <div class="modal" style="display:flex" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title">
    <div class="modal-head">
      <h3 id="confirm-title" data-confirm-title>Konfirmasi</h3>
      <button type="button" class="close-btn" data-confirm-cancel aria-label="Tutup"><x-icon name="x" width="16" height="16" /></button>
    </div>
    <div class="modal-body">
      <p class="cf-msg" data-confirm-msg>Apakah Anda yakin?</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn btn-ghost" data-confirm-cancel>Batal</button>
      <button type="button" class="btn btn-pri" data-confirm-ok>Ya, Lanjutkan</button>
    </div>
  </div>
</div>
