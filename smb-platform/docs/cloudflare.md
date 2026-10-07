# Cloudflare Setup — SMB Platform

Domain resmi: `lacaksmbbot.com` (DNS di Cloudflare).

## 1. Subdomain routing

| Hostname | Origin service | Catatan |
|---|---|---|
| `app.lacaksmbbot.com` | `http://127.0.0.1:5173` | SMB Web (Vite) — produksi `smb-web/dist` static |
| `api.lacaksmbbot.com` | `http://127.0.0.1:8100` | Laravel API |
| `ws.lacaksmbbot.com` | `http://127.0.0.1:3333` (WS `:8080`) | AdonisJS gateway — perlu WS route ke 8080 |
| `download.lacaksmbbot.com` | static dir `smb-downloads` | Download APK & docs |

Semua production wajib HTTPS/WSS.

## 2. Cloudflare Tunnel (cloudflared)

Install cloudflared sebagai Windows service:

```powershell
cloudflared service install <TUNNEL_TOKEN>
```

Buat tunnel di Cloudflare Zero Trust → Networks → Tunnels, lalu arahkan hostname di atas ke origin service lokal. Tunnel menangani HTTPS/WSS, jadi tidak perlu membuka port inbound router.

Konfigurasi tunnel (`%ProgramData%\cloudflared\config.yml`):

```yaml
tunnel: <TUNNEL_ID>
credentials-file: C:\ProgramData\cloudflared\<TUNNEL_ID>.json

ingress:
  - hostname: app.lacaksmbbot.com
    service: http://127.0.0.1:5173
  - hostname: api.lacaksmbbot.com
    service: http://127.0.0.1:8100
  - hostname: ws.lacaksmbbot.com
    service: http://127.0.0.1:8080
  - hostname: download.lacaksmbbot.com
    service: http://127.0.0.1:8081
  - service: http_status:404
```

Mulai & set auto-start:

```powershell
cloudflared service install
sc config cloudflared start= auto
```

## 3. SSL/TLS

Cloudflare → SSL/TLS → mode: **Full (strict)** karena origin hanya bisa diakses via tunnel. Jika menggunakan Cloudflare Origin Cert di origin, pilih **Full (strict)**; jika hanya tunnel, **Flexible** dapat diizinkan namun minimal gunakan https ke Cloudflare edge.

## 4. WAF & rate limiting

- Aktifkan WAF Managed Ruleset (OWASP + Cloudflare Specials)
- Rate limiting rule untuk `/api/v1/auth/login`, `/api/v1/devices/register`, `/api/v1/devices/{id}/otp/verify` → 5 req/min per IP
- Aktifkan Bot Fight Mode

## 5. WebSocket support

Cloudflare WebSocket sudah include di DNS proxy — WSS terminasi di edge lalu diteruskan ke `ws.lacaksmbbot.com` → tunnel → AdonisJS :8080.

## 6. Monitoring & failover

- Jika Tunnel putus, semua service lokal tetap jalan (PostgreSQL, Redis, Laravel, AdonisJS, Web).
- `SMB Doctor` (`php artisan smb:doctor`) mendeteksi Cloudflare Tunnel sebagai FAIL bila cloudflared tidak berjalan.
- Untuk memverifikasi tunnel: `cloudflared tunnel info <TUNNEL_ID>`, `cloudflared tunnel list`.

## 7. Secrets (TIDAK di-commit)

`CLOUDFLARE_ACCOUNT_ID`, `CLOUDFLARE_TUNNEL_ID`, `CLOUDFLARE_TUNNEL_TOKEN` harus di `.env` (lokal) atau secret manager. Contoh `.env`:

```
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_TUNNEL_ID=
CLOUDFLARE_TUNNEL_TOKEN=
```

Tidak ada secret ini di repo.
