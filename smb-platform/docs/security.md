# Security — SMB Platform

## Auth & sessions
- Laravel Sanctum token untuk admin (login menghasilkan `access_token`, berlaku per device, bisa di-revoke)
- 2FA TOTP via `pragmarx/google2fa` — wajib untuk admin pada production
- Device: UUID `device_id` + device credential acak 64-hex, disimpan sebagai `sha256` hash; tidak ada otentikasi by device_id saja
- WebSocket: token device di-query `?device_id=&token=` divalidasi ke Laravel `/devices/{device}/verify-credential`

## Authorization
- Spatie Laravel Permission: roles SUPER_ADMIN, ADMIN, OPERATOR, VIEWER
- Setiap route admin dilindungi middleware `permission:...`
- No IDOR: device update/delete/location/lock hanya via permission + Laravel `can` policy; device token hanya berlaku untuk device pemiliknya

## Command security
- Setiap command: `command_id` (UUID), `device_id`, `command_type`, `idempotency_key` (unique), `created_by`, `expires_at`, `status`
- Idempotency: command store cek `idempotency_key` duplikat → balikkan record existing
- Expire check: command expired tidak dieksekusi (`pending` filter `expires_at > now()`)
- ACK chain: QUEUED → SENT → DELIVERED → RECEIVED → EXECUTING → SUCCESS/FAILED/EXPIRED
- Tidak ada command langsung Telegram/Web → Android; semua melewati Laravel → AdonisJS → SMB Lacak

## OTP
- 6 digit `random_int`, disimpan `sha256` hash, single-use, 5 upaya gagal → blokir, expiry default 300s

## Rate limiting
- `POST /api/v1/auth/login` 5/min per IP
- `POST /api/v1/devices/register` 5/min per IP
- `otp/verify` max 5 attempts server-side

## Logging
- Semua aksi sensitif di `activity_log` (Spatie): login, logout, 2fa, registration, command create, lock, unlock, otp, telegram
- Tidak menyimpan password/OTP/token plaintext ke log

## Transport
- Production wajib HTTPS/WSS melalui Cloudflare Tunnel
- Cleartext traffic Android hanya di debug build
