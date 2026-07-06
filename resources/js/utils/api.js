import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useApiStore } from '@/stores/api'
import { useNotificationsStore } from '@/stores/notifications'

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
  async (error) => {
    useApiStore().stopLoading()

    const status = error.response?.status
    const notifications = useNotificationsStore()

    if (status === 401) {
      const authStore = useAuthStore()
      await authStore.clearSession()
      const router = (await import('@/router/index.js')).default
      router.push('/login')
    } else if (status === 403) {
      notifications.error(error.response.data?.message || "You don't have permission to do that.")
    } else if (status === 429) {
      notifications.error('Too many requests — please wait a moment and try again.')
    } else if (status >= 500) {
      notifications.error('Something went wrong on our side. Please try again.')
    } else if (!error.response) {
      notifications.error('Network error — check your connection and try again.')
    }
    // 404/422 are left to the calling view (forms show field errors inline).

    return Promise.reject(error)
  }
)

export default api
