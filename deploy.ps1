# =====================================================
# Script Unificado de Despliegue Multi-Instancia
# Gestiona TODOS los sistemas desplegados
# =====================================================
#
# USO:
#   .\deploy.ps1                    -> Menú interactivo
#   .\deploy.ps1 -Target all        -> Desplegar en TODAS las instancias
#   .\deploy.ps1 -Target amerimed   -> Todas las de activosamerimed.com
#   .\deploy.ps1 -Target vsecure    -> Solo vsecure.activoconvalor.com
#   .\deploy.ps1 -Target prod       -> Solo producción (raíz amerimed)
#   .\deploy.ps1 -Target test       -> Solo test.activosamerimed.com
#   .\deploy.ps1 -Target prod,test  -> Múltiples targets separados por coma
#
# =====================================================

param(
    [string]$Target = ""
)

$ErrorActionPreference = "Stop"

# =====================================================
# REGISTRO DE INSTANCIAS
# Agregar nuevos clientes/subdominios aquí
# =====================================================
$Instances = @{
    # --- Servidor: activosamerimed.com ---
    "prod" = @{
        Label       = "activosamerimed.com (Producción)"
        URL         = "https://activosamerimed.com"
        Group       = "amerimed"
        SSH_User    = "u499728070"
        SSH_IP      = "46.202.197.220"
        SSH_Port    = "65002"
        RemotePath  = "/home/u499728070/domains/activosamerimed.com/public_html"
        EnvFile     = ".env.production"
    }
    "test" = @{
        Label       = "test.activosamerimed.com"
        URL         = "https://test.activosamerimed.com"
        Group       = "amerimed"
        SSH_User    = "u499728070"
        SSH_IP      = "46.202.197.220"
        SSH_Port    = "65002"
        RemotePath  = "/home/u499728070/domains/activosamerimed.com/public_html/test"
        EnvFile     = ".env.test"
    }
    "biomedicacun" = @{
        Label       = "biomedicacun.activosamerimed.com"
        URL         = "https://biomedicacun.activosamerimed.com"
        Group       = "amerimed"
        SSH_User    = "u499728070"
        SSH_IP      = "46.202.197.220"
        SSH_Port    = "65002"
        RemotePath  = "/home/u499728070/domains/activosamerimed.com/public_html/biomedicacun"
        EnvFile     = ".env.biomedicacun"
    }
    "cdmxsanangel" = @{
        Label       = "cdmxsanangel.activosamerimed.com"
        URL         = "https://cdmxsanangel.activosamerimed.com"
        Group       = "amerimed"
        SSH_User    = "u499728070"
        SSH_IP      = "46.202.197.220"
        SSH_Port    = "65002"
        RemotePath  = "/home/u499728070/domains/activosamerimed.com/public_html/cdmxsanangel"
        EnvFile     = ".env.cdmxsanangel"
    }
    "manttocun" = @{
        Label       = "manttocun.activosamerimed.com"
        URL         = "https://manttocun.activosamerimed.com"
        Group       = "amerimed"
        SSH_User    = "u499728070"
        SSH_IP      = "46.202.197.220"
        SSH_Port    = "65002"
        RemotePath  = "/home/u499728070/domains/activosamerimed.com/public_html/manttocun"
        EnvFile     = ".env.manttocun"
    }
    "sistemascun" = @{
        Label       = "sistemascun.activosamerimed.com"
        URL         = "https://sistemascun.activosamerimed.com"
        Group       = "amerimed"
        SSH_User    = "u499728070"
        SSH_IP      = "46.202.197.220"
        SSH_Port    = "65002"
        RemotePath  = "/home/u499728070/domains/activosamerimed.com/public_html/sistemascun"
        EnvFile     = ".env.sistemascun"
    }
    # --- Servidor: activoconvalor.com ---
    "vsecure" = @{
        Label       = "vsecure.activoconvalor.com"
        URL         = "https://vsecure.activoconvalor.com"
        Group       = "activoconvalor"
        SSH_User    = "u306902845"
        SSH_IP      = "82.25.87.124"
        SSH_Port    = "65002"
        RemotePath  = "/home/u306902845/domains/activoconvalor.com/public_html/vsecure"
        EnvFile     = ""
    }
}

