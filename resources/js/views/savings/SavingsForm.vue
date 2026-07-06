<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiPiggyBank } from '@mdi/js'
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

const form = reactive({
  name: '',
  type: 'other',
  target_amount: '',
  current_amount: 0,
  monthly_contribution: '',
  target_date: '',
  account_id: '',
  description: '',
  dua_reminder: '',
})

const types = [
  { id: 'hajj', label: 'Hajj' },
  { id: 'umrah', label: 'Umrah' },
  { id: 'education', label: 'Education' },
  { id: 'marriage', label: 'Marriage' },
  { id: 'emergency', label: 'Emergency Fund' },
  { id: 'business', label: 'Business' },
  { id: 'other', label: 'Other' },
]

const accounts = ref([])
const loading = ref(false)
const errors = ref({})

const accountOptions = computed(() => [
  { id: '', label: '— None —' },
  ...accounts.value.map((a) => ({ id: a.id, label: a.name })),
])

onMounted(async () => {
  if (!fapi.hasFamily()) return
  accounts.value = items(await fapi.get('/accounts'))

  if (isEdit.value) {
    const g = record(await fapi.get(`/savings-goals/${props.id}`))
    Object.keys(form).forEach((k) => {
      if (g[k] !== undefined && g[k] !== null) form[k] = g[k]
    })
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}
  const payload = { ...form }
  ;['account_id', 'target_date', 'monthly_contribution', 'description', 'dua_reminder'].forEach((k) => {
    if (payload[k] === '' || payload[k] == null) delete payload[k]
  })
  try {
    if (isEdit.value) {
      await fapi.put(`/savings-goals/${props.id}`, payload)
    } else {
      await fapi.post('/savings-goals', payload)
    }
    router.push('/savings-goals')
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
        :icon="mdiPiggyBank"
        :title="isEdit ? 'Edit Savings Goal' : 'New Savings Goal'"
        main
      >
        <BaseButton to="/savings-goals" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Goal Name" :help="errors.name || 'e.g. Hajj 2028'">
            <FormControl v-model="form.name" required />
          </FormField>
          <FormField label="Type" :help="errors.type">
            <FormControl v-model="form.type" :options="types" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <FormField label="Target Amount" :help="errors.target_amount">
            <FormControl v-model="form.target_amount" type="number" inputmode="decimal" required />
          </FormField>
          <FormField label="Already Saved" :help="errors.current_amount">
            <FormControl v-model="form.current_amount" type="number" inputmode="decimal" />
          </FormField>
          <FormField label="Monthly Contribution" :help="errors.monthly_contribution || 'Optional'">
            <FormControl v-model="form.monthly_contribution" type="number" inputmode="decimal" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Target Date" :help="errors.target_date || 'Optional'">
            <FormControl v-model="form.target_date" type="date" />
          </FormField>
          <FormField label="Linked Account" :help="errors.account_id || 'Optional'">
            <FormControl v-model="form.account_id" :options="accountOptions" />
          </FormField>
        </div>

        <FormField label="Description" :help="errors.description">
          <FormControl v-model="form.description" type="textarea" />
        </FormField>

        <FormField label="Dua Reminder" :help="errors.dua_reminder || 'A dua to remember this goal by'">
          <FormControl v-model="form.dua_reminder" placeholder="اللهم ارزقنا حج بيتك الحرام" />
        </FormField>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Saving…' : isEdit ? 'Update Goal' : 'Create Goal'"
              :disabled="loading"
            />
            <BaseButton to="/savings-goals" color="whiteDark" outline label="Cancel" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
