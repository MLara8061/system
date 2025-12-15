param(
  [string]$Root = ""
)

if ([string]::IsNullOrWhiteSpace($Root)) {
  $Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
}

function S([int[]]$CodePoints) {
  return -join ($CodePoints | ForEach-Object { [char]$_ })
}

$targets = @(
  (Join-Path $Root 'app\views'),
  (Join-Path $Root 'legacy')
)

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$map = [ordered]@{}

# Inverted punctuation and degree sign: "Â¿", "Â¡", "Â°"
$map[(S @(0x00C2,0x00BF))] = (S @(0x00BF))
$map[(S @(0x00C2,0x00A1))] = (S @(0x00A1))
$map[(S @(0x00C2,0x00B0))] = (S @(0x00B0))

# Common UTF-8 -> CP1252 mojibake for Spanish letters: "Ã¡", "Ã©", ...
$map[(S @(0x00C3,0x00A1))] = (S @(0x00E1)) # á
$map[(S @(0x00C3,0x00A9))] = (S @(0x00E9)) # é
$map[(S @(0x00C3,0x00AD))] = (S @(0x00ED)) # í (often contains soft hyphen)
$map[(S @(0x00C3,0x00B3))] = (S @(0x00F3)) # ó
$map[(S @(0x00C3,0x00BA))] = (S @(0x00FA)) # ú
$map[(S @(0x00C3,0x00B1))] = (S @(0x00F1)) # ñ
$map[(S @(0x00C3,0x00BC))] = (S @(0x00FC)) # ü

# Uppercase (some appear as control/curly-quote characters depending on prior decode)
$map[(S @(0x00C3,0x0081))] = (S @(0x00C1)) # Á  (C3 81 -> "Ã" + U+0081)
$map[(S @(0x00C3,0x0089))] = (S @(0x00C9)) # É  (C3 89 -> "Ã" + U+0089)
$map[(S @(0x00C3,0x008D))] = (S @(0x00CD)) # Í  (C3 8D -> "Ã" + U+008D)
$map[(S @(0x00C3,0x201C))] = (S @(0x00D3)) # Ó  (C3 93 -> "Ã" + “)
$map[(S @(0x00C3,0x0161))] = (S @(0x00DA)) # Ú  (C3 9A -> "Ã" + š)
$map[(S @(0x00C3,0x2018))] = (S @(0x00D1)) # Ñ  (C3 91 -> "Ã" + ‘)
$map[(S @(0x00C3,0x2014))] = (S @(0x00D7)) # ×  (C3 97 -> "Ã" + —)
$map[(S @(0x00C3,0x0153))] = (S @(0x00DC)) # Ü  (C3 9C -> "Ã" + œ)

# Some files may literally contain "Ã“", "Ãš", "Ã‘", "Ã—" etc.
$map[(S @(0x00C3,0x201C))] = (S @(0x00D3))
$map[(S @(0x00C3,0x0161))] = (S @(0x00DA))
$map[(S @(0x00C3,0x2018))] = (S @(0x00D1))
$map[(S @(0x00C3,0x2014))] = (S @(0x00D7))

# Smart quotes/dashes/ellipsis and arrows: "â€“", "â€”", "â€¦", "â†", "â†’"
$map[(S @(0x00E2,0x20AC,0x2013))] = (S @(0x2013)) # –
$map[(S @(0x00E2,0x20AC,0x2014))] = (S @(0x2014)) # —
$map[(S @(0x00E2,0x20AC,0x00A6))] = (S @(0x2026)) # …
$map[(S @(0x00E2,0x20AC,0x2122))] = (S @(0x2019)) # ’
$map[(S @(0x00E2,0x20AC,0x0153))] = (S @(0x201C)) # “
$map[(S @(0x00E2,0x20AC,0x009D))] = (S @(0x201D)) # ”
$map[(S @(0x00E2,0x2020,0x0090))] = (S @(0x2190)) # ←
$map[(S @(0x00E2,0x2020,0x0092))] = (S @(0x2192)) # →

# Down triangle used in docs: "â–¼"
$map[(S @(0x00E2,0x2013,0x00BC))] = (S @(0x25BC)) # ▼

$excludeRx = '\\(assets|plugins|node_modules|vendor|lib)\\'

$files = Get-ChildItem -Path $targets -Recurse -File -Include *.php,*.js |
  Where-Object { $_.FullName -notmatch $excludeRx }

$changed = 0
foreach ($f in $files) {
  $text = [System.IO.File]::ReadAllText($f.FullName)
  if ($text -notmatch '[\u00C3\u00C2\u00E2]') { continue }

  $new = $text
  foreach ($k in $map.Keys) {
    $new = $new.Replace($k, $map[$k])
  }

  if ($new -ne $text) {
    [System.IO.File]::WriteAllText($f.FullName, $new, $utf8NoBom)
    $changed++
  }
}

Write-Output ("Mojibake sweep done. Files changed: $changed")
