<script setup>
import { ref, onMounted, computed } from 'vue'
import { mdiAccountMultiple, mdiHomeGroup, mdiSwapHorizontal, mdiCashMultiple, mdiChartTimelineVariant } from '@mdi/js'
import api from '@/utils/api'
import LayoutAdmin from '@/layouts/LayoutAdmin.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBoxWidget from '@/components/CardBoxWidget.vue'
import CardBox from '@/components/CardBox.vue'
import LineChart from '@/components/Charts/LineChart.vue'

const stats = ref(null)
const loading = ref(true)

const load = async () => {
  loading.value = true
  try {
    const response = await api.get('/admin/dashboard')
    stats.value = response.data.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

const signupsChart = computed(() => {
  if (!stats.value) return null
  return {
    labels: stats.value.signups.map(s => s.month),
    datasets: [
      {
        label: 'Signups',
        data: stats.value.signups.map(s => s.count),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.15)',
        fill: true,
        tension: 0.4,
        pointRadius: 3,
      },
    ],
  }
})
</script>

<template>
  <LayoutAdmin>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiChartTimelineVariant" title="Platform Overview" main />

      <div v-if="stats" class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-4">
        <CardBoxWidget
          :icon="mdiAccountMultiple"
          color="text-emerald-500"
          :number="stats.totals.users"
          label="Users"
        />
        <CardBoxWidget
          :icon="mdiHomeGroup"
          color="text-blue-500"
          :number="stats.totals.families"
          label="Families"
        />
        <CardBoxWidget
          :icon="mdiSwapHorizontal"
          color="text-amber-500"
          :number="stats.totals.transactions"
          label="Transactions"
        />
        <CardBoxWidget
          :icon="mdiCashMultiple"
          color="text-purple-500"
          :number="stats.totals.transaction_volume"
          prefix="Rs. "
          label="Volume (approved)"
        />
      </div>

      <CardBox v-if="signupsChart" class="mb-6" has-table>
        <div class="p-4">
          <h3 class="mb-3 text-lg font-semibold">Signups — last 12 months</h3>
          <LineChart :data="signupsChart" class="h-72" />
        </div>
      </CardBox>

      <CardBox v-if="stats" has-table>
        <div class="p-4">
          <h3 class="mb-3 text-lg font-semibold">Suspended accounts: {{ stats.totals.suspended_users }}</h3>
          <table>
            <thead>
              <tr>
                <th>Newest users</th>
                <th>Email</th>
                <th>Joined</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in stats.recent_users" :key="u.id">
                <td data-label="Name">
                  <router-link :to="`/admin/users/${u.id}`" class="text-emerald-600 hover:underline dark:text-emerald-400">
                    {{ u.name }}
                  </router-link>
                </td>
                <td data-label="Email">{{ u.email }}</td>
                <td data-label="Joined">{{ new Date(u.created_at).toLocaleDateString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>
    </SectionMain>
  </LayoutAdmin>
</template>
