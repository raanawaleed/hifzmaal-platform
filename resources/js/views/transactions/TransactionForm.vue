<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiSwapHorizontal } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import { useFamilyApi, items, record, extractErrors } from '@/utils/familyApi'

const props = defineProps({ id: { type: String, default: null } })
const isEdit = computed(() => !!props.id)

const fapi = useFamilyApi()
const router = useRouter()

const today = new Date().toISOString().slice(0, 10)

const form = reactive({
  account_id: '',
  category_id: '',
  type: 'expense',
  amount: '',
  date: today,
  description: '',
  notes: '',
  transfer_to_account_id: '',
})

const accounts = ref([])
const categories = ref([])
const loading = ref(false)
const errors = ref({})

const types = [
  { id: 'expense', label: 'Expense' },
  { id: 'income', label: 'Income' },
  { id: 'transfer', label: 'Transfer' },
]

const accountOptions = computed(() =>
  accounts.value.map((a) => ({ id: a.id, label: `${a.name} (${a.currency})` })),
)
const categoryOptions = computed(() =>
  categories.value
    .filter((c) => form.type === 'transfer' || c.type === form.type)
    .map((c) => ({ id: c.id, label: c.name })),
)
const transferTargets = computed(() =>
  accountOptions.value.filter((a) => a.id !== Number(form.account_id)),
)

onMounted(async () => {
  if (!fapi.hasFamily()) return
  const [accRes, catRes] = await Promise.all([fapi.get('/accounts'), fapi.get('/categories')])
  accounts.value = items(accRes)
  categories.value = items(catRes)

  if (isEdit.value) {
    const tx = record(await fapi.get(`/transactions/${props.id}`))
    Object.keys(form).forEach((k) => {
      if (tx[k] !== undefined && tx[k] !== null) form[k] = tx[k]
    })
    if (tx.account?.id) form.account_id = tx.account.id
    if (tx.category?.id) form.category_id = tx.category.id
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}
  const payload = { ...form }
  if (payload.type !== 'transfer') delete payload.transfer_to_account_id
  try {
    if (isEdit.value) {
      await fapi.put(`/transactions/${props.id}`, payload)
    } else {
      await fapi.post('/transactions', payload)
    }
    router.push('/transactions')
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
        :icon="mdiSwapHorizontal"
        :title="isEdit ? 'Edit Transaction' : 'New Transaction'"
        main
      >
        <BaseButton to="/transactions" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <FormField label="Type" :help="errors.type">
            <FormControl v-model="form.type" :options="types" />
          </FormField>
          <FormField label="Amount" :help="errors.amount">
            <FormControl v-model="form.amount" type="number" inputmode="decimal" required />
          </FormField>
          <FormField label="Date" :help="errors.date">
            <FormControl v-model="form.date" type="date" required />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Account" :help="errors.account_id">
            <FormControl v-model="form.account_id" :options="accountOptions" />
          </FormField>
          <FormField
            v-if="form.type === 'transfer'"
            label="Transfer To"
            :help="errors.transfer_to_account_id"
          >
            <FormControl v-model="form.transfer_to_account_id" :options="transferTargets" />
          </FormField>
          <FormField v-else label="Category" :help="errors.category_id">
            <FormControl v-model="form.category_id" :options="categoryOptions" />
          </FormField>
        </div>

        <FormField
          v-if="form.type === 'transfer'"
          label="Category"
          :help="errors.category_id"
        >
          <FormControl v-model="form.category_id" :options="categoryOptions" />
        </FormField>

        <FormField label="Description" :help="errors.description">
          <FormControl v-model="form.description" placeholder="e.g. Grocery shopping" />
        </FormField>

        <FormField label="Notes" :help="errors.notes">
          <FormControl v-model="form.notes" type="textarea" />
        </FormField>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Saving…' : isEdit ? 'Update' : 'Record Transaction'"
              :disabled="loading"
            />
            <BaseButton to="/transactions" color="whiteDark" outline label="Cancel" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
