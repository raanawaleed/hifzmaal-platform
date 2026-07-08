<script setup>
import {
  mdiWallet, mdiReceiptText, mdiChartPie, mdiPiggyBank, mdiHandCoin,
  mdiAccountGroup, mdiCheckCircle, mdiMenu, mdiClose,
} from '@mdi/js'
import { ref } from 'vue'
import BaseIcon from '@/components/BaseIcon.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const mobileMenuOpen = ref(false)

const features = [
  { icon: mdiWallet, title: 'Accounts & Transactions', text: 'Track every account and transaction across your whole household in one place.' },
  { icon: mdiAccountGroup, title: 'Built for families', text: 'Invite your spouse, kids, and dependents with roles and spending limits that fit each person.' },
  { icon: mdiReceiptText, title: 'Bills, on time', text: 'Never miss a due date — reminders and one-tap mark-as-paid for every recurring bill.' },
  { icon: mdiChartPie, title: 'Budgets that stick', text: 'Set category budgets and see exactly where the month is heading before it is too late.' },
  { icon: mdiPiggyBank, title: 'Savings goals', text: 'Save toward Hajj, a home, or an emergency fund with automatic contributions.' },
  { icon: mdiHandCoin, title: 'Zakat, calculated right', text: 'Nisab-aware Zakat calculations on gold, silver, and cash — with a payment history you can trust.' },
]
</script>

<template>
  <div class="min-h-screen bg-white text-gray-900 dark:bg-slate-900 dark:text-slate-100">
    <!-- Nav -->
    <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
      <div class="flex items-center gap-2">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-sm font-black text-white">
          HM
        </div>
        <span class="text-lg font-bold">HifzMaal</span>
      </div>

      <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
        <a href="#features" class="hover:text-emerald-600">Features</a>
        <a href="#pricing" class="hover:text-emerald-600">Pricing</a>
        <router-link
          v-if="authStore.isAuthenticated"
          to="/dashboard"
          class="rounded-full bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700"
        >
          Go to Dashboard
        </router-link>
        <template v-else>
          <router-link to="/login" class="hover:text-emerald-600">Log in</router-link>
          <router-link to="/register" class="rounded-full bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">
            Get started free
          </router-link>
        </template>
      </nav>

      <button class="md:hidden" @click="mobileMenuOpen = !mobileMenuOpen">
        <BaseIcon :path="mobileMenuOpen ? mdiClose : mdiMenu" size="24" />
      </button>
    </header>

    <div v-if="mobileMenuOpen" class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 text-sm md:hidden dark:border-slate-800">
      <a href="#features" @click="mobileMenuOpen = false">Features</a>
      <a href="#pricing" @click="mobileMenuOpen = false">Pricing</a>
      <router-link v-if="authStore.isAuthenticated" to="/dashboard">Go to Dashboard</router-link>
      <template v-else>
        <router-link to="/login">Log in</router-link>
        <router-link to="/register" class="font-semibold text-emerald-600">Get started free</router-link>
      </template>
    </div>

    <!-- Hero -->
    <section class="mx-auto max-w-4xl px-6 py-16 text-center sm:py-24">
      <p class="mb-3 text-sm font-semibold tracking-wide text-emerald-600 uppercase">حفظ مال · Islamic Family Finance</p>
      <h1 class="text-4xl font-black tracking-tight sm:text-5xl">
        Manage your family's money, the halal way.
      </h1>
      <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600 dark:text-slate-300">
        Accounts, bills, budgets, savings goals, and Zakat — shared across your whole household,
        with roles and spending limits for every family member.
      </p>
      <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
        <router-link
          to="/register"
          class="rounded-full bg-emerald-600 px-6 py-3 font-semibold text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-700"
        >
          Start your free trial
        </router-link>
        <router-link to="/login" class="px-6 py-3 font-semibold text-gray-700 hover:text-emerald-600 dark:text-slate-300">
          Log in
        </router-link>
      </div>
      <p class="mt-4 text-xs text-gray-400 dark:text-slate-500">No card required to start.</p>
    </section>

    <!-- Features -->
    <section id="features" class="mx-auto max-w-6xl px-6 py-16">
      <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="f in features" :key="f.title" class="rounded-2xl border border-gray-100 p-6 dark:border-slate-800">
          <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
            <BaseIcon :path="f.icon" size="22" />
          </div>
          <h3 class="mb-2 text-lg font-bold">{{ f.title }}</h3>
          <p class="text-sm text-gray-600 dark:text-slate-400">{{ f.text }}</p>
        </div>
      </div>
    </section>

    <!-- Pricing teaser -->
    <section id="pricing" class="mx-auto max-w-4xl px-6 py-16">
      <h2 class="mb-10 text-center text-3xl font-bold">Simple pricing</h2>
      <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-2xl border border-gray-100 p-8 dark:border-slate-800">
          <h3 class="text-lg font-bold">Free</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Try HifzMaal with one family</p>
          <p class="mt-4 text-3xl font-black">$0</p>
          <ul class="mt-6 space-y-3 text-sm">
            <li class="flex items-center gap-2">
              <BaseIcon :path="mdiCheckCircle" size="18" class="text-emerald-500" /> 1 family workspace
            </li>
            <li class="flex items-center gap-2">
              <BaseIcon :path="mdiCheckCircle" size="18" class="text-emerald-500" /> Up to 4 members
            </li>
            <li class="flex items-center gap-2">
              <BaseIcon :path="mdiCheckCircle" size="18" class="text-emerald-500" /> All core features
            </li>
          </ul>
        </div>
        <div class="rounded-2xl border-2 border-emerald-600 p-8">
          <h3 class="text-lg font-bold text-emerald-600">Pro</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">For bigger or blended families</p>
          <p class="mt-4 text-3xl font-black">
            $ / month
            <span class="text-sm font-normal text-gray-500 dark:text-slate-400">billed monthly or yearly</span>
          </p>
          <ul class="mt-6 space-y-3 text-sm">
            <li class="flex items-center gap-2">
              <BaseIcon :path="mdiCheckCircle" size="18" class="text-emerald-500" /> Unlimited family workspaces
            </li>
            <li class="flex items-center gap-2">
              <BaseIcon :path="mdiCheckCircle" size="18" class="text-emerald-500" /> Unlimited members
            </li>
            <li class="flex items-center gap-2">
              <BaseIcon :path="mdiCheckCircle" size="18" class="text-emerald-500" /> Priority support
            </li>
          </ul>
        </div>
      </div>
      <p class="mt-8 text-center">
        <router-link to="/register" class="font-semibold text-emerald-600 hover:underline">
          Start your free trial →
        </router-link>
      </p>
    </section>

    <!-- Footer -->
    <footer class="border-t border-gray-100 px-6 py-8 dark:border-slate-800">
      <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 text-sm text-gray-500 sm:flex-row dark:text-slate-400">
        <span>© {{ new Date().getFullYear() }} HifzMaal — Islamic Family Finance</span>
        <div class="flex gap-6">
          <router-link to="/terms" class="hover:text-emerald-600">Terms of Service</router-link>
          <router-link to="/privacy" class="hover:text-emerald-600">Privacy Policy</router-link>
        </div>
      </div>
    </footer>
  </div>
</template>
