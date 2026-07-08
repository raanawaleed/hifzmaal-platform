<script setup>
import { ref, computed } from 'vue'
import { mdiEmailAlert } from '@mdi/js'
import BaseIcon from '@/components/BaseIcon.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import api from '@/utils/api'

const authStore = useAuthStore()
const notifications = useNotificationsStore()
const sending = ref(false)
const sent = ref(false)

const visible = computed(() => authStore.user && !authStore.user.email_verified_at)

const resend = async () => {
  sending.value = true
  try {
    await api.post('/email/verification-notification')
    sent.value = true
    notifications.success('Verification link sent — check your inbox.')
  } catch {
    notifications.error('Could not send the verification email. Please try again.')
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div
    v-if="visible"
    class="flex flex-wrap items-center justify-between gap-2 bg-amber-100 px-4 py-2 text-sm text-amber-900 dark:bg-amber-900/40 dark:text-amber-200"
  >
    <div class="flex items-center gap-2">
      <BaseIcon :path="mdiEmailAlert" size="18" />
      <span>Please verify your email address to unlock billing and invites.</span>
    </div>
    <button
      type="button"
      class="font-medium underline underline-offset-2 disabled:opacity-50"
      :disabled="sending || sent"
      @click="resend"
    >
      {{ sent ? 'Sent!' : sending ? 'Sending…' : 'Resend email' }}
    </button>
  </div>
</template>
