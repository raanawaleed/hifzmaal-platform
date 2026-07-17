<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { mdiHandCoin, mdiPlus, mdiEye, mdiAccountHeart } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import { useFamilyStore } from '@/stores/family'
import { useFamilyApi, items } from '@/utils/familyApi'

const { t, locale } = useI18n()
const fapi = useFamilyApi()
const familyStore = useFamilyStore()
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

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString(locale.value))
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiHandCoin" :title="t('zakat.listTitle')" main>
        <BaseButtons>
          <BaseButton to="/zakat/recipients" :icon="mdiAccountHeart" :label="t('zakat.recipients')" color="whiteDark" rounded-full small />
          <BaseButton v-if="familyStore.canEdit" to="/zakat/create" :icon="mdiPlus" :label="t('zakat.newCalculation')" color="success" rounded-full small />
        </BaseButtons>
      </SectionTitleLineWithButton>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>{{ t('zakat.hijriYear') }}</th>
              <th>{{ t('zakat.netWealth') }}</th>
              <th>{{ t('zakat.nisab') }}</th>
              <th>{{ t('zakat.zakatDue') }}</th>
              <th>{{ t('zakat.paid') }}</th>
              <th>{{ t('zakat.status') }}</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td :data-label="t('zakat.hijriYear')" class="font-medium">
                {{ t('zakat.hijriYearValue', { year: row.hijri_year }) }}
              </td>
              <td :data-label="t('zakat.netWealth')">{{ fmt(row.net_wealth ?? row.total_wealth) }}</td>
              <td :data-label="t('zakat.nisab')">{{ fmt(row.nisab_amount) }} ({{ t(`zakat.metal.${row.nisab_type}`) }})</td>
              <td :data-label="t('zakat.zakatDue')" class="font-semibold text-emerald-600 dark:text-emerald-400">
                {{ fmt(row.zakat_amount) }}
              </td>
              <td :data-label="t('zakat.paid')">{{ fmt(row.total_paid ?? row.paid_amount) }}</td>
              <td :data-label="t('zakat.status')">
                <PillTag
                  :color="row.is_paid ? 'success' : row.is_zakat_due ?? row.zakat_amount > 0 ? 'warning' : 'info'"
                  :label="row.is_paid ? t('zakat.statusPaid') : (row.is_zakat_due ?? row.zakat_amount > 0) ? t('zakat.statusDue') : t('zakat.statusBelowNisab')"
                  small
                />
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiEye" small :label="t('zakat.detail')" :to="`/zakat/${row.id}`" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" :message="t('zakat.emptyList')" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