# =====================================================
# EXCLUSIONES COMUNES (archivos que NO se suben)
# =====================================================
$ExcludePatterns = @(
    ".git", ".vscode", "node_modules", "vendor",
    "*.log", "logs/*", "uploads/*", "cache/*",
    "*.sql", "DB_CREDENTIALS.txt",
    "deploy*.tar.gz", "deploy*.ps1",
    "ssh-connect.ps1", "cleanup-git.ps1", "update-subdominios.ps1",
    "config/.env.*",
    "ESTADO_FINAL_PROYECTO.md", "CONFIGURACION_SSH_SIN_CONTRASEÑA.md",
    "IMPLEMENTACION_SERVIDOR_COMPLETADA.md", "REPORTE_AUDITORIA_PRE_DESPLIEGUE.md",
    "RESUMEN_CORRECCIONES_COMPLETADAS.md", "README_AUDITORIA.md",
    "GUIA_IMPLEMENTACION_POST_AUDITORIA.md", "PRE_DEPLOYMENT_CHECKLIST.md",
    "INICIO_RAPIDO.md"
)

# =====================================================
# FUNCIONES
# =====================================================

function Show-Menu {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Cyan
    Write-Host "  SISTEMA DE DESPLIEGUE UNIFICADO" -ForegroundColor Cyan
    Write-Host "=========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  Instancias disponibles:" -ForegroundColor White
    Write-Host ""

    $idx = 1
    $menuMap = @{}

    # Agrupar por servidor
    $groups = @{}
    foreach ($key in ($Instances.Keys | Sort-Object)) {
        $inst = $Instances[$key]
        $g = $inst.Group
        if (-not $groups.ContainsKey($g)) { $groups[$g] = @() }
        $groups[$g] += @{ Key = $key; Instance = $inst }
    }

    foreach ($groupName in ($groups.Keys | Sort-Object)) {
        Write-Host "  [$groupName]" -ForegroundColor DarkCyan
        foreach ($item in $groups[$groupName]) {
            $k = $item.Key
            $inst = $item.Instance
            Write-Host "    $idx) $($inst.Label)" -ForegroundColor Gray
            $menuMap[$idx] = $k
            $idx++
        }
        Write-Host ""
    }

    Write-Host "  -----------------------------------" -ForegroundColor DarkGray
    Write-Host "    A) Todas las instancias" -ForegroundColor Yellow
    Write-Host "    M) Todas de amerimed" -ForegroundColor Yellow
    Write-Host "    V) Solo activoconvalor" -ForegroundColor Yellow
    Write-Host "    Q) Salir" -ForegroundColor Red
    Write-Host ""

    $choice = Read-Host "  Selecciona opción (número, letra, o nombres separados por coma)"

    if ($choice -eq "Q" -or $choice -eq "q") { return @() }
    if ($choice -eq "A" -or $choice -eq "a") { return @($Instances.Keys) }
    if ($choice -eq "M" -or $choice -eq "m") {
        return @($Instances.Keys | Where-Object { $Instances[$_].Group -eq "amerimed" })
    }
    if ($choice -eq "V" -or $choice -eq "v") {
        return @($Instances.Keys | Where-Object { $Instances[$_].Group -eq "activoconvalor" })
    }

    # Intentar como número(s)
    $selected = @()
    foreach ($part in ($choice -split ",")) {
        $part = $part.Trim()
        if ($part -match '^\d+$' -and $menuMap.ContainsKey([int]$part)) {
            $selected += $menuMap[[int]$part]
        } elseif ($Instances.ContainsKey($part)) {
            $selected += $part
        } else {
            Write-Host "  Opción no válida: $part" -ForegroundColor Red
        }
    }
    return $selected
}

