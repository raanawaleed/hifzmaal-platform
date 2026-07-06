<script setup>
import { ref, onMounted } from 'vue'
import { mdiHomeGroup, mdiMagnify } from '@mdi/js'
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
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'

const notifications = useNotificationsStore()

const rows = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const loading = ref(false)
const deleteTarget = ref(null)

const load = async () => {
  loading.value = true
  try {
    const response = await api.get('/admin/families', {
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

const confirmDelete = async () => {
  const family = deleteTarget.value
  deleteTarget.value = null
  const response = await api.delete(`/admin/families/${family.id}`)
  notifications.success(response.data.message)
  await load()
}
</script>

<template>
  <LayoutAdmin>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiHomeGroup" title="Families" main />

      <CardBox class="mb-6">
        <form class="flex items-center gap-2 p-4" @submit.prevent="doSearch">
          <FormControl v-model="search" :icon="mdiMagnify" placeholder="Search family name…" class="grow" />
          <BaseButton type="submit" color="info" label="Search" />
        </form>
      </CardBox>

      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Owner</th>
              <th>Members</th>
              <th>Accounts</th>
              <th>Transactions</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="family in rows" :key="family.id">
              <td data-label="Name">{{ family.name }}</td>
              <td data-label="Owner">{{ family.owner?.name }} ({{ family.owner?.email }})</td>
              <td data-label="Members">{{ family.members_count }}</td>
              <td data-label="Accounts">{{ family.accounts_count }}</td>
              <td data-label="Transactions">{{ family.transactions_count }}</td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="danger" label="Delete" small @click="deleteTarget = family" />
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
        :model-value="!!deleteTarget"
        title="Delete family?"
        button="danger"
        has-cancel
        @update:model-value="deleteTarget = null"
        @confirm="confirmDelete"
      >
        <p>
          <b>{{ deleteTarget?.name }}</b> will be soft-deleted along with access to all of its
          financial data. This is meant for abuse/moderation cases.
        </p>
      </CardBoxModal>
    </SectionMain>
  </LayoutAdmin>
</template>
