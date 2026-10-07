# Android Compatibility — SMB Lacak & SMB Master

## Target SDK matrix

| Android | API | Status build | Status test fisik |
|---|---|---|---|
| 8.0 | 26 | Supported (minSdk) | Belum diuji |
| 8.1 | 27 | Supported | Belum diuji |
| 9 | 28 | Supported | Belum diuji |
| 10 | 29 | Supported | Belum diuji |
| 11 | 30 | Supported | Belum diuji |
| 12 | 31 | Supported | Belum diuji |
| 12L | 32 | Supported | Belum diuji |
| 13 | 33 | Supported | Belum diuji |
| 14 | 34 | Supported | Belum diuji |
| 15 | 35 | Supported | Belum diuji |
| 16 | 36 | Target/compile 36 | Belum diuji |

## Perilaku berbeda per API level

- API 26+: foreground service memerlukan `FOREGROUND_SERVICE_DATA_SYNC` (Android 14+ enforcement) → sudah dideklarasikan
- API 33+: `POST_NOTIFICATIONS` diminta runtime permission
- API 29+: background location butuh separate permission — SMB Lacak hanya collect saat app aktif / foreground service (jujur: location mungkin tidak selalu update saat background)
- API 31+: eksak foreground service type untuk dataSync — sudah dideklarasikan

## Implementasi saat ini

- **SMB Lacak**: registrasi device, heartbeat tiap 30s (baterai + versi + Android API), **lokasi real via FusedLocationProvider** (`LocationHelper.lastLocation`) yang dikirim bersama heartbeat ke `device_heartbeats`/`device_locations`, WebSocket client dengan reconnect, command ack, `BootReceiver` auto-start, **kamera real via CameraX** (`CameraController`) untuk `camera_request`, **upload media** ke `/devices/{id}/media` (DO Spaces bila dikonfigurasi, fallback local).
- **SMB Master**: login admin (Sanctum), daftar device, detail device dengan aksi lock / unlock / request lokasi / request kamera (front/back) / generate OTP.

### Perilaku command di device

| command_type | Aksi | Hasil jujur |
|---|---|---|
| `lock` | buka app ke depan (Device Owner dibutuhkan untuk lock penuh) | `SUCCESS` (UI lock) |
| `unlock` | ack | `SUCCESS` |
| `location_request` | ambil `lastLocation` | `SUCCESS` (lat,lng) / `FAILED` alasan |
| `camera_request` | `CameraController.capture` bila app di depan | `SUCCESS` (media_id) / `FAILED` alasan (background/izin) |

## Kemampuan & fallback yang jujur

| Fitur | Kondisi tidak tersedia | Status laporan |
|---|---|---|
| Camera capture | background restricted / tanpa permission / Device Owner tanpa policy | `FAILED` dengan reason jelas; tidak mengembalikan SUCCESS palsu |
| Location | permission denied / GPS off / network unavailable | `last known` + `timestamp` + `source` dilaporkan; layanan mengembalikan `null` (bukan koordinat palsu) |
| Silent lock device | tidak Device Owner | UI lock ditampilkan di app; full lock memerlukan Device Owner / lock task mode |
| Upload foto ke DO Spaces | kredensial Spaces belum diisi | dicatat sebagai belum aktif; kamera tetap mengembalikan status jujur |

## Boot recovery

`BootReceiver` memulai `TrackingService` saat `BOOT_COMPLETED`. ForegroundService pesan "SMB Lacak — Device terhubung" membuat service persist via START_STICKY.

## WebSocket reconnect

Reconnect dengan exponential backoff 1s → 2s → 4s … max 30s + jitter ±500ms. Command QUEUED tetap dikawal via HTTPS fallback polling ke `/api/v1/devices/{device}/commands/pending`.

## Cara menjalankan compatibility test

1. Buat emulator AVD untuk API 26/28/29/30/31/33/34/35/36
2. Install APK: `adb install app-debug.apk`
3. Jalankan registratsi (Web → registration code → input di app)
4. Verifikasi: heartbeat tercatat di Laravel log & `device_heartbeats` table, WS connect terlihat di gateway, command lock dikirim → diterima device
5. Sinyal/airplane mode: putuskan wifi → hidupkan airplane → WS auto-reconnect setelah jaringan kembali
6. Restart emulator → TrackingService auto-start via BootReceiver
