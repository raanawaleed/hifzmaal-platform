<script setup>
import { reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { mdiAccount, mdiEmail, mdiAsterisk, mdiTranslate } from '@mdi/js'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import { useAuthStore } from '@/stores/auth'
import { extractErrors } from '@/utils/familyApi'

const authStore = useAuthStore()
const route = useRoute()

const form = reactive({
  name: '',
  email: route.query.email || '',
  password: '',
  password_confirmation: '',
  locale: 'en',
})

const locales = [
  { id: 'en', label: 'English' },
  { id: 'ur', label: 'اردو (Urdu)' },
  { id: 'hi', label: 'हिन्दी (Hindi)' },
  { id: 'bn', label: 'বাংলা (Bangla)' },
]

const loading = ref(false)
const errors = ref({})

const submit = async () => {
  loading.value = true
  errors.value = {}
  try {
    await authStore.register(form, route.query.redirect)
  } catch (err) {
    errors.value = extractErrors(err)
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
          <h1 class="text-2xl font-bold">Create your account</h1>
          <p class="text-sm text-gray-500 dark:text-slate-400">
            Free forever — manage your family finances the Halal way
          </p>
        </div>

        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <FormField label="Full Name" :help="errors.name || 'Your display name'">
          <FormControl
            v-model="form.name"
            :icon="mdiAccount"
            name="name"
            autocomplete="name"
            required
          />
        </FormField>

        <FormField label="Email" :help="errors.email || 'Used to sign in'">
          <FormControl
            v-model="form.email"
            :icon="mdiEmail"
            name="email"
            type="email"
            autocomplete="email"
            required
          />
        </FormField>

        <FormField label="Password" :help="errors.password || 'Minimum 8 characters'">
          <FormControl
            v-model="form.password"
            :icon="mdiAsterisk"
            type="password"
            name="password"
            autocomplete="new-password"
            required
          />
        </FormField>

        <FormField label="Confirm Password" help="Repeat your password">
          <FormControl
            v-model="form.password_confirmation"
            :icon="mdiAsterisk"
            type="password"
            name="password_confirmation"
            autocomplete="new-password"
            required
          />
        </FormField>

        <FormField label="Preferred Language">
          <FormControl v-model="form.locale" :icon="mdiTranslate" :options="locales" />
        </FormField>

        <p class="mb-4 text-xs text-gray-500 dark:text-slate-400">
          By creating an account you agree to our
          <router-link to="/terms" class="text-emerald-600 hover:underline">Terms of Service</router-link>
          and
          <router-link to="/privacy" class="text-emerald-600 hover:underline">Privacy Policy</router-link>.
        </p>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Creating…' : 'Create Account'"
              :disabled="loading"
            />
            <BaseButton
              :to="{ path: '/login', query: route.query }"
              color="success"
              outline
              label="Sign in instead"
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
