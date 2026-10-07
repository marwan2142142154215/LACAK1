import { WebSocketServer as Server } from 'ws'
import Redis from 'ioredis'

const LARAVEL_BASE = process.env.LARAVEL_BASE_URL || 'http://127.0.0.1:8000'
const WS_PORT = Number(process.env.WS_PORT || 8080)

const redis = new Redis({
  host: process.env.REDIS_HOST || '127.0.0.1',
  port: Number(process.env.REDIS_PORT || 6379),
  password: process.env.REDIS_PASSWORD || undefined,
  lazyConnect: true,
})
redis.connect().catch(() => {})

type DeviceSocket = { deviceId: string; token: string; socket: import('ws').WebSocket; lastPong: number }
const sessions = new Map<string, DeviceSocket>()

async function laravelVerify(deviceId: string, token: string): Promise<boolean> {
  try {
    const res = await fetch(`${LARAVEL_BASE}/api/v1/devices/${deviceId}/verify-credential`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json', Accept: 'application/json' },
    })
    return res.ok
  } catch {
    return false
  }
}

async function fetchPending(deviceId: string, token: string) {
  try {
    const res = await fetch(`${LARAVEL_BASE}/api/v1/devices/${deviceId}/commands/pending`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    if (!res.ok) return []
    const json = (await res.json()) as any
    return json.data || []
  } catch {
    return []
  }
}

async function postAck(deviceId: string, token: string, commandId: string, status: string, result?: string, error?: string) {
  await fetch(`${LARAVEL_BASE}/api/v1/commands/${commandId}/ack`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ status, result, error_message: error }),
  }).catch(() => {})
}

async function postHeartbeat(deviceId: string, token: string, payload: any) {
  await fetch(`${LARAVEL_BASE}/api/v1/devices/${deviceId}/heartbeat`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload || {}),
  }).catch(() => {})
}

export function startGateway() {
  const wss = new Server({ port: WS_PORT, path: '/ws' })

  wss.on('connection', (socket, req) => {
    const url = new URL(req.url || '', 'http://localhost')
    const token = url.searchParams.get('token')
    const deviceId = url.searchParams.get('device_id')
    if (!token || !deviceId) {
      socket.close(4401, 'device_id and token required')
      return
    }

    laravelVerify(deviceId, token).then((ok) => {
      if (!ok) {
        socket.close(4401, 'invalid device credential')
        return
      }

      sessions.set(deviceId, { deviceId, token, socket, lastPong: Date.now() })
      redis.set(`smb:presence:${deviceId}`, JSON.stringify({ online: true, at: Date.now() }), 'EX', 120).catch(() => {})
      socket.send(JSON.stringify({ type: 'device.connected', device_id: deviceId }))

      socket.on('message', async (raw) => {
        let msg: any
        try {
          msg = JSON.parse(String(raw))
        } catch {
          return
        }
        switch (msg.type) {
          case 'device.heartbeat':
            await postHeartbeat(deviceId, token, msg.payload)
            redis.set(`smb:presence:${deviceId}`, JSON.stringify({ online: true, at: Date.now() }), 'EX', 120).catch(() => {})
            socket.send(JSON.stringify({ type: 'device.heartbeat.ack' }))
            break
          case 'device.command.ack':
            await postAck(deviceId, token, msg.command_id, msg.status, msg.result, msg.error_message)
            break
          case 'ping':
            socket.send(JSON.stringify({ type: 'pong' }))
            break
        }
      })

      socket.on('close', () => {
        sessions.delete(deviceId)
        redis.del(`smb:presence:${deviceId}`).catch(() => {})
      })

      // command pump for this device
      const timer = setInterval(async () => {
        if (socket.readyState !== socket.OPEN) {
          clearInterval(timer)
          return
        }
        const pending = await fetchPending(deviceId, token)
        for (const cmd of pending as any[]) {
          socket.send(JSON.stringify({ type: 'device.command', command: cmd }))
        }
      }, 4000)
    })
  })

  return wss
}
