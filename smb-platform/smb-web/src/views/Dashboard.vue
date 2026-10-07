<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'

const loading = ref(true)
const stats = ref({ total: 0, online: 0, offline: 0, unknown: 0 })

onMounted(async () => {
  try {
    const { data } = await api.get('/devices', { params: { per_page: 100 } })
    const items = data.data.items || []
    stats.value.total = data.data.total ?? items.length
    stats.value.online = items.filter((d) => d.status === 'ONLINE').length
    stats.value.offline = items.filter((d) => d.status === 'OFFLINE').length
    stats.value.unknown = items.filter((d) => !d.status || d.status === 'UNKNOWN').length
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Dashboard</h1>
    <p v-if="loading">Memuat...</p>
    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded shadow p-4">Total Device: <b>{{ stats.total }}</b></div>
      <div class="bg-green-100 rounded shadow p-4">Online: <b>{{ stats.online }}</b></div>
      <div class="bg-red-100 rounded shadow p-4">Offline: <b>{{ stats.offline }}</b></div>
      <div class="bg-gray-100 rounded shadow p-4">Unknown: <b>{{ stats.unknown }}</b></div>
    </div>
    <p class="mt-6">
      <router-link class="text-blue-600" to="/devices">Daftar Device</router-link> ·
      <router-link class="text-blue-600" to="/map">Peta</router-link>
    </p>
  </div>
</template>
