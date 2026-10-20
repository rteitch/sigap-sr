<#
.SYNOPSIS
    SIGAP-SR - Skrip Deployment & Runner Lokal (Windows PowerShell)

.DESCRIPTION
    Menyiapkan prasyarat, dependensi, migrasi database, kompilasi aset frontend,
    serta mengoptimasi cache Laravel untuk dijalankan langsung di mesin lokal.

.PARAMETER Mode
    Mode eksekusi: 'Production' (default, teroptimasi penuh) atau 'Development' (hot-reload).

.PARAMETER Port
    Port HTTP untuk server lokal (default: 8000).

.PARAMETER Seed
    Menjalankan database seeder untuk akun demo dan data referensi awal.

.PARAMETER Fresh
    Melakukan reset migrasi database dari awal (migrate:fresh).

.PARAMETER SkipBuild
    Melewati proses 'npm install' dan 'npm run build' jika aset sudah terkompilasi.

.EXAMPLE
    .\deploy-local.ps1
    .\deploy-local.ps1 -Mode Development
    .\deploy-local.ps1 -Port 8080 -Seed
#>

[CmdletBinding()]
param(
    [ValidateSet('Production', 'Development', 'prod', 'dev')]
    [string]$Mode = 'Production',

    [int]$Port = 8000,

    [switch]$Seed,
    [switch]$Fresh,
    [switch]$SkipBuild,
    [switch]$NoDev,
    [switch]$Help
)

$ErrorActionPreference = 'Stop'

# Normalisasi parameter Mode
if ($Mode -eq 'prod') { $Mode = 'Production' }
if ($Mode -eq 'dev')  { $Mode = 'Development' }

function Write-Step {
    param([string]$Text)
    Write-Host "`n[+] $Text" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Text)
    Write-Host "    [OK] $Text" -ForegroundColor Green
}

function Write-Warn {
    param([string]$Text)
    Write-Host "    [!] $Text" -ForegroundColor Yellow
}

function Write-Failure {
    param([string]$Text)
    Write-Host "`n    [ERROR] $Text" -ForegroundColor Red
    exit 1
}

Write-Host @"
========================================================================
   SIGAP-SR — Sistem Informasi Pengasuhan, Pembinaan, dan Evaluasi
   Sekolah Rakyat (Local Deployment Runner)
========================================================================
Mode : $Mode
Port : $Port
"@ -ForegroundColor DarkCyan

# ----------------------------------------------------------------------
# 1. Pengecekan Prasyarat Sistem
# ----------------------------------------------------------------------
Write-Step "Memeriksa prasyarat sistem (PHP, Composer, Node.js, NPM)..."

# PHP Check
$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpCmd) {
    Write-Failure "PHP tidak ditemukan di PATH. Pastikan PHP 8.3+ sudah terpasang."
}
$phpVersionRaw = (php -r "echo PHP_VERSION;")
$isPhp83 = (php -r "echo version_compare(PHP_VERSION, '8.3.0', '>=') ? '1' : '0';")
if ($isPhp83 -ne '1') {
    Write-Failure "SIGAP-SR membutuhkan PHP 8.3 atau lebih baru. Terdeteksi: $phpVersionRaw"
}
Write-Success "PHP $phpVersionRaw terdeteksi"

# Composer Check
$composerCmd = Get-Command composer -ErrorAction SilentlyContinue
if (-not $composerCmd) {
    Write-Failure "Composer tidak ditemukan di PATH. Pastikan Composer sudah terpasang."
}
Write-Success "Composer terdeteksi"

# Node & NPM Check
$nodeCmd = Get-Command node -ErrorAction SilentlyContinue
$npmCmd  = Get-Command npm -ErrorAction SilentlyContinue
if (-not $nodeCmd -or -not $npmCmd) {
    Write-Failure "Node.js atau NPM tidak ditemukan di PATH."
}
$nodeVer = (node -v)
$npmVer  = (npm -v)
Write-Success "Node.js $nodeVer / NPM $npmVer terdeteksi"

