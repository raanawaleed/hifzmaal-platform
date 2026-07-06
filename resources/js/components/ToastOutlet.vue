<script setup>
import { mdiClose, mdiAlertCircle, mdiCheckCircle, mdiInformation } from '@mdi/js'
import { useNotificationsStore } from '@/stores/notifications'
import BaseIcon from '@/components/BaseIcon.vue'

const notifications = useNotificationsStore()

const colorClasses = {
  danger: 'bg-red-600 text-white',
  success: 'bg-emerald-600 text-white',
  info: 'bg-slate-700 text-white',
}

const iconFor = (color) => {
  if (color === 'success') return mdiCheckCircle
  if (color === 'info') return mdiInformation
  return mdiAlertCircle
}
</script>

<template>
  <div class="pointer-events-none fixed top-16 right-4 z-[60] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2">
    <transition-group name="toast">
      <div
        v-for="toast in notifications.toasts"
        :key="toast.id"
        :class="colorClasses[toast.color] || colorClasses.danger"
        class="pointer-events-auto flex items-start gap-2 rounded-lg px-4 py-3 shadow-lg"
        role="alert"
      >
        <BaseIcon :path="iconFor(toast.color)" size="20" class="mt-0.5 shrink-0" />
        <p class="grow text-sm">{{ toast.message }}</p>
        <button
          type="button"
          class="shrink-0 opacity-70 transition hover:opacity-100"
          aria-label="Dismiss"
          @click="notifications.dismiss(toast.id)"
        >
          <BaseIcon :path="mdiClose" size="18" />
        </button>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(1rem);
}
@media (prefers-reduced-motion: reduce) {
  .toast-enter-active,
  .toast-leave-active {
    transition: none;
  }
}
</style>
