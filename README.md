<div align="center">

<img src="public/icon.svg" width="108" height="108" alt="SIGAP-SR Logo" style="border-radius: 24px; box-shadow: 0 8px 24px rgba(15, 118, 110, 0.25);" />

# SIGAP-SR

**Sistem Informasi Pengasuhan, Pembinaan, dan Evaluasi Sekolah Rakyat**

Aplikasi web ramah anak dan berstandar produksi untuk membantu sekolah berasrama mencatat presensi, insiden, pembinaan asrama, tindak lanjut, dan laporan perlindungan anak dalam satu ruang kerja yang terbatas akses, kebal *race condition*, dan tercatat audit penuh.

[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![FrankenPHP Ready](https://img.shields.io/badge/FrankenPHP-HTTP%2F3%20Ready-00ADD8?style=flat&logo=caddy&logoColor=white)](https://frankenphp.dev)
[![Vue 3](https://img.shields.io/badge/Vue-3-4FC08D?style=flat&logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![Inertia.js v3](https://img.shields.io/badge/Inertia.js-v3-9553E9?style=flat&logo=inertia&logoColor=white)](https://inertiajs.com)
[![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=flat&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![PHPUnit 50/50 Passed](https://img.shields.io/badge/PHPUnit-50%2F50%20Passed-10b981?style=flat&logo=php)](https://phpunit.de)
[![Playwright 39/39 Passed](https://img.shields.io/badge/Playwright-39%2F39%20Passed-2EAD33?style=flat&logo=playwright&logoColor=white)](https://playwright.dev)
[![Production Ready](https://img.shields.io/badge/Production-Ready-0f766e?style=flat)](https://github.com/rteitch/sigap-sr)
[![License: MIT](https://img.shields.io/badge/License-MIT-teal.svg)](LICENSE)

Dikembangkan dan dipelihara oleh **Rizal Taufiq Hidayat** · [GitHub](https://github.com/rteitch)

</div>

---

## 📸 Galeri Antarmuka Aplikasi (Live Showcase)

Berikut adalah tangkapan layar tampilan antarmuka SIGAP-SR pada berbagai menu operasional:

### 🌟 Tampilan Utama (Hero Showcase)

| 📊 Dashboard Terpadu & Statistik Perlindungan | 🎓 Direktori Siswa & Avatar Identitas Santri |
| :---: | :---: |
| <img src="docs/screenshots/02-dashboard.png" width="100%" alt="Dashboard SIGAP-SR" /> | <img src="docs/screenshots/04-direktori-siswa.png" width="100%" alt="Direktori Siswa & Avatar" /> |

<details open>
<summary><b>📂 Klik untuk melihat galeri lengkap seluruh modul aplikasi...</b></summary>
<br>

#### 1. Manajemen Santri & Presensi Harian

| Formulir Santri, Usia Otomatis & Foto | Presensi Santri & Deteksi Siswa Tidak Ditemukan |
| :---: | :---: |
| <img src="docs/screenshots/05-form-siswa-foto.png" width="100%" alt="Formulir Santri & Unggah Foto" /> | <img src="docs/screenshots/06-presensi-santri.png" width="100%" alt="Presensi Harian Santri" /> |
| *Unggah foto terintegrasi avatar, hitung usia dinamis, dan pemilihan kelas.* | *Pencatatan sesi apel, makan, kelas dengan deteksi santri hilang otomatis.* |

#### 2. Perlindungan Anak & Kehidupan Asrama

| Manajemen Kasus & Riwayat Insiden | Pelaporan Insiden & Protokol Tindak Lanjut |
| :---: | :---: |
| <img src="docs/screenshots/07-daftar-insiden.png" width="100%" alt="Daftar Insiden Perlindungan Anak" /> | <img src="docs/screenshots/08-lapor-insiden.png" width="100%" alt="Formulir Pelaporan Kasus" /> |
| *Filter tingkat keparahan, jalur rahasia TPPK, dan status investigasi.* | *Pelaporan berbasis fakta teramati, checklist protokol, dan penugasan tim.* |

| Pembinaan Asrama, Ronde Malam & Logbook | Laporan Statistik Bulanan & Ekspor XLSX |
| :---: | :---: |
| <img src="docs/screenshots/09-pembinaan-asrama.png" width="100%" alt="Pembinaan Asrama" /> | <img src="docs/screenshots/10-laporan-bulanan.png" width="100%" alt="Laporan Bulanan" /> |
| *Checklist ronda malam, log pergantian shift pembina, dan catatan kejadian.* | *Agregasi tren santri, perlindungan kelompok kecil, dan ekspor data audit.* |

#### 3. Manajemen Tenaga Pendidik & Administrasi

| Direktori Guru, Wali Kelas & Wali Asrama | Kustomisasi Identitas & Branding Sekolah |
| :---: | :---: |
| <img src="docs/screenshots/11-guru-dan-staf.png" width="100%" alt="Manajemen Guru & Staf" /> | <img src="docs/screenshots/12-branding-sekolah.png" width="100%" alt="Branding Sekolah" /> |
| *Manajemen peran, penugasan kelas perwalian, dan asrama asuh binaan.* | *Kustomisasi nama sekolah, tagline, warna tema, dan logo aplikasi dinamis.* |

#### 4. Keamanan Akun & Kepatuhan Regulasi

| Halaman Autentikasi & Akun Uji Coba | Pengaturan Profil & Keamanan Mandiri |
| :---: | :---: |
| <img src="docs/screenshots/01-login.png" width="100%" alt="Halaman Login" /> | <img src="docs/screenshots/03-profil.png" width="100%" alt="Pengaturan Profil" /> |
| *Desain ramah anak, layout teroptimasi tanpa overlap, dan switcher demo.* | *Pembaruan kontak WhatsApp, ganti kata sandi, dan rekam jejak audit.* |

| Kebijakan Privasi (Kepatuhan UU PDP No. 27/2022) | Persyaratan Layanan Satuan Pendidikan Berasrama |
| :---: | :---: |
| <img src="docs/screenshots/13-kebijakan-privasi.png" width="100%" alt="Kebijakan Privasi" /> | <img src="docs/screenshots/14-persyaratan-layanan.png" width="100%" alt="Persyaratan Layanan" /> |
| *Standar perlindungan data pribadi anak, hak orang tua, dan pseudonimisasi.* | *Kode etik staf, larangan sebar tangkapan layar kasus, dan integritas presensi.* |

</details>

---

## 🎨 Filosofi Logo & Identitas Ramah ("Caring & Growth")

Logo **SIGAP-SR** dirancang dengan filosofi kehangatan, pengasuhan, dan perlindungan holistik bagi anak-anak di sekolah berasrama:
- **Rangkulan Asuh (Embracing Care)**: Sayap rangkulan lembut yang melingkari anak, menghadirkan rasa aman dari perundungan, kekerasan, maupun penelantaran.
- **Buku Masa Depan (Book of Growth)**: Fondasi terbuka yang melambangkan pendidikan karakter, ilmu pengetahuan, dan bimbingan guru pamong.
- **Tunas Bertumbuh (Sprout of Hope)**: Tunas daun hijau emerald yang tumbuh di tengah buku, mencerminkan potensi setiap anak yang mekar optimal.
- **Inti Hati Emas (Golden Heart of Care)**: Pusat hangat berwarna emas yang memancarkan ketulusan, kasih sayang, dan pendampingan tanpa stigma.

Logo hadir dalam format vektor SVG murni (`public/icon.svg`), didukung binary `public/favicon.ico` multi-resolusi riil (16×16, 32×32, 48×48 px) dan aset PWA adaptif (`icon-192.png` & `icon-512.png`).

---

## 🚀 Ringkasan Fitur Unggulan

- **Identitas Santri, Usia & Avatar Dinamis**:
  - Kolom `photo_path` pada tabel `student_identities` (prinsip *Privacy by Design* — identitas langsung anak dipisahkan dari tabel analitik operasional).
  - Unggah foto dengan kompresi otomatis dan pratinjau langsung.
  - Avatar dinamis tampil di tabel direktori siswa, form santri, profil detail, dan presensi harian.
  - Kalkulasi usia santri otomatis dari tanggal lahir (misal: `14 tahun`).
- **Manajemen Guru & Tenaga Kependidikan (`/admin/users`)**:
  - Filter cepat peran (*Semua Staf*, *Guru & Wali Kelas*, *Wali Asrama*, *Manajemen & TPPK*).
  - Eager-loading relasi penugasan kelas perwalian (`homeroomClasses`) dan asrama binaan (`dormAssignments`).
- **Presensi Harian & Deteksi Santri Hilang**:
  - Pencatatan kehadiran per kelas dan kegiatan asrama (apel pagi, makan, apel malam, jam tidur).
  - Deteksi otomatis santri tidak ditemukan (*missing student*) yang langsung dieskalasikan ke insiden darurat.
- **Manajemen Kasus & Perlindungan Anak (TPPK & Kepsek)**:
  - Pelaporan berbasis bahasa fakta teramati, 4 tingkat keparahan, jalur rahasia (*strictly confidential*).
  - Rekomendasi checklist protokol penanganan darurat otomatis.
- **Ronde Asrama & Logbook Pergantian Shift**:
  - Checklist ronda malam asrama, serah terima piket pembina, dan logbook insiden asrama.
- **Kepatuhan Hukum Satuan Pendidikan**:
  - **Kebijakan Privasi (`/kebijakan-privasi`)**: Kepatuhan terhadap UU No. 27/2022 (UU PDP) dan Permendikbudristek No. 46/2023.
  - **Persyaratan Layanan (`/persyaratan-layanan`)**: Standar etika kerahasiaan data anak bagi seluruh tenaga kependidikan.
- **Desain Bebas Overlap & Ramah Pengguna**:
  - Jarak teks terhadap ikon form minimal 40px terbukti matematis via audit visual Playwright.
  - Dropdown `<select>` memiliki padding kanan yang mencegah tabrakan dengan panah bawaan browser.

---

## ⚡ Pilihan Stack Server: Nginx vs FrankenPHP

SIGAP-SR kini mendukung **dua pilihan server modern** kelas produksi:

| Komponen | Stack 1: Nginx + PHP-FPM (Default) | Stack 2: FrankenPHP (Next-Gen Caddy) |
| :--- | :--- | :--- |
| **Server Engine** | Nginx Web Server + PHP 8.3 FPM | **FrankenPHP** (Caddy Server berbasis Go) |
| **Protokol HTTP** | HTTP/1.1 & HTTP/2 | **HTTP/1.1, HTTP/2, dan HTTP/3 (QUIC)** |
| **Kompresi Stream** | Gzip (Level 6) | **Zstandard (zstd)** + Gzip otomatis |
| **Sertifikat SSL** | Manual / Certbot Reverse Proxy | **Automatic HTTPS** (Let's Encrypt / ZeroSSL) |
| **Model Eksekusi** | FastCGI via Unix Socket / TCP | In-Process SAPI / **Worker Mode Ready** |
| **Perintah Cepat** | `.\deploy-docker.ps1 up` | `.\deploy-docker.ps1 up -Server frankenphp` |

---

## 🛠️ Panduan Deployment Cepat

SIGAP-SR menyediakan skrip otomasi deployment yang siap pakai untuk Windows (PowerShell) maupun Linux/macOS/Server (Bash):

```
├── deploy-local.ps1              # Runner lokal teroptimasi untuk Windows PowerShell
├── deploy-local.sh               # Runner lokal teroptimasi untuk Linux / macOS / WSL
├── deploy-docker.ps1             # Orkestrasi Docker (Nginx / FrankenPHP) untuk Windows
├── deploy-docker.sh              # Orkestrasi Docker (Nginx / FrankenPHP) untuk Linux/macOS
├── Dockerfile                    # Multi-stage image Nginx + PHP 8.3 FPM (~110MB)
├── Dockerfile.frankenphp         # Multi-stage image FrankenPHP Caddy (~140MB)
├── docker-compose.yml            # Orkestrasi Nginx Stack dengan volume persisten
├── docker-compose.frankenphp.yml # Orkestrasi FrankenPHP Stack (HTTP/3 & zstd)
└── docker/
    ├── Caddyfile                 # Konfigurasi Caddy untuk FrankenPHP
    ├── nginx.conf                # Konfigurasi Nginx produksi
    ├── php.ini                   # OPcache 256MB + JIT + Memory tuning
    ├── supervisord.conf          # Supervisor process manager
    ├── docker-entrypoint.sh      # Entrypoint startup Nginx
    └── frankenphp-entrypoint.sh  # Entrypoint startup FrankenPHP
```

### Opsi 1: Menjalankan di Mesin Lokal (Baremetal Host)

Skrip lokal secara otomatis memvalidasi PHP 8.3+, Composer, Node.js, menyiapkan `.env` & SQLite, mengompilasi aset Vite, menjalankan migrasi database, dan mengaktifkan optimasi cache produksi:

**Di Windows (PowerShell):**
```powershell
# 1. Jalankan mode produksi teroptimasi penuh (Default: port 8000)
.\deploy-local.ps1

# 2. Jalankan mode development dengan hot-reload
.\deploy-local.ps1 -Mode Development

# 3. Kustomisasi port dan sertakan database seeder (akun demo)
.\deploy-local.ps1 -Port 8080 -Seed

# 4. Reset migrasi database dari awal
.\deploy-local.ps1 -Fresh -Seed
```

**Di Linux / macOS / WSL (Bash):**
```bash
chmod +x deploy-local.sh

# 1. Jalankan mode produksi teroptimasi penuh
./deploy-local.sh

# 2. Jalankan mode development (hot-reload)
./deploy-local.sh --mode=dev

# 3. Kustomisasi port dan seeder
./deploy-local.sh --port=8080 --seed
```

---

### Opsi 2: Menjalankan dengan Docker (Nginx + PHP 8.3 FPM)

**Di Windows (PowerShell):**
```powershell
# Jalankan kontainer dan tunggu healthcheck sehat
.\deploy-docker.ps1 up

# Cek status kesehatan sistem & probe database/cache/storage
.\deploy-docker.ps1 status

# Lihat log aktif kontainer
.\deploy-docker.ps1 logs

# Jalankan perintah artisan di dalam kontainer
.\deploy-docker.ps1 artisan "route:list"
.\deploy-docker.ps1 artisan "tinker"

# Jalankan seeder akun demo
.\deploy-docker.ps1 seed

# Hentikan kontainer
.\deploy-docker.ps1 down
```

**Di Linux / macOS / WSL (Bash):**
```bash
chmod +x deploy-docker.sh

./deploy-docker.sh up
./deploy-docker.sh status
./deploy-docker.sh logs
./deploy-docker.sh artisan route:list
./deploy-docker.sh down
```

---

### Opsi 3: Menjalankan dengan Docker (FrankenPHP Caddy HTTP/3)

Untuk performa puncak dengan dukungan HTTP/3 dan kompresi Zstandard:

**Di Windows (PowerShell):**
```powershell
# Jalankan kontainer FrankenPHP
.\deploy-docker.ps1 up -Server frankenphp

# Cek status kesehatan
.\deploy-docker.ps1 status -Server frankenphp

# Pantau log aktif FrankenPHP
.\deploy-docker.ps1 logs -Server frankenphp

# Hentikan kontainer FrankenPHP
.\deploy-docker.ps1 down -Server frankenphp
```

**Di Linux / macOS / WSL (Bash):**
```bash
./deploy-docker.sh up --server=frankenphp
./deploy-docker.sh status --server=frankenphp
./deploy-docker.sh logs --server=frankenphp
./deploy-docker.sh down --server=frankenphp
```

---

## 🔒 Ketahanan Konkurensi & Keamanan Produksi

SIGAP-SR dirancang kebal terhadap tabrakan data dan anomali konkurensi:

```mermaid
flowchart TD
    subgraph Request Concurrency Protection
        Req1["Request A (Simultan)"] --> LockCheck{"Generate ID / Code"}
        Req2["Request B (Simultan)"] --> LockCheck
        LockCheck -->|Sequential Attempt| DBTx["DB::transaction()"]
        DBTx -->|Unique Collision Detected| Retry["Auto-Retry with Jitter (Attempt 1-5)"]
        Retry -->|Next Sequence Calculated| DBTx
        DBTx -->|Success| Commit["Database Commit & Audit Log"]
    end
```

1. **Bebas Tabrakan Nomor Siswa & Insiden (`Race-Condition Free`)**:
   - Pembuatan Kode Siswa (`SR-YYYY-XXXX`) dan Nomor Insiden (`SR-YYYY-XXXX`) dilindungi oleh blok *exponential retry loop* (hingga 5 percobaan dengan jitter milidetik) yang menangani `UniqueConstraintViolationException` secara otomatis.
2. **Integritas Presensi Bersamaan**:
   - Transaksi database dengan indeks unik gabungan `['student_id', 'date', 'type']` mencegah baris kehadiran ganda santri pada sesi yang sama.
3. **Pemisahan Identitas Langsung (Privacy by Design)**:
   - Data foto dan identitas langsung anak disimpan terpisah di tabel `student_identities`. Data analitik operasional hanya menggunakan kode pseudonim.
4. **Proteksi Hak Akses Bertingkat (Defense in Depth)**:
   - Insiden keparahan tinggi (*severity 3-4*) dan kasus berlabel *sangat rahasia* hanya dapat diakses oleh Kepala Sekolah dan TPPK.
5. **Rate Limiting & CSRF**:
   - Proteksi brute-force login `throttle:6,1` dan CSRF token Laravel murni pada seluruh endpoint mutasi.

---

## 🧪 Pengujian & Jaminan Mutu (TDD & Visual Audit)

Aplikasi memiliki rangkaian pengujian otomatis lengkap:

```bash
# 1. Jalankan seluruh suite tes backend (50 tes, 541 assertions)
php artisan test --compact

# 2. Jalankan tes spesifik fitur
php artisan test --filter=StudentPhotoAndIdentityTest
php artisan test --filter=TeacherManagementAndLegalTest
php artisan test --filter=ProfileManagementTest

# 3. Format kode sesuai standar PSR / Laravel Pint
vendor/bin/pint --dirty --format agent

# 4. Jalankan audit visual Playwright (39 tes E2E pada Desktop & Mobile)
npx playwright test tests/e2e/screenshot-audit.spec.ts
```

### Hasil Audit Terbukti:
- **50/50 PHPUnit Tests Passing (100% Green, 541 assertions, 0 errors)**.
- **39/39 Playwright Tests Passing**:
  - Nol *horizontal scrollbar overflow* di semua breakpoint desktop dan smartphone.
  - Nol *render leak* (`undefined`, `null`, `NaN`, `[object Object]`).
  - Nol tabrakan antara ikon dan teks input pada seluruh formulir.
  - Seluruh tombol memiliki nama aksesibel (*a11y compliant*).

---

## 📄 Lisensi & Pemeliharaan

Proyek ini dirilis di bawah lisensi terbuka [MIT License](LICENSE).  
Didedikasikan penuh untuk perlindungan, keselamatan, harkat, dan masa depan anak-anak di Sekolah Rakyat.

Dikembangkan oleh **Rizal Taufiq Hidayat** ([GitHub: @rteitch](https://github.com/rteitch)).
