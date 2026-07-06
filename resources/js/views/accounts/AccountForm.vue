<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiWallet } from '@mdi/js'
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
import { useFamilyApi, record, extractErrors } from '@/utils/familyApi'

const props = defineProps({ id: { type: String, default: null } })
const isEdit = computed(() => !!props.id)

const fapi = useFamilyApi()
const router = useRouter()

const form = reactive({
  name: '',
  type: 'cash',
  currency: 'PKR',
  initial_balance: 0,
  account_number: '',
  bank_name: '',
  include_in_zakat: true,
  description: '',
})

const types = [
  { id: 'cash', label: 'Cash' },
  { id: 'bank', label: 'Bank Account' },
  { id: 'wallet', label: 'Mobile Wallet' },
  { id: 'savings', label: 'Savings' },
  { id: 'investment', label: 'Investment' },
]
const currencies = ['PKR', 'USD', 'EUR', 'GBP', 'SAR', 'AED', 'INR', 'BDT']

const loading = ref(false)
const errors = ref({})

onMounted(async () => {
  if (isEdit.value) {
    const acc = record(await fapi.get(`/accounts/${props.id}`))
    Object.keys(form).forEach((k) => {
      if (acc[k] !== undefined && acc[k] !== null) form[k] = acc[k]
    })
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await fapi.put(`/accounts/${props.id}`, form)
    } else {
      await fapi.post('/accounts', form)
    }
    router.push('/accounts')
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
        :icon="mdiWallet"
        :title="isEdit ? 'Edit Account' : 'New Account'"
        main
      >
        <BaseButton to="/accounts" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <FormField label="Account Name" :help="errors.name || 'e.g. HBL Savings, Home Cash'">
          <FormControl v-model="form.name" required />
        </FormField>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Type" :help="errors.type">
            <FormControl v-model="form.type" :options="types" />
          </FormField>
          <FormField label="Currency" :help="errors.currency">
            <FormControl v-model="form.currency" :options="currencies" />
          </FormField>
        </div>

        <FormField
          label="Initial Balance"
          :help="errors.initial_balance || 'Opening balance for this account'"
        >
          <FormControl v-model="form.initial_balance" type="number" inputmode="decimal" required />
        </FormField>

        <template v-if="form.type === 'bank'">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <FormField label="Bank Name" :help="errors.bank_name">
              <FormControl v-model="form.bank_name" placeholder="e.g. HBL, Meezan" />
            </FormField>
            <FormField label="Account Number" :help="errors.account_number">
              <FormControl v-model="form.account_number" />
            </FormField>
          </div>
        </template>

        <FormField label="Description" :help="errors.description">
          <FormControl v-model="form.description" type="textarea" />
        </FormField>

        <FormCheckRadio
          v-model="form.include_in_zakat"
          name="include_in_zakat"
          type="switch"
          label="Include in Zakat calculation"
          :input-value="true"
        />

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Saving…' : isEdit ? 'Update Account' : 'Create Account'"
              :disabled="loading"
            />
            <BaseButton to="/accounts" color="whiteDark" outline label="Cancel" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
