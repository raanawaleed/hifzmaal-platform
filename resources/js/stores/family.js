import { defineStore } from 'pinia'
import api from '@/utils/api'

export const useFamilyStore = defineStore('family', {
  state: () => ({
    currentFamilyId: localStorage.getItem('currentFamilyId')
      ? Number(localStorage.getItem('currentFamilyId'))
      : null,
    families: [],
    loaded: false
  }),

  getters: {
    currentFamily: (state) => state.families.find(f => f.id === state.currentFamilyId) || null,
    hasFamily: (state) => state.families.length > 0
  },

  actions: {
    setFamilyId(id) {
      if (!id) return
      this.currentFamilyId = Number(id)
      localStorage.setItem('currentFamilyId', id)
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
    }
  }
})
