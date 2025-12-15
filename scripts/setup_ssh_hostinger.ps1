$ErrorActionPreference = 'Stop'

$hostName = '217.196.54.164'
$port = 65002
$user = 'u228864460'
$alias = 'hostinger-system'
$keyName = 'hostinger_system_ed25519'

$sshDir = Join-Path $env:USERPROFILE '.ssh'
New-Item -ItemType Directory -Force -Path $sshDir | Out-Null

$keyPath = Join-Path $sshDir $keyName
$pubPath = $keyPath + '.pub'

if (-not (Test-Path -Path $keyPath)) {
  Write-Host "Generando llave: $keyPath"
  # Nota: en algunas combinaciones PowerShell/OpenSSH, los argumentos vacíos pueden comportarse raro.
  # Usamos cmd.exe para garantizar -N "" y evitar prompts (modo no interactivo).
  $cmd = 'ssh-keygen -q -t ed25519 -f "' + $keyPath + '" -N ""'
  cmd.exe /c $cmd | Out-Null

  if (-not (Test-Path -Path $pubPath)) {
    throw "No se generó la llave pública: $pubPath"
  }
} else {
  Write-Host "Llave ya existe: $keyPath"
}

$configPath = Join-Path $sshDir 'config'
if (-not (Test-Path -Path $configPath)) {
  New-Item -ItemType File -Force -Path $configPath | Out-Null
}

$cfgRaw = Get-Content -Raw -Path $configPath
$already = $false
foreach ($line in ($cfgRaw -split "`r?`n")) {
  if ($line -match "^Host\s+$alias\s*$") { $already = $true; break }
}

if (-not $already) {
  $lines = @(
    "",
    "Host $alias",
    "  HostName $hostName",
    "  User $user",
    "  Port $port",
    "  IdentityFile ~/.ssh/$keyName",
    "  IdentitiesOnly yes",
    "  ServerAliveInterval 30",
    "  ServerAliveCountMax 3",
    "  ControlMaster auto",
    "  ControlPersist 10m",
    "  ControlPath ~/.ssh/cm_%r@%h:%p"
  )
  Add-Content -Path $configPath -Value ($lines -join "`r`n")
  Write-Host "Alias agregado a: $configPath"
} else {
  Write-Host "Alias ya existe en: $configPath"
}

Write-Host ""
Write-Host "Siguiente paso (una sola vez, te pedirá password):"
Write-Host "  Get-Content `"$pubPath`" | ssh $alias `"mkdir -p ~/.ssh; chmod 700 ~/.ssh; cat >> ~/.ssh/authorized_keys; chmod 600 ~/.ssh/authorized_keys`""
Write-Host ""
Write-Host "Luego ya podrás usar:"
Write-Host "  ssh $alias"
Write-Host "  ssh $alias 'cd public_html && ls -la'"
