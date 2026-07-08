<script setup>
import { reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { mdiEmail, mdiAsterisk } from '@mdi/js'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseDivider from '@/components/BaseDivider.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const route = useRoute()

const form = reactive({
  email: route.query.email || '',
  password: '',
})

const loading = ref(false)
const error = ref('')

const submit = async () => {
  loading.value = true
  error.value = ''
  try {
    await authStore.login(form, route.query.redirect)
  } catch (err) {
    error.value =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Invalid email or password.'
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
          <div
            class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-black text-white"
          >
            HM
          </div>
          <h1 class="text-2xl font-bold">HifzMaal</h1>
          <p class="text-sm text-gray-500 dark:text-slate-400">
            حفظ مال · Islamic Family Finance
          </p>
        </div>

        <NotificationBarInCard v-if="error" color="danger">
          {{ error }}
        </NotificationBarInCard>

        <FormField label="Email" help="Your account email address">
          <FormControl
            v-model="form.email"
            :icon="mdiEmail"
            name="email"
            type="email"
            autocomplete="email"
            required
          />
        </FormField>

        <FormField label="Password" help="Your account password">
          <FormControl
            v-model="form.password"
            :icon="mdiAsterisk"
            type="password"
            name="password"
            autocomplete="current-password"
            required
          />
        </FormField>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Signing in…' : 'Sign In'"
              :disabled="loading"
            />
            <BaseButton
              :to="{ path: '/register', query: route.query }"
              color="success"
              outline
              label="Create account"
            />
            <BaseButton to="/forgot-password" color="success" outline label="Forgot password?" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
