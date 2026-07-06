<script setup>
import { ref, onMounted } from 'vue'
import { mdiClockOutline, mdiCheck, mdiClose } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import NotificationBar from '@/components/NotificationBar.vue'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const rows = ref([])
const loading = ref(false)
const notice = ref(null)

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    rows.value = items(await fapi.get('/transactions/pending'))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const act = async (row, action) => {
  try {
    await fapi.post(`/transactions/${row.id}/${action}`)
    notice.value = { color: 'success', text: `Transaction ${action}d.` }
    await load()
  } catch (err) {
    notice.value = {
      color: 'danger',
      text: err.response?.data?.message || `Could not ${action} transaction.`,
    }
  }
}

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiClockOutline" title="Pending Approvals" main />

      <NotificationBar v-if="notice" :color="notice.color" @dismiss="notice = null">
        {{ notice.text }}
      </NotificationBar>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>Date</th>
              <th>Description</th>
              <th>Type</th>
              <th>Amount</th>
              <th>By</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="Date">{{ row.date }}</td>
              <td data-label="Description" class="font-medium">{{ row.description || '—' }}</td>
              <td data-label="Type">
                <PillTag :color="row.type === 'income' ? 'success' : 'danger'" :label="row.type" small />
              </td>
              <td data-label="Amount" class="font-semibold">{{ fmt(row.amount) }}</td>
              <td data-label="By">{{ row.created_by?.name || '—' }}</td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="success" :icon="mdiCheck" small label="Approve" @click="act(row, 'approve')" />
                  <BaseButton color="danger" :icon="mdiClose" small label="Reject" @click="act(row, 'reject')" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No transactions awaiting approval 🎉" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
