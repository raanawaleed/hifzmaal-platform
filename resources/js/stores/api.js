import { defineStore } from 'pinia'

export const useApiStore = defineStore('api', {
  state: () => ({
    loadingCount: 0,
    error: null
  }),
  getters: {
    loading: (state) => state.loadingCount > 0
  },
  actions: {
    startLoading() { this.loadingCount++ },
    stopLoading() { if (this.loadingCount > 0) this.loadingCount-- },
    setError(err) { this.error = err },
    clearError() { this.error = null }
  }
})
