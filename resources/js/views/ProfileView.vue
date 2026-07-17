<script setup>
import { computed, ref } from 'vue'
import QRCode from 'qrcode'
import { mdiAccount, mdiEmail, mdiAlertOctagon, mdiShieldKey, mdiShieldCheck } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseIcon from '@/components/BaseIcon.vue'
import PillTag from '@/components/PillTag.vue'
import UserCard from '@/components/UserCard.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import api from '@/utils/api'

const authStore = useAuthStore()
const notifications = useNotificationsStore()

const name = computed(() => authStore.user?.name || '')
const email = computed(() => authStore.user?.email || '')
const isVerified = computed(() => !!authStore.user?.email_verified_at)
const twoFactorEnabled = computed(() => !!authStore.user?.two_factor_enabled)
const logout = () => authStore.logout()

const showDeleteModal = ref(false)
const deletePassword = ref('')
const deleteError = ref('')
const deleting = ref(false)

const openDeleteModal = () => {
  deletePassword.value = ''
  deleteError.value = ''
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  deleteError.value = ''
  deleting.value = true
  try {
    await api.delete('/account', { data: { password: deletePassword.value } })
    showDeleteModal.value = false
    notifications.success('Your account has been deleted.')
    await authStore.clearSession()
    window.location.href = '/login'
  } catch (err) {
    deleteError.value = err.response?.data?.errors?.password?.[0]
      || err.response?.data?.message
      || 'Could not delete your account.'
  } finally {
    deleting.value = false
  }
}

// ── Two-factor authentication ──────────────────────────
const showSetupModal = ref(false)
const showDisableModal = ref(false)
const showRecoveryCodesModal = ref(false)

const setupStep = ref('qr') // 'qr' | 'confirm'
const qrDataUrl = ref('')
const secret = ref('')
const confirmCode = ref('')
const setupError = ref('')
const setupBusy = ref(false)

const disablePassword = ref('')
const disableError = ref('')
const disableBusy = ref(false)

const recoveryCodes = ref([])

const startSetup = async () => {
  setupError.value = ''
  confirmCode.value = ''
  setupStep.value = 'qr'
  showSetupModal.value = true
  setupBusy.value = true
  try {
    const res = await api.post('/two-factor/enable')
    secret.value = res.data.data.secret
    qrDataUrl.value = await QRCode.toDataURL(res.data.data.otpauth_url)
  } catch (err) {
    setupError.value = err.response?.data?.message || 'Could not start two-factor setup.'
  } finally {
    setupBusy.value = false
  }
}

const confirmSetup = async () => {
  setupError.value = ''
  setupBusy.value = true
  try {
    const res = await api.post('/two-factor/confirm', { code: confirmCode.value })
    recoveryCodes.value = res.data.data.recovery_codes
    showSetupModal.value = false
    showRecoveryCodesModal.value = true
    authStore.setUser({ ...authStore.user, two_factor_enabled: true })
    notifications.success('Two-factor authentication is now enabled.')
  } catch (err) {
    setupError.value = err.response?.data?.errors?.code?.[0]
      || err.response?.data?.message
      || 'That code is invalid.'
  } finally {
    setupBusy.value = false
  }
}

const openDisableModal = () => {
  disablePassword.value = ''
  disableError.value = ''
  showDisableModal.value = true
}

