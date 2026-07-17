<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()
const fapi = useFamilyApi()
const router = useRouter()

const form = reactive({
  name: '',
  category: 'fuqara',
  contact: '',
  address: '',
  notes: '',
})

// The 8 asnaf from Surah At-Tawbah 9:60 — computed so the labels
// re-render when the locale switches.
const categories = computed(() =>
  ['fuqara', 'masakin', 'amilin', 'muallaf', 'riqab', 'gharimin', 'fisabilillah', 'ibnus_sabil'].map(
    (id) => ({ id, label: t(`zakat.categories.${id}`) }),
  ),
)

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
        :title="isEdit ? t('zakat.editRecipient') : t('zakat.newZakatRecipient')"
        main
      >
        <BaseButton to="/zakat/recipients" :label="t('zakat.back')" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <FormField :label="t('zakat.name')" :help="errors.name || t('zakat.nameHelp')">
          <FormControl v-model="form.name" required />
        </FormField>

        <FormField :label="t('zakat.category')" :help="errors.category || t('zakat.categoryHelp')">
          <FormControl v-model="form.category" :options="categories" />
        </FormField>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField :label="t('zakat.contact')" :help="errors.contact || t('zakat.contactHelp')">
            <FormControl v-model="form.contact" />
          </FormField>
          <FormField :label="t('zakat.address')" :help="errors.address || t('zakat.optional')">
            <FormControl v-model="form.address" />
          </FormField>
        </div>

        <FormField :label="t('zakat.notes')" :help="errors.notes">
          <FormControl v-model="form.notes" type="textarea" />
        </FormField>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? t('zakat.saving') : isEdit ? t('zakat.updateRecipient') : t('zakat.addRecipient')"
              :disabled="loading"
            />
            <BaseButton to="/zakat/recipients" color="whiteDark" outline :label="t('zakat.cancel')" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