function Build-Package {
    $tempFile = "deploy-unified.tar.gz"
    Write-Host "[EMPAQUETANDO] Comprimiendo código local..." -ForegroundColor Yellow

    if (Test-Path $tempFile) { Remove-Item $tempFile -Force }

    $excludeArgs = $ExcludePatterns | ForEach-Object { "--exclude=$_" }
    & tar -czf $tempFile @excludeArgs *

    if (-not (Test-Path $tempFile)) {
        Write-Host "ERROR: No se pudo crear el archivo comprimido" -ForegroundColor Red
        exit 1
    }

    $fileSize = [math]::Round((Get-Item $tempFile).Length / 1MB, 2)
    Write-Host "OK Paquete creado: $fileSize MB" -ForegroundColor Green
    return $tempFile
}

function Deploy-Instance {
    param(
        [string]$Key,
        [hashtable]$Instance,
        [string]$PackageFile,
        [hashtable]$UploadedServers
    )

    $label    = $Instance.Label
    $sshUser  = $Instance.SSH_User
    $sshIP    = $Instance.SSH_IP
    $sshPort  = $Instance.SSH_Port
    $remote   = $Instance.RemotePath
    $sshHost  = "${sshUser}@${sshIP}"
    $serverKey = "${sshUser}@${sshIP}"
    $homeDir  = "/home/${sshUser}"

    Write-Host ""
    Write-Host "-------------------------------------------" -ForegroundColor DarkGray
    Write-Host "Desplegando: $label" -ForegroundColor Cyan
    Write-Host "  Destino: $remote" -ForegroundColor Gray

    # Subir paquete solo si no se ha subido a este servidor aún
    if (-not $UploadedServers.ContainsKey($serverKey)) {
        Write-Host "  [1] Subiendo paquete al servidor $sshIP..." -ForegroundColor Yellow
        scp -o StrictHostKeyChecking=no -P $sshPort $PackageFile ${sshHost}:${homeDir}/$PackageFile 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-Host "  ERROR al subir archivo a $sshIP" -ForegroundColor Red
            return $false
        }
        $UploadedServers[$serverKey] = $true
        Write-Host "  OK Paquete subido" -ForegroundColor Green
    } else {
        Write-Host "  [1] Paquete ya existe en $sshIP (reutilizando)" -ForegroundColor DarkGreen
    }

    # Extraer en destino (sin sobreescribir .env)
    Write-Host "  [2] Extrayendo código..." -ForegroundColor Yellow
    $extractCmd = "mkdir -p $remote && cd $remote && tar -xzf ${homeDir}/$PackageFile --exclude='config/.env' && mkdir -p uploads logs cache && chmod 755 uploads logs cache 2>/dev/null"
    ssh -o StrictHostKeyChecking=no -p $sshPort $sshHost $extractCmd 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  ERROR al extraer en $remote" -ForegroundColor Red
        return $false
    }
    Write-Host "  OK Código extraído" -ForegroundColor Green

    # Limpiar OPcache PHP (evita servir versiones cacheadas del bytecode)
    Write-Host "  [2b] Limpiando OPcache..." -ForegroundColor Yellow
    $opcacheCmd = 'php -r ''if(function_exists("opcache_reset")){opcache_reset();}'' 2>/dev/null; true'
    ssh -o StrictHostKeyChecking=no -p $sshPort $sshHost $opcacheCmd 2>&1 | Out-Null
    Write-Host "  OK OPcache limpiado" -ForegroundColor Green
    Write-Host "  [3] Configurando permisos..." -ForegroundColor Yellow
    $permsCmd = "cd $remote && chmod 600 config/.env 2>/dev/null; find . -type f -name '*.php' -exec chmod 644 {} + 2>/dev/null; find . -type d -exec chmod 755 {} + 2>/dev/null; chmod 600 config/.env 2>/dev/null"
    ssh -o StrictHostKeyChecking=no -p $sshPort $sshHost $permsCmd 2>&1 | Out-Null
    Write-Host "  OK Permisos configurados" -ForegroundColor Green

    Write-Host "  LISTO -> $($Instance.URL)" -ForegroundColor Green
    return $true
}

