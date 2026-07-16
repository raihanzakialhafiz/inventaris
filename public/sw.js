/*
 * Kill-switch service worker.
 *
 * Aplikasi ini tidak memakai service worker. Berkas ini ada semata-mata untuk
 * membersihkan registrasi "yatim" yang masih tersimpan di browser sebagian
 * pengguna — peninggalan aplikasi lain yang pernah memakai origin yang sama
 * (di Laragon/`artisan serve`, beberapa proyek kerap berbagi domain atau port).
 *
 * Membiarkan /sw.js menjawab 404 TIDAK membersihkannya: saat pemeriksaan
 * pembaruan gagal, browser justru mempertahankan service worker yang lama.
 * Satu-satunya cara membatalkannya adalah menyajikan skrip yang valid seperti
 * ini, yang membatalkan registrasi dirinya sendiri saat aktif.
 *
 * Jangan hapus berkas ini sampai yakin tidak ada lagi browser yang menyimpan
 * registrasi lama (menghapusnya mengembalikan 404, dan 404 tidak membersihkan
 * apa pun).
 */

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    // Buang seluruh cache yang ditinggalkan service worker lama.
    const keys = await caches.keys();
    await Promise.all(keys.map((key) => caches.delete(key)));

    await self.registration.unregister();

    // Muat ulang tab yang masih dikendalikan agar lepas kendali segera.
    const clients = await self.clients.matchAll({ type: 'window' });
    clients.forEach((client) => client.navigate(client.url));
  })());
});
