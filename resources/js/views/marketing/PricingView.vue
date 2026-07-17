<script setup>
import { ref, computed, onMounted } from 'vue'
import LayoutMarketing from '@/layouts/LayoutMarketing.vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/utils/api'

const authStore = useAuthStore()
const billing = ref('annual') // 'monthly' | 'annual'
const pricing = ref(null)

onMounted(async () => {
  const res = await api.get('/pricing')
  pricing.value = res.data.data
})

const annual = computed(() => billing.value === 'annual')

const proMonthlyDisplay = computed(() => {
  if (!pricing.value) return null
  return annual.value ? pricing.value.pro_yearly_price / 12 : pricing.value.pro_monthly_price
})

const proTarget = computed(() => {
  const redirect = encodeURIComponent('/billing')
  return authStore.isAuthenticated ? '/billing' : `/register?redirect=${redirect}`
})

const faqs = [
  {
    q: 'Is HifzMaal Shariah-compliant?',
    a: 'Zakat is calculated against nisab using live gold and silver rates on the lunar (Hijri) year, per standard fiqh. It is informational — always confirm with a qualified scholar for your own situation.',
  },
  {
    q: 'Do you sell my data?',
    a: 'Never. Your household\'s financial data is not sold, shared, or used to train anything. See our Security page for details.',
  },
  {
    q: 'Can I cancel anytime?',
    a: 'Yes — cancel from the Billing page whenever you like. You keep Pro access until the end of the period you already paid for.',
  },
  {
    q: 'What currency am I billed in?',
    a: 'Subscriptions are billed in USD via Stripe. Your household\'s own budgets and transactions can still be tracked in any currency you choose.',
  },
]
</script>

<template>
  <LayoutMarketing>
    <section class="mx-auto max-w-3xl px-6 pt-20 pb-10 text-center">
      <h1 class="text-4xl font-bold sm:text-5xl" style="color: #f4eede">Simple, honest pricing</h1>
      <p class="mx-auto mt-5 max-w-md text-lg" style="color: #a9bcae">
        Start with intention, upgrade when your family grows.
      </p>

      <div
        class="mx-auto mt-8 inline-flex items-center gap-1 rounded-[11px] p-1"
        style="background: rgba(216, 178, 106, 0.08)"
      >
        <button
          class="rounded-[9px] px-5 py-[9px] text-sm font-semibold transition-all"
          :style="!annual ? 'background:#d8b26a;color:#072019' : 'background:transparent;color:#a9bcae'"
          @click="billing = 'monthly'"
        >
          Monthly
        </button>
        <button
          class="relative rounded-[9px] px-5 py-[9px] text-sm font-semibold transition-all"
          :style="annual ? 'background:#d8b26a;color:#072019' : 'background:transparent;color:#a9bcae'"
          @click="billing = 'annual'"
        >
          Annual
          <span
            class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-bold"
            style="background: rgba(46, 168, 124, 0.2); color: #2ea87c"
          >
            Save 2 months
          </span>
        </button>
      </div>
    </section>

    <section class="mx-auto max-w-3xl px-6 pb-16">
      <div class="grid gap-6 sm:grid-cols-2">
        <!-- Niyyah / Free -->
        <div class="rounded-2xl border p-8" style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22">
          <p class="text-sm" style="font-family: 'Amiri', serif; color: #d8b26a">النية</p>
          <h3 class="mt-2 text-xl font-bold" style="color: #f4eede">Niyyah</h3>
          <p class="mt-1 text-sm" style="color: #8fb0a0">Start with intention</p>
          <p class="mt-6 text-3xl font-bold" style="color: #f4eede">$0</p>
          <p class="text-xs" style="color: #8fb0a0">forever free</p>

          <ul class="mt-6 space-y-3 text-sm" style="color: #c9d6cd">
            <li>✓ {{ pricing?.free?.max_families ?? 1 }} family workspace</li>
            <li>✓ Up to {{ pricing?.free?.max_members_per_family ?? 4 }} family members</li>
            <li>✓ Full Zakat calculator</li>
            <li>✓ Savings goals, bills & budgets</li>
          </ul>

          <router-link
            to="/register"
            class="mt-8 block rounded-[10px] border py-3 text-center text-sm font-semibold"
            style="border-color: rgba(244, 238, 222, 0.25); color: #f4eede"
          >
            Start free
          </router-link>
        </div>

        <!-- Barakah / Pro -->
        <div
          class="relative rounded-2xl p-8"
          style="
            border: 1.5px solid #d8b26a;
            background: linear-gradient(160deg, rgba(216, 178, 106, 0.12), rgba(12, 44, 34, 1));
          "
        >
          <span
            class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full px-3 py-1 text-[11px] font-bold tracking-wide uppercase"
            style="background: #d8b26a; color: #072019"
          >
            Most popular
          </span>
          <p class="text-sm" style="font-family: 'Amiri', serif; color: #d8b26a">البركة</p>
          <h3 class="mt-2 text-xl font-bold" style="color: #f4eede">Barakah</h3>
          <p class="mt-1 text-sm" style="color: #8fb0a0">For growing families</p>
          <p class="mt-6 text-3xl font-bold" style="color: #f4eede">
            ${{ proMonthlyDisplay?.toFixed(2).replace(/\.00$/, '') ?? '—' }}<span class="text-base font-normal" style="color: #a9bcae">/mo</span>
          </p>
          <p class="text-xs" style="color: #8fb0a0">
            {{ annual ? 'billed annually · 2 months free' : 'billed monthly' }}
          </p>

          <ul class="mt-6 space-y-3 text-sm" style="color: #c9d6cd">
            <li>✓ Unlimited family workspaces</li>
            <li>✓ Unlimited family members</li>
            <li>✓ Everything in Niyyah</li>
            <li>✓ Priority support</li>
            <li>✓ {{ pricing?.trial_days ?? 14 }}-day free trial</li>
          </ul>

          <router-link
            :to="proTarget"
            class="mt-8 block rounded-[10px] py-3 text-center text-sm font-semibold"
            style="background: #d8b26a; color: #072019"
          >
            Start {{ pricing?.trial_days ?? 14 }}-day trial
          </router-link>
        </div>
      </div>

      <div
        class="mt-8 rounded-2xl border px-6 py-5 text-center text-sm"
        style="border-color: rgba(216, 178, 106, 0.14); background: rgba(216, 178, 106, 0.05); color: #c9d6cd"
      >
        Running a masjid or nonprofit? <router-link to="/contact" class="font-semibold underline" style="color: #d8b26a">Get in touch</router-link> — we offer free Barakah access.
      </div>
    </section>

    <section class="mx-auto max-w-3xl px-6 pb-24">
      <h2 class="mb-8 text-center text-2xl font-bold" style="color: #f4eede">Frequently asked questions</h2>
      <div class="grid gap-5 sm:grid-cols-2">
        <div v-for="faq in faqs" :key="faq.q" class="rounded-2xl border p-6" style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22">
          <h3 class="font-semibold" style="color: #f4eede">{{ faq.q }}</h3>
          <p class="mt-2 text-sm leading-relaxed" style="color: #a9bcae">{{ faq.a }}</p>
        </div>
      </div>
    </section>
  </LayoutMarketing>
</template>
