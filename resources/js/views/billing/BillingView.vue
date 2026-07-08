<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mdiCreditCardOutline, mdiCrown, mdiCheckCircle, mdiOpenInNew } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseIcon from '@/components/BaseIcon.vue'
import { useNotificationsStore } from '@/stores/notifications'
import api from '@/utils/api'

const route = useRoute()
const router = useRouter()
const notifications = useNotificationsStore()

const status = ref(null)
const loading = ref(true)
const redirecting = ref(null) // holds the plan key being checked out, or 'portal'

const load = async () => {
  loading.value = true
  try {
    const res = await api.get('/billing/status')
    status.value = res.data.data
  } finally {
    loading.value = false
  }
}

const trialDaysLeft = () => {
  if (!status.value?.trial_ends_at) return 0
  const ms = new Date(status.value.trial_ends_at) - new Date()
  return Math.max(0, Math.ceil(ms / (1000 * 60 * 60 * 24)))
}

const upgrade = async (planKey) => {
  redirecting.value = planKey
  try {
    const res = await api.post('/billing/checkout', { plan: planKey })
    window.location.href = res.data.url
  } catch (err) {
    notifications.error(err.response?.data?.message || 'Could not start checkout. Please try again.')
    redirecting.value = null
  }
}

const openPortal = async () => {
  redirecting.value = 'portal'
  try {
    const res = await api.get('/billing/portal')
    window.location.href = res.data.url
  } catch (err) {
    notifications.error(err.response?.data?.message || 'Could not open the billing portal.')
    redirecting.value = null
  }
}

onMounted(async () => {
  if (route.query.checkout === 'success') {
    notifications.success("You're on Pro — thanks for subscribing!")
    router.replace({ query: {} })
  } else if (route.query.checkout === 'cancelled') {
    notifications.error('Checkout was cancelled — no changes were made.')
    router.replace({ query: {} })
  }
  await load()
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiCreditCardOutline" title="Billing" main />

      <CardBox v-if="loading" class="mb-6">
        <p class="text-gray-500 dark:text-slate-400">Loading billing status…</p>
      </CardBox>

      <template v-else-if="status">
        <CardBox class="mb-6">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <div class="flex items-center gap-2">
                <BaseIcon v-if="status.plan === 'pro'" :path="mdiCrown" class="text-amber-500" size="20" />
                <h3 class="text-lg font-bold">
                  {{ status.plan === 'pro' ? 'Pro plan' : 'Free plan' }}
                </h3>
              </div>

              <p v-if="status.on_trial" class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                {{ trialDaysLeft() }} day(s) left in your free Pro trial — no card required yet.
              </p>
              <p v-else-if="status.subscribed && status.cancelled" class="mt-1 text-sm text-amber-600 dark:text-amber-400">
                Your subscription is cancelled and stays active until
                {{ new Date(status.ends_at).toLocaleDateString() }}.
              </p>
              <p v-else-if="status.subscribed" class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Billed monthly or yearly in USD. Manage your card and invoices any time.
              </p>
              <p v-else class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ status.families.used }} of
                {{ status.families.limit ?? '∞' }} family workspace(s) used.
              </p>
            </div>

            <BaseButton
              v-if="status.subscribed"
              color="info"
              :icon="mdiOpenInNew"
              label="Manage billing"
              :disabled="redirecting === 'portal'"
              @click="openPortal"
            />
          </div>
        </CardBox>

        <div v-if="!status.subscribed" class="grid gap-6 md:grid-cols-2">
          <CardBox v-for="plan in status.plans" :key="plan.key">
            <h3 class="text-lg font-bold">{{ plan.label }}</h3>
            <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-slate-300">
              <li class="flex items-center gap-2">
                <BaseIcon :path="mdiCheckCircle" size="16" class="text-emerald-500" />
                Unlimited family workspaces
              </li>
              <li class="flex items-center gap-2">
                <BaseIcon :path="mdiCheckCircle" size="16" class="text-emerald-500" />
                Unlimited members per family
              </li>
              <li class="flex items-center gap-2">
                <BaseIcon :path="mdiCheckCircle" size="16" class="text-emerald-500" />
                Priority support
              </li>
            </ul>

            <template #footer>
              <BaseButton
                color="success"
                :label="redirecting === plan.key ? 'Redirecting…' : `Upgrade — ${plan.label}`"
                :disabled="!!redirecting"
                class="w-full justify-center"
                @click="upgrade(plan.key)"
              />
            </template>
          </CardBox>
        </div>

        <CardBox v-if="!status.subscribed && status.plans.length === 0">
          <p class="text-gray-500 dark:text-slate-400">
            Pro plans aren't configured yet. Please contact support.
          </p>
        </CardBox>
      </template>
    </SectionMain>
  </LayoutAuthenticated>
</template>
