<script setup>
import { useRouter } from 'vue-router'
const router = useRouter()
function logout() {
  localStorage.removeItem('smb_token')
  router.push('/login')
}
</script>

<template>
  <header class="bg-white shadow p-4 flex gap-4">
    <router-link to="/dashboard" class="font-bold">SMB</router-link>
    <router-link to="/devices" class="text-blue-600">Device</router-link>
    <router-link to="/map" class="text-blue-600">Peta</router-link>
    <button class="ml-auto text-red-600" @click="logout">Logout</button>
  </header>
  <router-view />
</template>
