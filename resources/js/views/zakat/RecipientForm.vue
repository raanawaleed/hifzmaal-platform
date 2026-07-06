<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiAccountHeart } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import { useFamilyApi, items, extractErrors } from '@/utils/familyApi'

const props = defineProps({ id: { type: String, default: null } })
const isEdit = computed(() => !!props.id)

const fapi = useFamilyApi()
const router = useRouter()

const form = reactive({
  name: '',
  category: 'fuqara',
  contact: '',
  address: '',
  notes: '',
})

const categories = [
  { id: 'fuqara', label: 'The Poor (الفقراء)' },
  { id: 'masakin', label: 'The Needy (المساكين)' },
  { id: 'amilin', label: 'Zakat Administrators (العاملين عليها)' },
  { id: 'muallaf', label: 'New Muslims (المؤلفة قلوبهم)' },
  { id: 'riqab', label: 'Freeing Captives (في الرقاب)' },
  { id: 'gharimin', label: 'Those in Debt (الغارمين)' },
  { id: 'fisabilillah', label: 'In the Cause of Allah (في سبيل الله)' },
  { id: 'ibnus_sabil', label: 'Stranded Travelers (ابن السبيل)' },
]

const loading = ref(false)
const errors = ref({})

onMounted(async () => {
  if (isEdit.value) {
    const res = await fapi.get('/zakat/recipients')
    const rec = items(res).find((r) => String(r.id) === String(props.id))
    if (rec) {
      Object.keys(form).forEach((k) => {
        if (rec[k] !== undefined && rec[k] !== null) form[k] = rec[k]
      })
    }
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await fapi.put(`/zakat/recipients/${props.id}`, form)
    } else {
      await fapi.post('/zakat/recipients', form)
    }
    router.push('/zakat/recipients')
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
        :icon="mdiAccountHeart"
        :title="isEdit ? 'Edit Recipient' : 'New Zakat Recipient'"
        main
      >
        <BaseButton to="/zakat/recipients" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <FormField label="Name" :help="errors.name || 'Person or organization'">
          <FormControl v-model="form.name" required />
        </FormField>

        <FormField label="Category" :help="errors.category || 'One of the 8 categories from Surah At-Tawbah 9:60'">
          <FormControl v-model="form.category" :options="categories" />
        </FormField>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Contact" :help="errors.contact || 'Phone / email — optional'">
            <FormControl v-model="form.contact" />
          </FormField>
          <FormField label="Address" :help="errors.address || 'Optional'">
            <FormControl v-model="form.address" />
          </FormField>
        </div>

        <FormField label="Notes" :help="errors.notes">
          <FormControl v-model="form.notes" type="textarea" />
        </FormField>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Saving…' : isEdit ? 'Update Recipient' : 'Add Recipient'"
              :disabled="loading"
            />
            <BaseButton to="/zakat/recipients" color="whiteDark" outline label="Cancel" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
