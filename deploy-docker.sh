#!/usr/bin/env bash
# ==============================================================================
# SIGAP-SR - Skrip Orkestrasi & Deployment Docker (Linux / macOS / WSL)
# ==============================================================================
set -e

ACTION="up"
PORT=8000
SERVER="nginx"
FORCE_BUILD=false
RUN_SEED=false
EXTRA_ARGS=()

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
        up|start|down|stop|restart|build|logs|artisan|migrate|seed|shell|status|help)
            ACTION="$1"
            ;;
        --port=*)
            PORT="${1#*=}"
            ;;
        --server=*)
            SERVER="${1#*=}"
            ;;
        --build)
            FORCE_BUILD=true
            ;;
        --seed)
            RUN_SEED=true
            ;;
        -h|--help)
            ACTION="help"
            ;;
        *)
            EXTRA_ARGS+=("$1")
            ;;
    esac
    shift
done

# Check Docker daemon
if ! docker info &>/dev/null; then
    fail "Docker tidak aktif atau daemon Docker belum berjalan."
fi

if [ "$SERVER" = "frankenphp" ]; then
    COMPOSE_FILE="docker-compose.frankenphp.yml"
else
    COMPOSE_FILE="docker-compose.yml"
fi

export APP_PORT="${PORT}"
if [ "$RUN_SEED" = true ]; then
    export RUN_SEEDER="true"
fi

