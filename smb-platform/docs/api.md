# API — SMB Platform (Laravel)

Base URL: `https://api.lacaksmbbot.com/api/v1` (dev: `http://127.0.0.1:8100/api/v1`)

Response sukses:

```json
{ "success": true, "message": "Operasi berhasil.", "data": {} }
```

Error:

```json
{ "success": false, "message": "Operasi gagal.", "errors": {} }
```

## Auth (admin)

| Method | Path | Description |
|---|---|---|
| POST | `/auth/login` | email, password, device_name, two_factor_code (jika 2FA aktif) → Bearer token |
| POST | `/auth/logout` | revoke current token |
| GET | `/auth/me` | profil + roles |
| POST | `/auth/2fa/enable` | generate secret + qr_url |
| POST | `/auth/2fa/verify` | aktifkan 2FA setelah kode valid |
| DELETE | `/auth/2fa` | nonaktifkan 2FA |

## Health

| GET | `/health` | cek PostgreSQL, Redis, dll |

## Devices

| GET | `/devices` | list (filter site_id, team_id, status, android_version; paginasi page/per_page) |
| GET | `/devices/{device}` | detail |
| PATCH | `/devices/{device}` | update name/site/team |
| DELETE | `/devices/{device}` | soft delete |
| POST | `/devices/register` | registrasi via registration code (throttle 5/min) |
| POST | `/devices/{device}/verify-credential` | validasi device token (dipakai AdonisJS) |

## Commands

| POST | `/devices/{device}/commands` | create command — body: command_type, payload?, expires_in?, idempotency_key (unique) |
| GET | `/devices/{device}/commands` | riwayat command |
| GET | `/devices/{device}/commands/pending` | QUEUED/SENT (untuk polling HTTPS fallback oleh device) |
| POST | `/commands/{command}/ack` | update status command (device token) |

## Lock / Unlock / OTP / Location / Camera

| POST | `/devices/{device}/lock` | kirim lock command |
| POST | `/devices/{device}/unlock` | kirim unlock command |
| POST | `/devices/{device}/otp` | admin generate OTP (hashed, single-use, 5 digit/6 digit) |
| POST | `/devices/{device}/otp/verify` | device verify OTP (throttle server-side 5 attempt → 429) |
| POST | `/devices/{device}/location/request` | request lokasi terbaru dari device |
| POST | `/devices/{device}/camera/request` | request capture (body: lens: front\|back) — device harus capability + permission |
| GET | `/devices/{device}/locations` | riwayat lokasi (paginasi) |
| GET | `/devices/{device}/locations/latest` | lokasi terakhir |
| GET | `/map/devices` | GeoJSON FeatureCollection untuk peta dashboard |

## Registration codes

| POST | `/registration-codes` | admin generate code untuk site_id + team_id (single-use, 24h expiry) |

## Pagination

Semua endpoint list mengembalikan:

```json
{ "success": true, "message": "...", "data": { "items": [], "total": 0, "current_page": 1, "last_page": 1, "per_page": 15 } }
```
