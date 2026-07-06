<script setup>
import { ref, onMounted } from 'vue'
import { mdiAccountMultiple, mdiMagnify } from '@mdi/js'
import api from '@/utils/api'
import { useNotificationsStore } from '@/stores/notifications'
import LayoutAdmin from '@/layouts/LayoutAdmin.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormControl from '@/components/FormControl.vue'
import PillTag from '@/components/PillTag.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'

const notifications = useNotificationsStore()

const rows = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const loading = ref(false)
const suspendTarget = ref(null)

const load = async () => {
  loading.value = true
  try {
    const response = await api.get('/admin/users', {
      params: { search: search.value || undefined, page: page.value },
    })
    rows.value = response.data.data
    meta.value = { current_page: response.data.current_page, last_page: response.data.last_page }
  } finally {
    loading.value = false
  }
}

onMounted(load)

const doSearch = () => {
  page.value = 1
  load()
}

const changePage = (delta) => {
  page.value += delta
  load()
}

const confirmSuspend = async () => {
  const user = suspendTarget.value
  suspendTarget.value = null
  try {
    const action = user.is_active ? 'suspend' : 'unsuspend'
    const response = await api.post(`/admin/users/${user.id}/${action}`)
    notifications.success(response.data.message)
    await load()
  } catch (err) {
    if (err.response?.status === 422) {
      notifications.error(err.response.data.message)
    }
  }
}
</script>

<template>
  <LayoutAdmin>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccountMultiple" title="Users" main />

      <CardBox class="mb-6">
        <form class="flex items-center gap-2 p-4" @submit.prevent="doSearch">
          <FormControl v-model="search" :icon="mdiMagnify" placeholder="Search name or email…" class="grow" />
          <BaseButton type="submit" color="info" label="Search" />
        </form>
      </CardBox>

      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Families</th>
              <th>Status</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in rows" :key="user.id">
              <td data-label="Name">
                <router-link :to="`/admin/users/${user.id}`" class="text-emerald-600 hover:underline dark:text-emerald-400">
                  {{ user.name }}
                </router-link>
              </td>
              <td data-label="Email">{{ user.email }}</td>
              <td data-label="Families">
                {{ user.owned_families_count }} owned · {{ user.family_memberships_count }} memberships
              </td>
              <td data-label="Status">
                <PillTag :color="user.is_active ? 'success' : 'danger'" :label="user.is_active ? 'Active' : 'Suspended'" small />
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    :color="user.is_active ? 'danger' : 'success'"
                    :label="user.is_active ? 'Suspend' : 'Reactivate'"
                    small
                    @click="suspendTarget = user"
                  />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-if="!rows.length && !loading" />
        <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between border-t border-gray-100 p-3 dark:border-slate-700">
          <BaseButton :disabled="meta.current_page <= 1" label="Previous" small @click="changePage(-1)" />
          <span class="text-sm">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
          <BaseButton :disabled="meta.current_page >= meta.last_page" label="Next" small @click="changePage(1)" />
        </div>
      </CardBox>

      <CardBoxModal
        :model-value="!!suspendTarget"
        :title="suspendTarget?.is_active ? 'Suspend user?' : 'Reactivate user?'"
        button="danger"
        has-cancel
        @update:model-value="suspendTarget = null"
        @confirm="confirmSuspend"
      >
        <p v-if="suspendTarget?.is_active">
          <b>{{ suspendTarget?.email }}</b> will be logged out everywhere and blocked from signing in.
        </p>
        <p v-else>
          <b>{{ suspendTarget?.email }}</b> will be able to sign in again.
        </p>
      </CardBoxModal>
    </SectionMain>
  </LayoutAdmin>
</template>