const confirmDisable = async () => {
  disableError.value = ''
  disableBusy.value = true
  try {
    await api.delete('/two-factor', { data: { password: disablePassword.value } })
    showDisableModal.value = false
    authStore.setUser({ ...authStore.user, two_factor_enabled: false })
    notifications.success('Two-factor authentication has been disabled.')
  } catch (err) {
    disableError.value = err.response?.data?.errors?.password?.[0]
      || err.response?.data?.message
      || 'Could not disable two-factor authentication.'
  } finally {
    disableBusy.value = false
  }
}
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccount" title="Profile" main />

      <UserCard class="mb-6" />

      <CardBox class="mb-6">
        <FormField label="Name" help="Your display name">
          <FormControl :model-value="name" :icon="mdiAccount" disabled />
        </FormField>
        <FormField label="Email" help="Your sign-in email">
          <FormControl :model-value="email" :icon="mdiEmail" type="email" disabled />
        </FormField>
        <PillTag
          :color="isVerified ? 'success' : 'warning'"
          :label="isVerified ? 'Verified' : 'Not verified'"
          small
          class="-mt-4 mb-6"
        />

        <template #footer>
          <BaseButtons>
            <BaseButton color="danger" outline label="Log Out" @click="logout" />
          </BaseButtons>
        </template>
      </CardBox>

      <CardBox class="mb-6">
        <h3 class="mb-2 flex items-center gap-2 text-lg font-bold">
          <BaseIcon :path="mdiShieldKey" size="20" />
          Two-Factor Authentication
        </h3>
        <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">
          Require a code from an authenticator app (Google Authenticator, Authy, 1Password…)
          in addition to your password when signing in.
        </p>
        <PillTag
          :color="twoFactorEnabled ? 'success' : 'warning'"
          :label="twoFactorEnabled ? 'Enabled' : 'Disabled'"
          small
          class="mb-4"
        />
        <template #footer>
          <BaseButtons>
            <BaseButton
              v-if="!twoFactorEnabled"
              color="success"
              :icon="mdiShieldCheck"
              label="Enable 2FA"
              @click="startSetup"
            />
            <BaseButton v-else color="danger" outline label="Disable 2FA" @click="openDisableModal" />
          </BaseButtons>
        </template>
      </CardBox>

      <CardBox>
        <h3 class="mb-2 flex items-center gap-2 text-lg font-bold text-red-600 dark:text-red-400">
          <BaseIcon :path="mdiAlertOctagon" size="20" />
          Danger zone
        </h3>
        <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">
          Deleting your account is permanent. You must delete or transfer ownership of every
          family you own before you can delete your account.
        </p>
        <BaseButton color="danger" label="Delete my account" @click="openDeleteModal" />
      </CardBox>

      <CardBoxModal
        v-model="showDeleteModal"
        title="Delete your account?"
        button="danger"
        button-label="Delete permanently"
        has-cancel
        :is-processing="deleting"
        @confirm="confirmDelete"
      >
        <p class="mb-4 text-sm text-gray-600 dark:text-slate-300">
          This cannot be undone. Enter your password to confirm.
        </p>
        <FormField label="Password" :help="deleteError" :class="{ 'text-red-500': deleteError }">
          <FormControl v-model="deletePassword" type="password" placeholder="Your current password" />
        </FormField>
      </CardBoxModal>

      <CardBoxModal
        v-model="showSetupModal"
        title="Enable Two-Factor Authentication"
        button="success"
        :button-label="setupStep === 'qr' ? 'Next' : (setupBusy ? 'Verifying…' : 'Verify & Enable')"
        has-cancel
        is-form
        :is-processing="setupBusy"
        @confirm="setupStep === 'qr' ? (setupStep = 'confirm') : confirmSetup()"
      >
        <div v-if="setupStep === 'qr'">
          <p class="mb-4 text-sm text-gray-600 dark:text-slate-300">
            Scan this QR code with your authenticator app, or enter the code manually.
          </p>
          <img v-if="qrDataUrl" :src="qrDataUrl" alt="2FA QR code" class="mx-auto mb-4 h-48 w-48" />
          <p class="mb-2 text-center font-mono text-sm break-all">{{ secret }}</p>
        </div>
        <div v-else>
          <p class="mb-4 text-sm text-gray-600 dark:text-slate-300">
            Enter the 6-digit code your app is now showing.
          </p>
          <FormField label="Code" :help="setupError" :class="{ 'text-red-500': setupError }">
            <FormControl v-model="confirmCode" inputmode="numeric" autofocus />
          </FormField>
        </div>
      </CardBoxModal>

      <CardBoxModal
        v-model="showRecoveryCodesModal"
        title="Save your recovery codes"
        button="success"
        button-label="I've saved these"
        @confirm="showRecoveryCodesModal = false"
      >
        <p class="mb-4 text-sm text-gray-600 dark:text-slate-300">
          Store these somewhere safe. Each one can be used once to sign in if you lose access
          to your authenticator app. They won't be shown again.
        </p>
        <div class="grid grid-cols-2 gap-2 rounded-lg bg-gray-50 p-4 font-mono text-sm dark:bg-slate-700">
          <span v-for="code in recoveryCodes" :key="code">{{ code }}</span>
        </div>
      </CardBoxModal>

      <CardBoxModal
        v-model="showDisableModal"
        title="Disable Two-Factor Authentication?"
        button="danger"
        button-label="Disable"
        has-cancel
        :is-processing="disableBusy"
        @confirm="confirmDisable"
      >
        <p class="mb-4 text-sm text-gray-600 dark:text-slate-300">
          Your account will only need a password to sign in. Enter your password to confirm.
        </p>
        <FormField label="Password" :help="disableError" :class="{ 'text-red-500': disableError }">
          <FormControl v-model="disablePassword" type="password" placeholder="Your current password" />
        </FormField>
      </CardBoxModal>
    </SectionMain>
  </LayoutAuthenticated>
</template>