# =====================================================
# EJECUCIÓN PRINCIPAL
# =====================================================

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  DESPLIEGUE UNIFICADO - $(Get-Date -Format 'yyyy-MM-dd HH:mm')" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

# Resolver targets
$selectedKeys = @()

if ($Target -eq "") {
    # Menú interactivo
    $selectedKeys = Show-Menu
} elseif ($Target -eq "all") {
    $selectedKeys = @($Instances.Keys)
} elseif ($Target -eq "amerimed") {
    $selectedKeys = @($Instances.Keys | Where-Object { $Instances[$_].Group -eq "amerimed" })
} elseif ($Target -eq "activoconvalor") {
    $selectedKeys = @($Instances.Keys | Where-Object { $Instances[$_].Group -eq "activoconvalor" })
} else {
    # Lista separada por comas: prod,test,vsecure
    foreach ($t in ($Target -split ",")) {
        $t = $t.Trim()
        if ($Instances.ContainsKey($t)) {
            $selectedKeys += $t
        } else {
            Write-Host "Instancia no encontrada: '$t'" -ForegroundColor Red
            Write-Host "Disponibles: $($Instances.Keys -join ', ')" -ForegroundColor Yellow
            exit 1
        }
    }
}

if ($selectedKeys.Count -eq 0) {
    Write-Host "No se seleccionó ninguna instancia. Saliendo." -ForegroundColor Yellow
    exit 0
}

# Confirmar
Write-Host ""
Write-Host "Se desplegará en $($selectedKeys.Count) instancia(s):" -ForegroundColor White
foreach ($k in $selectedKeys) {
    Write-Host "  -> $($Instances[$k].Label)" -ForegroundColor Gray
}
Write-Host ""
$confirm = Read-Host "¿Continuar? (S/n)"
if ($confirm -eq "n" -or $confirm -eq "N") {
    Write-Host "Cancelado." -ForegroundColor Yellow
    exit 0
}

# Empaquetar (una sola vez)
$package = Build-Package

# Desplegar en cada instancia
$uploadedServers = @{}
$results = @{}
$successCount = 0
$failCount = 0

foreach ($key in $selectedKeys) {
    $inst = $Instances[$key]
    $ok = Deploy-Instance -Key $key -Instance $inst -PackageFile $package -UploadedServers $uploadedServers
    $results[$key] = $ok
    if ($ok) { $successCount++ } else { $failCount++ }
}

# Limpiar paquetes remotos
Write-Host ""
Write-Host "[LIMPIEZA] Eliminando paquetes temporales..." -ForegroundColor Yellow
foreach ($serverKey in $uploadedServers.Keys) {
    $parts = $serverKey -split "@"
    $user = $parts[0]
    $ip = $parts[1]
    # Obtener puerto del primer instance que use este servidor
    $port = "65002"
    foreach ($k in $Instances.Keys) {
        if ("$($Instances[$k].SSH_User)@$($Instances[$k].SSH_IP)" -eq $serverKey) {
            $port = $Instances[$k].SSH_Port
            break
        }
    }
    ssh -o StrictHostKeyChecking=no -p $port $serverKey "rm -f /home/${user}/${package}" 2>&1 | Out-Null
}
Remove-Item $package -Force -ErrorAction SilentlyContinue
Write-Host "OK Limpieza completada" -ForegroundColor Green

# Resumen final
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  RESUMEN DE DESPLIEGUE" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

foreach ($key in $selectedKeys) {
    $inst = $Instances[$key]
    if ($results[$key]) {
        Write-Host "  OK $($inst.Label)" -ForegroundColor Green
        Write-Host "     $($inst.URL)" -ForegroundColor DarkGreen
    } else {
        Write-Host "  FALLO $($inst.Label)" -ForegroundColor Red
    }
}

Write-Host ""
if ($failCount -eq 0) {
    Write-Host "  Despliegue completado: $successCount/$($selectedKeys.Count) exitosos" -ForegroundColor Green
} else {
    Write-Host "  Despliegue parcial: $successCount exitosos, $failCount fallidos" -ForegroundColor Yellow
}
Write-Host ""
