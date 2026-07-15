/**
 * Toast notification — notifikasi ringkas yang muncul di pojok layar.
 *
 * API global:
 *   toast(message, type = 'ok', ttl = 4500)   // type: ok | err | warn | info
 *
 * Pesan flash dari server (session success/error/warning/info + error validasi)
 * disuntikkan sebagai JSON pada elemen #flash-data lalu ditampilkan otomatis.
 */
(function () {
  'use strict';

  const ICONS = { ok: '✓', err: '✕', warn: '⚠', info: 'ℹ' };
  const DEFAULT_TTL = 4500;

  function container() {
    let el = document.getElementById('toasts');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toasts';
      document.body.appendChild(el);
    }
    return el;
  }

  function dismiss(node) {
    node.style.animation = 'toastOut .2s ease forwards';
    node.addEventListener('animationend', () => node.remove(), { once: true });
  }

  function toast(message, type = 'ok', ttl = DEFAULT_TTL) {
    if (!message) return null;
    if (!(type in ICONS)) type = 'ok';

    const node = document.createElement('div');
    node.className = 'toast ' + type;
    node.setAttribute('role', 'status');
    node.innerHTML =
      '<span class="toast-ic">' + ICONS[type] + '</span>' +
      '<span class="toast-msg"></span>' +
      '<button type="button" class="toast-x" aria-label="Tutup">✕</button>';
    node.querySelector('.toast-msg').textContent = message;

    const timer = window.setTimeout(() => dismiss(node), ttl);
    node.querySelector('.toast-x').addEventListener('click', () => {
      window.clearTimeout(timer);
      dismiss(node);
    });

    container().appendChild(node);
    return node;
  }

  window.toast = toast;

  // Tampilkan pesan flash dari server saat halaman dimuat.
  document.addEventListener('DOMContentLoaded', function () {
    const holder = document.getElementById('flash-data');
    if (!holder) return;

    let flash;
    try {
      flash = JSON.parse(holder.textContent || '{}');
    } catch (e) {
      return;
    }

    // Error dibiarkan lebih lama agar pesan validasi sempat dibaca.
    (flash.success || []).forEach((m) => toast(m, 'ok'));
    (flash.error   || []).forEach((m) => toast(m, 'err', 7000));
    (flash.warning || []).forEach((m) => toast(m, 'warn', 7000));
    (flash.info    || []).forEach((m) => toast(m, 'info'));
  });
})();
