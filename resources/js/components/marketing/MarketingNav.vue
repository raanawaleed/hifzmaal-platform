<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { mdiMenu, mdiClose } from '@mdi/js'
import LogoMark from '@/components/marketing/LogoMark.vue'
import BaseIcon from '@/components/BaseIcon.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const authStore = useAuthStore()
const mobileOpen = ref(false)

const navItems = [
  { name: 'marketing-home', label: 'Home' },
  { name: 'marketing-features', label: 'Features' },
  { name: 'marketing-pricing', label: 'Pricing' },
  { name: 'marketing-security', label: 'Security' },
  { name: 'marketing-contact', label: 'Contact' },
]

const isActive = (name) => route.name === name

const dashboardTarget = computed(() => (authStore.isSuperAdmin ? '/admin' : '/dashboard'))
</script>

<template>
  <header
    class="sticky top-0 z-50 border-b"
    style="background: rgba(7, 32, 25, 0.82); backdrop-filter: blur(14px); border-color: rgba(216, 178, 106, 0.14)"
  >
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
      <router-link :to="{ name: 'marketing-home' }" class="flex items-center gap-3">
        <LogoMark :size="40" />
        <div class="leading-tight">
          <div class="text-[19px] font-bold" style="color: #f3ecd9">HifzMaal</div>
          <div class="text-[13px]" style="font-family: 'Amiri', serif; color: #d8b26a">حفظ المال</div>
        </div>
      </router-link>

      <nav class="hidden items-center gap-7 md:flex">
        <router-link
          v-for="item in navItems"
          :key="item.name"
          :to="{ name: item.name }"
          class="text-[15px] transition-colors"
          :style="{ color: isActive(item.name) ? '#f4eede' : '#a9bcae', fontWeight: isActive(item.name) ? 600 : 500 }"
        >
          {{ item.label }}
        </router-link>
      </nav>

      <div class="hidden items-center gap-5 md:flex">
        <router-link v-if="authStore.isAuthenticated" :to="dashboardTarget" class="text-[15px]" style="color: #a9bcae">
          Go to dashboard
        </router-link>
        <template v-else>
          <router-link to="/login" class="text-[15px]" style="color: #a9bcae">Sign in</router-link>
          <router-link
            to="/register"
            class="rounded-[9px] px-5 py-[11px] text-[15px] font-semibold"
            style="background: #d8b26a; color: #072019"
          >
            Start free
          </router-link>
        </template>
      </div>

      <button class="md:hidden" style="color: #f4eede" @click="mobileOpen = !mobileOpen">
        <BaseIcon :path="mobileOpen ? mdiClose : mdiMenu" size="26" />
      </button>
    </div>

    <div
      v-if="mobileOpen"
      class="flex flex-col gap-4 border-t px-6 py-5 md:hidden"
      style="border-color: rgba(216, 178, 106, 0.14)"
    >
      <router-link
        v-for="item in navItems"
        :key="item.name"
        :to="{ name: item.name }"
        class="text-[15px]"
        :style="{ color: isActive(item.name) ? '#f4eede' : '#a9bcae', fontWeight: isActive(item.name) ? 600 : 500 }"
        @click="mobileOpen = false"
      >
        {{ item.label }}
      </router-link>
      <router-link v-if="authStore.isAuthenticated" :to="dashboardTarget" style="color: #a9bcae" @click="mobileOpen = false">
        Go to dashboard
      </router-link>
      <template v-else>
        <router-link to="/login" style="color: #a9bcae" @click="mobileOpen = false">Sign in</router-link>
        <router-link
          to="/register"
          class="w-fit rounded-[9px] px-5 py-[11px] text-[15px] font-semibold"
          style="background: #d8b26a; color: #072019"
          @click="mobileOpen = false"
        >
          Start free
        </router-link>
      </template>
    </div>
  </header>
</template>
