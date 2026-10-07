<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import L from 'leaflet'
import api from '../api'

const el = ref(null)
let map

onMounted(async () => {
  map = L.map(el.value).setView([-6.2, 106.8], 11)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map)
  try {
    const { data } = await api.get('/map/devices')
    for (const f of data.data.features) {
      if (!f.geometry) continue
      const [lng, lat] = f.geometry.coordinates
      L.marker([lat, lng]).addTo(map).bindPopup(`${f.properties.name || f.properties.device_id}<br>${f.properties.status}<br>${f.properties.recorded_at || ''}`)
    }
  } catch (e) {
    console.error(e)
  }
})

onUnmounted(() => map?.remove())
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Peta Device</h1>
    <div ref="el" style="height: 70vh"></div>
  </div>
</template>
