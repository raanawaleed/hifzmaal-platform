<script setup>
import { mdiTranslate } from '@mdi/js'
import BaseIcon from '@/components/BaseIcon.vue'
import { useLocaleStore } from '@/stores/locale'
import { SUPPORTED_LOCALES } from '@/i18n'

defineProps({
  dark: Boolean,
})

const localeStore = useLocaleStore()
</script>

<template>
  <label class="flex items-center gap-1.5 text-sm" :class="dark ? 'text-white' : 'text-gray-600 dark:text-slate-300'">
    <BaseIcon :path="mdiTranslate" size="18" />
    <select
      :value="localeStore.current"
      class="cursor-pointer border-none bg-transparent text-sm focus:outline-none"
      :class="dark ? 'text-white' : 'text-gray-600 dark:text-slate-300'"
      @change="localeStore.set($event.target.value)"
    >
      <option v-for="l in SUPPORTED_LOCALES" :key="l.id" :value="l.id" class="text-gray-900">
        {{ l.label }}
      </option>
    </select>
  </label>
</template>
