<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { mdiHandCoin, mdiCalculator } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import PillTag from '@/components/PillTag.vue'
import { useFamilyApi, extractErrors } from '@/utils/familyApi'

const fapi = useFamilyApi()
const router = useRouter()

// Approximate current Hijri year
const currentHijri = Math.floor((new Date().getFullYear() - 622) * (33 / 32))

const form = reactive({
  hijri_year: currentHijri,
  cash_in_hand: 0,
  cash_in_bank: 0,
  gold_value: 0,
  silver_value: 0,
  business_inventory: 0,
  investments: 0,
  loans_receivable: 0,
  other_assets: 0,
  debts: 0,
  nisab_type: 'silver',
  notes: '',
})

const nisabTypes = [
  { id: 'silver', label: 'Silver (612.36g) — recommended' },
  { id: 'gold', label: 'Gold (87.48g)' },
]

const nisabAmount = ref(null)
const loading = ref(false)
const autoFilling = ref(false)
const errors = ref({})

const totalAssets = computed(
  () =>
    Number(form.cash_in_hand || 0) +
    Number(form.cash_in_bank || 0) +
    Number(form.gold_value || 0) +
    Number(form.silver_value || 0) +
    Number(form.business_inventory || 0) +
    Number(form.investments || 0) +
    Number(form.loans_receivable || 0) +
    Number(form.other_assets || 0),
)
const netWealth = computed(() => totalAssets.value - Number(form.debts || 0))
const estimatedZakat = computed(() =>
  nisabAmount.value != null && netWealth.value >= nisabAmount.value
    ? Math.round(netWealth.value * 0.025 * 100) / 100
    : 0,
)

const loadNisab = async () => {
  try {
    const res = await fapi.get(`/zakat/nisab-amount?type=${form.nisab_type}`)
    nisabAmount.value = res.data.data?.nisab_amount ?? res.data.nisab_amount ?? null
  } catch {
    nisabAmount.value = null
  }
}

onMounted(() => {
  if (fapi.hasFamily()) loadNisab()
})

const autoFill = async () => {
  autoFilling.value = true
  try {
    const res = await fapi.post('/zakat/auto-calculate', { hijri_year: form.hijri_year })
    const d = res.data.data || res.data
    ;['cash_in_hand', 'cash_in_bank', 'gold_value', 'silver_value', 'investments'].forEach((k) => {
      if (d[k] != null) form[k] = d[k]
    })
    if (d.cash_in_bank == null && d.bank_balance != null) form.cash_in_bank = d.bank_balance
  } catch (err) {
    errors.value = extractErrors(err)
  } finally {
    autoFilling.value = false
  }
}

const submit = async () => {
  loading.value = true
  errors.value = {}
  try {
    await fapi.post('/zakat', form)
    router.push('/zakat')
  } catch (err) {
    errors.value = extractErrors(err)
  } finally {
    loading.value = false
  }
}

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiHandCoin" title="Calculate Zakat" main>
        <BaseButton to="/zakat" label="Back" color="whiteDark" rounded-full small />
      </SectionTitleLineWithButton>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Form -->
        <div class="lg:col-span-2">
          <CardBox is-form @submit.prevent="submit">
            <NotificationBarInCard v-if="errors._message" color="danger">
              {{ errors._message }}
            </NotificationBarInCard>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <FormField label="Hijri Year" :help="errors.hijri_year">
                <FormControl v-model="form.hijri_year" type="number" required />
              </FormField>
              <FormField label="Nisab Standard" :help="errors.nisab_type">
                <FormControl v-model="form.nisab_type" :options="nisabTypes" @change="loadNisab" />
              </FormField>
            </div>

            <div class="mb-2">
              <BaseButton
                :icon="mdiCalculator"
                :label="autoFilling ? 'Filling from accounts…' : 'Auto-fill from accounts'"
                color="info"
                outline
                small
                :disabled="autoFilling"
                @click="autoFill"
              />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <FormField label="Cash in Hand" :help="errors.cash_in_hand">
                <FormControl v-model="form.cash_in_hand" type="number" inputmode="decimal" required />
              </FormField>
              <FormField label="Cash in Bank" :help="errors.cash_in_bank">
                <FormControl v-model="form.cash_in_bank" type="number" inputmode="decimal" required />
              </FormField>
              <FormField label="Gold Value" :help="errors.gold_value">
                <FormControl v-model="form.gold_value" type="number" inputmode="decimal" />
              </FormField>
              <FormField label="Silver Value" :help="errors.silver_value">
                <FormControl v-model="form.silver_value" type="number" inputmode="decimal" />
              </FormField>
              <FormField label="Business Inventory" :help="errors.business_inventory">
                <FormControl v-model="form.business_inventory" type="number" inputmode="decimal" />
              </FormField>
              <FormField label="Investments" :help="errors.investments">
                <FormControl v-model="form.investments" type="number" inputmode="decimal" />
              </FormField>
              <FormField label="Loans Receivable" :help="errors.loans_receivable">
                <FormControl v-model="form.loans_receivable" type="number" inputmode="decimal" />
              </FormField>
              <FormField label="Other Assets" :help="errors.other_assets">
                <FormControl v-model="form.other_assets" type="number" inputmode="decimal" />
              </FormField>
            </div>

            <FormField label="Debts & Liabilities" :help="errors.debts || 'Deducted from your wealth'">
              <FormControl v-model="form.debts" type="number" inputmode="decimal" />
            </FormField>

            <FormField label="Notes" :help="errors.notes">
              <FormControl v-model="form.notes" type="textarea" />
            </FormField>

            <template #footer>
              <BaseButtons>
                <BaseButton
                  type="submit"
                  color="success"
                  :label="loading ? 'Calculating…' : 'Save Calculation'"
                  :disabled="loading"
                />
                <BaseButton to="/zakat" color="whiteDark" outline label="Cancel" />
              </BaseButtons>
            </template>
          </CardBox>
        </div>

        <!-- Live summary -->
        <div>
          <CardBox class="sticky top-20">
            <h3 class="mb-4 text-lg font-semibold">Live Summary</h3>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-slate-400">Total Assets</span>
                <b>{{ fmt(totalAssets) }}</b>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-slate-400">Debts</span>
                <b class="text-red-500">−{{ fmt(form.debts) }}</b>
              </div>
              <hr class="border-gray-100 dark:border-slate-700" />
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-slate-400">Net Wealth</span>
                <b>{{ fmt(netWealth) }}</b>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-slate-400">Nisab ({{ form.nisab_type }})</span>
                <b>{{ fmt(nisabAmount) }}</b>
              </div>
              <div class="pt-2">
                <PillTag
                  v-if="nisabAmount != null"
                  :color="netWealth >= nisabAmount ? 'warning' : 'info'"
                  :label="netWealth >= nisabAmount ? 'Zakat is due' : 'Below Nisab — no Zakat'"
                />
              </div>
              <div
                class="mt-2 rounded-xl bg-emerald-50 p-4 text-center dark:bg-emerald-900/20"
              >
                <p class="text-xs text-emerald-700 uppercase dark:text-emerald-400">
                  Estimated Zakat (2.5%)
                </p>
                <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">
                  {{ fmt(estimatedZakat) }}
                </p>
              </div>
            </div>
          </CardBox>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>
