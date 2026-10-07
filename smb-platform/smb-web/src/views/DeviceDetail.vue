<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'

const route = useRoute()
const device = ref(null)
const locations = ref([])

onMounted(async () => {
  const [d, l] = await Promise.all([
    api.get(`/devices/${route.params.id}`),
    api.get(`/devices/${route.params.id}/locations`, { params: { per_page: 10 } }),
  ])
  device.value = d.data.data
  locations.value = l.data.data.items || []
})

async function lock() {
  await api.post(`/devices/${route.params.id}/lock`)
  alert('Perintah kunci dikirim')
}

async function unlock() {
  await api.post(`/devices/${route.params.id}/unlock`)
  alert('Perintah buka kunci dikirim')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50" v-if="device">
    <div class="max-w-7xl mx-auto p-6 space-y-6">
      <div>
        <router-link to="/devices" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke Daftar Device</router-link>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 mt-2">{{ device.name || `Device #${device.id}` }}</h1>
        <p class="text-sm text-gray-500 mt-1">ID: {{ device.id }}</p>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900">Informasi Perangkat</h2>
          <dl class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <dt class="text-gray-500">Status</dt>
              <dd class="mt-1">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset',
                  device.status === 'ONLINE' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : device.status === 'OFFLINE' ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-gray-50 text-gray-700 ring-gray-200'
                ]">
                  {{ device.status || 'UNKNOWN' }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-gray-500">Terakhir Dilihat</dt>
              <dd class="mt-1 text-gray-900">{{ device.last_seen_at || '-' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Versi Android</dt>
              <dd class="mt-1 text-gray-900">{{ device.android_version || '-' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Baterai</dt>
              <dd class="mt-1 text-gray-900">{{ device.battery_level ?? '-' }}<span v-if="device.battery_level !== null && device.battery_level !== undefined">%</span></dd>
            </div>
          </dl>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900">Aksi Perangkat</h2>
          <div class="flex flex-wrap gap-2">
            <button class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors" @click="lock">Lock</button>
            <button class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors" @click="unlock">Unlock</button>
            <router-link to="/map" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
              Lihat di Peta →
            </router-link>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Riwayat Lokasi Terbaru</h2>
        <div v-if="locations.length" class="divide-y divide-gray-200">
          <div v-for="l in locations" :key="l.id" class="py-3 flex items-center justify-between text-sm">
            <div>
              <p class="text-gray-900 font-medium">{{ l.recorded_at || '-' }}</p>
              <p class="text-gray-500 mt-0.5">{{ l.latitude }}, {{ l.longitude }} • Akurasi: {{ l.accuracy_m ?? l.accuracy ?? '-' }} m</p>
            </div>
            <a v-if="l.latitude && l.longitude" :href="`https://www.google.com/maps?q=${l.latitude},${l.longitude}`" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-700 text-xs font-medium">
              Buka di Maps →
            </a>
          </div>
        </div>
        <p v-else class="text-sm text-gray-500">Tidak ada data lokasi.</p>
      </div>
    </div>
  </div>
</template>
