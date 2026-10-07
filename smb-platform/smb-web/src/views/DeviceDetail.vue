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
  <div class="p-6" v-if="device">
    <h1 class="text-2xl font-bold mb-2">{{ device.name || device.id }}</h1>
    <p>Status: {{ device.status }} | Android: {{ device.android_version }} | Baterai: {{ device.battery_level }}%</p>
    <p>Last seen: {{ device.last_seen_at }}</p>
    <div class="mt-4">
      <button class="bg-red-600 text-white px-4 py-2 rounded mr-2" @click="lock">Lock</button>
      <button class="bg-green-600 text-white px-4 py-2 rounded" @click="unlock">Unlock</button>
    </div>
    <h2 class="text-xl font-semibold mt-6">Lokasi Terakhir</h2>
    <ul class="list-disc ml-6">
      <li v-for="l in locations" :key="l.id">{{ l.latitude }}, {{ l.longitude }} — {{ l.accuracy }}m — {{ l.recorded_at }}</li>
    </ul>
  </div>
</template>
