import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useApiStore } from '@/stores/api'
import { useNotificationsStore } from '@/stores/notifications'

// Sanctum SPA cookie auth, not a Bearer token — withCredentials sends the
// session + XSRF-TOKEN cookies on every request (same-origin: the SPA and
// API share a domain), and axios reads XSRF-TOKEN itself to set the
// X-XSRF-TOKEN header automatically. No Authorization header to attach.
const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// A fresh browser has no XSRF-TOKEN cookie yet for axios to read — Sanctum's
// /sanctum/csrf-cookie endpoint sets it. Must run (and be awaited) before
// login/register/the 2FA challenge, each time, since logout() invalidates
// the session and rotates the token. Not under /api, so a plain axios call.
export const ensureCsrfCookie = () => axios.get('/sanctum/csrf-cookie', { withCredentials: true })

api.interceptors.request.use((config) => {
  useApiStore().startLoading()
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
