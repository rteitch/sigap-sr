<div align="center">

# SIGAP-SR

**Sistem Informasi Pengasuhan, Pembinaan, dan Evaluasi Sekolah Rakyat**

Aplikasi web untuk membantu sekolah rakyat mencatat presensi, insiden, pembinaan asrama, tindak lanjut, dan laporan perlindungan anak dalam satu ruang kerja yang terbatas akses dan tercatat audit.

Dikembangkan dan dipelihara oleh **Rizal Taufiq Hidayat** · [GitHub](https://github.com/rteitch)

</div>

---

## Ringkasan

SIGAP-SR dirancang untuk kebutuhan operasional harian sekolah berasrama:

- Presensi kelas dan kegiatan asrama, termasuk deteksi siswa tidak ditemukan.
- Pencatatan insiden dengan tingkat keparahan, kerahasiaan, penugasan otomatis, langkah protokol, dan tindak lanjut mingguan.
- Pembinaan asrama: ronde, logbook piket, serah terima shift, dan catatan kejadian.
- Data siswa, wali, kelas, asrama, dan kontak keluarga dengan pembatasan akses berbasis peran.
- Laporan bulanan dengan perlindungan kelompok kecil, ekspor, dan jejak audit.
- Notifikasi dalam aplikasi, impor/ekspor siswa, PWA, dan pencarian operasional.
- Data master terkelola: tahun ajaran, jenjang, kelas, asrama, dan lokasi insiden.
- Branding sekolah dinamis: nama aplikasi, tagline, logo, dan warna utama.

## Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | PHP 8.3, Laravel 13 |
| Frontend | Inertia.js, Vue 3, Tailwind CSS 4, Lucide |
| Build | Vite 8 |
| Database | SQLite bawaan; MySQL/PostgreSQL dapat digunakan |
| Testing | PHPUnit, Playwright |
| PWA | Web manifest dinamis, service worker, halaman offline |

## Persyaratan

- PHP 8.3+ dengan ekstensi `pdo`, `mbstring`, `openssl`, `fileinfo`, `zip`, dan `gd` bila memakai pemrosesan gambar.
- Composer 2.
- Node.js 20+ dan npm.
- Chrome terpasang untuk audit Playwright lokal.
- SQLite (bawaan) atau server database lain.

## Menjalankan Secara Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm ci
npm run build
php artisan serve
```

Di Windows PowerShell, gunakan `copy .env.example .env` sebagai pengganti `cp`.

Buka `http://127.0.0.1:8000`. Lingkungan `local` menampilkan panel akun demo pada halaman login bila `SIGAP_SHOW_DEMO_ACCOUNTS=true`.

### Akun demo

Semua akun memakai kata sandi `password`.

| Peran | Email |
|---|---|
| Administrator | `admin@sigap.test` |
| Kepala sekolah | `kepsek@sigap.test` |
| TPPK | `tppk@sigap.test` |
| Guru BK | `bk@sigap.test` |
| Wali kelas | `walikelas@sigap.test` |
| Wali asrama | `waliasrama@sigap.test` |
| Wali asrama 2 | `waliasrama2@sigap.test` |
| Guru | `guru@sigap.test` |

> Akun demo hanya untuk pengembangan dan pengujian. Di `APP_ENV=production`, panel demo disembunyikan dan seeder menolak berjalan kecuali `SIGAP_ALLOW_DEMO_SEED=true` secara eksplisit.

## Konfigurasi Penting

Tambahkan atau sesuaikan variabel berikut pada `.env`:

```dotenv
APP_NAME=SIGAP-SR
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_VERSION=1.0.0

# Demo
SIGAP_SHOW_DEMO_ACCOUNTS=true
SIGAP_ALLOW_DEMO_SEED=false

# API publik baca-saja
SIGAP_API_KEY=
SIGAP_API_SCHOOL_ID=

# Observability
SIGAP_TELEMETRY_ENABLED=true
SIGAP_HEARTBEAT_TTL=180
SIGAP_METRICS_TOKEN=
SIGAP_SYNTHETIC_ENABLED=false
SIGAP_SYNTHETIC_URL=
SIGAP_SYNTHETIC_TIMEOUT=10
```

Untuk produksi:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sigap.example.sch.id
SIGAP_SHOW_DEMO_ACCOUNTS=false
```

Setelah mengubah konfigurasi, jalankan `php artisan optimize:clear` atau `php artisan config:cache` sesuai strategi deployment.

## Data Master dan Branding

Menu **Administrasi → Data master** menyediakan pengelolaan:

- Tahun ajaran, termasuk satu tahun ajaran aktif.
- Jenjang dan kode jenjang.
- Kelas beserta jenjang, tahun ajaran, dan wali kelas.
- Asrama beserta gender, kapasitas, dan wali asrama.
- Lokasi insiden untuk formulir laporan.

Data dinonaktifkan, bukan dihapus, agar riwayat tetap utuh. Semua perubahan tercatat pada jejak audit.

Menu **Administrasi → Pengaturan** mengatur identitas sekolah, nama aplikasi, tagline, logo, warna utama, ambang perlindungan data agregat, dan SOP siswa tidak ditemukan. Logo disimpan pada disk `branding` di `public/uploads`, tanpa memerlukan symlink.

## Kesehatan, Observability, dan Ketahanan

| Endpoint | Fungsi |
|---|---|
| `GET /health/live` | Liveness; tidak menyentuh database. |
| `GET /health/ready` | Readiness database, cache, dan storage; `503` bila gagal. |
| `GET /health/heartbeat` | Memeriksa heartbeat scheduler berdasarkan TTL. |
| `GET /health/metrics` | Metrik Prometheus; memerlukan Bearer `SIGAP_METRICS_TOKEN`. |

- Setiap respons memiliki `X-Request-ID` untuk korelasi log.
- Log request tidak memuat isi formulir, query string sensitif, atau data pribadi siswa.
- Metrik mencakup jumlah request, status 2xx/4xx/5xx, durasi, login, akses API, pembukaan circuit breaker, dan synthetic check.
- `sigap:heartbeat` dijadwalkan setiap menit.
- `sigap:synthetic-check` dapat diaktifkan untuk memeriksa readiness dari luar.
- `App\Support\CircuitBreaker` menyediakan pola failure threshold, cooldown, dan fallback.
- Rate limit: login `6/menit`, API `60/menit`, health `120/menit` per IP.

Aktifkan scheduler produksi melalui cron:

```cron
* * * * * cd /path/to/sigap-app && php artisan schedule:run >> /dev/null 2>&1
```

## API

Dokumentasi publik tersedia di `/api/docs`; spesifikasi OpenAPI di `/api/openapi.json`.

Endpoint yang tersedia:

- `GET /api/v1/meta` — publik.
- `GET /api/v1/academic-years`
- `GET /api/v1/levels`
- `GET /api/v1/classrooms`
- `GET /api/v1/dorms`
- `GET /api/v1/incident-categories`
- `GET /api/v1/incident-locations`

Endpoint data master memerlukan `X-API-Key` atau `Authorization: Bearer <key>`. API bersifat baca-saja dan tidak mengekspos identitas siswa, isi kasus, atau data sangat rahasia.

```bash
curl https://sigap.example.sch.id/api/v1/levels \
  -H "X-API-Key: $SIGAP_API_KEY" \
  -H "Accept: application/json"
```

## Pengujian

```bash
# PHPUnit
php artisan test

# Gaya kode PHP
vendor/bin/pint --format agent

# Build aset
npm run build

# Audit UI desktop + mobile dengan Chrome terpasang
php artisan migrate:fresh --seed
npm run test:e2e
```

Playwright menguji seluruh halaman utama, halaman detail, formulir, modal data master, penyimpanan branding, overflow responsif, error konsol, dan nama aksesibel tombol.

## Struktur Singkat

```text
app/
  Console/Commands/       Heartbeat dan synthetic monitoring
  Http/Controllers/       Web, admin, health, dan API
  Http/Middleware/        Telemetry, security headers, API key
  Models/                 Siswa, insiden, asrama, data master, audit
  Services/               Branding, insiden, notifikasi, metrik
  Support/                Circuit breaker
database/
  migrations/             Skema dan data master
  seeders/                Data demo deterministik
resources/js/
  Pages/                  Halaman Inertia/Vue
  Layouts/                Shell aplikasi
  Components/             Komponen bersama
routes/
  web.php                 Aplikasi utama dan health
  api.php                 API v1
tests/
  Feature/                Pengujian integrasi
  e2e/                    Audit Playwright
```

## Keamanan dan Privasi

- Kasus `sangat_rahasia` hanya dapat dibuka kepala sekolah, TPPK, dan guru BK; administrator tidak dapat membukanya.
- Profil siswa dibatasi untuk manajemen, wali kelas siswa tersebut, atau wali asrama terkait.
- Data asrama dan presensi asrama dibatasi pada pengguna yang ditugaskan.
- Laporan agregat menyembunyikan kelompok di bawah ambang perlindungan.
- Sanksi keluar/pulang tidak tersedia sampai alur exit M10 tersedia.
- Jangan mengirim data siswa melalui API pihak ketiga tanpa dasar pemrosesan yang sah.
- Gunakan HTTPS, rotasi kunci API/token metrik, backup terenkripsi, dan pembatasan akses server.

## Batasan dan Peta Jalan

Fitur PRD berikut belum diimplementasikan dan perlu diputuskan sebelum dianggap sebagai cakupan produksi penuh:

- Autentikasi dua faktor (2FA).
- Antrean formulir offline penuh untuk insiden, presensi, dan logbook.
- Notifikasi email.
- Pencarian global lintas modul.
- Batas sesi idle dan pencabutan sesi jarak jauh.
- Alur izin pulang/cuti siswa.
- Penandaan alpa berturut-turut.
- Lampiran pada insiden.
- Riwayat perubahan tingkat field.

PWA saat ini menyediakan shell dan halaman offline, bukan sinkronisasi formulir offline.

## Lisensi

Dirilis di bawah [MIT License](LICENSE). Proyek personal Rizal Taufiq Hidayat.

---

<div align="center">

Dibuat untuk memastikan setiap anak terlihat, tercatat, dan terlindungi.

</div>
