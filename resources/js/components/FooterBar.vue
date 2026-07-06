<script setup>
import { containerMaxW } from '@/config.js'

const year = new Date().getFullYear()
</script>

<template>
  <footer class="px-6 py-2" :class="containerMaxW">
    <div class="flex flex-col items-center justify-between gap-2 text-sm text-gray-500 lg:flex-row dark:text-slate-400">
      <div>&copy; {{ year }} <b>HifzMaal</b> · حفظ مال</div>
      <div><slot /></div>
    </div>
  </footer>
</template>
