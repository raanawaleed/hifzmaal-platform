<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  mdiChartTimelineVariant,
  mdiWallet,
  mdiTrendingUp,
  mdiTrendingDown,
  mdiClockOutline,
  mdiAccountGroup,
  mdiReceiptText,
  mdiSwapHorizontal,
  mdiPiggyBank,
  mdiChartPie,
  mdiHandCoin,
  mdiHomeGroup,
  mdiDownload,
} from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBoxWidget from '@/components/CardBoxWidget.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentTitle from '@/components/CardBoxComponentTitle.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import LineChart from '@/components/Charts/LineChart.vue'
import { useFamilyStore } from '@/stores/family'
import { useNotificationsStore } from '@/stores/notifications'
import { useFamilyApi } from '@/utils/familyApi'

const { t } = useI18n()
const familyStore = useFamilyStore()
const fapi = useFamilyApi()
const notifications = useNotificationsStore()

const data = ref(null)
const loading = ref(false)
const downloadingReport = ref(false)

const downloadMonthlyReport = async () => {
  downloadingReport.value = true
  try {
    const now = new Date()
    const month = now.getMonth() + 1
    const year = now.getFullYear()
    await fapi.download(`/reports/monthly?month=${month}&year=${year}`, `monthly-report-${year}-${month}.pdf`)
  } catch {
    notifications.error('Could not download the monthly report. Please try again.')
  } finally {
    downloadingReport.value = false
  }
}

const load = async () => {
  if (!familyStore.currentFamilyId) return
  loading.value = true
  try {
    const res = await fapi.get('/dashboard')
    data.value = res.data.data || {}
  } catch (err) {
    console.error('Dashboard load failed', err)
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => familyStore.currentFamilyId, load)

const fo = computed(() => data.value?.financial_overview || {})
const bo = computed(() => data.value?.budget_overview || {})
const so = computed(() => data.value?.savings_overview || {})
const netIncome = computed(() => (fo.value.monthly_income || 0) - (fo.value.monthly_expense || 0))

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())

const trend = computed(() => data.value?.monthly_trend || [])
const hasTrend = computed(() => trend.value.some((t) => t.income > 0 || t.expense > 0))

