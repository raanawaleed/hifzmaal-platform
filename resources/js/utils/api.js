import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useApiStore } from '@/stores/api'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

api.interceptors.request.use((config) => {
  useApiStore().startLoading()
  const authStore = useAuthStore()
  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => {
    useApiStore().stopLoading()
    return response
  },
  (error) => {
    useApiStore().stopLoading()

    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.token = null
      authStore.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('currentFamilyId')
      window.location.href = '/login'
    }

    return Promise.reject(error)
  }
)

export default api
