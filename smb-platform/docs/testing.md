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

Belum ada test unit Vue. Rencana: test `api.js` interceptor & mapping GeoJSON via Vitest. (Caveat: dashboard bersifat thin client pada Laravel API yang sudah ter-cover Pest.)

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
