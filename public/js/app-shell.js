/**
 * Perilaku shell aplikasi (layout utama):
 *  - appShell(): state sidebar (mini/mobile) untuk Alpine
 *  - hotkey "/" fokus ke pencarian global
 *  - idle session timeout + keep-alive ping
 *  - page loading bar
 *
 * Konfigurasi dibaca dari window.__APP (disuntik layout):
 *   { idleMinutes: number, pingUrl: string }
 */
function appShell() {
  const isMobile = () => window.innerWidth < 861;
  const saved    = localStorage.getItem('sb-mini');
  return {
    mini:       !isMobile() && saved === '1',
    mobileOpen: false,
    toggle() {
      if (isMobile()) {
        this.mobileOpen = !this.mobileOpen;
      } else {
        this.mini = !this.mini;
        localStorage.setItem('sb-mini', this.mini ? '1' : '0');
      }
    },
  };
}

// Tampil/sembunyi kata sandi — dipakai komponen <x-password-input>.
function togglePassword(btn) {
  const input = btn.parentElement.querySelector('input');
  if (!input) return;
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.classList.toggle('is-visible', show);
}
window.togglePassword = togglePassword;

// "/" fokus ke pencarian global (diabaikan saat sedang mengetik di field lain)
document.addEventListener('keydown', function (e) {
  if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
  const t = e.target;
  if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
  const box = document.querySelector('.topbar-search input');
  if (box) { e.preventDefault(); box.focus(); }
});

// Idle session timeout — peringatan hitung mundur dulu, baru logout otomatis.
(function () {
  const cfg = window.__APP || {};
  const IDLE_MS    = Math.max(1, cfg.idleMinutes || 30) * 60 * 1000;
  // Lama peringatan tampil sebelum logout: 60 dtk (lebih pendek bila timeout singkat).
  const WARN_MS    = Math.min(60000, Math.max(15000, IDLE_MS / 3));
  const PING_URL   = cfg.pingUrl;
  const PING_EVERY = Math.max(60000, IDLE_MS / 2); // segarkan sesi server sebelum kedaluwarsa
  let warnTimer  = null;
  let countTimer = null;
  let warning    = false;
  let lastPing   = Date.now();

  const modal = document.getElementById('idle-warning');
  const angka = document.getElementById('idle-countdown');

  function logoutIdle() {
    const form = document.getElementById('idle-logout-form');
    if (form) form.submit();
  }
  function keepAlive(force) {
    if (!PING_URL || (!force && Date.now() - lastPing < PING_EVERY)) return;
    lastPing = Date.now();
    fetch(PING_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).catch(() => {});
  }
  function showWarning() {
    if (!modal) { logoutIdle(); return; } // fallback bila partial tidak ada
    warning = true;
    modal.hidden = false;
    let sisa = Math.round(WARN_MS / 1000);
    if (angka) angka.textContent = sisa;
    countTimer = setInterval(() => {
      sisa--;
      if (angka) angka.textContent = Math.max(0, sisa);
      if (sisa <= 0) { clearInterval(countTimer); logoutIdle(); }
    }, 1000);
  }
  function stay() {
    warning = false;
    if (modal) modal.hidden = true;
    clearInterval(countTimer);
    keepAlive(true); // segarkan sesi server segera
    resetIdle();
  }
  function resetIdle() {
    if (warning) return; // saat peringatan tampil, hanya tombol "Tetap Masuk" yang memperpanjang
    clearTimeout(warnTimer);
    warnTimer = setTimeout(showWarning, Math.max(1000, IDLE_MS - WARN_MS));
    keepAlive();
  }
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-idle-stay]')) stay();
  });
  ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach((ev) =>
    document.addEventListener(ev, resetIdle, { passive: true }));
  resetIdle();
})();

