import { defineStore } from 'pinia'
import api from '@/utils/api'
import router from '@/router/index.js'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    userName: (state) => state.user?.name || ''
  },

  actions: {
    async login(credentials) {
      const response = await api.post('/login', credentials)
      this.token = response.data.token
      this.user = response.data.user
      localStorage.setItem('token', this.token)
      // Load families after login (import inside action to avoid circular dep)
      const { useFamilyStore } = await import('./family')
      await useFamilyStore().loadUserFamilies()
      router.push('/dashboard')
    },

    async register(data) {
      const response = await api.post('/register', data)
      this.token = response.data.token
      this.user = response.data.user
      localStorage.setItem('token', this.token)
      router.push('/dashboard')
    },

    async logout() {
      try { await api.post('/logout') } catch {}
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('currentFamilyId')
      const { useFamilyStore } = await import('./family')
      useFamilyStore().reset()
      router.push('/login')
    },

    async fetchUser() {
      const response = await api.get('/user')
      this.user = response.data
    }
  }
})
