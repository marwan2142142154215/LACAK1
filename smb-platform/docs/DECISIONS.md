# DECISIONS

## 1. Struktur repo
Monorepo `smb-platform/` berisi komponen terpisah sesuai spec section 60.

## 2. Toolchain
- PHP 8.3.33 (winget PHP.PHP.8.3) + pdo_pgsql/pgsql/zip/curl/mbstring/intl/gd/openssl diaktifkan via php.ini
- Composer 2.10.3 (composer.phar + Links\composer.cmd)
- Node.js v24 + npm
- PostgreSQL 17-alpine & Redis 7-alpine jalan di Docker (data persisten via volume)
- JDK 17 (Temurin) + Android SDK (platforms android-36) + Gradle 8.9
- .NET SDK 8.0.425 untuk smb-server-launcher

## 3. WebSocket authentication
Device tidak boleh auth hanya dengan device_id. Gateway memvalidasi device credential (token sha256) terhadap Laravel API `POST /api/v1/devices/{device}/verify-credential` sebelum menerima koneksi.

## 4. Command delivery
Fallback HTTPS polling disediakan ketika WSS gagal (`GET /api/v1/devices/{device}/commands/pending` + `POST /api/v1/commands/{command}/ack`). Command yang expired tidak dieksekusi. Idempotency key + command_id unique constraint mencegah duplicate execution.

## 5. Camera & Location
Capability detection jujur: jika Android membatasi background camera/location, status FAILED dengan reason yang jelas. Tidak ada klaim false-positive.

## 6. Device Owner
Fitur lock task mode hanya aktif jika app memang Device Owner. Jika tidak, ditampilkan "Fitur membutuhkan perangkat terkelola."

## 7. Port lokal
- Laravel dev: 127.0.0.1:8100 (menghindari tabrakan dengan service lain di :8000)
- AdonisJS HTTP: 3000 (lokal dev) — di tunnel production ws.lacaksmbbot.com forward ke :8080
- WS server: 8080 (AdonisJS provider)

## 8. Android minSdk
Tetap 26 sesuai requirement; tidak dinaikkan.

## 9. Telegram integration
Poller berbasis `php artisan telegram:poll` (long-poll getUpdates) yang memetakan Telegram ID → user → permission → command creation. Tidak ada bypass: hanya Telegram ID terdaftar di `telegram_accounts` yang bisa membuat command. Webhook production opsional (set webhook ke `/api/v1/telegram/webhook` jika ingin tanpa poller).

## 10. Environment directories
Semua komponen memakai `.env` lokal — tidak ada secret di repo.

## 11. Real GPS di SMB Lacak
Lokasi diambil via `FusedLocationProviderClient.lastLocation` (play-services-location 21.3.0 + kotlinx-coroutines-play-services). Jika permission tidak ada atau lokasi tidak tersedia, `null` dikirim — tidak ada koordinat palsu. Lokasi disertakan pada heartbeat HTTP dan WS.

## 12. Observability & test web
Sentry di SMB Web bersifat opt-in (`VITE_SENTRY_DSN`); tanpa DSN kode ter-tree-shake. SMB Web memiliki test Vitest (api interceptor + Login.vue) yang berjalan via `npm test`.

## 13. Telegram
Token bot dimasukkan ke `.env` lokal saja (tidak di-commit). CA bundle PHP (`curl.cainfo`/`openssl.cafile`) di-set ke `cacert.pem` untuk memperbaiki verifikasi TLS ke api.telegram.org pada build PHP winget.

## 14. Media storage (DigitalOcean Spaces)
Disk `spaces` (S3-compatible) dipilih otomatis hanya bila `DO_SPACES_KEY` + `DO_SPACES_BUCKET` terisi; sebaliknya disk `local` privat. Download selalu lewat API (`/media/{media}/download`) agar bucket tidak perlu publik. Kredensial `DO_SPACES_*` hanya di `.env`.

## 15. Kamera Android
Capture nyata via CameraX hanya saat Activity di depan (Android memblokir kamera background). Bila tidak memungkinkan, device mengirim ACK `FAILED` dengan alasan eksplisit — tidak ada klaim sukses palsu. Hasil capture diunggah ke media API.
