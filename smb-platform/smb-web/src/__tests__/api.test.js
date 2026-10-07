import { describe, it, expect, beforeEach } from 'vitest'
import api from '../api'

describe('api client', () => {
  beforeEach(() => localStorage.clear())

  it('uses the /api/v1 base URL', () => {
    expect(api.defaults.baseURL).toBe('/api/v1')
  })

  it('attaches a bearer token when one is stored', async () => {
    localStorage.setItem('smb_token', 'abc123')
    let captured
    api.defaults.adapter = (config) => {
      captured = config
      return Promise.resolve({ data: {}, status: 200, statusText: 'OK', headers: {}, config })
    }
    await api.get('/devices')
    expect(captured.headers.Authorization).toBe('Bearer abc123')
  })

  it('sends no Authorization header without a token', async () => {
    let captured
    api.defaults.adapter = (config) => {
      captured = config
      return Promise.resolve({ data: {}, status: 200, statusText: 'OK', headers: {}, config })
    }
    await api.get('/devices')
    expect(captured.headers.Authorization).toBeUndefined()
  })
})
