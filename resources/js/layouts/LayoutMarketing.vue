<script setup>
import MarketingNav from '@/components/marketing/MarketingNav.vue'
import MarketingFooter from '@/components/marketing/MarketingFooter.vue'
</script>

<template>
  <div class="relative min-h-screen" style="background: #072019; font-family: 'Space Grotesk', sans-serif">
    <div
      class="pointer-events-none absolute inset-x-0 top-0 h-[900px]"
      style="
        background:
          radial-gradient(circle at 15% 4%, rgba(46, 168, 124, 0.12), transparent 34%),
          radial-gradient(circle at 88% 2%, rgba(216, 178, 106, 0.09), transparent 36%);
      "
    />

    <div class="relative">
      <MarketingNav />
      <main>
        <slot />
      </main>
      <MarketingFooter />
    </div>
  </div>
</template>
