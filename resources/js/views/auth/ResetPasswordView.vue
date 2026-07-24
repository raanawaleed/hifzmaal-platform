<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mdiEmail, mdiAsterisk } from '@mdi/js'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import api from '@/utils/api'
import { useNotificationsStore } from '@/stores/notifications'

const route = useRoute()
const router = useRouter()
const notifications = useNotificationsStore()

const form = reactive({
  token: route.query.token || '',
  email: route.query.email || '',
  password: '',
  password_confirmation: '',
})

const loading = ref(false)
const error = ref('')
const linkIncomplete = !form.token || !form.email

const submit = async () => {
  loading.value = true
  error.value = ''
  try {
    await api.post('/reset-password', form)
    notifications.success('Password reset. Please sign in with your new password.')
    router.push('/login')
  } catch (err) {
    error.value =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.errors?.password?.[0] ||
      err.response?.data?.message ||
      'Could not reset the password. The link may have expired.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <LayoutGuest>
    <SectionFullScreen v-if="linkIncomplete" v-slot="{ cardClass }" bg="emerald">
      <CardBox :class="cardClass">
        <div class="mb-6 text-center">
          <h1 class="text-2xl font-bold">Invalid reset link</h1>
          <p class="text-sm text-gray-500 dark:text-slate-400">
            This password reset link is incomplete or has already been used. Request a new one to continue.
          </p>
        </div>

        <template #footer>
          <BaseButtons>
            <BaseButton to="/forgot-password" color="success" label="Request a new link" />
            <BaseButton to="/login" color="success" outline label="Back to sign in" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionFullScreen>

    <SectionFullScreen v-else v-slot="{ cardClass }" bg="emerald">
      <CardBox :class="cardClass" is-form @submit.prevent="submit">
        <div class="mb-6 text-center">
          <h1 class="text-2xl font-bold">Set a new password</h1>
          <p class="text-sm text-gray-500 dark:text-slate-400">
            Must be at least 8 characters with letters and numbers.
          </p>
        </div>

        <NotificationBarInCard v-if="error" color="danger">
          {{ error }}
        </NotificationBarInCard>

        <FormField label="Email">
          <FormControl
            v-model="form.email"
            :icon="mdiEmail"
            name="email"
            type="email"
            autocomplete="email"
            required
          />
        </FormField>

        <FormField label="New password">
          <FormControl
            v-model="form.password"
            :icon="mdiAsterisk"
            type="password"
            name="password"
            autocomplete="new-password"
            required
          />
        </FormField>

        <FormField label="Confirm new password">
          <FormControl
            v-model="form.password_confirmation"
            :icon="mdiAsterisk"
            type="password"
            name="password_confirmation"
            autocomplete="new-password"
            required
          />
        </FormField>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Resetting…' : 'Reset password'"
              :disabled="loading"
            />
            <BaseButton to="/login" color="success" outline label="Back to sign in" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
