<script setup>
import LogoMark from '@/components/marketing/LogoMark.vue'

const year = new Date().getFullYear()
</script>

<template>
  <footer class="border-t" style="border-color: rgba(216, 178, 106, 0.14); background: #061c16">
    <div class="mx-auto max-w-6xl px-6 py-16">
      <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <div class="flex items-center gap-3">
            <LogoMark :size="34" />
            <span class="text-[17px] font-bold" style="color: #f3ecd9">HifzMaal</span>
          </div>
          <p class="mt-4 max-w-[240px] text-sm leading-relaxed" style="color: #8fb0a0">
            Protection of wealth for the modern Muslim family. An Amanah, a responsibility, a step towards Barakah.
          </p>
        </div>

        <div>
          <div class="mb-4 text-xs font-semibold tracking-wider uppercase" style="color: #a9bcae">Product</div>
          <ul class="space-y-3 text-sm">
            <li><router-link :to="{ name: 'marketing-features' }" class="hover:underline" style="color: #c9d6cd">Features</router-link></li>
            <li><router-link :to="{ name: 'marketing-pricing' }" class="hover:underline" style="color: #c9d6cd">Pricing</router-link></li>
            <li><router-link :to="{ name: 'marketing-security' }" class="hover:underline" style="color: #c9d6cd">Security</router-link></li>
          </ul>
        </div>

        <div>
          <div class="mb-4 text-xs font-semibold tracking-wider uppercase" style="color: #a9bcae">Company</div>
          <ul class="space-y-3 text-sm">
            <li><router-link :to="{ name: 'marketing-contact' }" class="hover:underline" style="color: #c9d6cd">Contact</router-link></li>
            <li><span style="color: #8fb0a0">About</span></li>
            <li><span style="color: #8fb0a0">Roadmap</span></li>
          </ul>
        </div>

        <div>
          <div class="mb-4 text-xs font-semibold tracking-wider uppercase" style="color: #a9bcae">Resources</div>
          <ul class="space-y-3 text-sm">
            <li><span style="color: #8fb0a0">Zakat guide</span></li>
            <li><span style="color: #8fb0a0">Blog</span></li>
            <li><span style="color: #8fb0a0">Help center</span></li>
          </ul>
        </div>
      </div>

      <div
        class="mt-14 flex flex-col items-center justify-between gap-4 border-t pt-8 text-xs sm:flex-row"
        style="border-color: rgba(216, 178, 106, 0.14); color: #8fb0a0"
      >
        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
          <span>© {{ year }} HifzMaal · Amanah · Barakah · Discipline</span>
          <router-link to="/terms" class="hover:underline" style="color: #8fb0a0">Terms</router-link>
          <router-link to="/privacy" class="hover:underline" style="color: #8fb0a0">Privacy</router-link>
        </div>
        <span>Made with Barakah for the Ummah 🌙</span>
      </div>
    </div>
  </footer>
</template>
