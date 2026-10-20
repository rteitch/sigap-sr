<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>Dokumentasi API — {{ config('sigap.app_name') }}</title>
    <style>
        :root { color-scheme: light; --brand: {{ config('sigap.branding.primary_color') }}; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --soft: #f8fafc; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; color: var(--ink); background: #fff; line-height: 1.6; }
        a { color: var(--brand); }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 0 20px; }
        header { border-bottom: 1px solid var(--line); background: linear-gradient(135deg, var(--brand), #0f172a); color: #fff; }
        header .wrap { padding-top: 40px; padding-bottom: 40px; }
        .badge { display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(255,255,255,.35); border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 700; letter-spacing: .02em; }
        h1 { margin: 14px 0 8px; font-size: clamp(28px, 4vw, 42px); line-height: 1.15; }
        header p { max-width: 720px; margin: 0; color: rgba(255,255,255,.85); }
        header nav { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
        header nav a { color: #fff; border: 1px solid rgba(255,255,255,.35); border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 600; text-decoration: none; }
        main { padding: 36px 0 60px; }
        section { margin-bottom: 36px; }
        h2 { font-size: 22px; margin: 0 0 12px; }
        h3 { font-size: 15px; margin: 22px 0 8px; }
        p { margin: 8px 0; color: var(--muted); }
        code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        code { background: var(--soft); border: 1px solid var(--line); border-radius: 5px; padding: 1px 5px; font-size: .9em; color: #0f172a; }
        pre { background: #0f172a; color: #e2e8f0; border-radius: 12px; padding: 16px; overflow-x: auto; font-size: 12.5px; line-height: 1.65; }
        .grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); }
        .card { border: 1px solid var(--line); border-radius: 14px; padding: 16px; background: #fff; }
        .card h3 { margin-top: 0; }
        .endpoint { border: 1px solid var(--line); border-radius: 12px; overflow: hidden; margin-bottom: 10px; }
        .endpoint summary { cursor: pointer; padding: 12px 14px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; background: var(--soft); font-weight: 600; }
        .endpoint .body { padding: 14px; border-top: 1px solid var(--line); }
        .method { display: inline-block; border-radius: 5px; padding: 2px 7px; font-size: 11px; font-weight: 800; color: #fff; background: #059669; letter-spacing: .03em; }
        .path { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; }
        .muted { color: var(--muted); font-size: 13px; }
        .tag { display: inline-block; border: 1px solid var(--line); border-radius: 999px; padding: 1px 8px; font-size: 11px; color: var(--muted); background: #fff; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; border-bottom: 1px solid var(--line); padding: 9px 8px; vertical-align: top; }
        th { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .note { border-left: 4px solid #f59e0b; background: #fffbeb; border-radius: 8px; padding: 12px 14px; color: #78350f; font-size: 13px; }
        footer { border-top: 1px solid var(--line); padding: 22px 0; color: var(--muted); font-size: 12px; }
        @media (max-width: 640px) { header .wrap { padding-top: 28px; padding-bottom: 28px; } main { padding-top: 24px; } }
    </style>
</head>
<body>
<header>
    <div class="wrap">
        <span class="badge">API v1 · {{ config('sigap.app_version') }}</span>
        <h1>Dokumentasi API {{ config('sigap.app_name') }}</h1>
        <p>Integrasi data referensi sekolah: tahun ajaran, jenjang, kelas, asrama, kategori insiden, dan lokasi insiden. API ini tidak mengekspos identitas siswa atau isi kasus.</p>
        <nav>
            <a href="#autentikasi">Autentikasi</a>
            <a href="#endpoint">Endpoint</a>
            <a href="#health">Health</a>
            <a href="{{ url('/api/openapi.json') }}">OpenAPI JSON</a>
            <a href="{{ url('/') }}">Kembali ke aplikasi</a>
        </nav>
    </div>
</header>

<main class="wrap">
    <section id="autentikasi">
        <h2>Autentikasi</h2>
        <p>Endpoint data master memerlukan kunci API. Kirim kunci melalui header <code>X-API-Key</code> atau <code>Authorization: Bearer &lt;key&gt;</code>. Kunci diatur administrator melalui variabel <code>SIGAP_API_KEY</code>; sekolah yang dilayani diatur melalui <code>SIGAP_API_SCHOOL_ID</code>.</p>
        <div class="grid">
            <div class="card">
                <h3>X-API-Key</h3>
                <pre>curl {{ url('/api/v1/levels') }} \
  -H "X-API-Key: $SIGAP_API_KEY" \
  -H "Accept: application/json"</pre>
            </div>
            <div class="card">
                <h3>Bearer token</h3>
                <pre>curl {{ url('/api/v1/levels') }} \
  -H "Authorization: Bearer $SIGAP_API_KEY" \
  -H "Accept: application/json"</pre>
            </div>
        </div>
        <p class="note">Jangan menaruh kunci API pada kode frontend publik. Simpan kunci di server atau secret manager. Batas bawaan: 60 permintaan per menit per alamat IP.</p>
    </section>

    <section id="endpoint">
        <h2>Endpoint data master</h2>
        <p>Semua endpoint mengembalikan <code>data</code> berupa array dan <code>meta.count</code> serta <code>meta.generated_at</code>. Data dibatasi pada sekolah yang dikonfigurasi.</p>

        <details class="endpoint" open>
            <summary><span class="method">GET</span><span class="path">/api/v1/meta</span><span class="tag">Publik</span></summary>
            <div class="body">
                <p>Metadata aplikasi, versi, sekolah, pengembang, lisensi, dan tautan dokumentasi. Endpoint ini tidak memerlukan kunci API.</p>
            </div>
        </details>

        <details class="endpoint">
            <summary><span class="method">GET</span><span class="path">/api/v1/academic-years</span><span class="tag">API key</span></summary>
            <div class="body">
                <p>Daftar tahun ajaran, termasuk tanggal mulai/selesai dan penanda tahun ajaran aktif.</p>
                <pre>{
  "data": [
    { "id": 1, "name": "2025/2026", "start_date": "2025-07-01", "end_date": "2026-06-30", "is_active": true }
  ],
  "meta": { "count": 1, "generated_at": "2026-10-20T08:00:00+07:00" }
}</pre>
            </div>
        </details>

        <details class="endpoint">
            <summary><span class="method">GET</span><span class="path">/api/v1/levels</span><span class="tag">API key</span></summary>
            <div class="body">
                <p>Daftar jenjang beserta kode, nama, urutan, dan status aktif.</p>
            </div>
        </details>

        <details class="endpoint">
            <summary><span class="method">GET</span><span class="path">/api/v1/classrooms</span><span class="tag">API key</span></summary>
            <div class="body">
                <p>Kelas aktif beserta jenjang dan tahun ajaran. Nama wali kelas dan data siswa tidak disertakan.</p>
            </div>
        </details>

        <details class="endpoint">
            <summary><span class="method">GET</span><span class="path">/api/v1/dorms</span><span class="tag">API key</span></summary>
            <div class="body">
                <p>Asrama aktif beserta gender dan kapasitas. Data penghuni tidak disertakan.</p>
            </div>
        </details>

        <details class="endpoint">
            <summary><span class="method">GET</span><span class="path">/api/v1/incident-categories</span><span class="tag">API key</span></summary>
            <div class="body">
                <p>Kategori insiden aktif milik sekolah dan kategori global. Kerahasiaan awal disertakan agar integrasi dapat menampilkan peringatan.</p>
            </div>
        </details>

        <details class="endpoint">
            <summary><span class="method">GET</span><span class="path">/api/v1/incident-locations</span><span class="tag">API key</span></summary>
            <div class="body">
                <p>Lokasi insiden aktif yang dapat dipakai pada formulir eksternal.</p>
            </div>
        </details>

        <h3>Format error</h3>
        <table>
            <thead><tr><th>Status</th><th>Arti</th></tr></thead>
            <tbody>
                <tr><td><code>401</code></td><td>Kunci API tidak valid atau tidak dikirim.</td></tr>
                <tr><td><code>429</code></td><td>Melebihi batas permintaan. Gunakan header <code>Retry-After</code>.</td></tr>
                <tr><td><code>503</code></td><td>API belum dikonfigurasi atau sekolah tidak ditemukan.</td></tr>
            </tbody>
        </table>
    </section>

    <section id="health">
        <h2>Health dan observability</h2>
        <div class="grid">
            <div class="card">
                <h3><code>/health/live</code></h3>
                <p>Liveness. Tidak menyentuh database. Gunakan untuk restart container.</p>
            </div>
            <div class="card">
                <h3><code>/health/ready</code></h3>
                <p>Readiness. Memeriksa database, cache, dan storage. Status 503 bila salah satu gagal.</p>
            </div>
            <div class="card">
                <h3><code>/health/heartbeat</code></h3>
                <p>Memastikan scheduler menjalankan heartbeat setiap menit. Status 503 bila basi.</p>
            </div>
            <div class="card">
                <h3><code>/health/metrics</code></h3>
                <p>Metrik Prometheus. Memerlukan Bearer token <code>SIGAP_METRICS_TOKEN</code>.</p>
            </div>
        </div>
        <p class="note">Setiap respons memiliki header <code>X-Request-ID</code>. Sertakan ID tersebut saat melaporkan masalah agar log dapat ditelusuri tanpa membuka data pribadi.</p>
    </section>

    <section>
        <h2>Integritas dan batasan</h2>
        <ul>
            <li>API hanya membaca data referensi; operasi tulis tetap melalui antarmuka SIGAP-SR dengan autentikasi pengguna.</li>
            <li>Data sangat rahasia, identitas siswa, dan isi kasus tidak tersedia melalui API ini.</li>
            <li>Gunakan HTTPS di produksi. Kunci API dan token metrik tidak boleh dikirim melalui koneksi tanpa TLS.</li>
            <li>Perubahan data master langsung terlihat pada respons berikutnya; tidak ada cache data master di sisi API.</li>
        </ul>
    </section>
</main>

<footer>
    <div class="wrap">
        {{ config('sigap.app_name') }} {{ config('sigap.app_version') }} · Lisensi {{ config('sigap.license') }} ·
        Dikembangkan oleh <a href="{{ config('sigap.developer.github') }}">{{ config('sigap.developer.name') }}</a>
    </div>
</footer>
</body>
</html>
