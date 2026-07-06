<script setup>
import { ref, onMounted } from 'vue'
import { mdiPiggyBank, mdiPlus, mdiPencil, mdiTrashCan, mdiCashPlus } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import PillTag from '@/components/PillTag.vue'
import NotificationBar from '@/components/NotificationBar.vue'
import { useFamilyStore } from '@/stores/family'
import { useFamilyApi, items, extractErrors } from '@/utils/familyApi'

const fapi = useFamilyApi()
const familyStore = useFamilyStore()
const rows = ref([])
const loading = ref(false)
const deleteTarget = ref(null)
const contributeTarget = ref(null)
const contributeAmount = ref('')
const contributeBusy = ref(false)
const notice = ref(null)

const typeLabels = {
  hajj: 'Hajj 🕋',
  umrah: 'Umrah',
  education: 'Education',
  marriage: 'Marriage',
  emergency: 'Emergency',
  business: 'Business',
  other: 'Other',
}

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    rows.value = items(await fapi.get('/savings-goals'))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const confirmDelete = async () => {
  await fapi.delete(`/savings-goals/${deleteTarget.value.id}`)
  deleteTarget.value = null
  await load()
}

const submitContribution = async () => {
  if (!contributeAmount.value || contributeBusy.value) return
  contributeBusy.value = true
  try {
    await fapi.post(`/savings-goals/${contributeTarget.value.id}/contribute`, {
      amount: Number(contributeAmount.value),
    })
    notice.value = { color: 'success', text: `Contribution added to ${contributeTarget.value.name}.` }
    contributeTarget.value = null
    contributeAmount.value = ''
    await load()
  } catch (err) {
    notice.value = { color: 'danger', text: extractErrors(err)._message }
  } finally {
    contributeBusy.value = false
  }
}

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())
const pct = (row) => {
  const p = row.progress ?? row.progress_percentage
  if (p != null) return Math.round(p)
  if (row.current_amount != null && row.target_amount) {
    return Math.round((row.current_amount / row.target_amount) * 100)
  }
  return 0
}
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiPiggyBank" title="Savings Goals" main>
        <BaseButton v-if="familyStore.canEdit" to="/savings-goals/create" :icon="mdiPlus" label="New Goal" color="success" rounded-full small />
      </SectionTitleLineWithButton>

      <NotificationBar v-if="notice" :color="notice.color" @dismiss="notice = null">
        {{ notice.text }}
      </NotificationBar>

      <!-- Delete modal -->
      <CardBoxModal
        :model-value="!!deleteTarget"
        title="Delete goal?"
        button="danger"
        button-label="Delete"
        has-cancel
        @update:model-value="deleteTarget = null"
        @confirm="confirmDelete"
      >
        <p>Delete <b>{{ deleteTarget?.name }}</b>?</p>
      </CardBoxModal>

      <!-- Contribute modal -->
      <CardBoxModal
        :model-value="!!contributeTarget"
        :title="`Contribute to ${contributeTarget?.name || ''}`"
        button="success"
        button-label="Contribute"
        has-cancel
        is-form
        :is-processing="contributeBusy"
        @update:model-value="contributeTarget = null"
        @confirm="submitContribution"
        @cancel="contributeAmount = ''"
      >
        <FormField label="Amount">
          <FormControl v-model="contributeAmount" type="number" inputmode="decimal" required />
        </FormField>
        <p class="text-sm text-gray-500 dark:text-slate-400">
          Saved so far: <b>{{ fmt(contributeTarget?.current_amount) }}</b> of
          <b>{{ fmt(contributeTarget?.target_amount) }}</b>
        </p>
      </CardBoxModal>

      <!-- Goal cards -->
      <div v-if="rows.length" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        <CardBox v-for="row in rows" :key="row.id">
          <div class="mb-3 flex items-start justify-between">
            <div>
              <h3 class="text-lg font-semibold">{{ row.name }}</h3>
              <PillTag color="info" :label="typeLabels[row.type] || row.type" small />
            </div>
            <span class="text-2xl font-bold" :class="pct(row) >= 100 ? 'text-emerald-500' : ''">
              {{ pct(row) }}%
            </span>
          </div>

          <div class="mb-3 h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="pct(row) >= 100 ? 'bg-emerald-500' : 'bg-blue-500'"
              :style="{ width: Math.min(pct(row), 100) + '%' }"
            />
          </div>

          <div class="mb-4 flex justify-between text-sm text-gray-500 dark:text-slate-400">
            <span>Saved: <b class="text-gray-800 dark:text-slate-200">{{ fmt(row.current_amount) }}</b></span>
            <span>Target: <b class="text-gray-800 dark:text-slate-200">{{ fmt(row.target_amount) }}</b></span>
          </div>

          <BaseButtons>
            <BaseButton
              color="success"
              :icon="mdiCashPlus"
              label="Contribute"
              small
              @click="contributeTarget = row"
            />
            <BaseButton color="info" :icon="mdiPencil" small :to="`/savings-goals/${row.id}/edit`" />
            <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteTarget = row" />
          </BaseButtons>
        </CardBox>
      </div>

      <CardBox v-else-if="!loading">
        <CardBoxComponentEmpty message="No savings goals — start saving for Hajj, education or emergencies" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
