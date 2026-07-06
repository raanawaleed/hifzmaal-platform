import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useMainStore = defineStore('main', () => {
  const userName = ref('')
  const userEmail = ref('')

  const userAvatar = computed(
    () =>
      `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(userName.value || 'U')}`,
  )

  const isFieldFocusRegistered = ref(false)

  function setUser(payload) {
    if (payload?.name) userName.value = payload.name
    if (payload?.email) userEmail.value = payload.email
  }

  return {
    userName,
    userEmail,
    userAvatar,
    isFieldFocusRegistered,
    setUser,
  }
})
