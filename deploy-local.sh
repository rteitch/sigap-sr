#!/usr/bin/env bash
# ==============================================================================
# SIGAP-SR - Skrip Deployment & Runner Lokal (Linux / macOS / WSL)
# ==============================================================================
set -e

# Default Options
MODE="prod"
PORT=8000
RUN_SEED=false
RUN_FRESH=false
SKIP_BUILD=false
NO_DEV=false

# Colors
C_RESET="\033[0m"
C_CYAN="\033[1;36m"
C_GREEN="\033[1;32m"
C_YELLOW="\033[1;33m"
C_RED="\033[1;31m"
C_BOLD="\033[1m"

step() { echo -e "\n${C_CYAN}[+] $1${C_RESET}"; }
success() { echo -e "    ${C_GREEN}[OK] $1${C_RESET}"; }
warn() { echo -e "    ${C_YELLOW}[!] $1${C_RESET}"; }
fail() { echo -e "\n    ${C_RED}[ERROR] $1${C_RESET}"; exit 1; }

# Parse arguments
while [[ "$#" -gt 0 ]]; do
    case "$1" in
        --mode=*) MODE="${1#*=}" ;;
        --port=*) PORT="${1#*=}" ;;
        --seed) RUN_SEED=true ;;
        --fresh) RUN_FRESH=true ;;
        --skip-build) SKIP_BUILD=true ;;
        --no-dev) NO_DEV=true ;;
        -h|--help)
            echo "Penggunaan: ./deploy-local.sh [OPSI]"
            echo "Opsi:"
            echo "  --mode=prod|dev   Pilih mode: 'prod' (teroptimasi) atau 'dev' (hot-reload) (default: prod)"
            echo "  --port=8000       Port HTTP server lokal (default: 8000)"
            echo "  --seed            Jalankan seeder akun demo dan data awal"
            echo "  --fresh           Reset migrasi database (migrate:fresh)"
            echo "  --skip-build      Lewati kompilasi aset npm build"
            echo "  --no-dev          Keluarkan dependensi dev (require-dev) dari vendor"
            echo "  -h, --help        Tampilkan panduan bantuan ini"
            exit 0
            ;;
        *)
            echo "Argumen tidak dikenal: $1"
            exit 1
            ;;
    esac
    shift
done

echo -e "${C_CYAN}"
echo "========================================================================"
echo "   SIGAP-SR — Sistem Informasi Pengasuhan, Pembinaan, dan Evaluasi"
echo "   Sekolah Rakyat (Local Deployment Runner)"
echo "========================================================================"
echo "Mode : ${MODE}"
echo "Port : ${PORT}"
echo -e "${C_RESET}"

# 1. Pengecekan Prasyarat
step "Memeriksa prasyarat sistem (PHP, Composer, Node.js, NPM)..."

if ! command -v php &>/dev/null; then
    fail "PHP tidak ditemukan di sistem. Harap instal PHP 8.3+."
fi

PHP_VER=$(php -r "echo PHP_VERSION;")
IS_PHP83=$(php -r "echo version_compare(PHP_VERSION, '8.3.0', '>=') ? '1' : '0';")
if [ "$IS_PHP83" != "1" ]; then
    fail "SIGAP-SR membutuhkan PHP 8.3 atau lebih baru. Terdeteksi: ${PHP_VER}"
fi
success "PHP ${PHP_VER} terdeteksi"

if ! command -v composer &>/dev/null; then
    fail "Composer tidak ditemukan di sistem."
fi
success "Composer terdeteksi"

if ! command -v node &>/dev/null || ! command -v npm &>/dev/null; then
    fail "Node.js atau NPM tidak ditemukan di sistem."
fi
success "Node.js $(node -v) / NPM $(npm -v) terdeteksi"

# 2. Inisialisasi Environment & Database
step "Menyiapkan environment configuration & file database..."

if [ ! -f .env ]; then
    warn ".env belum ada. Menyalin dari .env.example..."
    cp .env.example .env
    success "File .env berhasil dibuat"
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    warn "APP_KEY belum digenerate. Menghasilkan Application Key..."
    php artisan key:generate --ansi
    success "Application key berhasil digenerate"
fi

mkdir -p database
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    success "File database/database.sqlite berhasil dibuat"
fi

# 3. Instalasi Dependensi PHP
step "Menginstal dependensi Composer (${MODE} mode)..."
if [ "$NO_DEV" = true ]; then
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
else
    composer install --prefer-dist --optimize-autoloader --no-interaction
fi
success "Composer dependensi siap & teroptimasi"

# 4. Kompilasi Aset Frontend (Vite)
if [ "$SKIP_BUILD" = false ]; then
    step "Menginstal package NPM dan mem-build asset frontend..."
    npm ci --prefer-offline --no-audit || npm install
    npm run build
    success "Asset frontend berhasil dikompilasi ke public/build"
else
    warn "Melewati build frontend (--skip-build aktif)"
fi

# 5. Storage Symlink & Migrasi Database
step "Membuat storage link dan menjalankan migrasi database..."
php artisan storage:link --force &>/dev/null || true
success "Storage symlink siap"

if [ "$RUN_FRESH" = true ]; then
    warn "Menjalankan migrate:fresh..."
    php artisan migrate:fresh --force --ansi
else
    php artisan migrate --force --ansi
fi
success "Migrasi database berhasil dieksekusi"

if [ "$RUN_SEED" = true ]; then
    step "Menjalankan database seeders..."
    php artisan db:seed --force --ansi
    success "Database seeder selesai dijalankan"
fi

# 6. Optimasi Cache (Production vs Development)
if [ "$MODE" = "prod" ]; then
    step "Mengoptimasi cache Laravel untuk performa maksimal..."
    php artisan config:cache --ansi
    php artisan route:cache --ansi
    php artisan view:cache --ansi
    php artisan event:cache --ansi
    success "Config, Route, View, dan Event cache berhasil diaktifkan"
else
    step "Membersihkan cache untuk lingkungan Development..."
    php artisan optimize:clear --ansi
    success "Cache dibersihkan untuk mode hot-reload"
fi

# 7. Memulai Web Server
APP_URL="http://127.0.0.1:${PORT}"
echo -e "\n${C_GREEN}========================================================================"
echo "   SIGAP-SR SIAP DIJALANKAN!"
echo "========================================================================"
echo "   URL Aplikasi : ${APP_URL}"
echo "   Mode         : ${MODE}"
echo "   Tekan [Ctrl + C] untuk menghentikan server."
echo -e "========================================================================${C_RESET}\n"

trap 'echo -e "\nServer dihentikan."; exit 0' INT TERM

if [ "$MODE" = "prod" ]; then
    php artisan serve --host=127.0.0.1 --port="${PORT}"
else
    npm run dev
fi
