@echo off
REM SMB Platform autostart — launched from the user's Startup folder at logon.
REM Waits for Docker Desktop / network, then runs the idempotent start script.
timeout /t 45 /nobreak >nul
powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "%~dp0start-all.ps1"
