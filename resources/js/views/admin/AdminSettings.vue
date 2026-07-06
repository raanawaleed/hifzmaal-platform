<script setup>
import { reactive, ref, onMounted } from 'vue'
import { mdiCog } from '@mdi/js'
import api from '@/utils/api'
import { useNotificationsStore } from '@/stores/notifications'
import LayoutAdmin from '@/layouts/LayoutAdmin.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseDivider from '@/components/BaseDivider.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'

const notifications = useNotificationsStore()

const saving = ref(false)
const errors = ref({})

const rates = reactive({ currency: 'PKR', gold_per_gram: 0, silver_per_gram: 0 })
const nisab = reactive({ gold_grams: 87.48, silver_grams: 612.36 })

const load = async () => {
  const response = await api.get('/admin/settings')
  const data = response.data.data
  if (data.metal_rates) Object.assign(rates, data.metal_rates)
  if (data.nisab) Object.assign(nisab, data.nisab)
}

onMounted(load)

const save = async () => {
  saving.value = true
  errors.value = {}
  try {
    await api.put('/admin/settings', {
      metal_rates: {
        currency: rates.currency,
        gold_per_gram: Number(rates.gold_per_gram),
        silver_per_gram: Number(rates.silver_per_gram),
      },
      nisab: {
        gold_grams: Number(nisab.gold_grams),
        silver_grams: Number(nisab.silver_grams),
      },
    })
    notifications.success('Zakat settings saved. New calculations will use these rates.')
  } catch (err) {
    const responseErrors = err.response?.data?.errors || {}
    errors.value = Object.fromEntries(
      Object.entries(responseErrors).map(([key, messages]) => [key, messages[0]])
    )
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <LayoutAdmin>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiCog" title="Zakat Settings" main />

      <CardBox is-form @submit.prevent="save">
        <h3 class="mb-1 text-lg font-semibold">Metal rates</h3>
        <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">
          Current market prices used to compute the nisab threshold for every family.
          Update these regularly (e.g. weekly) from your local gold market.
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <FormField label="Currency" :help="errors['metal_rates.currency']">
            <FormControl v-model="rates.currency" disabled />
          </FormField>
          <FormField :label="`Gold — per gram (${rates.currency})`" :help="errors['metal_rates.gold_per_gram']">
            <FormControl v-model="rates.gold_per_gram" type="number" step="0.01" min="0" />
          </FormField>
          <FormField :label="`Silver — per gram (${rates.currency})`" :help="errors['metal_rates.silver_per_gram']">
            <FormControl v-model="rates.silver_per_gram" type="number" step="0.01" min="0" />
          </FormField>
        </div>

        <BaseDivider />

        <h3 class="mb-1 text-lg font-semibold">Nisab thresholds</h3>
        <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">
          Classical values: 87.48 g gold (7.5 tola) and 612.36 g silver (52.5 tola).
          Only change these if your scholars advise different measures.
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField label="Gold nisab (grams)" :help="errors['nisab.gold_grams']">
            <FormControl v-model="nisab.gold_grams" type="number" step="0.01" min="0" />
          </FormField>
          <FormField label="Silver nisab (grams)" :help="errors['nisab.silver_grams']">
            <FormControl v-model="nisab.silver_grams" type="number" step="0.01" min="0" />
          </FormField>
        </div>

        <div class="mt-4 rounded-lg bg-emerald-50 p-4 text-sm dark:bg-slate-700">
          <p>
            <b>Preview:</b>
            silver nisab ≈ {{ (nisab.silver_grams * rates.silver_per_gram).toLocaleString() }} {{ rates.currency }},
            gold nisab ≈ {{ (nisab.gold_grams * rates.gold_per_gram).toLocaleString() }} {{ rates.currency }}
          </p>
        </div>

        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" :label="saving ? 'Saving…' : 'Save settings'" :disabled="saving" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAdmin>
</template>
