<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiSwapHorizontal, mdiPaperclip, mdiClose, mdiFilePdfBox } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseIcon from '@/components/BaseIcon.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useFamilyApi, items, record, extractErrors } from '@/utils/familyApi'

const props = defineProps({ id: { type: String, default: null } })
const isEdit = computed(() => !!props.id)

const fapi = useFamilyApi()
const router = useRouter()
const notifications = useNotificationsStore()

const receipts = ref([])
const uploadingReceipt = ref(false)
const receiptError = ref('')
const fileInput = ref(null)

const loadReceipts = async () => {
  const tx = record(await fapi.get(`/transactions/${props.id}`))
  receipts.value = tx.receipts || []
}

const uploadReceipt = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  receiptError.value = ''
  uploadingReceipt.value = true
  const data = new FormData()
  data.append('receipt', file)
  try {
    // api.js's axios instance defaults Content-Type to application/json,
    // which would make axios JSON.stringify this FormData (losing the
    // file) instead of sending it as multipart. Unsetting it here lets
    // axios detect the FormData body and have the browser generate the
    // correct multipart/form-data header with its boundary itself.
    await fapi.post(`/transactions/${props.id}/receipts`, data, {
      headers: { 'Content-Type': undefined },
    })
    await loadReceipts()
    notifications.success('Receipt uploaded.')
  } catch (err) {
    receiptError.value = err.response?.data?.errors?.receipt?.[0]
      || err.response?.data?.message
      || 'Could not upload the receipt.'
  } finally {
    uploadingReceipt.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const deleteReceipt = async (receipt) => {
  try {
    await fapi.delete(`/transactions/${props.id}/receipts/${receipt.id}`)
    receipts.value = receipts.value.filter((r) => r.id !== receipt.id)
  } catch {
    notifications.error('Could not remove the receipt.')
  }
}

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
    receipts.value = tx.receipts || []
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

        <FormField label="Receipts" help="JPG, PNG, or PDF — up to 5MB" :error="receiptError">
          <div v-if="isEdit">
            <div v-if="receipts.length" class="mb-3 flex flex-wrap gap-3">
              <div
                v-for="receipt in receipts"
                :key="receipt.id"
                class="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-lg border border-gray-200 dark:border-slate-600"
              >
                <a :href="receipt.url" target="_blank" rel="noopener" class="flex h-full w-full items-center justify-center">
                  <img
                    v-if="receipt.type?.startsWith('image/')"
                    :src="receipt.url"
                    :alt="receipt.name"
                    class="h-full w-full object-cover"
                  />
                  <BaseIcon v-else :path="mdiFilePdfBox" size="32" class="text-gray-400" />
                </a>
                <button
                  type="button"
                  class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80"
                  @click="deleteReceipt(receipt)"
                >
                  <BaseIcon :path="mdiClose" size="14" />
                </button>
              </div>
            </div>
            <input ref="fileInput" type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="uploadReceipt" />
            <BaseButton
              type="button"
              :icon="mdiPaperclip"
              :label="uploadingReceipt ? 'Uploading…' : 'Attach Receipt'"
              color="info"
              outline
              small
              :disabled="uploadingReceipt"
              @click="fileInput.click()"
            />
          </div>
          <p v-else class="text-sm text-gray-400 dark:text-slate-500">
            Save the transaction first to attach receipts.
          </p>
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
