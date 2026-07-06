<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiHomeGroup } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import api from '@/utils/api'
import { record, extractErrors } from '@/utils/familyApi'
import { useFamilyStore } from '@/stores/family'

const props = defineProps({ id: { type: String, default: null } })
const isEdit = computed(() => !!props.id)

const familyStore = useFamilyStore()
const router = useRouter()

const form = reactive({
  name: '',
  currency: 'PKR',
  locale: 'en',
})

const currencies = ['PKR', 'USD', 'EUR', 'GBP', 'SAR', 'AED', 'INR', 'BDT']
const locales = [
  { id: 'en', label: 'English' },
  { id: 'ur', label: 'اردو (Urdu)' },
  { id: 'hi', label: 'हिन्दी (Hindi)' },
  { id: 'bn', label: 'বাংলা (Bangla)' },
]

const loading = ref(false)
const errors = ref({})

onMounted(async () => {
  if (isEdit.value) {
    const f = record(await api.get(`/families/${props.id}`))
    Object.keys(form).forEach((k) => {
      if (f[k] !== undefined && f[k] !== null) form[k] = f[k]
    })
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await api.put(`/families/${props.id}`, form)
    } else {
      const res = await api.post('/families', form)
      const created = record(res)
      if (created?.id) familyStore.setFamilyId(created.id)
    }
    await familyStore.loadUserFamilies()
    router.push(isEdit.value ? '/families' : '/dashboard')
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
        :icon="mdiHomeGroup"
        :title="isEdit ? 'Edit Family' : 'Create Family'"
        main
      >
        <BaseButton to="/families" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <FormField label="Family Name" :help="errors.name || 'e.g. Khan Family'">
          <FormControl v-model="form.name" required />
        </FormField>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Currency" :help="errors.currency || 'Primary currency for the family'">
            <FormControl v-model="form.currency" :options="currencies" />
          </FormField>
          <FormField label="Language" :help="errors.locale">
            <FormControl v-model="form.locale" :options="locales" />
          </FormField>
        </div>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Saving…' : isEdit ? 'Update Family' : 'Create Family'"
              :disabled="loading"
            />
            <BaseButton to="/families" color="whiteDark" outline label="Cancel" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
