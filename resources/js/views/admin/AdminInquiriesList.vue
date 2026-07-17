<script setup>
import { ref, onMounted } from 'vue'
import { mdiEmailOutline, mdiMagnify } from '@mdi/js'
import api from '@/utils/api'
import LayoutAdmin from '@/layouts/LayoutAdmin.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import BaseButton from '@/components/BaseButton.vue'
import FormControl from '@/components/FormControl.vue'
import PillTag from '@/components/PillTag.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'

const statusMeta = {
  new: { color: 'info', label: 'New' },
  in_progress: { color: 'warning', label: 'In progress' },
  resolved: { color: 'success', label: 'Resolved' },
  closed: { color: 'light', label: 'Closed' },
}

const statusOptions = [
  { id: '', label: 'All statuses' },
  { id: 'new', label: 'New' },
  { id: 'in_progress', label: 'In progress' },
  { id: 'resolved', label: 'Resolved' },
  { id: 'closed', label: 'Closed' },
]

const rows = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const status = ref('')
const loading = ref(false)

const load = async () => {
  loading.value = true
  try {
    const response = await api.get('/admin/contact-messages', {
      params: {
        search: search.value || undefined,
        status: status.value || undefined,
        page: page.value,
      },
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

const changeStatusFilter = () => {
  page.value = 1
  load()
}

const changePage = (delta) => {
  page.value += delta
  load()
}
</script>

<template>
  <LayoutAdmin>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiEmailOutline" title="Inquiries" main />

      <CardBox class="mb-6">
        <form class="flex flex-col items-stretch gap-2 p-4 sm:flex-row sm:items-center" @submit.prevent="doSearch">
          <FormControl v-model="search" :icon="mdiMagnify" placeholder="Search name, email or subject…" class="grow" />
          <FormControl v-model="status" :options="statusOptions" class="sm:w-48" @change="changeStatusFilter" />
          <BaseButton type="submit" color="info" label="Search" />
        </form>
      </CardBox>

      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>Submitter</th>
              <th>Subject</th>
              <th>Status</th>
              <th>Replies</th>
              <th>Received</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inquiry in rows" :key="inquiry.id">
              <td data-label="Submitter">
                <router-link :to="`/admin/inquiries/${inquiry.id}`" class="text-emerald-600 hover:underline dark:text-emerald-400">
                  {{ inquiry.name }}
                </router-link>
                <div class="text-sm text-gray-500">{{ inquiry.email }}</div>
              </td>
              <td data-label="Subject">{{ inquiry.subject }}</td>
              <td data-label="Status">
                <PillTag
                  :color="statusMeta[inquiry.status]?.color || 'light'"
                  :label="statusMeta[inquiry.status]?.label || inquiry.status"
                  small
                />
              </td>
              <td data-label="Replies">{{ inquiry.replies_count }}</td>
              <td data-label="Received">{{ new Date(inquiry.created_at).toLocaleDateString() }}</td>
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
    </SectionMain>
  </LayoutAdmin>
</template>
