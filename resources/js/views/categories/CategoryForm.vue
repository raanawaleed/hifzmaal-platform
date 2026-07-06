<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiTagMultiple } from '@mdi/js'
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
  name_ur: '',
  type: 'expense',
  color: '#10b981',
  is_halal: true,
})

const types = [
  { id: 'expense', label: 'Expense' },
  { id: 'income', label: 'Income' },
]

const loading = ref(false)
const errors = ref({})

onMounted(async () => {
  if (isEdit.value) {
    const c = record(await fapi.get(`/categories/${props.id}`))
    Object.keys(form).forEach((k) => {
      if (c[k] !== undefined && c[k] !== null) form[k] = c[k]
    })
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await fapi.put(`/categories/${props.id}`, form)
    } else {
      await fapi.post('/categories', form)
    }
    router.push('/categories')
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
        :icon="mdiTagMultiple"
        :title="isEdit ? 'Edit Category' : 'New Category'"
        main
      >
        <BaseButton to="/categories" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBox is-form @submit.prevent="submit">
        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Name" :help="errors.name || 'e.g. Groceries, Salary'">
            <FormControl v-model="form.name" required />
          </FormField>
          <FormField label="Urdu Name" :help="errors.name_ur || 'Optional'">
            <FormControl v-model="form.name_ur" placeholder="مثلاً کریانہ" />
          </FormField>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Type" :help="errors.type">
            <FormControl v-model="form.type" :options="types" />
          </FormField>
          <FormField label="Color" :help="errors.color">
            <div class="flex items-center gap-3">
              <input
                v-model="form.color"
                type="color"
                class="h-12 w-16 cursor-pointer rounded border border-gray-300 dark:border-slate-600"
              />
              <FormControl v-model="form.color" class="flex-1" />
            </div>
          </FormField>
        </div>

        <FormCheckRadio
          v-model="form.is_halal"
          name="is_halal"
          type="switch"
          label="Halal category"
          :input-value="true"
        />

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="success"
              :label="loading ? 'Saving…' : isEdit ? 'Update Category' : 'Create Category'"
              :disabled="loading"
            />
            <BaseButton to="/categories" color="whiteDark" outline label="Cancel" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
