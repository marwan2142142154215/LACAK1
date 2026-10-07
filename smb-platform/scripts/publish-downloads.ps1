# SMB Platform — publish freshly built APKs into the download host folder.
# Usage: powershell -ExecutionPolicy Bypass -File scripts\publish-downloads.ps1

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
$Dl   = Join-Path $Root 'smb-downloads'
New-Item -ItemType Directory -Force -Path $Dl | Out-Null

$map = @{
  'smb-tracker-android\app\build\outputs\apk\debug\app-debug.apk' = 'smb-lacak-debug.apk'
  'smb-master-android\app\build\outputs\apk\debug\app-debug.apk'  = 'smb-master-debug.apk'
}

foreach ($k in $map.Keys) {
  $src = Join-Path $Root $k
  if (-not (Test-Path $src)) { Write-Host "[SKIP] not built: $k"; continue }
  Copy-Item $src (Join-Path $Dl $map[$k]) -Force
  $size = [math]::Round((Get-Item $src).Length / 1MB, 2)
  Write-Host "[OK] $($map[$k])  ($size MB)"
}
