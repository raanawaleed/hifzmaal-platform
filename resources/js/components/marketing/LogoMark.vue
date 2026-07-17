<script setup>
defineProps({
  size: { type: Number, default: 40 },
})
</script>

<template>
  <div
    class="flex shrink-0 items-center justify-center rounded-xl border"
    :style="{
      width: `${size}px`,
      height: `${size}px`,
      borderColor: 'rgba(216,178,106,.4)',
      background: 'rgba(216,178,106,.08)',
    }"
  >
    <div
      class="rounded-[4px] border-2"
      :style="{
        width: `${size * 0.375}px`,
        height: `${size * 0.375}px`,
        borderColor: '#d8b26a',
        transform: 'rotate(45deg)',
      }"
    />
  </div>
</template>
