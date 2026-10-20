const VERSION = 'sigap-sr-v2';
const STATIC_CACHE = `${VERSION}-static`;
const PRECACHE = [
    '/manifest.webmanifest',
    '/icon.svg',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => !key.startsWith(VERSION)).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

function offlineResponse() {
    const html = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Offline · SIGAP-SR</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f8fafc;color:#0f172a;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
.card{max-width:26rem;margin:1.5rem;padding:2rem;border-radius:1.25rem;background:#fff;border:1px solid #e2e8f0;text-align:center;box-shadow:0 10px 30px rgba(15,23,42,.06)}
.logo{width:3.5rem;height:3.5rem;border-radius:1rem;background:#0f766e;color:#fff;display:grid;place-items:center;font-weight:700;font-size:1.1rem;margin:0 auto 1rem}
h1{font-size:1.1rem;margin:0 0 .5rem}
p{font-size:.85rem;line-height:1.6;color:#64748b;margin:0 0 1.25rem}
button{border:0;border-radius:.75rem;background:#0f766e;color:#fff;font-weight:600;padding:.6rem 1rem;font-size:.85rem;cursor:pointer}
</style>
</head>
<body>
<main class="card">
<div class="logo">SR</div>
<h1>Tidak ada koneksi</h1>
<p>SIGAP-SR memerlukan koneksi untuk menampilkan data anak. Sambungkan perangkat ke internet lalu muat ulang halaman.</p>
<button onclick="location.reload()">Muat ulang</button>
</main>
</body>
</html>`;

    return new Response(html, {
        status: 503,
        headers: { 'Content-Type': 'text/html; charset=utf-8' },
    });
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.headers.get('X-Inertia')) return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => offlineResponse()));
        return;
    }

    const isStatic =
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname === '/icon.svg' ||
        url.pathname === '/manifest.webmanifest';

    if (!isStatic) return;

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) return cached;

            return fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                }
                return response;
            });
        }),
    );
});
