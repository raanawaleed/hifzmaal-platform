<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiAccountGroup } from '@mdi/js'
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
  email: '',
  relationship: 'son',
  role: 'member',
  date_of_birth: '',
  spending_limit: '',
  is_active: true,
})

const relationships = [
  { id: 'spouse', label: 'Spouse' },
  { id: 'son', label: 'Son' },
  { id: 'daughter', label: 'Daughter' },
  { id: 'father', label: 'Father' },
  { id: 'mother', label: 'Mother' },
  { id: 'brother', label: 'Brother' },
  { id: 'sister', label: 'Sister' },
  { id: 'dependent', label: 'Dependent' },
]
const roles = [
  { id: 'viewer', label: 'Viewer — read only' },
  { id: 'member', label: 'Member — can add transactions and bills' },
]

const loading = ref(false)
const errors = ref({})

onMounted(async () => {
  if (isEdit.value) {
    const m = record(await fapi.get(`/members/${props.id}`))
    Object.keys(form).forEach((k) => {
      if (m[k] !== undefined && m[k] !== null) form[k] = m[k]
    })
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}
  const payload = { ...form }
  if (!payload.email) delete payload.email
  if (!payload.date_of_birth) delete payload.date_of_birth
  if (payload.spending_limit === '') delete payload.spending_limit
  try {
    if (isEdit.value) {
      await fapi.put(`/members/${props.id}`, payload)
    } else {
      await fapi.post('/members', payload)
    }
    router.push('/family-members')
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
        :icon="mdiAccountGroup"
        :title="isEdit ? 'Edit Member' : 'Add Family Member'"
        main
      >
        <BaseButton to="/family-members" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Name" :help="errors.name">
            <FormControl v-model="form.name" required />
          </FormField>
          <FormField label="Email" :help="errors.email || 'Optional — allows them to sign in'">
            <FormControl v-model="form.email" type="email" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Relationship" :help="errors.relationship">
            <FormControl v-model="form.relationship" :options="relationships" />
          </FormField>
          <FormField label="Access Role" :help="errors.role">
            <FormControl v-model="form.role" :options="roles" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Date of Birth" :help="errors.date_of_birth || 'Optional'">
            <FormControl v-model="form.date_of_birth" type="date" />
          </FormField>
          <FormField label="Spending Limit" :help="errors.spending_limit || 'Transactions above this need approval'">
            <FormControl v-model="form.spending_limit" type="number" inputmode="decimal" />
          </FormField>
        </div>

        <FormCheckRadio
          v-model="form.is_active"
          name="is_active"
          type="switch"
          label="Active member"
          :input-value="true"
        />

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Saving…' : isEdit ? 'Update Member' : 'Add Member'"
              :disabled="loading"
            />
            <BaseButton to="/family-members" color="whiteDark" outline label="Cancel" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