# ----------------------------------------------------------------------
# 2. Inisialisasi Environment & Database
# ----------------------------------------------------------------------
Write-Step "Menyiapkan environment configuration & file database..."

if (-not (Test-Path ".env")) {
    Write-Warn ".env belum ada. Menyalin dari .env.example..."
    Copy-Item ".env.example" ".env"
    Write-Success "File .env berhasil dibuat"
}

$envContent = Get-Content ".env" -Raw
if ($envContent -notmatch "APP_KEY=base64:") {
    Write-Warn "APP_KEY belum digenerate. Menghasilkan Application Key..."
    php artisan key:generate --ansi
    Write-Success "Application key berhasil digenerate"
}

# Pastikan database SQLite siap jika koneksi memakai sqlite
if (-not (Test-Path "database")) {
    New-Item -ItemType Directory -Path "database" | Out-Null
}
if (-not (Test-Path "database/database.sqlite")) {
    New-Item -ItemType File -Path "database/database.sqlite" | Out-Null
    Write-Success "File database/database.sqlite berhasil dibuat"
}

# ----------------------------------------------------------------------
# 3. Instalasi Dependensi PHP (Composer)
# ----------------------------------------------------------------------
Write-Step "Menginstal dependensi Composer ($Mode mode)..."

if ($NoDev) {
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
} else {
    composer install --prefer-dist --optimize-autoloader --no-interaction
}
Write-Success "Composer dependensi siap & teroptimasi"

# ----------------------------------------------------------------------
# 4. Kompilasi Aset Frontend (Vite)
# ----------------------------------------------------------------------
if (-not $SkipBuild) {
    Write-Step "Menginstal package NPM dan mem-build asset frontend..."
    npm ci --prefer-offline --no-audit
    if ($LASTEXITCODE -ne 0) {
        Write-Warn "npm ci gagal, mencoba 'npm install'..."
        npm install
    }
    
    npm run build
    Write-Success "Asset frontend berhasil dikompilasi ke public/build"
} else {
    Write-Warn "Melewati build frontend (--SkipBuild aktif)"
}

# ----------------------------------------------------------------------
# 5. Storage Symlink & Migrasi Database
# ----------------------------------------------------------------------
Write-Step "Membuat storage link dan menjalankan migrasi database..."

php artisan storage:link --force 2>$null | Out-Null
Write-Success "Storage symlink siap"

if ($Fresh) {
    Write-Warn "Menjalankan migrate:fresh..."
    php artisan migrate:fresh --force --ansi
} else {
    php artisan migrate --force --ansi
}
Write-Success "Migrasi database berhasil dieksekusi"

if ($Seed) {
    Write-Step "Menjalankan database seeders..."
    php artisan db:seed --force --ansi
    Write-Success "Database seeder selesai dijalankan"
}

# ----------------------------------------------------------------------
# 6. Optimasi Cache (Production vs Development)
# ----------------------------------------------------------------------
if ($Mode -eq 'Production') {
    Write-Step "Mengoptimasi cache Laravel untuk performa maksimal..."
    php artisan config:cache --ansi
    php artisan route:cache --ansi
    php artisan view:cache --ansi
    php artisan event:cache --ansi
    Write-Success "Config, Route, View, dan Event cache berhasil diaktifkan"
} else {
    Write-Step "Membersihkan cache untuk lingkungan Development..."
    php artisan optimize:clear --ansi
    Write-Success "Cache dibersihkan untuk mode hot-reload"
}

# ----------------------------------------------------------------------
# 7. Memulai Web Server
# ----------------------------------------------------------------------
$appUrl = "http://127.0.0.1:$Port"
Write-Host @"

========================================================================
   SIGAP-SR SIAP DIJALANKAN!
========================================================================
   URL Aplikasi : $appUrl
   Mode         : $Mode
   Tekan [Ctrl + C] untuk menghentikan server.
========================================================================

"@ -ForegroundColor Green

if ($Mode -eq 'Production') {
    php artisan serve --host=127.0.0.1 --port=$Port
} else {
    npm run dev
}
