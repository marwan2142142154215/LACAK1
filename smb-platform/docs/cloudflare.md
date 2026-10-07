# Cloudflare Setup — SMB Platform

Domain resmi: `lacaksmbbot.com` (DNS di Cloudflare).

## 1. Subdomain routing

| Hostname | Origin service | Catatan |
|---|---|---|
| `app.lacaksmbbot.com` | `http://127.0.0.1:4173` | SMB Web — bundle produksi `smb-web/dist` disajikan `scripts/static-server.js` (SPA) |
| `api.lacaksmbbot.com` | `http://127.0.0.1:8100` | Laravel API |
| `ws.lacaksmbbot.com` | `http://127.0.0.1:8080` | AdonisJS WebSocket gateway |
| `download.lacaksmbbot.com` | `http://127.0.0.1:8081` | static dir `smb-downloads` (APK) |
| `broker.lacaksmbbot.com` | `https://127.0.0.1:8787` | (milik proyek lain, dipertahankan) |

Semua production wajib HTTPS/WSS. Status terverifikasi:
`api` → 200, `ws` → 426 (server WS hidup), `app` → 200, `download` → 200.

## 2. Cloudflare Tunnel (cloudflared)

Tunnel: **`LACAKSMB`** (`016877bc-4115-4700-9e16-0ad256e82d64`), **remotely-managed** — service Windows berjalan dengan `--token-file C:\ProgramData\cloudflared\token`, jadi ingress diatur dari sisi Cloudflare (dashboard atau API), bukan `config.yml` lokal.

Ingress aktif (urut, catch-all terakhir):

```json
{
  "config": {
    "ingress": [
      { "hostname": "broker.lacaksmbbot.com", "service": "https://127.0.0.1:8787", "originRequest": { "noTLSVerify": true } },
      { "hostname": "api.lacaksmbbot.com",      "service": "http://127.0.0.1:8100" },
      { "hostname": "ws.lacaksmbbot.com",       "service": "http://127.0.0.1:8080" },
      { "hostname": "app.lacaksmbbot.com",      "service": "http://127.0.0.1:4173" },
      { "hostname": "download.lacaksmbbot.com", "service": "http://127.0.0.1:8081" },
      { "service": "http_status:404" }
    ],
    "warp-routing": { "enabled": false }
  }
}
```

Push ingress via API (butuh token dengan izin **Cloudflare Tunnel: Edit**):

```powershell
Invoke-RestMethod -Method PUT `
  -Uri "https://api.cloudflare.com/client/v4/accounts/$CLOUDFLARE_ACCOUNT_ID/cfd_tunnel/$CLOUDFLARE_TUNNEL_ID/configurations" `
  -Headers @{ Authorization = "Bearer $CF_API_TOKEN"; 'Content-Type' = 'application/json' } `
  -Body $body
```

DNS CNAME dibuat dengan kredensial lokal (`cert.pem`), tanpa perlu API token DNS:

```powershell
cloudflared tunnel route dns LACAKSMB api.lacaksmbbot.com
cloudflared tunnel route dns LACAKSMB ws.lacaksmbbot.com
cloudflared tunnel route dns LACAKSMB app.lacaksmbbot.com
cloudflared tunnel route dns LACAKSMB download.lacaksmbbot.com
```

Service auto-start (sudah terpasang): `Get-Service Cloudflared` → `Running` / `Automatic`.

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

`CLOUDFLARE_ACCOUNT_ID`, `CLOUDFLARE_TUNNEL_ID`, dan token API hanya di `.env` lokal (git-ignored) atau secret manager. `.env` saat ini sudah berisi `CLOUDFLARE_ACCOUNT_ID` dan `CLOUDFLARE_TUNNEL_ID`; **jangan commit nilai apa pun dari file `.env`.**

```
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_TUNNEL_ID=
CF_API_TOKEN=            # hanya untuk operasi API manual, jangan di-commit
```

## 8. Operasional (scripts/)

| Script | Fungsi |
|---|---|
| `scripts/start-all.ps1` | Nyalakan semua service lokal (idempotent) |
| `scripts/stop-all.ps1` | Matikan service aplikasi (tunnel tidak disentuh) |
| `scripts/status.ps1` | Cek port lokal + endpoint publik via tunnel |
| `scripts/register-autostart.ps1` | (opsional, perlu admin) daftar scheduled task logon |
| `scripts/autostart.cmd` | Dipanggil dari Startup folder saat logon |
| `scripts/publish-downloads.ps1` | Salin APK hasil build ke `smb-downloads/` |

Autostart tanpa admin sudah dipasang lewat Startup folder:
`%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\SMB Platform.cmd`
→ menunggu 45 detik (Docker Desktop), lalu menjalankan `start-all.ps1`.
