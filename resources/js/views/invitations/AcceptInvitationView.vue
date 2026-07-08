<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import { useAuthStore } from '@/stores/auth'
import { useFamilyStore } from '@/stores/family'
import { useNotificationsStore } from '@/stores/notifications'
import api from '@/utils/api'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const familyStore = useFamilyStore()
const notifications = useNotificationsStore()

const token = route.query.token || ''
const redirectPath = `/invitations/accept?token=${encodeURIComponent(token)}`

const loading = ref(true)
const accepting = ref(false)
const error = ref('')
const invite = ref(null)

const emailMatches = computed(() => {
  if (!invite.value || !authStore.user) return false
  return authStore.user.email?.toLowerCase() === invite.value.email?.toLowerCase()
})

const load = async () => {
  if (!token) {
    error.value = 'This invitation link is missing its token.'
    loading.value = false
    return
  }
  try {
    const res = await api.get(`/invitations/${token}`)
    invite.value = res.data.data
  } catch (err) {
    error.value = err.response?.data?.message || 'This invitation could not be found or has expired.'
  } finally {
    loading.value = false
  }
}

const accept = async () => {
  accepting.value = true
  try {
    const res = await api.post(`/invitations/${token}/accept`)
    notifications.success(`You've joined ${res.data.data.family.name}.`)
    await familyStore.loadUserFamilies()
    familyStore.setFamilyId(res.data.data.family.id)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Could not accept this invitation.'
  } finally {
    accepting.value = false
  }
}

onMounted(load)
</script>

<template>
  <LayoutGuest>
    <SectionFullScreen v-slot="{ cardClass }" bg="emerald">
      <CardBox :class="cardClass">
        <div v-if="loading" class="py-8 text-center text-gray-500 dark:text-slate-400">
          Loading invitation…
        </div>

        <template v-else-if="error">
          <NotificationBarInCard color="danger">{{ error }}</NotificationBarInCard>
          <BaseButton to="/dashboard" color="success" label="Go to HifzMaal" class="mt-4" />
        </template>

        <template v-else>
          <div class="mb-6 text-center">
            <div
              class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-black text-white"
            >
              HM
            </div>
            <h1 class="text-xl font-bold">You're invited!</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">
              <b>{{ invite.invited_by }}</b> invited you to join
              <b>{{ invite.family_name }}</b> as a <b>{{ invite.role }}</b>.
            </p>
          </div>

          <template v-if="authStore.isAuthenticated">
            <NotificationBarInCard v-if="!emailMatches" color="warning">
              This invite was sent to {{ invite.email }}, but you're signed in as
              {{ authStore.user?.email }}. Log out and sign in with that address to accept it.
            </NotificationBarInCard>
            <BaseButtons v-else class="justify-center">
              <BaseButton
                color="success"
                :label="accepting ? 'Joining…' : 'Accept & Join'"
                :disabled="accepting"
                @click="accept"
              />
            </BaseButtons>
          </template>

          <template v-else>
            <p class="mb-4 text-center text-sm text-gray-500 dark:text-slate-400">
              Log in or create an account with <b>{{ invite.email }}</b> to accept.
            </p>
            <BaseButtons class="justify-center">
              <BaseButton
                :to="{ path: '/login', query: { redirect: redirectPath, email: invite.email } }"
                color="success"
                label="Log in to accept"
              />
              <BaseButton
                :to="{ path: '/register', query: { redirect: redirectPath, email: invite.email } }"
                color="success"
                outline
                label="Create an account"
              />
            </BaseButtons>
          </template>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
