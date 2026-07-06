<script setup>
import { ref, onMounted } from 'vue'
import { mdiSwapHorizontal, mdiPlus, mdiPencil, mdiTrashCan } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const rows = ref([])
const loading = ref(false)
const deleteTarget = ref(null)
const filterType = ref('')

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    const q = filterType.value ? `?type=${filterType.value}` : ''
    rows.value = items(await fapi.get(`/transactions${q}`))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const confirmDelete = async () => {
  await fapi.delete(`/transactions/${deleteTarget.value.id}`)
  deleteTarget.value = null
  await load()
}

const setFilter = (t) => {
  filterType.value = filterType.value === t ? '' : t
  load()
}

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())
const statusColor = { approved: 'success', pending: 'warning', rejected: 'danger' }
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiSwapHorizontal" title="Transactions" main>
        <BaseButton to="/transactions/create" :icon="mdiPlus" label="New Transaction" color="success" rounded-full small />
      </SectionTitleLineWithButton>

      <div class="mb-4 flex gap-2">
        <BaseButton
          label="Income"
          small
          :color="filterType === 'income' ? 'success' : 'whiteDark'"
          @click="setFilter('income')"
        />
        <BaseButton
          label="Expense"
          small
          :color="filterType === 'expense' ? 'danger' : 'whiteDark'"
          @click="setFilter('expense')"
        />
        <BaseButton
          label="Transfer"
          small
          :color="filterType === 'transfer' ? 'info' : 'whiteDark'"
          @click="setFilter('transfer')"
        />
      </div>

      <CardBoxModal
        :model-value="!!deleteTarget"
        title="Delete transaction?"
        button="danger"
        button-label="Delete"
        has-cancel
        @update:model-value="deleteTarget = null"
        @confirm="confirmDelete"
      >
        <p>Delete this {{ deleteTarget?.type }} of <b>{{ fmt(deleteTarget?.amount) }}</b>?</p>
      </CardBoxModal>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>Date</th>
              <th>Description</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Status</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="Date">{{ row.date }}</td>
              <td data-label="Description" class="font-medium">{{ row.description || '—' }}</td>
              <td data-label="Type">
                <PillTag
                  :color="row.type === 'income' ? 'success' : row.type === 'expense' ? 'danger' : 'info'"
                  :label="row.type"
                  small
                />
              </td>
              <td
                data-label="Amount"
                class="font-semibold"
                :class="row.type === 'income' ? 'text-emerald-500' : 'text-red-500'"
              >
                {{ row.type === 'income' ? '+' : '-' }}{{ fmt(row.amount) }}
              </td>
              <td data-label="Status">
                <PillTag :color="statusColor[row.status] || 'info'" :label="row.status" small />
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiPencil" small :to="`/transactions/${row.id}/edit`" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteTarget = row" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No transactions found" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
