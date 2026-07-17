<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { mdiCog, mdiCloudRefresh } from '@mdi/js'
import api from '@/utils/api'
import { useNotificationsStore } from '@/stores/notifications'
import { SUPPORTED_LOCALES } from '@/i18n'
import LayoutAdmin from '@/layouts/LayoutAdmin.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseDivider from '@/components/BaseDivider.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'

const { t } = useI18n()
const notifications = useNotificationsStore()

const saving = ref(false)
const refreshing = ref(false)
const errors = ref({})

const rates = reactive({ currency: 'PKR', gold_per_gram: 0, silver_per_gram: 0 })
const nisab = reactive({ gold_grams: 87.48, silver_grams: 612.36 })
const defaultLanguage = ref('en')

// Native names from the switcher — deliberately not translated, so every
// admin can recognize each language in its own script.
const languageOptions = computed(() => SUPPORTED_LOCALES.map((l) => ({ id: l.id, label: l.label })))

const load = async () => {
  const response = await api.get('/admin/settings')
  const data = response.data.data
  if (data.metal_rates) Object.assign(rates, data.metal_rates)
  if (data.nisab) Object.assign(nisab, data.nisab)
  if (data.default_language) defaultLanguage.value = data.default_language
}

onMounted(load)

const refreshFromLivePrices = async () => {
  refreshing.value = true
  try {
    const res = await api.post('/admin/settings/refresh-metal-rates', { currency: rates.currency })
    Object.assign(rates, res.data.data.metal_rates)
    notifications.success(t('adminSettings.refreshed'))
  } catch (err) {
    notifications.error(err.response?.data?.message || t('adminSettings.refreshError'))
  } finally {
    refreshing.value = false
  }
}

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
      default_language: defaultLanguage.value,
    })
    notifications.success(t('adminSettings.saved'))
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
      <SectionTitleLineWithButton :icon="mdiCog" :title="t('adminSettings.title')" main />

      <CardBox is-form @submit.prevent="save">
        <div class="mb-4 flex items-start justify-between gap-4">
          <div>
            <h3 class="mb-1 text-lg font-semibold">{{ t('adminSettings.metalRates') }}</h3>
            <p class="text-sm text-gray-500 dark:text-slate-400">
              {{ t('adminSettings.metalRatesDesc', { key: 'GOLDAPI_KEY' }) }}
            </p>
          </div>
          <BaseButton
            type="button"
            :icon="mdiCloudRefresh"
            :label="refreshing ? t('adminSettings.refreshing') : t('adminSettings.refreshNow')"
            color="info"
            outline
            small
            :disabled="refreshing"
            @click="refreshFromLivePrices"
          />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <FormField :label="t('adminSettings.currency')" :help="errors['metal_rates.currency']">
            <FormControl v-model="rates.currency" disabled />
          </FormField>
          <FormField :label="t('adminSettings.goldPerGram', { currency: rates.currency })" :help="errors['metal_rates.gold_per_gram']">
            <FormControl v-model="rates.gold_per_gram" type="number" step="0.01" min="0" />
          </FormField>
          <FormField :label="t('adminSettings.silverPerGram', { currency: rates.currency })" :help="errors['metal_rates.silver_per_gram']">
            <FormControl v-model="rates.silver_per_gram" type="number" step="0.01" min="0" />
          </FormField>
        </div>

        <BaseDivider />

        <h3 class="mb-1 text-lg font-semibold">{{ t('adminSettings.nisabThresholds') }}</h3>
        <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">
          {{ t('adminSettings.nisabDesc') }}
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField :label="t('adminSettings.goldNisabGrams')" :help="errors['nisab.gold_grams']">
            <FormControl v-model="nisab.gold_grams" type="number" step="0.01" min="0" />
          </FormField>
          <FormField :label="t('adminSettings.silverNisabGrams')" :help="errors['nisab.silver_grams']">
            <FormControl v-model="nisab.silver_grams" type="number" step="0.01" min="0" />
          </FormField>
        </div>

        <div class="mt-4 rounded-lg bg-emerald-50 p-4 text-sm dark:bg-slate-700">
          <p>
            <b>{{ t('adminSettings.preview') }}</b>
            {{
              t('adminSettings.previewText', {
                silver: (nisab.silver_grams * rates.silver_per_gram).toLocaleString(),
                gold: (nisab.gold_grams * rates.gold_per_gram).toLocaleString(),
                currency: rates.currency,
              })
            }}
          </p>
        </div>

        <BaseDivider />

        <h3 class="mb-1 text-lg font-semibold">{{ t('adminSettings.defaultLanguage') }}</h3>
        <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">
          {{ t('adminSettings.defaultLanguageDesc') }}
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <FormField :label="t('adminSettings.defaultLanguage')" :help="errors['default_language']">
            <FormControl v-model="defaultLanguage" :options="languageOptions" />
          </FormField>
        </div>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="info"
              :label="saving ? t('adminSettings.savingBtn') : t('adminSettings.saveSettings')"
              :disabled="saving"
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAdmin>
</template>
