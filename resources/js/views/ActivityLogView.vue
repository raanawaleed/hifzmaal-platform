<script setup>
import { ref, onMounted } from 'vue'
import { mdiHistory } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import PillTag from '@/components/PillTag.vue'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const rows = ref([])
const loading = ref(false)

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    rows.value = items(await fapi.get('/activity'))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const eventColor = { created: 'success', updated: 'info', deleted: 'danger' }

const fmtValue = (v) => {
  if (v == null) return '—'
  if (typeof v === 'boolean') return v ? 'Yes' : 'No'
  return String(v)
}

const changeSummary = (changes) => {
  if (!changes?.attributes) return null
  const old = changes.old || {}
  return Object.entries(changes.attributes).map(([field, value]) => ({
    field,
    from: fmtValue(old[field]),
    to: fmtValue(value),
  }))
}

const fmtDate = (iso) => new Date(iso).toLocaleString()
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiHistory" title="Activity Log" main />

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>When</th>
              <th>Who</th>
              <th>What</th>
              <th>Event</th>
              <th>Changes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="When" class="whitespace-nowrap">{{ fmtDate(row.created_at) }}</td>
              <td data-label="Who">{{ row.causer?.name || 'System' }}</td>
              <td data-label="What">{{ row.subject_type }} #{{ row.subject_id }}</td>
              <td data-label="Event">
                <PillTag :color="eventColor[row.event] || 'info'" :label="row.event" small />
              </td>
              <td data-label="Changes">
                <ul v-if="changeSummary(row.changes)" class="space-y-0.5 text-xs">
                  <li v-for="c in changeSummary(row.changes)" :key="c.field">
                    <b>{{ c.field }}</b>: {{ c.from }} → {{ c.to }}
                  </li>
                </ul>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No activity recorded yet" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
