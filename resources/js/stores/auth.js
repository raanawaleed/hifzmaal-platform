import { defineStore } from 'pinia'
import api from '@/utils/api'
import router from '@/router/index.js'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    userName: (state) => state.user?.name || '',
    isSuperAdmin: (state) => (state.user?.roles || []).includes('superadmin')
  },

  actions: {
    setUser(user) {
      this.user = user
      localStorage.setItem('user', JSON.stringify(user))
    },

    async login(credentials, redirect) {
      const response = await api.post('/login', credentials)
      this.token = response.data.token
      this.setUser(response.data.user)
      localStorage.setItem('token', this.token)
      // Load families after login (import inside action to avoid circular dep)
      const { useFamilyStore } = await import('./family')
      await useFamilyStore().ensureLoaded()
      router.push(redirect || (this.isSuperAdmin ? '/admin' : '/dashboard'))
    },

    async register(data, redirect) {
      const response = await api.post('/register', data)
      this.token = response.data.token
      this.setUser(response.data.user)
      localStorage.setItem('token', this.token)
      const { useFamilyStore } = await import('./family')
      await useFamilyStore().ensureLoaded()
      router.push(redirect || '/dashboard')
    },

    async logout() {
      try { await api.post('/logout') } catch {}
      await this.clearSession()
      router.push('/login')
    },

    async clearSession() {
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('currentFamilyId')
      const { useFamilyStore } = await import('./family')
      useFamilyStore().reset()
    },

    async fetchUser() {
      const response = await api.get('/user')
      // /api/user returns a UserResource: { data: {...} }
      this.setUser(response.data.data || response.data)
    }
  }
})
