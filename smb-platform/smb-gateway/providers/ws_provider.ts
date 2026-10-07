import type { ApplicationService } from '@adonisjs/core/types'

export default class WsProvider {
  constructor(protected app: ApplicationService) {}

  async start() {
    if (process.env.NODE_ENV === 'test') return
    const { startGateway } = await import('#start/ws')
    startGateway()
    console.log(`SMB Gateway WebSocket listening on :${process.env.WS_PORT || 8080}/ws`)
  }
}
