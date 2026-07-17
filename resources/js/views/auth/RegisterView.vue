<script setup>
import { reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { mdiAccount, mdiEmail, mdiAsterisk, mdiTranslate } from '@mdi/js'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import { useAuthStore } from '@/stores/auth'
import { extractErrors } from '@/utils/familyApi'

const authStore = useAuthStore()
const route = useRoute()
const { t } = useI18n()

const form = reactive({
  name: '',
  email: route.query.email || '',
  password: '',
  password_confirmation: '',
  locale: 'en',
})

// This is the family/account data locale (sent to the backend), separate
// from the page's own display language above — a user can browse this
// form in Urdu while still choosing English as their family's locale.
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
        <div class="mb-4 flex justify-end">
          <LanguageSwitcher />
        </div>

        <div class="mb-6 text-center">
          <div
            class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-black text-white"
          >
            HM
          </div>
          <h1 class="text-2xl font-bold">{{ t('auth.createYourAccount') }}</h1>
          <p class="text-sm text-gray-500 dark:text-slate-400">
            {{ t('auth.freeForever') }}
          </p>
        </div>

        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <FormField :label="t('auth.fullName')" :help="errors.name || t('auth.fullNameHelp')">
          <FormControl
            v-model="form.name"
            :icon="mdiAccount"
            name="name"
            autocomplete="name"
            required
          />
        </FormField>

        <FormField :label="t('auth.email')" :help="errors.email || t('auth.usedToSignIn')">
          <FormControl
            v-model="form.email"
            :icon="mdiEmail"
            name="email"
            type="email"
            autocomplete="email"
            required
          />
        </FormField>

        <FormField :label="t('auth.password')" :help="errors.password || t('auth.minEightChars')">
          <FormControl
            v-model="form.password"
            :icon="mdiAsterisk"
            type="password"
            name="password"
            autocomplete="new-password"
            required
          />
        </FormField>

        <FormField :label="t('auth.confirmPassword')" :help="t('auth.repeatPassword')">
          <FormControl
            v-model="form.password_confirmation"
            :icon="mdiAsterisk"
            type="password"
            name="password_confirmation"
            autocomplete="new-password"
            required
          />
        </FormField>

        <FormField :label="t('auth.preferredLanguage')">
          <FormControl v-model="form.locale" :icon="mdiTranslate" :options="locales" />
        </FormField>

        <p class="mb-4 text-xs text-gray-500 dark:text-slate-400">
          {{ t('auth.agreeToTerms') }}
          <router-link to="/terms" class="text-emerald-600 hover:underline">{{ t('landing.termsOfService') }}</router-link>
          {{ t('auth.and') }}
          <router-link to="/privacy" class="text-emerald-600 hover:underline">{{ t('landing.privacyPolicy') }}</router-link>.
        </p>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? t('auth.creatingAccount') : t('auth.createAccountBtn')"
              :disabled="loading"
            />
            <BaseButton
              :to="{ path: '/login', query: route.query }"
              color="success"
              outline
              :label="t('auth.signInInstead')"
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
