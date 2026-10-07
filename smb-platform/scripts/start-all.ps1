# SMB Platform — start all services (idempotent).
# Requires: PHP, Node.js, Docker Desktop, cloudflared service.
# Usage: powershell -ExecutionPolicy Bypass -File scripts\start-all.ps1

$ErrorActionPreference = 'SilentlyContinue'
$env:Path = [System.Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path','User') + ';C:\Program Files (x86)\cloudflared'

$Root    = Split-Path -Parent $PSScriptRoot
$ApiDir  = Join-Path $Root 'smb-api'
$GwDir   = Join-Path $Root 'smb-gateway'
$WebDist = Join-Path $Root 'smb-web\dist'
$DlDir   = Join-Path $Root 'smb-downloads'
$SrvJs   = Join-Path $Root 'scripts\static-server.js'
$LogDir  = Join-Path $Root 'logs'
New-Item -ItemType Directory -Force -Path $LogDir | Out-Null

function Test-Port([int]$Port) {
  try {
    $c = New-Object System.Net.Sockets.TcpClient
    $iar = $c.BeginConnect('127.0.0.1', $Port, $null, $null)
    $ok = $iar.AsyncWaitHandle.WaitOne(1500)
    $connected = $ok -and $c.Connected
    $c.Close()
    return $connected
  } catch { return $false }
}

function Start-Hidden([string]$Label, [string]$File, [string[]]$Args, [string]$WorkDir, [string]$Log) {
  if (-not $File) { Write-Host "[FAIL] $Label — executable not found"; return }
  Start-Process -WindowStyle Hidden -FilePath $File -ArgumentList $Args -WorkingDirectory $WorkDir `
    -RedirectStandardOutput $Log -RedirectStandardError ($Log -replace '\.log$','.err.log')
  Write-Host "[START] $Label"
}

Write-Host "== SMB Platform start =="

# 1. Docker backing services
docker start apk-postgres-1 | Out-Null
docker start smb-redis        | Out-Null
docker start apk-redis-1      | Out-Null

# 2. Laravel API :8100
if (Test-Port 8100) { Write-Host "[SKIP] Laravel API already on :8100" }
else {
  $php = (Get-Command php).Source
  Start-Hidden 'Laravel API :8100' $php @('artisan','serve','--host=127.0.0.1','--port=8100') $ApiDir (Join-Path $LogDir 'laravel.log')
}

# 3. AdonisJS gateway (HTTP :3333 + WS :8080)
if (Test-Port 8080) { Write-Host "[SKIP] WebSocket already on :8080" }
else {
  $node = (Get-Command node).Source
  Start-Hidden 'AdonisJS Gateway :3333/:8080' $node @('ace.js','serve','--hmr') $GwDir (Join-Path $LogDir 'gateway.log')
}

# 4. Web dashboard static host :4173
if (-not (Test-Path (Join-Path $WebDist 'index.html'))) {
  Write-Host "[BUILD] smb-web production bundle"
  $env:VITE_API_BASE_URL = 'https://api.lacaksmbbot.com/api/v1'
  Push-Location (Join-Path $Root 'smb-web'); npm.cmd run build | Out-Null; Pop-Location
}
if (Test-Port 4173) { Write-Host "[SKIP] Web dashboard already on :4173" }
else {
  $node = (Get-Command node).Source
  Start-Hidden 'Web dashboard :4173' $node @($SrvJs,$WebDist,'4173','spa') $Root (Join-Path $LogDir 'web.log')
}

# 5. APK download host :8081
if (Test-Port 8081) { Write-Host "[SKIP] Download host already on :8081" }
else {
  $node = (Get-Command node).Source
  Start-Hidden 'Download host :8081' $node @($SrvJs,$DlDir,'8081') $Root (Join-Path $LogDir 'download.log')
}

# 6. Telegram poller
$tgRunning = Get-CimInstance Win32_Process -Filter "Name='php.exe'" | Where-Object { $_.CommandLine -like '*telegram:poll*' }
if ($tgRunning) { Write-Host "[SKIP] Telegram poller already running" }
else {
  $php = (Get-Command php).Source
  Start-Hidden 'Telegram poller' $php @('artisan','telegram:poll') $ApiDir (Join-Path $LogDir 'telegram.log')
}

# 7. Cloudflared tunnel (Windows service, auto-start)
$svc = Get-Service Cloudflared
if ($svc -and $svc.Status -ne 'Running') { Start-Service Cloudflared }
Write-Host "[OK] Cloudflared service: $((Get-Service Cloudflared).Status)"

Start-Sleep 3
Write-Host "== Port status =="
foreach ($p in 8100,3333,8080,4173,8081) {
  Write-Host ("  :{0} {1}" -f $p, $(if (Test-Port $p) { 'UP' } else { 'DOWN' }))
}
Write-Host "== Done. Run scripts\status.ps1 for the full picture. =="
