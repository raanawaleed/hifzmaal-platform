<script setup>
import { ref, onMounted } from 'vue'
import { mdiHandCoin, mdiPlus, mdiEye, mdiAccountHeart } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const rows = ref([])
const loading = ref(false)

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    rows.value = items(await fapi.get('/zakat'))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiHandCoin" title="Zakat Calculations" main>
        <BaseButtons>
          <BaseButton to="/zakat/recipients" :icon="mdiAccountHeart" label="Recipients" color="whiteDark" rounded-full small />
          <BaseButton to="/zakat/create" :icon="mdiPlus" label="New Calculation" color="success" rounded-full small />
        </BaseButtons>
      </SectionTitleLineWithButton>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>Hijri Year</th>
              <th>Net Wealth</th>
              <th>Nisab</th>
              <th>Zakat Due</th>
              <th>Paid</th>
              <th>Status</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="Hijri Year" class="font-medium">{{ row.hijri_year }} AH</td>
              <td data-label="Net Wealth">{{ fmt(row.net_wealth ?? row.total_wealth) }}</td>
              <td data-label="Nisab">{{ fmt(row.nisab_amount) }} ({{ row.nisab_type }})</td>
              <td data-label="Zakat Due" class="font-semibold text-emerald-600 dark:text-emerald-400">
                {{ fmt(row.zakat_amount) }}
              </td>
              <td data-label="Paid">{{ fmt(row.total_paid ?? row.paid_amount) }}</td>
              <td data-label="Status">
                <PillTag
                  :color="row.is_paid ? 'success' : row.is_zakat_due ?? row.zakat_amount > 0 ? 'warning' : 'info'"
                  :label="row.is_paid ? 'paid' : (row.is_zakat_due ?? row.zakat_amount > 0) ? 'due' : 'below nisab'"
                  small
                />
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiEye" small label="Detail" :to="`/zakat/${row.id}`" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty
          v-else-if="!loading"
          message="No Zakat calculations yet — calculate your first Zakat"
        />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