// Indikator kekuatan kata sandi — input dengan atribut [data-pw-meter].
(function () {
  const LABELS = ['', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];
  const COLORS = ['#E2E8F0', '#DC2626', '#D97706', '#0D9488', '#0F766E'];

  function score(v) {
    if (!v) return 0;
    let s = 0;
    if (v.length >= 8) s++;
    if (/[a-zA-Z]/.test(v) && /[0-9]/.test(v)) s++;              // kebijakan minimum sistem
    if (v.length >= 12) s++;
    if (/[^a-zA-Z0-9]/.test(v) || (/[a-z]/.test(v) && /[A-Z]/.test(v))) s++;
    return s;
  }

  document.addEventListener('input', (e) => {
    const inp = e.target;
    if (!inp.matches || !inp.matches('[data-pw-meter]')) return;
    const wrap  = inp.closest('.pw-wrap');
    const meter = wrap && wrap.nextElementSibling;
    if (!meter || !meter.classList.contains('pw-meter')) return;

    const s   = score(inp.value);
    const bar = meter.firstElementChild;
    bar.style.width = (s * 25) + '%';
    bar.style.background = COLORS[s];

    const hint = meter.nextElementSibling;
    if (hint && hint.hasAttribute('data-pw-hint')) {
      hint.textContent = inp.value
        ? (LABELS[s] || 'Lemah') + ' — minimal 8 karakter, kombinasi huruf & angka.'
        : 'Minimal 8 karakter, kombinasi huruf & angka.';
    }
  });
})();

// Badge notifikasi — disegarkan berkala tanpa reload halaman.
(function () {
  const cfg = window.__APP || {};
  if (!cfg.notifCountUrl) return;

  async function refresh() {
    try {
      const r = await fetch(cfg.notifCountUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin',
      });
      if (!r.ok) return; // sesi habis / server sibuk — biarkan apa adanya
      const { unread } = await r.json();
      // Topbar sempit → 9+; sidebar lebih lega → 99+.
      document.querySelectorAll('#notif-badge').forEach((el) => {
        el.hidden = !(unread > 0);
        el.textContent = unread > 9 ? '9+' : unread;
      });
      document.querySelectorAll('[data-badge-for="notifikasi.index"]').forEach((el) => {
        el.hidden = !(unread > 0);
        el.textContent = unread > 99 ? '99+' : unread;
      });
    } catch (e) { /* offline — dicoba lagi interval berikutnya */ }
  }

  setInterval(refresh, 60000);
})();

// Page loading bar
(function () {
  const bar = document.getElementById('pg-loading');
  if (!bar) return;
  let safety = null;
  function start() {
    bar.className = 'run';
    // Jaring pengaman: bila navigasi tak pernah terjadi (mis. unduhan yang lolos
    // filter), bar tetap dibersihkan setelah 12 dtk alih-alih macet selamanya.
    clearTimeout(safety);
    safety = setTimeout(done, 12000);
  }
  function done()  { clearTimeout(safety); bar.style.width = '100%'; setTimeout(() => { bar.style.opacity = 0; setTimeout(() => { bar.className = ''; bar.style.width = ''; bar.style.opacity = ''; }, 400); }, 200); }
  // Unduhan (ekspor PDF/Excel) tidak berpindah halaman → jangan nyalakan bar,
  // karena 'pageshow' tak akan pernah membersihkannya.
  const isDownload = (a) => a.hasAttribute('download') || /\/export(\/|$|\?)|\.(pdf|xlsx|xls|csv)(\?|$)/i.test(a.getAttribute('href') || '');
  document.addEventListener('submit', start);
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a');
    if (a && a.href && !a.href.startsWith('#') && !a.target && a.hostname === location.hostname && !isDownload(a)) start();
  });
  window.addEventListener('pageshow', done);
})();

// Cegah double-submit — nonaktifkan tombol submit sesaat setelah form terkirim.
(function () {
  document.addEventListener('submit', function (e) {
    // Form yang masih menunggu konfirmasi (ui-confirm mem-preventDefault di fase
    // capture) belum benar-benar terkirim — jangan dikunci.
    if (e.defaultPrevented) return;
    const form = e.target;
    if (!form.matches || !form.matches('form')) return;

    // Kunci setelah tick berjalan agar nilai tombol tetap ikut terkirim.
    setTimeout(() => {
      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((b) => {
        if (b.disabled) return;
        b.disabled = true;
        b.dataset.reenable = '1';
        // Beri umpan balik teks hanya untuk tombol teks murni (bukan tombol ikon).
        if (b.tagName === 'BUTTON' && b.children.length === 0 && b.textContent.trim().length > 3) {
          b.dataset.label = b.innerHTML;
          b.textContent = 'Menyimpan…';
        }
      });
    }, 0);
  });

  // Pulihkan tombol saat halaman ditampilkan lagi (tombol Back / bfcache).
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('[data-reenable="1"]').forEach((b) => {
      b.disabled = false;
      b.removeAttribute('data-reenable');
      if (b.dataset.label != null) { b.innerHTML = b.dataset.label; b.removeAttribute('data-label'); }
    });
  });
})();
