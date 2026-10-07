<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const email = ref('')
const password = ref('')
const twoFactor = ref('')
const error = ref('')
const loading = ref(false)
const needsTwoFactor = ref(false)
const twoFactorQr = ref('')
const twoFactorSecret = ref('')
const twoFactorSetupMode = ref(false)
const twoFactorSetupCode = ref('')
const twoFactorSetupVerified = ref(false)
const twoFactorSecretShown = ref(false)

const canSubmit = computed(() => {
  if (twoFactorSetupMode.value) {
    if (twoFactorSetupVerified.value) return !loading.value
    return !loading.value
  }
  if (needsTwoFactor.value) return twoFactor.value.length === 6 && !loading.value
  return email.value && password.value && !loading.value
})

async function login() {
  loading.value = true
  error.value = ''
  try {
    const payload = {
      email: email.value,
      password: password.value,
      device_name: 'web-dashboard',
    }
    if (needsTwoFactor.value) {
      payload.two_factor_code = twoFactor.value
    }
    const { data } = await api.post('/auth/login', payload)
    localStorage.setItem('smb_token', data.data.access_token)
    router.push('/dashboard')
  } catch (e) {
    const msg = e.response?.data?.message || 'Login gagal'
    const status = e.response?.status
    if (status === 401 && msg.includes('2FA')) {
      needsTwoFactor.value = true
      error.value = '2FA diperlukan. Masukkan kode dari aplikasi authenticator Anda.'
    } else if (status === 403 || msg.includes('2FA')) {
      needsTwoFactor.value = true
      error.value = msg
    } else {
      error.value = msg
    }
  } finally {
    loading.value = false
  }
}

async function setupTwoFactor() {
  const token = localStorage.getItem('smb_token')
  if (!token) {
    error.value = 'Login diperlukan untuk mengatur 2FA'
    return
  }
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.post('/auth/2fa/enable')
    twoFactorQr.value = data.data.qr_url
    twoFactorSecret.value = data.data.secret
    twoFactorSetupMode.value = true
    twoFactorSetupVerified.value = false
    twoFactorSetupCode.value = ''
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memulai setup 2FA'
  } finally {
    loading.value = false
  }
}

async function verifyTwoFactorSetup() {
  if (!twoFactorSetupCode.value || twoFactorSetupCode.value.length !== 6) return
  const token = localStorage.getItem('smb_token')
  if (!token) {
    error.value = 'Sesi tidak ditemukan. Silakan login ulang.'
    return
  }
  loading.value = true
  error.value = ''
  try {
    await api.post('/auth/2fa/verify', { code: twoFactorSetupCode.value })
    twoFactorSetupVerified.value = true
    error.value = ''
    alert('2FA berhasil diaktifkan. Silakan logout dan login kembali untuk masuk.')
  } catch (e) {
    error.value = e.response?.data?.message || 'Kode 2FA tidak valid'
  } finally {
    loading.value = false
  }
}

