<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiBellOutline, mdiCheckAll } from '@mdi/js'
import BaseIcon from '@/components/BaseIcon.vue'
import api from '@/utils/api'

const router = useRouter()
const open = ref(false)
const loading = ref(false)
const items = ref([])
const unreadCount = ref(0)
const root = ref(null)
let poll = null

const load = async () => {
  loading.value = true
  try {
    const res = await api.get('/notifications')
    items.value = res.data.data
    unreadCount.value = res.data.unread_count
  } finally {
    loading.value = false
  }
}

const toggle = () => {
  open.value = !open.value
  if (open.value) load()
}

const relativeTime = (iso) => {
  const seconds = Math.floor((Date.now() - new Date(iso)) / 1000)
  if (seconds < 60) return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}

const openNotification = async (item) => {
  open.value = false
  if (!item.read_at) {
    try {
      await api.post(`/notifications/${item.id}/read`)
    } catch {}
  }
  router.push(item.route)
}

const markAllAsRead = async () => {
  try {
    await api.post('/notifications/read-all')
    items.value = items.value.map((i) => ({ ...i, read_at: i.read_at || new Date().toISOString() }))
    unreadCount.value = 0
  } catch {}
}

const onClickOutside = (event) => {
  if (open.value && root.value && !root.value.contains(event.target)) {
    open.value = false
  }
}

onMounted(() => {
  load()
  document.addEventListener('click', onClickOutside)
  // Light polling so the badge updates without a manual refresh.
  poll = setInterval(load, 60000)
})

onUnmounted(() => {
  document.removeEventListener('click', onClickOutside)
  if (poll) clearInterval(poll)
})
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      class="relative flex h-9 w-9 items-center justify-center rounded-full text-gray-600 hover:bg-gray-200/60 dark:text-slate-300 dark:hover:bg-slate-700"
      @click.stop="toggle"
    >
      <BaseIcon :path="mdiBellOutline" size="20" />
      <span
        v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <div
      v-if="open"
      class="absolute right-0 z-40 mt-2 w-80 max-w-[90vw] rounded-lg border border-gray-100 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800"
    >
      <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2 dark:border-slate-700">
        <span class="text-sm font-bold">Notifications</span>
        <button
          v-if="unreadCount > 0"
          type="button"
          class="flex items-center gap-1 text-xs text-emerald-600 hover:underline dark:text-emerald-400"
          @click.stop="markAllAsRead"
        >
          <BaseIcon :path="mdiCheckAll" size="14" />
          Mark all read
        </button>
      </div>

      <div class="max-h-96 overflow-y-auto">
        <p v-if="loading" class="px-4 py-6 text-center text-sm text-gray-400">Loading…</p>
        <p v-else-if="!items.length" class="px-4 py-6 text-center text-sm text-gray-400">
          You're all caught up.
        </p>
        <button
          v-for="item in items"
          :key="item.id"
          type="button"
          class="flex w-full flex-col gap-0.5 border-b border-gray-50 px-4 py-3 text-left last:border-b-0 hover:bg-gray-50 dark:border-slate-700/60 dark:hover:bg-slate-700/40"
          @click.stop="openNotification(item)"
        >
          <span class="flex items-center gap-2 text-sm font-semibold">
            <span
              class="h-1.5 w-1.5 shrink-0 rounded-full"
              :class="item.read_at ? 'bg-transparent' : 'bg-emerald-500'"
            />
            {{ item.title }}
          </span>
          <span class="pl-3.5 text-xs text-gray-500 dark:text-slate-400">{{ item.message }}</span>
          <span class="pl-3.5 text-[11px] text-gray-400 dark:text-slate-500">{{ relativeTime(item.created_at) }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
