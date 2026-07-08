<script setup>
import { mdiForwardburger, mdiBackburger, mdiMenu, mdiShieldCrown } from '@mdi/js'
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { menuAsideMain, menuAsideBottom } from '@/menuAside.js'
import menuNavBar from '@/menuNavBar.js'
import { useDarkModeStore } from '@/stores/darkMode.js'
import { useAuthStore } from '@/stores/auth.js'
import { useMainStore } from '@/stores/main.js'
import { useFamilyStore } from '@/stores/family.js'
import BaseIcon from '@/components/BaseIcon.vue'
import NavBar from '@/components/NavBar.vue'
import NavBarItemPlain from '@/components/NavBarItemPlain.vue'
import AsideMenu from '@/components/AsideMenu.vue'
import FooterBar from '@/components/FooterBar.vue'
import EmailVerificationBanner from '@/components/EmailVerificationBanner.vue'

const layoutAsidePadding = 'xl:pl-60'

const darkModeStore = useDarkModeStore()
const authStore = useAuthStore()
const mainStore = useMainStore()
const familyStore = useFamilyStore()

const router = useRouter()

const isAsideMobileExpanded = ref(false)
const isAsideLgActive = ref(false)

const menuBottom = computed(() => (
  authStore.isSuperAdmin
    ? [...menuAsideBottom, { to: '/admin', icon: mdiShieldCrown, label: 'Admin Panel' }]
    : menuAsideBottom
))

onMounted(async () => {
  if (authStore.isAuthenticated) {
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch {}
    }
    mainStore.setUser(authStore.user || {})
    await familyStore.ensureLoaded()
  }
})

router.beforeEach(() => {
  isAsideMobileExpanded.value = false
  isAsideLgActive.value = false
})

const menuClick = (event, item) => {
  if (item.isToggleLightDark) {
    darkModeStore.set(null, true)
  }

  if (item.isLogout) {
    authStore.logout()
  }
}

const onFamilyChange = (event) => {
  familyStore.setFamilyId(Number(event.target.value))
  router.push('/dashboard')
}
</script>

<template>
  <div
    :class="{
      'overflow-hidden lg:overflow-visible': isAsideMobileExpanded,
    }"
  >
    <div
      :class="[layoutAsidePadding, { 'ml-60 lg:ml-0': isAsideMobileExpanded }]"
      class="min-h-screen w-screen bg-gray-50 pt-14 transition-(--transition-position) lg:w-auto dark:bg-slate-800 dark:text-slate-100"
    >
      <NavBar
        :menu="menuNavBar"
        :class="[layoutAsidePadding, { 'ml-60 lg:ml-0': isAsideMobileExpanded }]"
        @menu-click="menuClick"
      >
        <NavBarItemPlain
          display="flex lg:hidden"
          @click.prevent="isAsideMobileExpanded = !isAsideMobileExpanded"
        >
          <BaseIcon :path="isAsideMobileExpanded ? mdiBackburger : mdiForwardburger" size="24" />
        </NavBarItemPlain>
        <NavBarItemPlain display="hidden lg:flex xl:hidden" @click.prevent="isAsideLgActive = true">
          <BaseIcon :path="mdiMenu" size="24" />
        </NavBarItemPlain>

        <!-- Family switcher -->
        <NavBarItemPlain use-margin>
          <div class="flex items-center gap-2">
            <span class="hidden text-xs tracking-wide text-gray-500 uppercase md:inline dark:text-slate-400">
              Family
            </span>
            <select
              v-if="familyStore.hasFamily"
              :value="familyStore.currentFamilyId"
              class="h-9 rounded-sm border border-gray-300 bg-white px-2 pr-8 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-slate-600 dark:bg-slate-800"
              @change="onFamilyChange"
            >
              <option v-for="f in familyStore.families" :key="f.id" :value="f.id">
                {{ f.name }}
              </option>
            </select>
            <router-link
              v-else
              to="/families/create"
              class="text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400"
            >
              + Create family
            </router-link>
          </div>
        </NavBarItemPlain>
      </NavBar>
      <AsideMenu
        :is-aside-mobile-expanded="isAsideMobileExpanded"
        :is-aside-lg-active="isAsideLgActive"
        :menu="menuAsideMain"
        :menu-bottom="menuBottom"
        @menu-click="menuClick"
        @aside-lg-close-click="isAsideLgActive = false"
      />
      <EmailVerificationBanner />
      <slot />
      <FooterBar>
        HifzMaal — Islamic Family Finance
      </FooterBar>
    </div>
  </div>
</template>
