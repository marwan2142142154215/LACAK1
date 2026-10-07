# WebSocket — SMB Gateway (AdonisJS)

Endpoint production: `wss://ws.lacaksmbbot.com/ws`
Endpoint dev: `ws://10.0.2.2:8080/ws` (emulator) atau `ws://127.0.0.1:8080/ws`

## Handshake

```
/ws?device_id=<UUID>&token=<device_token>
```

Token divalidasi ke Laravel `POST /api/v1/devices/{device}/verify-credential`. Token tidak valid → close code 4401.

## Events

### Client → Server

| type | payload |
|---|---|
| `ping` | `{}` |
| `device.heartbeat` | `{ "payload": { "battery_level": 80, "network_type": "wifi" } }` |
| `device.command.ack` | `{ "command_id": "...", "status": "RECEIVED|EXECUTING|SUCCESS|FAILED", "result": "...", "error_message": "..." }` |

### Server → Client

| type |
|---|
| `device.connected` |
| `device.heartbeat.ack` |
| `device.command` |

Contoh push command:

```json
{ "type": "device.command", "command": { "id": "...", "device_id": "...", "command_type": "lock", "payload": null, "expires_at": "...", "status": "QUEUED" } }
```

## Presence

Redis key: `smb:presence:{device_id}` = `{ online: true, at: <ts> }`, TTL 120s, diperbarui setiap heartbeat; dihapus saat socket close.

## Reconnect

Client reconnect dengan exponential backoff (1s, 2s, 4s, ... max 30s) + jitter (lihat `WebSocketClient.kt`). Semua command QUEUED/SENT yang belum expired dikirim ulang saat reconnect — device/server pakai `idempotency_key` + status command untuk dedup.