function resetTwoFactorFlow() {
  twoFactorSetupMode.value = false
  twoFactorQr.value = ''
  twoFactorSecret.value = ''
  twoFactorSetupCode.value = ''
  twoFactorSetupVerified.value = false
  twoFactorSecretShown.value = false
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center px-4">
    <form @submit.prevent="login" class="bg-white/95 backdrop-blur border border-gray-200 p-8 rounded-2xl shadow-xl w-full max-w-md space-y-5">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">SMB — Masuk</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola perangkat dan pemantauan dalam satu tempat.</p>
      </div>

      <div v-if="!twoFactorSetupMode" class="space-y-4">
        <div class="space-y-2">
          <label for="email" class="text-sm font-medium text-gray-700">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="nama@perusahaan.com"
            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            required
            autocomplete="email"
          />
        </div>
        <div class="space-y-2">
          <label for="password" class="text-sm font-medium text-gray-700">Kata Sandi</label>
          <input
            id="password"
            v-model="password"
            type="password"
            placeholder="••••••••"
            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            required
            autocomplete="current-password"
          />
        </div>
        <div v-if="needsTwoFactor" class="space-y-2">
          <label for="2fa" class="text-sm font-medium text-gray-700">Kode 2FA</label>
          <input
            id="2fa"
            v-model="twoFactor"
            type="text"
            inputmode="numeric"
            maxlength="6"
            pattern="[0-9]{6}"
            placeholder="6 digit kode"
            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm tracking-widest text-center font-mono shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            autocomplete="one-time-code"
          />
          <p class="text-xs text-gray-500">Masukkan kode dari aplikasi authenticator (TOTP).</p>
        </div>
      </div>

      <div v-else class="space-y-5">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Aktifkan 2FA (Two-Factor Authentication)</h2>
          <p class="text-sm text-gray-500 mt-1">Scan QR Code terlebih dahulu, lalu verifikasi dengan kode 6 digit.</p>
        </div>

        <div v-if="twoFactorQr" class="flex flex-col items-center justify-center space-y-3">
          <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <img :src="twoFactorQr" alt="QR Code 2FA" class="w-48 h-48" />
          </div>
          <p class="text-xs text-gray-500 text-center">Buka Google Authenticator, Authy, atau aplikasi TOTP serupa, lalu scan QR ini.</p>
        </div>

        <div v-if="twoFactorSecret" class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-gray-700">Kode rahasia (manual entry)</label>
            <button type="button" class="text-xs text-blue-600 hover:text-blue-700" @click="twoFactorSecretShown = !twoFactorSecretShown">
              {{ twoFactorSecretShown ? 'Sembunyikan' : 'Tampilkan' }}
            </button>
          </div>
          <div class="border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 font-mono text-xs break-all select-all">
            <span v-if="twoFactorSecretShown">{{ twoFactorSecret.value }}</span>
            <span v-else>••••••••••••••••••••••••••••••</span>
          </div>
        </div>

        <div v-if="!twoFactorSetupVerified" class="space-y-2">
          <label for="setup-2fa" class="text-sm font-medium text-gray-700">Verifikasi kode 2FA</label>
          <input
            id="setup-2fa"
            v-model="twoFactorSetupCode"
            type="text"
            inputmode="numeric"
            maxlength="6"
            pattern="[0-9]{6}"
            placeholder="6 digit kode"
            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm tracking-widest text-center font-mono shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
          />
          <p class="text-xs text-gray-500">Setelah scan QR, masukkan kode yang muncul untuk mengaktifkan 2FA.</p>
        </div>

        <div v-else class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
          2FA berhasil diaktifkan. Silakan logout dan login kembali.
        </div>

        <div class="flex items-center justify-between gap-2">
          <button type="button" class="text-sm text-gray-600 hover:text-gray-900" @click="resetTwoFactorFlow">Kembali ke Login</button>
          <button
            v-if="!twoFactorSetupVerified"
            type="button"
            :disabled="twoFactorSetupCode.length !== 6 || loading"
            class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg px-4 py-2 shadow-sm transition"
            @click="verifyTwoFactorSetup"
          >
            {{ loading ? 'Memverifikasi...' : 'Verifikasi & Aktifkan' }}
          </button>
        </div>
      </div>

      <div v-if="!twoFactorSetupMode" class="space-y-3">
        <button
          type="submit"
          :disabled="!canSubmit"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg px-4 py-2.5 shadow-sm transition"
        >
          {{ loading ? 'Memproses...' : needsTwoFactor ? 'Verifikasi 2FA' : 'Masuk' }}
        </button>
        <button
          type="button"
          class="w-full text-sm text-gray-600 hover:text-gray-900 border border-gray-200 rounded-lg px-4 py-2 hover:bg-gray-50 transition"
          @click="setupTwoFactor"
        >
          Aktifkan 2FA (perlu login terlebih dahulu)
        </button>
      </div>

      <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ error }}</p>
    </form>
  </div>
</template>