case "$ACTION" in
    up|start)
        echo -e "${C_CYAN}"
        echo "========================================================================"
        echo "   SIGAP-SR — Menjalankan Layanan Docker Container (${SERVER})"
        echo "========================================================================"
        echo "Host Port : ${PORT}"
        echo "Server    : ${SERVER} (${COMPOSE_FILE})"
        echo -e "${C_RESET}"

        if [ "$FORCE_BUILD" = true ]; then
            step "Mem-build image Docker SIGAP-SR (${SERVER})..."
            docker compose -f "${COMPOSE_FILE}" build
        fi

        step "Memulai container di background (detached mode)..."
        docker compose -f "${COMPOSE_FILE}" up -d

        step "Menunggu kontainer inisialisasi dan sehat (healthcheck probe)..."
        MAX_RETRIES=25
        RETRIES=0
        IS_READY=false

        while [ "$RETRIES" -lt "$MAX_RETRIES" ]; do
            sleep 2
            RETRIES=$((RETRIES + 1))
            if curl -s -f "http://127.0.0.1:${PORT}/up" &>/dev/null; then
                IS_READY=true
                break
            fi
            echo -n "."
        done
        echo ""

        if [ "$IS_READY" = true ]; then
            success "Kontainer aktif dan merespons dengan status 200 OK!"
            echo -e "\n${C_GREEN}========================================================================"
            echo "   SIGAP-SR DOCKER SIAP DIGUNAKAN! (${SERVER})"
            echo "========================================================================"
            echo "   URL Aplikasi : http://localhost:${PORT}"
            echo "   Server Stack : ${SERVER} (${COMPOSE_FILE})"
            echo "   Storage Vol  : sigap_sr_storage (foto siswa & asset dinamis)"
            echo "   Database Vol : sigap_sr_database (SQLite terenkapsulasi)"
            echo "========================================================================"
            echo "Tips:"
            echo "  - Lihat log live    : ./deploy-docker.sh logs --server=${SERVER}"
            echo "  - Jalankan artisan  : ./deploy-docker.sh artisan <perintah> --server=${SERVER}"
            echo "  - Cek status sistem : ./deploy-docker.sh status --server=${SERVER}"
            echo "  - Matikan kontainer : ./deploy-docker.sh down --server=${SERVER}"
            echo -e "========================================================================${C_RESET}\n"
        else
            warn "Kontainer sedang boot atau memerlukan waktu lebih lama. Cek log dengan: ./deploy-docker.sh logs --server=${SERVER}"
        fi
        ;;

    down|stop)
        step "Menghentikan dan menghapus kontainer SIGAP-SR (${SERVER})..."
        docker compose -f "${COMPOSE_FILE}" down
        success "Kontainer berhasil dimatikan dengan aman."
        ;;

    restart)
        step "Merestart kontainer SIGAP-SR (${SERVER})..."
        docker compose -f "${COMPOSE_FILE}" restart
        success "Kontainer telah direstart."
        ;;

    build)
        step "Mem-build ulang image Docker SIGAP-SR (${SERVER} clean build)..."
        docker compose -f "${COMPOSE_FILE}" build --no-cache
        success "Build selesai."
        ;;

    logs)
        step "Menampilkan log aktif SIGAP-SR (${SERVER})... (Tekan Ctrl+C untuk keluar)"
        docker compose -f "${COMPOSE_FILE}" logs -f app
        ;;

    artisan)
        if [ "${#EXTRA_ARGS[@]}" -eq 0 ]; then
            fail "Harap sertakan perintah artisan. Contoh: ./deploy-docker.sh artisan route:list"
        fi
        step "Mengeksekusi di dalam kontainer (${SERVER}): php artisan ${EXTRA_ARGS[*]}"
        docker compose -f "${COMPOSE_FILE}" exec app php artisan "${EXTRA_ARGS[@]}"
        ;;

    migrate)
        step "Menjalankan migrasi database di dalam kontainer (${SERVER})..."
        docker compose -f "${COMPOSE_FILE}" exec app php artisan migrate --force
        success "Migrasi database selesai."
        ;;

    seed)
        step "Menjalankan database seeder di dalam kontainer (${SERVER})..."
        docker compose -f "${COMPOSE_FILE}" exec app php artisan db:seed --force
        success "Database seeder selesai."
        ;;

    shell)
        step "Membuka interaktif shell di dalam kontainer (${SERVER})..."
        docker compose -f "${COMPOSE_FILE}" exec app sh
        ;;

    status)
        step "Status Kontainer SIGAP-SR (${SERVER}):"
        docker compose -f "${COMPOSE_FILE}" ps
        echo ""
        if curl -s -f "http://127.0.0.1:${PORT}/health/ready" &>/dev/null; then
            success "Endpoint /health/ready : OK"
            curl -s "http://127.0.0.1:${PORT}/health/ready" | jq . 2>/dev/null || curl -s "http://127.0.0.1:${PORT}/health/ready"
            echo ""
        else
            warn "Aplikasi belum merespons di http://127.0.0.1:${PORT}/health/ready"
        fi
        ;;

    help)
        echo "Penggunaan: ./deploy-docker.sh [AKSI] [OPSI]"
        echo ""
        echo "Daftar Aksi:"
        echo "  up       : Jalankan kontainer di background dan tunggu hingga sehat (default)"
        echo "  down     : Hentikan dan bersihkan kontainer"
        echo "  restart  : Restart kontainer aplikasi"
        echo "  build    : Rebuild image Docker tanpa cache"
        echo "  logs     : Tampilkan live log server dan worker"
        echo "  artisan  : Jalankan perintah artisan di kontainer (contoh: ./deploy-docker.sh artisan tinker)"
        echo "  migrate  : Jalankan migrasi database di kontainer"
        echo "  seed     : Jalankan database seeder di kontainer"
        echo "  shell    : Buka shell terminal di dalam kontainer"
        echo "  status   : Periksa status kontainer dan probe kesehatan sistem"
        echo ""
        echo "Opsi:"
        echo "  --server=nginx|frankenphp  Pilih stack server (default: nginx)"
        echo "  --port=8000                Port host yang diexpose (default: 8000)"
        echo "  --build                    Paksa build ulang image sebelum 'up'"
        echo "  --seed                     Jalankan seeder saat kontainer 'up'"
        ;;
esac
