import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

const push = vi.fn()
vi.mock('vue-router', () => ({ useRouter: () => ({ push }) }))

const post = vi.fn()
vi.mock('../api', () => ({ default: { post: (...args) => post(...args) } }))

import Login from '../views/Login.vue'

describe('Login.vue', () => {
  beforeEach(() => {
    post.mockReset()
    push.mockReset()
    localStorage.clear()
  })

  it('stores the token and redirects on success', async () => {
    post.mockResolvedValue({ data: { data: { access_token: 'tok' } } })
    const wrapper = mount(Login)
    await wrapper.find('input[type=email]').setValue('a@b.com')
    await wrapper.find('input[type=password]').setValue('secret')
    await wrapper.find('form').trigger('submit.prevent')
    await new Promise((r) => setTimeout(r))
    expect(post).toHaveBeenCalled()
    expect(localStorage.getItem('smb_token')).toBe('tok')
    expect(push).toHaveBeenCalledWith('/dashboard')
  })

  it('shows the server error message on failure', async () => {
    post.mockRejectedValue({ response: { data: { message: 'Login gagal' } } })
    const wrapper = mount(Login)
    await wrapper.find('input[type=email]').setValue('a@b.com')
    await wrapper.find('input[type=password]').setValue('bad')
    await wrapper.find('form').trigger('submit.prevent')
    await new Promise((r) => setTimeout(r))
    expect(wrapper.text()).toContain('Login gagal')
  })
})
