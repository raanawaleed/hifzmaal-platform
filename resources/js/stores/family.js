import { defineStore } from 'pinia'
import api from '@/utils/api'

export const useFamilyStore = defineStore('family', {
  state: () => ({
    currentFamilyId: localStorage.getItem('currentFamilyId')
      ? Number(localStorage.getItem('currentFamilyId'))
      : null,
    families: [],
    loaded: false,
    loadPromise: null
  }),

  getters: {
    currentFamily: (state) => state.families.find(f => f.id === state.currentFamilyId) || null,
    hasFamily: (state) => state.families.length > 0,
    currentRole() {
      return this.currentFamily?.current_user_role || null
    },
    // Viewers get a read-only UI; owners and members can create/edit.
    canEdit() {
      return ['owner', 'member'].includes(this.currentRole)
    },
    isOwner() {
      return this.currentRole === 'owner'
    }
  },

  actions: {
    setFamilyId(id) {
      if (!id) return
      this.currentFamilyId = Number(id)
      localStorage.setItem('currentFamilyId', id)
    },

    /**
     * Memoized loader: concurrent callers (router guard, layout, login)
     * share one in-flight request, and once loaded it's a no-op.
     */
    async ensureLoaded() {
      if (this.loaded) return
      if (!this.loadPromise) {
        this.loadPromise = this.loadUserFamilies().finally(() => {
          this.loadPromise = null
        })
      }
      await this.loadPromise
    },

    async loadUserFamilies() {
      try {
        const response = await api.get('/families')
        this.families = response.data.data || response.data || []
        this.loaded = true
        if (this.families.length > 0 && !this.currentFamilyId) {
          this.setFamilyId(this.families[0].id)
        }
        // If stored family no longer exists, reset to first
        if (this.currentFamilyId && !this.families.find(f => f.id === this.currentFamilyId)) {
          this.setFamilyId(this.families[0]?.id || null)
        }
      } catch (err) {
        console.warn('Could not load families', err)
      }
    },

    reset() {
      this.currentFamilyId = null
      this.families = []
      this.loaded = false
      this.loadPromise = null
    }
  }
})
