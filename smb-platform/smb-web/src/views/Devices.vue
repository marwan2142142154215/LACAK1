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
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Daftar Device</h1>
    <p v-if="loading">Memuat...</p>
    <table v-else class="w-full border">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2 text-left">Nama</th>
          <th class="p-2 text-left">Status</th>
          <th class="p-2 text-left">Android</th>
          <th class="p-2 text-left">Baterai</th>
          <th class="p-2 text-left">Last Seen</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="d in devices" :key="d.id" class="border-t">
          <td class="p-2"><router-link class="text-blue-600" :to="`/devices/${d.id}`">{{ d.name || d.id }}</router-link></td>
          <td class="p-2">{{ d.status }}</td>
          <td class="p-2">{{ d.android_version }}</td>
          <td class="p-2">{{ d.battery_level }}%</td>
          <td class="p-2">{{ d.last_seen_at }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
