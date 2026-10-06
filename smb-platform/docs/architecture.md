# Arsitektur SMB Platform

## 1. Komponen & Tanggung Jawab

| Komponen | Tanggung jawab |
|---|---|
| smb-api (Laravel) | business logic, authentication (Sanctum + 2FA), authorization (Spatie Permission), user/site/team/device management, registration codes, command records, audit, OTP, policies, locations metadata, media metadata, reporting, transactions |
| smb-gateway (AdonisJS) | device gateway, WebSocket, realtime communication, device presence, heartbeat, reconnect handling, command delivery + acknowledgement, connection/session management, realtime event processing, integrasi realtime dashboard & Telegram |
| smb-web (Vue) | dashboard admin/operator (UI Bahasa Indonesia) |
| smb-tracker-android (SMB Lacak) | managed-device agent: registration, credential, heartbeat, WebSocket + HTTPS fallback, location, lock, command execution, boot recovery, foreground service |
| smb-master-android (SMB Master) | aplikasi admin operator; semua akses melalui server |
| smb-server-launcher | Windows launcher: start/stop Laravel, AdonisJS, Web, PostgreSQL check, Redis check, Cloudflare Tunnel check, health status, SMB Doctor |
| Telegram bot | interface master; semua perintah melalui server & authorization |
| PostgreSQL | source of truth (transaksional) |
| Redis | cache, presence, ephemeral state, non-transactional jobs |
| Cloudflare + cloudflared | DNS, WAF, DDoS protection, HTTPS/WSS, edge, tunnel (tanpa buka inbound port) |

## 2. Topologi Produksi

```
Internet -> Cloudflare -> cloudflared (Windows server)
                              |-- app.lacaksmbbot.com  -> smb-web (Vite preview / static serve)
                              |-- api.lacaksmbbot.com  -> Laravel (php artisan serve / octane)
                              |-- ws.lacaksmbbot.com   -> AdonisJS (node ace serve)
                              |-- download...          -> static files
```

Semua traffic production: HTTPS/WSS. Tidak ada `http://IP:PORT`.

## 3. Alur Command (WAJIB melalui server)

```
Telegram/Web/Master -> Laravel (authorize, create command, idempotency_key)
                    -> AdonisJS (dispatch via WS / HTTPS fallback)
                    -> SMB Lacak (execute, ack)
                    -> Laravel (status update, audit)
```

Status command: PENDING, QUEUED, SENT, DELIVERED, RECEIVED, EXECUTING, SUCCESS, FAILED, EXPIRED, CANCELLED.

## 4. Keamanan ringkas

- Tidak ada command langsung Telegram/Web/Master -> Android.
- Device auth via device credential (bukan hanya device_id).
- No IDOR: setiap akses dicek ownership/permission di server.
- OTP di-hash, single-use, short-expiry, rate-limited.
- Audit log untuk semua aksi sensitif.
- Secret hanya di `.env`, tidak di-commit.

## 5. Android Support

minSdk 26, targetSdk 36, compileSdk 36. Lihat `docs/android-compatibility.md`.
