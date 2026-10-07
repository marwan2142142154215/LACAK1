<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../api'

const devices = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/devices', { params: { per_page: 50 } })
    devices.value = data.data.items || []
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-gray-900">Daftar Device</h1>
          <p class="text-sm text-gray-500 mt-1">Kelola dan pantau seluruh perangkat terdaftar</p>
        </div>
        <router-link to="/map" class="hidden md:inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
          Lihat Peta →
        </router-link>
      </div>

      <div v-if="loading" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm text-gray-500">
        Memuat daftar perangkat...
      </div>

      <div v-else class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Android</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Baterai</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terakhir Dilihat</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="d in devices" :key="d.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-semibold">
                      {{ (d.name || d.id).toString().charAt(0).toUpperCase() }}
                    </div>
                    <div class="ml-4">
                      <p class="text-sm font-medium text-gray-900">{{ d.name || `Device #${d.id}` }}</p>
                      <p class="text-xs text-gray-500">ID: {{ d.id }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset',
                    d.status === 'ONLINE' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : d.status === 'OFFLINE' ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-gray-50 text-gray-700 ring-gray-200'
                  ]">
                    {{ d.status || 'UNKNOWN' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ d.android_version || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ d.battery_level ?? '-' }}<span v-if="d.battery_level !== null && d.battery_level !== undefined">%</span></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ d.last_seen_at || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                  <router-link :to="`/devices/${d.id}`" class="text-blue-600 hover:text-blue-700 font-medium">
                    Detail →
                  </router-link>
                </td>
              </tr>
              <tr v-if="devices.length === 0">
                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                  Belum ada device terdaftar.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
