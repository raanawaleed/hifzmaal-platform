<script setup>
import { computed, ref } from 'vue'
import { mdiAccount, mdiEmail, mdiAlertOctagon } from '@mdi/js'
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
    </SectionMain>
  </LayoutAuthenticated>
</template>
