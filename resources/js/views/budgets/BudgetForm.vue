<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiChartPie } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import FormCheckRadio from '@/components/FormCheckRadio.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import { useFamilyApi, items, record, extractErrors } from '@/utils/familyApi'

const props = defineProps({ id: { type: String, default: null } })
const isEdit = computed(() => !!props.id)

const fapi = useFamilyApi()
const router = useRouter()

const today = new Date()
const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10)
const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().slice(0, 10)

const form = reactive({
  name: '',
  category_id: '',
  amount: '',
  period: 'monthly',
  start_date: firstOfMonth,
  end_date: endOfMonth,
  alert_threshold: 80,
  is_active: true,
})

const periods = [
  { id: 'weekly', label: 'Weekly' },
  { id: 'monthly', label: 'Monthly' },
  { id: 'yearly', label: 'Yearly' },
]

const categories = ref([])
const loading = ref(false)
const errors = ref({})

const categoryOptions = computed(() =>
  categories.value.filter((c) => c.type === 'expense').map((c) => ({ id: c.id, label: c.name })),
)

onMounted(async () => {
  if (!fapi.hasFamily()) return
  categories.value = items(await fapi.get('/categories'))

  if (isEdit.value) {
    const b = record(await fapi.get(`/budgets/${props.id}`))
    Object.keys(form).forEach((k) => {
      if (b[k] !== undefined && b[k] !== null) form[k] = b[k]
    })
    if (b.category?.id) form.category_id = b.category.id
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await fapi.put(`/budgets/${props.id}`, form)
    } else {
      await fapi.post('/budgets', form)
    }
    router.push('/budgets')
  } catch (err) {
    errors.value = extractErrors(err)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiChartPie"
        :title="isEdit ? 'Edit Budget' : 'New Budget'"
        main
      >
        <BaseButton to="/budgets" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Budget Name" :help="errors.name || 'e.g. Monthly Groceries'">
            <FormControl v-model="form.name" required />
          </FormField>
          <FormField label="Category" :help="errors.category_id">
            <FormControl v-model="form.category_id" :options="categoryOptions" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Amount" :help="errors.amount">
            <FormControl v-model="form.amount" type="number" inputmode="decimal" required />
          </FormField>
          <FormField label="Period" :help="errors.period">
            <FormControl v-model="form.period" :options="periods" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <FormField label="Start Date" :help="errors.start_date">
            <FormControl v-model="form.start_date" type="date" required />
          </FormField>
          <FormField label="End Date" :help="errors.end_date">
            <FormControl v-model="form.end_date" type="date" required />
          </FormField>
          <FormField label="Alert Threshold (%)" :help="errors.alert_threshold || 'Alert when usage reaches this %'">
            <FormControl v-model="form.alert_threshold" type="number" />
          </FormField>
        </div>

        <FormCheckRadio
          v-model="form.is_active"
          name="is_active"
          type="switch"
          label="Active"
          :input-value="true"
        />

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Saving…' : isEdit ? 'Update Budget' : 'Create Budget'"
              :disabled="loading"
            />
            <BaseButton to="/budgets" color="whiteDark" outline label="Cancel" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
