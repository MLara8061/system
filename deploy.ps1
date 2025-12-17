# Script de despliegue automatizado
# Uso: .\deploy.ps1 ["mensaje de commit"]

param(
    [string]$CommitMessage = "chore: update production"
)

$SSH_KEY = "C:\Users\Arla.ALLIENWARE.001\Desktop\system\.ssh\deploy_id_nopass_new"
$SSH_HOST = "u228864460@217.196.54.164"
$SSH_PORT = "65002"
$REMOTE_PATH = "domains/indigo-porcupine-764368.hostingersite.com/public_html"

Write-Host "=== DESPLIEGUE AUTOMATIZADO ===" -ForegroundColor Cyan

# 1. Commit local
Write-Host "`n[1/4] Committing cambios locales..." -ForegroundColor Yellow
git add -A
if ($LASTEXITCODE -eq 0) {
    git commit -m $CommitMessage
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  (sin cambios para commitear; ya esta actualizado)" -ForegroundColor Gray
    }
}

# 2. Push a GitHub
Write-Host "`n[2/4] Pushing a GitHub..." -ForegroundColor Yellow
git push origin main
if ($LASTEXITCODE -ne 0) {
    Write-Host "  Error en git push. Abortando." -ForegroundColor Red
    exit 1
}
Write-Host "  Push exitoso" -ForegroundColor Green

# 3. Pull en producción
Write-Host "`n[3/4] Actualizando produccion (git pull)..." -ForegroundColor Yellow
$pullCmd = "cd $REMOTE_PATH; git pull origin main 2>&1"
$result = ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST $pullCmd

if ($LASTEXITCODE -eq 0) {
    Write-Host "  Produccion actualizada" -ForegroundColor Green
    Write-Host $result -ForegroundColor Gray
} else {
    Write-Host "  Error en git pull:" -ForegroundColor Red
    Write-Host $result -ForegroundColor Red
    
    # Intentar resolver conflictos
    Write-Host "`n  Intentando resolver conflictos..." -ForegroundColor Yellow
    $cleanCmd = "cd $REMOTE_PATH; git reset --hard origin/main; git clean -fd"
    ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST $cleanCmd
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  Conflictos resueltos, produccion en sync" -ForegroundColor Green
    } else {
        Write-Host "  No se pudo resolver. Revisar manualmente." -ForegroundColor Red
        exit 1
    }
}

# 4. Verificar estado
Write-Host "`n[4/4] Verificando estado de produccion..." -ForegroundColor Yellow
$statusCmd = "cd $REMOTE_PATH; git log --oneline -1; echo '---'; git status --short"
$status = ssh -i $SSH_KEY -p $SSH_PORT $SSH_HOST $statusCmd
Write-Host $status -ForegroundColor Gray

Write-Host "`nDESPLIEGUE COMPLETADO" -ForegroundColor Green
Write-Host "🌐 URL: https://indigo-porcupine-764368.hostingersite.com" -ForegroundColor Cyan
Write-Host "`nIMPORTANTE: Recargar con Ctrl+Shift+R para evitar cache del navegador" -ForegroundColor Yellow
