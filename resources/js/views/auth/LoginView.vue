<script setup>
import { reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { mdiEmail, mdiAsterisk, mdiShieldKey } from '@mdi/js'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseDivider from '@/components/BaseDivider.vue'
import BaseIcon from '@/components/BaseIcon.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const route = useRoute()
const { t } = useI18n()

const form = reactive({
  email: route.query.email || '',
  password: '',
})

const loading = ref(false)
const error = ref('')

// Set once login() reports two_factor_required — switches the card into
// the code-entry step instead of a second full page.
const twoFactorToken = ref('')
const useRecoveryCode = ref(false)
const twoFactorInput = ref('')

const submit = async () => {
  loading.value = true
  error.value = ''
  try {
    const result = await authStore.login(form, route.query.redirect)
    if (result.twoFactorRequired) {
      twoFactorToken.value = result.twoFactorToken
    }
  } catch (err) {
    error.value =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      t('auth.invalidCredentials')
  } finally {
    loading.value = false
  }
}

const submitTwoFactor = async () => {
  loading.value = true
  error.value = ''
  try {
    const payload = { two_factor_token: twoFactorToken.value }
    if (useRecoveryCode.value) {
      payload.recovery_code = twoFactorInput.value
    } else {
      payload.code = twoFactorInput.value
    }
    await authStore.completeTwoFactorChallenge(payload, route.query.redirect)
  } catch (err) {
    error.value =
      err.response?.data?.errors?.code?.[0] ||
      err.response?.data?.errors?.recovery_code?.[0] ||
      err.response?.data?.message ||
      'That code is invalid or has expired.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <LayoutGuest>
    <SectionFullScreen v-slot="{ cardClass }" bg="emerald">
      <CardBox
        v-if="!twoFactorToken"
        :class="cardClass"
        is-form
        @submit.prevent="submit"
      >
        <div class="mb-4 flex justify-end">
          <LanguageSwitcher />
        </div>

        <div class="mb-6 text-center">
          <div
            class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-black text-white"
          >
            HM
          </div>
          <h1 class="text-2xl font-bold">HifzMaal</h1>
          <p class="text-sm text-gray-500 dark:text-slate-400">
            {{ t('auth.appTagline') }}
          </p>
        </div>

        <NotificationBarInCard v-if="error" color="danger">
          {{ error }}
        </NotificationBarInCard>

        <FormField :label="t('auth.email')" :help="t('auth.emailHelp')">
          <FormControl
            v-model="form.email"
            :icon="mdiEmail"
            name="email"
            type="email"
            autocomplete="email"
            required
          />
        </FormField>

        <FormField :label="t('auth.password')" :help="t('auth.passwordHelp')">
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
              :label="loading ? t('auth.signingIn') : t('auth.signIn')"
              :disabled="loading"
            />
            <BaseButton
              :to="{ path: '/register', query: route.query }"
              color="success"
              outline
              :label="t('auth.createAccount')"
            />
            <BaseButton to="/forgot-password" color="success" outline :label="t('auth.forgotPassword')" />
          </BaseButtons>
        </template>
      </CardBox>

      <CardBox v-else :class="cardClass" is-form @submit.prevent="submitTwoFactor">
        <div class="mb-6 text-center">
          <div
            class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-black text-white"
          >
            <BaseIcon :path="mdiShieldKey" size="28" />
          </div>
          <h1 class="text-2xl font-bold">Two-Factor Verification</h1>
          <p class="text-sm text-gray-500 dark:text-slate-400">
            {{ useRecoveryCode ? 'Enter one of your recovery codes.' : 'Enter the 6-digit code from your authenticator app.' }}
          </p>
        </div>

        <NotificationBarInCard v-if="error" color="danger">
          {{ error }}
        </NotificationBarInCard>

        <FormField :label="useRecoveryCode ? 'Recovery code' : 'Authentication code'">
          <FormControl
            v-model="twoFactorInput"
            :icon="mdiShieldKey"
            :inputmode="useRecoveryCode ? 'text' : 'numeric'"
            autocomplete="one-time-code"
            required
            autofocus
          />
        </FormField>

        <button
          type="button"
          class="mb-4 text-xs text-emerald-600 hover:underline dark:text-emerald-400"
          @click="useRecoveryCode = !useRecoveryCode; twoFactorInput = ''"
        >
          {{ useRecoveryCode ? 'Use an authenticator code instead' : 'Use a recovery code instead' }}
        </button>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Verifying…' : 'Verify'"
              :disabled="loading || !twoFactorInput"
            />
            <BaseButton color="whiteDark" outline label="Back" @click="twoFactorToken = ''; error = ''" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
