# SMB Platform — register (or remove) a logon scheduled task that starts all services.
# Usage:
#   powershell -ExecutionPolicy Bypass -File scripts\register-autostart.ps1
#   powershell -ExecutionPolicy Bypass -File scripts\register-autostart.ps1 -Remove
param([switch]$Remove)

$ErrorActionPreference = 'Stop'
$TaskName = 'SMB Platform'
$Root     = Split-Path -Parent $PSScriptRoot
$StartPs1 = Join-Path $Root 'scripts\start-all.ps1'

if ($Remove) {
  Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue
  Write-Host "[REMOVED] scheduled task '$TaskName'"
  return
}

if (-not (Test-Path $StartPs1)) { throw "start-all.ps1 not found at $StartPs1" }

$psExe  = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
$action = New-ScheduledTaskAction -Execute $psExe `
  -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$StartPs1`""

# At logon, delayed 1 minute so Docker Desktop has time to come up first.
$trigger = New-ScheduledTaskTrigger -AtLogOn
$trigger.Delay = 'PT1M'

$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" `
  -LogonType Interactive -RunLevel Highest

$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
  -StartWhenAvailable -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 5) `
  -ExecutionTimeLimit ([TimeSpan]::Zero)

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger `
  -Principal $principal -Settings $settings -Force | Out-Null

Write-Host "[OK] scheduled task '$TaskName' registered (runs at logon +1 min)."
Get-ScheduledTask -TaskName $TaskName | Select-Object TaskName, State | Format-Table -AutoSize
