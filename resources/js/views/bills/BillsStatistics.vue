<script setup>
import { ref, onMounted } from 'vue'
import { mdiChartBar, mdiReceiptText, mdiAlertCircle, mdiCheckCircle, mdiCashClock } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBoxWidget from '@/components/CardBoxWidget.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import BaseButton from '@/components/BaseButton.vue'
import { useFamilyApi } from '@/utils/familyApi'

const fapi = useFamilyApi()
const stats = ref(null)

onMounted(async () => {
  if (!fapi.hasFamily()) return
  try {
    const res = await fapi.get('/bills/statistics')
    stats.value = res.data.data || res.data
  } catch (err) {
    console.error(err)
  }
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiChartBar" title="Bill Statistics" main>
        <BaseButton to="/bills" label="Back to bills" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <div v-if="stats" class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <CardBoxWidget
          color="text-blue-500"
          :icon="mdiReceiptText"
          :number="Number(stats.total_bills || 0)"
          label="Total Bills"
        />
        <CardBoxWidget
          color="text-emerald-500"
          :icon="mdiCheckCircle"
          :number="Number(stats.paid_this_month ?? stats.paid_count ?? 0)"
          label="Paid This Month"
        />
        <CardBoxWidget
          color="text-amber-500"
          :icon="mdiCashClock"
          :number="Number(stats.upcoming_count ?? 0)"
          label="Upcoming"
        />
        <CardBoxWidget
          color="text-red-500"
          :icon="mdiAlertCircle"
          :number="Number(stats.overdue_count ?? 0)"
          label="Overdue"
        />
      </div>

      <CardBox v-if="stats" class="mt-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div>
            <h3 class="text-sm text-gray-500 uppercase dark:text-slate-400">Monthly Total</h3>
            <p class="text-2xl font-bold">{{ Number(stats.monthly_total || 0).toLocaleString() }}</p>
          </div>
          <div>
            <h3 class="text-sm text-gray-500 uppercase dark:text-slate-400">Amount Overdue</h3>
            <p class="text-2xl font-bold text-red-500">
              {{ Number(stats.overdue_amount || 0).toLocaleString() }}
            </p>
          </div>
        </div>
      </CardBox>

      <CardBox v-else>
        <CardBoxComponentEmpty message="Loading statistics…" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
