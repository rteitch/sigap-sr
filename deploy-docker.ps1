<#
.SYNOPSIS
    SIGAP-SR - Skrip Orkestrasi & Deployment Docker (Windows PowerShell)

.DESCRIPTION
    Mengelola lifecycle kontainer Docker SIGAP-SR: build, up, down, restart, logs,
    eksekusi perintah artisan, migrasi database, dan pengecekan health status.

.PARAMETER Action
    Aksi yang ingin dijalankan:
    'up', 'down', 'restart', 'build', 'logs', 'artisan', 'migrate', 'seed', 'shell', 'status' (default: 'up').

.PARAMETER Port
    Port HTTP pada host yang akan diarahkan ke kontainer (default: 8000).

.PARAMETER Build
    Memaksa build ulang image sebelum menjalankan 'up'.

.PARAMETER Seed
    Menjalankan database seeder saat kontainer dijalankan.

.EXAMPLE
    .\deploy-docker.ps1 up
    .\deploy-docker.ps1 down
    .\deploy-docker.ps1 logs
    .\deploy-docker.ps1 artisan "route:list"
    .\deploy-docker.ps1 artisan "tinker"
    .\deploy-docker.ps1 status
#>

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [ValidateSet('up', 'start', 'down', 'stop', 'restart', 'build', 'logs', 'artisan', 'migrate', 'seed', 'shell', 'status', 'help')]
    [string]$Action = 'up',

    [Parameter(Position = 1, ValueFromRemainingArguments = $true)]
    [string[]]$CommandArgs,

    [int]$Port = 8000,
    [ValidateSet('nginx', 'frankenphp')]
    [string]$Server = 'nginx',
    [switch]$Build,
    [switch]$Seed
)

$ErrorActionPreference = 'Stop'
$composeFile = if ($Server -eq 'frankenphp') { "docker-compose.frankenphp.yml" } else { "docker-compose.yml" }

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

# ----------------------------------------------------------------------
# 1. Pengecekan Docker Daemon
# ----------------------------------------------------------------------
try {
    $null = docker info 2>$null
    if ($LASTEXITCODE -ne 0) { throw "Docker daemon offline" }
} catch {
    Write-Failure "Docker tidak berjalan atau Docker Desktop belum dibuka. Pastikan service Docker aktif."
}

# Siapkan environment variable port untuk docker compose
$env:APP_PORT = $Port.ToString()
if ($Seed) { $env:RUN_SEEDER = "true" }

