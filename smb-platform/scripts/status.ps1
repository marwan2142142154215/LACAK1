# SMB Platform — health/status overview across local services and the public tunnel.
# Usage: powershell -ExecutionPolicy Bypass -File scripts\status.ps1

$ErrorActionPreference = 'SilentlyContinue'
$ProgressPreference = 'SilentlyContinue'

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

Write-Host '== Local services =='
$ports = @{
  'Laravel API'     = 8100
  'AdonisJS HTTP'   = 3333
  'WebSocket (WS)'  = 8080
  'Web dashboard'   = 4173
  'Download host'   = 8081
  'PostgreSQL'      = 5432
  'Redis'           = 6379
}
foreach ($k in $ports.Keys) {
  Write-Host ("  {0,-16} :{1,-5} {2}" -f $k, $ports[$k], $(if (Test-Port $ports[$k]) { 'UP' } else { 'DOWN' }))
}

Write-Host '== Cloudflared service =='
$svc = Get-Service Cloudflared
Write-Host "  Status: $($svc.Status)  StartType: $($svc.StartType)"

Write-Host '== Public endpoints (via tunnel) =='
$urls = @(
  'https://api.lacaksmbbot.com/api/v1/health',
  'https://ws.lacaksmbbot.com/',
  'https://app.lacaksmbbot.com/',
  'https://download.lacaksmbbot.com/'
)
foreach ($u in $urls) {
  try {
    $r = Invoke-WebRequest -Uri $u -TimeoutSec 20 -UseBasicParsing
    Write-Host ("  {0} -> HTTP {1}" -f $u, $r.StatusCode)
  } catch {
    $code = if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode } else { 'ERR' }
    Write-Host ("  {0} -> {1}" -f $u, $code)
  }
}
