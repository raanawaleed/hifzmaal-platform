<script setup>
import { computed } from 'vue'
import { mdiAccount, mdiEmail } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import UserCard from '@/components/UserCard.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const name = computed(() => authStore.user?.name || '')
const email = computed(() => authStore.user?.email || '')
const logout = () => authStore.logout()
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccount" title="Profile" main />

      <UserCard class="mb-6" />

      <CardBox>
        <FormField label="Name" help="Your display name">
          <FormControl :model-value="name" :icon="mdiAccount" disabled />
        </FormField>
        <FormField label="Email" help="Your sign-in email">
          <FormControl :model-value="email" :icon="mdiEmail" type="email" disabled />
        </FormField>

        <template #footer>
          <BaseButtons>
            <BaseButton color="danger" outline label="Log Out" @click="logout" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