const chartData = computed(() => ({
  labels: trend.value.map((t) => t.month),
  datasets: [
    {
      label: 'Income',
      data: trend.value.map((t) => t.income),
      borderColor: '#10b981',
      backgroundColor: 'rgba(16,185,129,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 3,
    },
    {
      label: 'Expense',
      data: trend.value.map((t) => t.expense),
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239,68,68,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 3,
    },
  ],
}))
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <!-- No family: onboarding -->
      <template v-if="!familyStore.currentFamilyId">
        <SectionTitleLineWithButton :icon="mdiHomeGroup" title="Welcome to HifzMaal" main />
        <CardBox>
          <div class="py-8 text-center">
            <h2 class="mb-2 text-xl font-bold">Assalamu Alaikum! 👋</h2>
            <p class="mb-6 text-gray-500 dark:text-slate-400">
              Create your family to start tracking accounts, budgets, bills, savings and Zakat —
              together.
            </p>
            <BaseButton to="/families/create" color="success" label="Create your family" />
          </div>
        </CardBox>
      </template>

      <template v-else>
        <SectionTitleLineWithButton :icon="mdiChartTimelineVariant" title="Overview" main>
          <BaseButtons>
            <BaseButton
              :icon="mdiDownload"
              :label="downloadingReport ? 'Downloading…' : 'Monthly Report'"
              color="info"
              outline
              rounded-full
              small
              :disabled="downloadingReport"
              @click="downloadMonthlyReport"
            />
            <BaseButton
              to="/transactions/create"
              :icon="mdiSwapHorizontal"
              label="New Transaction"
              color="success"
              rounded-full
              small
            />
          </BaseButtons>
        </SectionTitleLineWithButton>

        <!-- Stat widgets -->
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-4">
          <CardBoxWidget
            color="text-emerald-500"
            :icon="mdiWallet"
            :number="Number(fo.total_balance || 0)"
            :suffix="` ${fo.currency || 'PKR'}`"
            label="Total Balance"
          />
          <CardBoxWidget
            :color="netIncome >= 0 ? 'text-blue-500' : 'text-red-500'"
            :icon="netIncome >= 0 ? mdiTrendingUp : mdiTrendingDown"
            :number="netIncome"
            label="Net This Month"
          />
          <CardBoxWidget
            color="text-amber-500"
            :icon="mdiClockOutline"
            :number="Number(data?.pending_approvals || 0)"
            label="Pending Approvals"
          />
          <CardBoxWidget
            color="text-violet-500"
            :icon="mdiAccountGroup"
            :number="Number(data?.family_members || 0)"
            label="Family Members"
          />
        </div>

        <!-- Budget + Savings -->
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
          <CardBox>
            <CardBoxComponentTitle title="Budget Usage">
              <PillTag
                :color="(bo.overall_percentage || 0) >= 100 ? 'danger' : (bo.overall_percentage || 0) >= 80 ? 'warning' : 'success'"
                :label="`${bo.overall_percentage || 0}%`"
                small
              />
            </CardBoxComponentTitle>
            <div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
              <div
                class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                :style="{ width: Math.min(bo.overall_percentage || 0, 100) + '%' }"
              />
            </div>
            <div class="mt-3 flex justify-between text-sm text-gray-500 dark:text-slate-400">
              <span>Spent: <b class="text-gray-800 dark:text-slate-200">{{ fmt(bo.total_spent) }}</b></span>
              <span>Remaining: <b class="text-gray-800 dark:text-slate-200">{{ fmt(bo.total_remaining) }}</b></span>
            </div>
            <div class="mt-4">
              <BaseButton to="/budgets" :icon="mdiChartPie" label="Manage budgets" color="whiteDark" small />
            </div>
          </CardBox>

          <CardBox>
            <CardBoxComponentTitle title="Savings Goals">
              <PillTag color="info" :label="`${so.overall_progress || 0}%`" small />
            </CardBoxComponentTitle>
            <div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
              <div
                class="h-full rounded-full bg-blue-500 transition-all duration-500"
                :style="{ width: Math.min(so.overall_progress || 0, 100) + '%' }"
              />
            </div>
            <div class="mt-3 flex justify-between text-sm text-gray-500 dark:text-slate-400">
              <span>Saved: <b class="text-gray-800 dark:text-slate-200">{{ fmt(so.total_saved) }}</b></span>
              <span>Target: <b class="text-gray-800 dark:text-slate-200">{{ fmt(so.total_target) }}</b></span>
            </div>
            <div class="mt-4">
              <BaseButton to="/savings-goals" :icon="mdiPiggyBank" label="View goals" color="whiteDark" small />
            </div>
          </CardBox>
        </div>

        <!-- Trend chart -->
        <SectionTitleLineWithButton :icon="mdiChartPie" title="Income vs Expense trend" />
        <CardBox class="mb-6">
          <div v-if="hasTrend">
            <LineChart :data="chartData" class="h-96" />
          </div>
          <CardBoxComponentEmpty v-else message="No transaction data yet — record transactions to see your trend" />
        </CardBox>

        <!-- Bills + Recent transactions -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <CardBox has-table>
            <CardBoxComponentTitle title="Upcoming Bills" class="px-6 pt-6">
              <BaseButton to="/bills" :icon="mdiReceiptText" color="whiteDark" small label="All bills" />
            </CardBoxComponentTitle>
            <table v-if="data?.bills?.upcoming?.length">
              <thead>
                <tr>
                  <th>Bill</th>
                  <th>Due</th>
                  <th>Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="bill in data.bills.upcoming.slice(0, 5)" :key="bill.id">
                  <td data-label="Bill">{{ bill.name }}</td>
                  <td data-label="Due">{{ bill.due_date }}</td>
                  <td data-label="Amount" class="font-semibold text-red-500">{{ fmt(bill.amount) }}</td>
                </tr>
              </tbody>
            </table>
            <CardBoxComponentEmpty v-else message="No upcoming bills" />
          </CardBox>

          <CardBox has-table>
            <CardBoxComponentTitle title="Recent Transactions" class="px-6 pt-6">
              <BaseButton to="/transactions" :icon="mdiSwapHorizontal" color="whiteDark" small label="All" />
            </CardBoxComponentTitle>
            <table v-if="data?.recent_transactions?.length">
              <thead>
                <tr>
                  <th>Description</th>
                  <th>Date</th>
                  <th>Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="tx in data.recent_transactions.slice(0, 5)" :key="tx.id">
                  <td data-label="Description">{{ tx.description || '—' }}</td>
                  <td data-label="Date">{{ tx.date }}</td>
                  <td
                    data-label="Amount"
                    class="font-semibold"
                    :class="tx.type === 'income' ? 'text-emerald-500' : 'text-red-500'"
                  >
                    {{ tx.type === 'income' ? '+' : '-' }}{{ fmt(tx.amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
            <CardBoxComponentEmpty v-else message="No transactions yet" />
          </CardBox>
        </div>

        <!-- Zakat banner -->
        <CardBox
          v-if="data?.zakat_status"
          class="mt-6 bg-linear-to-r from-emerald-600 to-teal-600 text-white"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-emerald-100">{{ t('dashboard.zakatStatus') }}</p>
              <p class="text-lg font-bold">
                {{ data.zakat_status.is_due ? t('dashboard.zakatIsDue') : t('dashboard.zakatCalculated') }}
              </p>
            </div>
            <BaseButton to="/zakat" :icon="mdiHandCoin" :label="t('dashboard.viewZakat')" color="whiteDark" small />
          </div>
        </CardBox>
      </template>
    </SectionMain>
  </LayoutAuthenticated>
</template>
