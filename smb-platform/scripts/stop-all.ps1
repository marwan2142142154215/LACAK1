# SMB Platform — stop all application services (cloudflared service left running).
# Usage: powershell -ExecutionPolicy Bypass -File scripts\stop-all.ps1

$ErrorActionPreference = 'SilentlyContinue'

function Stop-ByPort([int]$Port, [string]$Label) {
  $lines = netstat -ano | Select-String ":$Port\s+.*LISTENING"
  foreach ($line in $lines) {
    $parts = ($line.Line -split '\s+') | Where-Object { $_ -ne '' }
    $pid = $parts[-1]
    if ($pid -match '^\d+$') {
      Stop-Process -Id ([int]$pid) -Force
      Write-Host "[STOP] $Label (pid $pid)"
    }
  }
}

Stop-ByPort 8100 'Laravel API'
Stop-ByPort 3333 'AdonisJS HTTP'
Stop-ByPort 8080 'WebSocket gateway'
Stop-ByPort 4173 'Web dashboard'
Stop-ByPort 8081 'Download host'

Get-CimInstance Win32_Process -Filter "Name='php.exe'" |
  Where-Object { $_.CommandLine -like '*telegram:poll*' } |
  ForEach-Object { Stop-Process -Id $_.ProcessId -Force; Write-Host "[STOP] Telegram poller (pid $($_.ProcessId))" }

Write-Host '== Application services stopped (cloudflared service untouched). =='
