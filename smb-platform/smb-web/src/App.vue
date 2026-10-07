<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const isAuthenticated = computed(() => !!localStorage.getItem('smb_token'))
const hideNav = computed(() => route.path === '/login')

function logout() {
  localStorage.removeItem('smb_token')
  router.push('/login')
}
</script>

<template>
  <header
    v-if="isAuthenticated && !hideNav"
    class="bg-white/95 backdrop-blur border-b border-gray-200 px-4 py-3 flex items-center gap-6 sticky top-0 z-10 shadow-sm"
  >
    <router-link to="/dashboard" class="font-bold text-lg tracking-tight">SMB</router-link>
    <nav class="flex items-center gap-4 text-sm">
      <router-link to="/dashboard" class="text-gray-600 hover:text-gray-900 transition-colors">Dashboard</router-link>
      <router-link to="/devices" class="text-gray-600 hover:text-gray-900 transition-colors">Device</router-link>
      <router-link to="/map" class="text-gray-600 hover:text-gray-900 transition-colors">Peta</router-link>
    </nav>
    <button class="ml-auto px-3 py-1.5 text-sm rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition-colors" @click="logout">Keluar</button>
  </header>
  <router-view />
</template>
