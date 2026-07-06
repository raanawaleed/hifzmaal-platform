import { defineStore } from 'pinia'

let nextId = 1

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    toasts: []
  }),

  actions: {
    push(message, color = 'danger', timeout = 6000) {
      const id = nextId++
      this.toasts.push({ id, message, color })
      if (timeout > 0) {
        setTimeout(() => this.dismiss(id), timeout)
      }
      return id
    },

    success(message) {
      return this.push(message, 'success', 4000)
    },

    error(message) {
      return this.push(message, 'danger')
    },

    dismiss(id) {
      this.toasts = this.toasts.filter(t => t.id !== id)
    }
  }
})
