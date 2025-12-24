# =====================================================
# Script de Actualizacion de Subdominios
# Actualiza codigo en los 3 subdominios de Hostinger
# =====================================================

$ErrorActionPreference = "Stop"

# Configuracion
$SSH_HOST = "u499728070@46.202.197.220"
$SSH_PORT = "65002"
$REMOTE_PATH = "/home/u499728070/domains/activosamerimed.com/public_html"
$SUBDOMINIOS = @("biomedicacun", "sistemascun", "manttocun")
$TEMP_FILE = "deploy-update.tar.gz"

Write-Host "=== Actualizacion de Subdominios ===" -ForegroundColor Cyan
Write-Host ""

# Paso 1: Comprimir codigo local
Write-Host "[1/4] Comprimiendo codigo local..." -ForegroundColor Yellow
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
    *

if (-not (Test-Path $TEMP_FILE)) {
    Write-Host "Error: No se pudo crear el archivo comprimido" -ForegroundColor Red
    exit 1
}

$fileSize = [math]::Round((Get-Item $TEMP_FILE).Length / 1MB, 2)
Write-Host "OK Archivo creado: $fileSize MB" -ForegroundColor Green

# Paso 2: Subir al servidor
Write-Host ""
Write-Host "[2/4] Subiendo al servidor..." -ForegroundColor Yellow
scp -P $SSH_PORT $TEMP_FILE ${SSH_HOST}:/home/u499728070/$TEMP_FILE

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error al subir archivo" -ForegroundColor Red
    exit 1
}
Write-Host "OK Archivo subido exitosamente" -ForegroundColor Green

# Paso 3: Extraer en subdominios
Write-Host ""
Write-Host "[3/4] Actualizando subdominios..." -ForegroundColor Yellow

foreach ($subdominio in $SUBDOMINIOS) {
    Write-Host "  -> Actualizando $subdominio..." -ForegroundColor Gray
    
    $extractCmd = "cd $REMOTE_PATH/$subdominio && tar -xzf /home/u499728070/$TEMP_FILE --exclude='config/.env' && chmod 755 uploads logs cache 2>/dev/null || true"
    
    ssh -p $SSH_PORT $SSH_HOST $extractCmd
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error al actualizar $subdominio" -ForegroundColor Red
        exit 1
    }
}

Write-Host "OK Todos los subdominios actualizados" -ForegroundColor Green

# Paso 4: Limpiar archivos temporales
Write-Host ""
Write-Host "[4/4] Limpiando archivos temporales..." -ForegroundColor Yellow
ssh -p $SSH_PORT $SSH_HOST "rm -f /home/u499728070/$TEMP_FILE"
Remove-Item $TEMP_FILE -Force
Write-Host "OK Archivos temporales eliminados" -ForegroundColor Green

Write-Host ""
Write-Host "==================================" -ForegroundColor Cyan
Write-Host "OK Actualizacion completada exitosamente" -ForegroundColor Green
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Subdominios actualizados:" -ForegroundColor White
Write-Host "  - https://biomedicacun.activosamerimed.com" -ForegroundColor Gray
Write-Host "  - https://sistemascun.activosamerimed.com" -ForegroundColor Gray
Write-Host "  - https://manttocun.activosamerimed.com" -ForegroundColor Gray
Write-Host ""
