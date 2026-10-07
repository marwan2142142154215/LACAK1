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
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-6 space-y-6">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">Ringkasan status perangkat dalam satu pandang</p>
      </div>

      <div v-if="loading" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm text-gray-500">
        Memuat data perangkat...
      </div>

      <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm flex items-end justify-between">
          <div>
            <p class="text-sm text-gray-500">Total Device</p>
            <p class="text-3xl font-bold tracking-tight text-gray-900 mt-1">{{ stats.total }}</p>
          </div>
          <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600">∑</div>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm flex items-end justify-between">
          <div>
            <p class="text-sm text-emerald-700">Online</p>
            <p class="text-3xl font-bold tracking-tight text-emerald-900 mt-1">{{ stats.online }}</p>
          </div>
          <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700">●</div>
        </div>
        <div class="rounded-2xl border border-red-200 bg-red-50 p-6 shadow-sm flex items-end justify-between">
          <div>
            <p class="text-sm text-red-700">Offline</p>
            <p class="text-3xl font-bold tracking-tight text-red-900 mt-1">{{ stats.offline }}</p>
          </div>
          <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center text-red-700">○</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-gray-100 p-6 shadow-sm flex items-end justify-between">
          <div>
            <p class="text-sm text-gray-700">Unknown</p>
            <p class="text-3xl font-bold tracking-tight text-gray-900 mt-1">{{ stats.unknown }}</p>
          </div>
          <div class="h-10 w-10 rounded-full bg-white/60 flex items-center justify-center text-gray-600">?</div>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Navigasi Cepat</h2>
        <div class="mt-4 flex flex-wrap gap-3">
          <router-link to="/devices" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
            Daftar Device →
          </router-link>
          <router-link to="/map" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
            Lihat Peta →
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>
