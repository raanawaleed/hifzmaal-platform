<script setup>
import { ref, onMounted, computed } from 'vue'
import { mdiChartPie, mdiPlus, mdiPencil, mdiTrashCan } from '@mdi/js'
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
const overview = ref(null)
const loading = ref(false)
const deleteTarget = ref(null)

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    const [listRes, ovRes] = await Promise.all([
      fapi.get('/budgets'),
      fapi.get('/budgets/overview').catch(() => null),
    ])
    rows.value = items(listRes)
    overview.value = ovRes?.data?.data || null
  } finally {
    loading.value = false
  }
}

onMounted(load)

const confirmDelete = async () => {
  await fapi.delete(`/budgets/${deleteTarget.value.id}`)
  deleteTarget.value = null
  await load()
}

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())

const pct = (row) => {
  const p = row.percentage ?? row.percentage_used
  if (p != null) return Math.round(p)
  if (row.spent != null && row.amount) return Math.round((row.spent / row.amount) * 100)
  return 0
}

const barColor = (p) => (p >= 100 ? 'bg-red-500' : p >= 80 ? 'bg-amber-500' : 'bg-emerald-500')
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiChartPie" title="Budgets" main>
        <BaseButton to="/budgets/create" :icon="mdiPlus" label="New Budget" color="success" rounded-full small />
      </SectionTitleLineWithButton>

      <!-- Overview strip -->
      <CardBox v-if="overview" class="mb-6">
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <p class="text-xs text-gray-500 uppercase dark:text-slate-400">Total Budget</p>
            <p class="text-xl font-bold">{{ fmt(overview.total_budget) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase dark:text-slate-400">Spent</p>
            <p class="text-xl font-bold text-amber-500">{{ fmt(overview.total_spent) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase dark:text-slate-400">Remaining</p>
            <p class="text-xl font-bold text-emerald-500">{{ fmt(overview.total_remaining) }}</p>
          </div>
        </div>
      </CardBox>

      <CardBoxModal
        :model-value="!!deleteTarget"
        title="Delete budget?"
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
              <th>Budget</th>
              <th>Period</th>
              <th>Amount</th>
              <th style="min-width: 160px">Usage</th>
              <th>Status</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="Budget" class="font-medium">{{ row.name }}</td>
              <td data-label="Period">{{ row.period }}</td>
              <td data-label="Amount" class="font-semibold">{{ fmt(row.amount) }}</td>
              <td data-label="Usage">
                <div class="flex items-center gap-2">
                  <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
                    <div
                      class="h-full rounded-full transition-all"
                      :class="barColor(pct(row))"
                      :style="{ width: Math.min(pct(row), 100) + '%' }"
                    />
                  </div>
                  <span class="w-10 text-right text-xs font-semibold">{{ pct(row) }}%</span>
                </div>
              </td>
              <td data-label="Status">
                <PillTag
                  :color="row.is_active ? 'success' : 'warning'"
                  :label="row.is_active ? 'active' : 'inactive'"
                  small
                />
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiPencil" small :to="`/budgets/${row.id}/edit`" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteTarget = row" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No budgets yet — create one to control spending" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
