# Testing — SMB Platform

## Backend (Pest)

```
cd smb-platform/smb-api
php artisan test
```

Suite yang ada (Feature):
- AuthTest: valid login, invalid login, unauthenticated device list
- RegistrationTest: registration code → register → heartbeat → code reuse rejected (422), invalid code (422)

Semua test menggunakan database PostgreSQL `smb_dev_test` per `phpunit.xml`.

## AdonisJS Gateway

Gateway diuji via integrasi: login admin → create command → WS client menerima `device.command` dalam ~4s. Poller `node ace.js serve --hmr` harus berjalan dan Laravel `--port=8100` harus aktif.

Manual WS test:
```
node --input-type=module -e "import WebSocket from 'ws'; const ws = new WebSocket('ws://127.0.0.1:8080/ws?device_id=...&token=...'); ws.on('message', m => console.log(m.toString()));"
```

## Web (Vitest)

```
cd smb-platform/smb-web
npm test
```

Suite yang ada (3 file tests via Vitest + jsdom + @vue/test-utils):
- `api.test.js`: base URL `/api/v1`, header `Authorization: Bearer` ada saat token tersimpan, absen saat tidak ada token
- `Login.test.js`: simpan token + redirect ke `/dashboard` saat sukses, tampilkan pesan error server saat gagal

Hasil saat ini: **5 passed**.

## Media & Spaces

Media device diunggah ke `POST /api/v1/devices/{device}/media`. Controller memilih disk `spaces` hanya bila `DO_SPACES_KEY` dan `DO_SPACES_BUCKET` terisi; jika tidak jatuh ke disk `local` (private). Download melalui `GET /api/v1/media/{media}/download` sehingga bucket tetap privat. Pest `MediaTest` menguji: upload image sukses tersimpan (`local:`), dan upload non-image ditolak (422). Total backend: **9 passed**.

## Observability (Sentry)

Sentry bersifat opt-in di SMB Web: aktif hanya bila `VITE_SENTRY_DSN` di-set saat build (`npm run build`). Tanpa DSN, blok inisialisasi ter-tree-shake dan tidak menambah bundle. Konfigurasi ada di `src/main.js` + `.env.example`.

## Android

- Unit test: belum dibuat (planned: device API client & WebSocket client reconnect backoff)
- Instrumentation: geplant (planned) untuk MainActivity registration form, TrackingService start/stop
- Compatibility test: belum dijalankan pada perangkat nyata — `docs/android-compatibility.md` harus di-update ketika test fisik di emulator API 26–36 selesai

## Security tests yang sudah terverifikasi

- Login invalid → 401 ✓
- Unauthenticated `/api/v1/devices` → 401 ✓
- Registration code reuse → 422 ✓
- OTP verify salah/brute-force → see OtpController (attempts >=5 → 429)
- Login + register rate limit → throttle middleware 5/min

## Recovery tests (manual)

- Redis restart → `smb:doctor` Redis FAIL → restart container → back to OK
- AdonisJS restart → WS client auto-reconnect dengan exponential backoff (lihat `WebSocketClient.kt`)
- Laravel restart → device HTTPS fallback via `DeviceApiClient` tetap aktif saat WS putus
