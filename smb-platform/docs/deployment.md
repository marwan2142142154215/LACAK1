# Deployment — SMB Platform

## Environments

- **LOCAL**: docker-compose Postgres/Redis, Laravel `php artisan serve`, AdonisJS `node ace.js serve`, Vite dev server
- **STAGING/PRODUCTION**: Windows server runs: PostgreSQL (Docker), Redis (Docker), Laravel, AdonisJS, SMB Web (static serve), Cloudflare Tunnel

## Startup order

1. PostgreSQL
2. Redis
3. Laravel API
4. AdonisJS Gateway (HTTP :3333 + WS :8080)
5. SMB Web
6. Cloudflare Tunnel
7. Health verification (`php artisan smb:doctor`)

## Local dev quick start

```powershell
cd C:\Users\ACE COMPUTER\Documents\apk opencode\smb-platform
# PostgreSQL & Redis via docker
# terminals:
cd smb-api; php artisan serve --port=8100
cd smb-gateway; node ace.js serve --hmr
cd smb-web; npm run dev
```

- API: http://127.0.0.1:8100/api/v1
- Gateway WS: ws://127.0.0.1:8080/ws?device_id=&token=
- Web: http://127.0.0.1:5173

## Production checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://app.lacaksmbbot.com`
- API `API_BASE_URL=https://api.lacaksmbbot.com` di SMB Gateway & Android
- WS `wss://ws.lacaksmbbot.com/ws` di Android
- Set secrets di `.env` (`TELEGRAM_BOT_TOKEN`, `CLOUDFLARE_TUNNEL_TOKEN`, `DB_PASSWORD`, `REDIS_PASSWORD`, `SMB_ADMIN_PASSWORD`)
- Install .NET launcher: `cd smb-server-launcher && dotnet publish -c Release`
- Jalankan `SmbServerLauncher start`

## Backup

- PostgreSQL: `docker exec apk-postgres-1 pg_dump -U smb_local smb_dev > backup.sql`
- Redis (optional cache): RDB attach di volume
- DigitalOcean Spaces untuk media (bukan DB)

## Retention & scheduling

`php artisan smb:retention` berjalan manual atau dijadwalkan via Task Scheduler: setiap hari `php artisan smb:retention`.
