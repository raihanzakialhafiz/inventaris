/**
 * Confirmation modal — pengganti window.confirm() bawaan browser.
 *
 * Pemakaian deklaratif (disarankan):
 *   <form data-confirm="Hapus data ini?" data-confirm-variant="danger" data-confirm-ok="Hapus">
 *   <a href="…" data-confirm="Lanjutkan?">
 *
 * Atribut opsional: data-confirm-title, data-confirm-ok, data-confirm-variant="danger".
 *
 * Pemakaian imperatif:
 *   const ok = await window.uiConfirm({ message, title, okLabel, variant });
 */
(function () {
  'use strict';

  const modal = document.getElementById('confirm-modal');
  if (!modal) return;

  const overlayEls = modal.querySelectorAll('[data-confirm-cancel]');
  const titleEl    = modal.querySelector('[data-confirm-title]');
  const msgEl      = modal.querySelector('[data-confirm-msg]');
  const okBtn      = modal.querySelector('[data-confirm-ok]');

  let resolveCurrent = null;

  function close(result) {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    const cb = resolveCurrent;
    resolveCurrent = null;
    if (cb) cb(result);
  }

  function open(opts = {}) {
    return new Promise((resolve) => {
      resolveCurrent = resolve;
      titleEl.textContent = opts.title   || 'Konfirmasi';
      msgEl.textContent   = opts.message || 'Apakah Anda yakin?';
      okBtn.textContent   = opts.okLabel || 'Ya, Lanjutkan';
      okBtn.className     = 'btn ' + (opts.variant === 'danger' ? 'btn-danger' : 'btn-pri');
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      okBtn.focus();
    });
  }

  function optsFrom(el) {
    return {
      message: el.dataset.confirm,
      title:   el.dataset.confirmTitle,
      okLabel: el.dataset.confirmOk,
      variant: el.dataset.confirmVariant,
    };
  }

  okBtn.addEventListener('click', () => close(true));
  overlayEls.forEach((el) => el.addEventListener('click', () => close(false)));
  document.addEventListener('keydown', (e) => {
    if (modal.hidden) return;
    if (e.key === 'Escape') { close(false); }
    else if (e.key === 'Enter') { e.preventDefault(); close(true); }
  });

  window.uiConfirm = open;

  // ── Intersepsi submit form (fase capture agar mendahului page-loading bar) ──
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form.matches || !form.matches('form[data-confirm]')) return;

    // Sudah dikonfirmasi → biarkan submit berjalan normal.
    if (form.dataset.confirmed === '1') {
      form.dataset.confirmed = '';
      return;
    }

    e.preventDefault();
    e.stopPropagation();

    const submitter = e.submitter;
    open(optsFrom(form)).then((ok) => {
      if (!ok) return;
      form.dataset.confirmed = '1';
      if (typeof form.requestSubmit === 'function') {
        try { form.requestSubmit(submitter || undefined); }
        catch (err) { form.requestSubmit(); }
      } else {
        form.submit();
      }
    });
  }, true);

  // ── Intersepsi klik tautan dengan data-confirm ──
  document.addEventListener('click', function (e) {
    const link = e.target.closest('a[data-confirm]');
    if (!link) return;
    e.preventDefault();
    e.stopPropagation();
    open(optsFrom(link)).then((ok) => {
      if (ok) window.location.href = link.href;
    });
  }, true);
})();
