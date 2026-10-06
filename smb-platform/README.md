# SMB — Device Management & Tracking Platform

Platform manajemen perangkat Android milik/berizin pengguna sendiri.

## Arsitektur

```
                  CLOUDFLARE
                      |
             lacaksmbbot.com
                      |
          +-----------+-----------+
          |                       |
        HTTPS                    WSS
          |                       |
          v                       v
      LARAVEL API            ADONISJS
          |                       |
          +----------+------------+
                     |
                 POSTGRESQL
                     |
                   REDIS
                     |
       +-------------+-------------+
       |             |             |
       v             v             v
   SMB Lacak     SMB Master    Telegram
   Android        Android        Bot
```

## Komponen

| Direktori | Komponen | Teknologi |
|---|---|---|
| `smb-api/` | Laravel API (business logic, auth, RBAC, device management) | PHP 8.3, Laravel 11, PostgreSQL, Sanctum, Spatie Permission, Pest |
| `smb-gateway/` | AdonisJS Gateway (WebSocket, realtime, presence, command delivery) | Node.js LTS, AdonisJS 6 |
| `smb-web/` | Web Master dashboard | Vue 3, Vite, Tailwind CSS, Pinia, TanStack Table, ApexCharts |
| `smb-tracker-android/` | SMB Lacak (device agent) | Kotlin, minSdk 26, targetSdk 36 |
| `smb-master-android/` | SMB Master (admin operator) | Kotlin, minSdk 26, targetSdk 36 |
| `smb-server-launcher/` | Windows launcher (SMB Server.exe) | .NET 8 |
| `cloudflare/` | Tunnel config & docs | cloudflared |
| `docs/` | Dokumentasi | Markdown |

## Requirements

- PHP 8.3+ & Composer 2
- Node.js LTS 20/22 & npm
- PostgreSQL 16
- Redis 7
- JDK 17 + Android SDK 36 (compileSdk 36, targetSdk 36, minSdk 26)
- .NET 8 SDK (Windows launcher)
- cloudflared

## Environment

```
LOCAL / STAGING / PRODUCTION
```

Production URLs:
- APP: https://app.lacaksmbbot.com
- API: https://api.lacaksmbbot.com
- WS: wss://ws.lacaksmbbot.com
- Download: https://download.lacaksmbbot.com

## Development

Lihat `docs/deployment.md` (segera), `docs/architecture.md`.

## Git workflow

Branch: `main`, `develop`, `feature/*`, `fix/*`, `hotfix/*`.
Commit prefix: `feat:`, `fix:`, `refactor:`, `docs:`, `chore:`.

## Dokumentasi

- `docs/architecture.md`
- `docs/api.md`
- `docs/websocket.md`
- `docs/android-compatibility.md`
- `docs/cloudflare.md`
- `docs/deployment.md`
- `docs/security.md`
- `docs/testing.md`
- `docs/DECISIONS.md`
