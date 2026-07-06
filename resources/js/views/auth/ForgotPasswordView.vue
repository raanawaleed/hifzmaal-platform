<script setup>
import { reactive, ref } from 'vue'
import { mdiEmail } from '@mdi/js'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import api from '@/utils/api'

const form = reactive({ email: '' })
const loading = ref(false)
const sent = ref(false)
const error = ref('')

const submit = async () => {
  loading.value = true
  error.value = ''
  try {
    await api.post('/forgot-password', form)
    sent.value = true
  } catch (err) {
    error.value = err.response?.data?.message || 'Could not send the reset link. Try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <LayoutGuest>
    <SectionFullScreen v-slot="{ cardClass }" bg="emerald">
      <CardBox :class="cardClass" is-form @submit.prevent="submit">
        <div class="mb-6 text-center">
          <h1 class="text-2xl font-bold">Forgot your password?</h1>
          <p class="text-sm text-gray-500 dark:text-slate-400">
            Enter your email and we'll send you a reset link.
          </p>
        </div>

        <NotificationBarInCard v-if="sent" color="success">
          If that email address exists, a password reset link has been sent. Check your inbox.
        </NotificationBarInCard>
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

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Sending…' : 'Send reset link'"
              :disabled="loading || sent"
            />
            <BaseButton to="/login" color="success" outline label="Back to sign in" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
