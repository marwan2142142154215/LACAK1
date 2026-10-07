import axios from 'axios'

const api = axios.create({ baseURL: '/api/v1' })

api.interceptors.request.use((config) => {
  const t = localStorage.getItem('smb_token')
  if (t) config.headers.Authorization = `Bearer ${t}`
  return config
})

export default api
