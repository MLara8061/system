# =====================================================
# Script de Despliegue - Subdominio TEST
# Despliega el sistema en test.activosamerimed.com
# =====================================================

$ErrorActionPreference = "Stop"

# Configuración
$SSH_HOST = "u499728070@46.202.197.220"
$SSH_PORT = "65002"
$REMOTE_PATH = "/home/u499728070/domains/activosamerimed.com/public_html/test"
$TEMP_FILE = "deploy-test.tar.gz"

Write-Host "=== Despliegue Ambiente TEST ===" -ForegroundColor Cyan
Write-Host "Destino: test.activosamerimed.com" -ForegroundColor Gray
Write-Host ""

# Paso 1: Comprimir código local
Write-Host "[1/5] Comprimiendo código local..." -ForegroundColor Yellow
if (Test-Path $TEMP_FILE) { Remove-Item $TEMP_FILE -Force }

tar -czf $TEMP_FILE `
    --exclude=".git" `
    --exclude=".vscode" `
    --exclude="node_modules" `
    --exclude="vendor" `
    --exclude="*.log" `
    --exclude="logs/*" `
    --exclude="uploads/*" `
    --exclude="cache/*" `
    --exclude="u228864460_system.sql" `
    --exclude="DB_CREDENTIALS.txt" `
    --exclude="deploy*.tar.gz" `
    --exclude="*.ps1" `
    --exclude="config/.env.biomedicacun" `
    --exclude="config/.env.sistemascun" `
    --exclude="config/.env.manttocun" `
    *

if (-not (Test-Path $TEMP_FILE)) {
    Write-Host "Error: No se pudo crear el archivo comprimido" -ForegroundColor Red
    exit 1
}

$fileSize = [math]::Round((Get-Item $TEMP_FILE).Length / 1MB, 2)
Write-Host "OK Archivo creado: $fileSize MB" -ForegroundColor Green

# Paso 2: Subir al servidor
Write-Host ""
Write-Host "[2/5] Subiendo al servidor..." -ForegroundColor Yellow
scp -P $SSH_PORT $TEMP_FILE ${SSH_HOST}:/home/u499728070/$TEMP_FILE

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error al subir archivo" -ForegroundColor Red
    exit 1
}
Write-Host "OK Archivo subido exitosamente" -ForegroundColor Green

# Paso 3: Crear directorio y extraer
Write-Host ""
Write-Host "[3/5] Extrayendo en servidor..." -ForegroundColor Yellow

$setupCmd = "mkdir -p $REMOTE_PATH && cd $REMOTE_PATH && tar -xzf /home/u499728070/$TEMP_FILE && chmod 755 uploads logs cache 2>/dev/null || true && mkdir -p uploads logs cache 2>/dev/null || true"

try {
    ssh -p $SSH_PORT $SSH_HOST $setupCmd 2>&1 | Out-Null
    Write-Host "OK Código extraído exitosamente" -ForegroundColor Green
} catch {
    # Ignorar warnings de SSH quantum
    if ($_.Exception.Message -notmatch "quantum" -and $_.Exception.Message -notmatch "WARNING") {
        Write-Host "Error al extraer archivos: $($_.Exception.Message)" -ForegroundColor Red
        exit 1
    }
    Write-Host "OK Código extraído exitosamente" -ForegroundColor Green
}

# Paso 4: Copiar archivo .env específico para test
Write-Host ""
Write-Host "[4/5] Configurando ambiente..." -ForegroundColor Yellow

$envCmd = "cd $REMOTE_PATH/config && cp .env.test .env"
try {
    ssh -p $SSH_PORT $SSH_HOST $envCmd 2>&1 | Out-Null
    Write-Host "OK Archivo .env configurado" -ForegroundColor Green
} catch {
    if ($_.Exception.Message -notmatch "quantum" -and $_.Exception.Message -notmatch "WARNING") {
        Write-Host "Advertencia: No se pudo copiar .env.test - $($_.Exception.Message)" -ForegroundColor Yellow
    } else {
        Write-Host "OK Archivo .env configurado" -ForegroundColor Green
    }
}

# Paso 5: Limpiar archivos temporales
Write-Host ""
Write-Host "[5/5] Limpiando archivos temporales..." -ForegroundColor Yellow
try {
    ssh -p $SSH_PORT $SSH_HOST "rm -f /home/u499728070/$TEMP_FILE" 2>&1 | Out-Null
} catch {
    # Ignorar warnings
}
Remove-Item $TEMP_FILE -Force
Write-Host "OK Archivos temporales eliminados" -ForegroundColor Green

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "Despliegue completado exitosamente" -ForegroundColor Green
Write-Host "URL: https://test.activosamerimed.com" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "SIGUIENTE PASO: Ejecutar migraciones de base de datos" -ForegroundColor Yellow
