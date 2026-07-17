import { defineStore } from 'pinia'
import api, { ensureCsrfCookie } from '@/utils/api'
import router from '@/router/index.js'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
  }),

  getters: {
    // Not a security boundary — just avoids flashing authenticated UI
    // before a stale/expired session cookie's first API call 401s and
    // the axios interceptor redirects to /login. The real boundary is
    // the httpOnly session cookie, checked server-side on every request.
    isAuthenticated: (state) => !!state.user,
    userName: (state) => state.user?.name || '',
    isSuperAdmin: (state) => (state.user?.roles || []).includes('superadmin')
  },

  actions: {
    setUser(user) {
      this.user = user
      localStorage.setItem('user', JSON.stringify(user))
    },

    async login(credentials, redirect) {
      await ensureCsrfCookie()
      const response = await api.post('/login', credentials)

      if (response.data.two_factor_required) {
        // No session yet — the caller (LoginView) shows a code-entry step
        // and calls completeTwoFactorChallenge() with this token.
        return { twoFactorRequired: true, twoFactorToken: response.data.two_factor_token }
      }

      await this.applySession(response.data, redirect)
      return { twoFactorRequired: false }
    },

    async completeTwoFactorChallenge(payload, redirect) {
      // The CSRF cookie is already set from the login() call just before
      // this — re-fetching would also work, but isn't needed.
      const response = await api.post('/login/two-factor-challenge', payload)
      await this.applySession(response.data, redirect)
    },

    async applySession(data, redirect) {
      this.setUser(data.user)
      // Load families after login (import inside action to avoid circular dep)
      const { useFamilyStore } = await import('./family')
      await useFamilyStore().ensureLoaded()
      router.push(redirect || (this.isSuperAdmin ? '/admin' : '/dashboard'))
    },

    async register(data, redirect) {
      await ensureCsrfCookie()
      const response = await api.post('/register', data)
      this.setUser(response.data.user)
      const { useFamilyStore } = await import('./family')
      await useFamilyStore().ensureLoaded()
      // A redirect (e.g. from an invite link) means they're joining an
      // existing family — skip the "create your first family" wizard.
      router.push(redirect || '/onboarding')
    },

    async logout() {
      try { await api.post('/logout') } catch {}
      await this.clearSession()
      router.push('/login')
    },

    async clearSession() {
      this.user = null
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
