<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiReceiptText } from '@mdi/js'
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

const form = reactive({
  name: '',
  type: 'electricity',
  category_id: '',
  amount: '',
  due_date: '',
  frequency: 'monthly',
  is_recurring: true,
  auto_pay: false,
  account_id: '',
  provider: '',
  account_number: '',
  reminder_days: 3,
})

const types = [
  { id: 'electricity', label: 'Electricity' },
  { id: 'gas', label: 'Gas' },
  { id: 'water', label: 'Water' },
  { id: 'internet', label: 'Internet' },
  { id: 'mobile', label: 'Mobile' },
  { id: 'rent', label: 'Rent' },
  { id: 'school_fees', label: 'School Fees' },
  { id: 'other', label: 'Other' },
]
const frequencies = [
  { id: 'monthly', label: 'Monthly' },
  { id: 'quarterly', label: 'Quarterly' },
  { id: 'yearly', label: 'Yearly' },
]

const categories = ref([])
const accounts = ref([])
const loading = ref(false)
const errors = ref({})

const categoryOptions = computed(() =>
  categories.value.filter((c) => c.type === 'expense').map((c) => ({ id: c.id, label: c.name })),
)
const accountOptions = computed(() => [
  { id: '', label: '— None —' },
  ...accounts.value.map((a) => ({ id: a.id, label: a.name })),
])

onMounted(async () => {
  if (!fapi.hasFamily()) return
  const [catRes, accRes] = await Promise.all([fapi.get('/categories'), fapi.get('/accounts')])
  categories.value = items(catRes)
  accounts.value = items(accRes)

  if (isEdit.value) {
    const bill = record(await fapi.get(`/bills/${props.id}`))
    Object.keys(form).forEach((k) => {
      if (bill[k] !== undefined && bill[k] !== null) form[k] = bill[k]
    })
    if (bill.category?.id) form.category_id = bill.category.id
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}
  const payload = { ...form }
  if (!payload.account_id) delete payload.account_id
  try {
    if (isEdit.value) {
      await fapi.put(`/bills/${props.id}`, payload)
    } else {
      await fapi.post('/bills', payload)
    }
    router.push('/bills')
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
        :icon="mdiReceiptText"
        :title="isEdit ? 'Edit Bill' : 'New Bill'"
        main
      >
        <BaseButton to="/bills" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Bill Name" :help="errors.name || 'e.g. K-Electric, PTCL'">
            <FormControl v-model="form.name" required />
          </FormField>
          <FormField label="Type" :help="errors.type">
            <FormControl v-model="form.type" :options="types" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <FormField label="Amount" :help="errors.amount">
            <FormControl v-model="form.amount" type="number" inputmode="decimal" required />
          </FormField>
          <FormField label="Due Date" :help="errors.due_date">
            <FormControl v-model="form.due_date" type="date" required />
          </FormField>
          <FormField label="Frequency" :help="errors.frequency">
            <FormControl v-model="form.frequency" :options="frequencies" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Category" :help="errors.category_id">
            <FormControl v-model="form.category_id" :options="categoryOptions" />
          </FormField>
          <FormField label="Pay From Account" :help="errors.account_id || 'Optional'">
            <FormControl v-model="form.account_id" :options="accountOptions" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <FormField label="Provider" :help="errors.provider || 'Optional'">
            <FormControl v-model="form.provider" />
          </FormField>
          <FormField label="Consumer / Account #" :help="errors.account_number || 'Optional'">
            <FormControl v-model="form.account_number" />
          </FormField>
          <FormField label="Reminder (days before)" :help="errors.reminder_days">
            <FormControl v-model="form.reminder_days" type="number" />
          </FormField>
        </div>

        <div class="flex gap-6">
          <FormCheckRadio
            v-model="form.is_recurring"
            name="is_recurring"
            type="switch"
            label="Recurring bill"
            :input-value="true"
          />
          <FormCheckRadio
            v-model="form.auto_pay"
            name="auto_pay"
            type="switch"
            label="Auto-pay"
            :input-value="true"
          />
        </div>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Saving…' : isEdit ? 'Update Bill' : 'Create Bill'"
              :disabled="loading"
            />
            <BaseButton to="/bills" color="whiteDark" outline label="Cancel" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