# ----------------------------------------------------------------------
# 2. Router Aksi
# ----------------------------------------------------------------------
switch ($Action.ToLower()) {
    { $_ -in 'up', 'start' } {
        Write-Host @"
========================================================================
   SIGAP-SR — Menjalankan Layanan Docker Container ($Server)
========================================================================
Host Port : $Port
Server    : $Server ($composeFile)
"@ -ForegroundColor DarkCyan

        if ($Build) {
            Write-Step "Mem-build image Docker SIGAP-SR ($Server)..."
            docker compose -f $composeFile build
        }

        Write-Step "Memulai container di background (detached mode)..."
        docker compose -f $composeFile up -d

        Write-Step "Menunggu kontainer inisialisasi dan sehat (healthcheck probe)..."
        $containerName = "sigap-sr-app"
        $maxRetries = 25
        $retries = 0
        $isReady = $false

        while ($retries -lt $maxRetries) {
            Start-Sleep -Seconds 2
            $retries++

            # Cek status curl endpoint /up
            try {
                $response = Invoke-RestMethod -Uri "http://127.0.0.1:$Port/up" -TimeoutSec 2 -ErrorAction SilentlyContinue
                if ($response) {
                    $isReady = $true
                    break
                }
            } catch {
                Write-Host -NoNewline "."
            }
        }

        Write-Host ""
        if ($isReady) {
            Write-Success "Kontainer aktif dan merespons dengan status 200 OK!"
            Write-Host @"

========================================================================
   SIGAP-SR DOCKER SIAP DIGUNAKAN!
========================================================================
   URL Aplikasi : http://localhost:$Port
   Port Forward : $Port -> 80 (Nginx + PHP 8.3 FPM)
   Storage Vol  : sigap_sr_storage (foto siswa & asset dinamis)
   Database Vol : sigap_sr_database (SQLite terenkapsulasi)
========================================================================
Tips:
  - Lihat log live    : .\deploy-docker.ps1 logs
  - Jalankan artisan  : .\deploy-docker.ps1 artisan "nama_perintah"
  - Cek status sistem : .\deploy-docker.ps1 status
  - Matikan kontainer : .\deploy-docker.ps1 down
========================================================================
"@ -ForegroundColor Green
        } else {
            Write-Warn "Kontainer sedang boot atau memerlukan waktu lebih lama. Cek log dengan: .\deploy-docker.ps1 logs"
        }
    }

    { $_ -in 'down', 'stop' } {
        Write-Step "Menghentikan dan menghapus kontainer SIGAP-SR ($Server)..."
        docker compose -f $composeFile down
        Write-Success "Kontainer berhasil dimatikan dengan aman."
    }

    'restart' {
        Write-Step "Merestart kontainer SIGAP-SR ($Server)..."
        docker compose -f $composeFile restart
        Write-Success "Kontainer telah direstart."
    }

    'build' {
        Write-Step "Mem-build ulang image Docker SIGAP-SR ($Server clean build)..."
        docker compose -f $composeFile build --no-cache
        Write-Success "Build selesai."
    }

    'logs' {
        Write-Step "Menampilkan log aktif SIGAP-SR ($Server)... (Tekan Ctrl+C untuk keluar)"
        docker compose -f $composeFile logs -f app
    }

    'artisan' {
        if (-not $CommandArgs -or $CommandArgs.Count -eq 0) {
            Write-Failure "Harap sertakan perintah artisan. Contoh: .\deploy-docker.ps1 artisan 'route:list'"
        }
        $cmd = $CommandArgs -join ' '
        Write-Step "Mengeksekusi di dalam kontainer ($Server): php artisan $cmd"
        docker compose -f $composeFile exec app php artisan $CommandArgs
    }

    'migrate' {
        Write-Step "Menjalankan migrasi database di dalam kontainer ($Server)..."
        docker compose -f $composeFile exec app php artisan migrate --force
        Write-Success "Migrasi database selesai."
    }

    'seed' {
        Write-Step "Menjalankan database seeder di dalam kontainer ($Server)..."
        docker compose -f $composeFile exec app php artisan db:seed --force
        Write-Success "Database seeder selesai."
    }

    'shell' {
        Write-Step "Membuka interaktif shell di dalam kontainer ($Server)..."
        docker compose -f $composeFile exec -it app sh
    }

    'status' {
        Write-Step "Status Kontainer SIGAP-SR ($Server):"
        docker compose -f $composeFile ps
        Write-Host ""
        try {
            $health = Invoke-RestMethod -Uri "http://127.0.0.1:$Port/health/ready" -TimeoutSec 3 -ErrorAction SilentlyContinue
            if ($health) {
                Write-Success "Endpoint /health/ready : $($health.status)"
                Write-Host ($health | ConvertTo-Json -Depth 3) -ForegroundColor Gray
            }
        } catch {
            Write-Warn "Aplikasi belum merespons di http://127.0.0.1:$Port/health/ready"
        }
    }

    'help' {
        Write-Host @"
Penggunaan:
    .\deploy-docker.ps1 [Aksi] [-Server nginx|frankenphp] [-Port 8000] [-Build] [-Seed]

Daftar Aksi:
    up       : Menjalankan kontainer di background dan memeriksa kesehatan sistem.
    down     : Menghentikan seluruh layanan kontainer.
    restart  : Merestart kontainer.
    build    : Mem-build ulang image Docker tanpa cache.
    logs     : Menampilkan live stream log dari server dan worker.
    artisan  : Menjalankan perintah artisan (contoh: .\deploy-docker.ps1 artisan 'tinker').
    migrate  : Menjalankan migrasi database di kontainer.
    seed     : Menjalankan database seeder di kontainer.
    shell    : Membuka terminal interaktif di dalam kontainer.
    status   : Memeriksa status proses dan probe kesehatan database/cache/storage.

Pilihan Server:
    -Server nginx       : Menggunakan Nginx + PHP 8.3 FPM + Supervisord (Default)
    -Server frankenphp  : Menggunakan FrankenPHP Caddy Server (HTTP/3 & Zstandard)
"@ -ForegroundColor Yellow
    }
}
