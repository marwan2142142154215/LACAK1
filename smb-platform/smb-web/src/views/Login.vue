<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const email = ref('')
const password = ref('')
const twoFactor = ref('')
const error = ref('')
const loading = ref(false)

async function login() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.post('/auth/login', {
      email: email.value,
      password: password.value,
      device_name: 'web-dashboard',
      two_factor_code: twoFactor.value || undefined,
    })
    localStorage.setItem('smb_token', data.data.access_token)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login gagal'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <form @submit.prevent="login" class="bg-white p-8 rounded shadow w-96">
      <h1 class="text-xl font-bold mb-4">SMB — Login</h1>
      <input v-model="email" type="email" placeholder="Email" class="w-full border rounded p-2 mb-3" required />
      <input v-model="password" type="password" placeholder="Password" class="w-full border rounded p-2 mb-3" required />
      <input v-model="twoFactor" type="text" maxlength="6" placeholder="Kode 2FA (jika aktif)" class="w-full border rounded p-2 mb-3" />
      <button :disabled="loading" class="w-full bg-blue-600 text-white rounded p-2">{{ loading ? 'Memproses...' : 'Masuk' }}</button>
      <p v-if="error" class="text-red-600 mt-3 text-sm">{{ error }}</p>
    </form>
  </div>
</template>
