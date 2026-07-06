<script setup>
import { ref, onMounted } from 'vue'
import { mdiReceiptText, mdiPlus, mdiPencil, mdiTrashCan, mdiCheck, mdiChartBar } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import NotificationBar from '@/components/NotificationBar.vue'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const rows = ref([])
const loading = ref(false)
const deleteTarget = ref(null)
const notice = ref(null)
const view = ref('all') // all | upcoming | overdue

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    const path = view.value === 'all' ? '/bills' : `/bills/${view.value}`
    rows.value = items(await fapi.get(path))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const setView = (v) => {
  view.value = v
  load()
}

const confirmDelete = async () => {
  await fapi.delete(`/bills/${deleteTarget.value.id}`)
  deleteTarget.value = null
  await load()
}

const markPaid = async (row) => {
  try {
    await fapi.post(`/bills/${row.id}/mark-as-paid`)
    notice.value = { color: 'success', text: `${row.name} marked as paid.` }
    await load()
  } catch (err) {
    notice.value = { color: 'danger', text: err.response?.data?.message || 'Failed to mark as paid.' }
  }
}

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())
const statusColor = { paid: 'success', pending: 'warning', overdue: 'danger' }
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiReceiptText" title="Bills" main>
        <BaseButtons>
          <BaseButton to="/bills/statistics" :icon="mdiChartBar" label="Statistics" color="whiteDark" rounded-full small />
          <BaseButton to="/bills/create" :icon="mdiPlus" label="New Bill" color="success" rounded-full small />
        </BaseButtons>
      </SectionTitleLineWithButton>

      <NotificationBar v-if="notice" :color="notice.color" @dismiss="notice = null">
        {{ notice.text }}
      </NotificationBar>

      <div class="mb-4 flex gap-2">
        <BaseButton label="All" small :color="view === 'all' ? 'info' : 'whiteDark'" @click="setView('all')" />
        <BaseButton label="Upcoming" small :color="view === 'upcoming' ? 'info' : 'whiteDark'" @click="setView('upcoming')" />
        <BaseButton label="Overdue" small :color="view === 'overdue' ? 'danger' : 'whiteDark'" @click="setView('overdue')" />
      </div>

      <CardBoxModal
        :model-value="!!deleteTarget"
        title="Delete bill?"
        button="danger"
        button-label="Delete"
        has-cancel
        @update:model-value="deleteTarget = null"
        @confirm="confirmDelete"
      >
        <p>Delete <b>{{ deleteTarget?.name }}</b>?</p>
      </CardBoxModal>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>Bill</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Due Date</th>
              <th>Status</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="Bill" class="font-medium">{{ row.name }}</td>
              <td data-label="Type">{{ row.type }}</td>
              <td data-label="Amount" class="font-semibold">{{ fmt(row.amount) }}</td>
              <td data-label="Due Date">{{ row.due_date }}</td>
              <td data-label="Status">
                <PillTag :color="statusColor[row.status] || 'info'" :label="row.status || '—'" small />
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    v-if="row.status !== 'paid'"
                    color="success"
                    :icon="mdiCheck"
                    small
                    title="Mark as paid"
                    @click="markPaid(row)"
                  />
                  <BaseButton color="info" :icon="mdiPencil" small :to="`/bills/${row.id}/edit`" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteTarget = row" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No bills found" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
