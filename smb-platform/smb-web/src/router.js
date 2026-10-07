import { createRouter, createWebHistory } from 'vue-router'
import Login from './views/Login.vue'
import Dashboard from './views/Dashboard.vue'
import Devices from './views/Devices.vue'
import DeviceDetail from './views/DeviceDetail.vue'
import MapView from './views/MapView.vue'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', component: Login },
  { path: '/dashboard', component: Dashboard, meta: { auth: true } },
  { path: '/devices', component: Devices, meta: { auth: true } },
  { path: '/devices/:id', component: DeviceDetail, meta: { auth: true } },
  { path: '/map', component: MapView, meta: { auth: true } },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to) => {
  if (to.meta.auth && !localStorage.getItem('smb_token')) return '/login'
})

export default router
