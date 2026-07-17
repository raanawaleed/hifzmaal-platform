<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { mdiHandCoin, mdiCashPlus, mdiDownload } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import PillTag from '@/components/PillTag.vue'
import NotificationBar from '@/components/NotificationBar.vue'
import { useFamilyApi, items, record, extractErrors } from '@/utils/familyApi'

const props = defineProps({ id: { type: String, required: true } })

const { t, locale } = useI18n()
const fapi = useFamilyApi()
const calc = ref(null)
const payments = ref([])
const recipients = ref([])
const notice = ref(null)
const downloadingCertificate = ref(false)

const downloadCertificate = async () => {
  downloadingCertificate.value = true
  try {
    await fapi.download(`/zakat/${props.id}/certificate`, `zakat-certificate-${calc.value?.hijri_year}AH.pdf`)
  } catch {
    notice.value = { color: 'danger', text: t('zakat.certificateError') }
  } finally {
    downloadingCertificate.value = false
  }
}

const payModal = ref(false)
const payBusy = ref(false)
const payForm = ref({ amount: '', type: 'zakat', recipient_id: '', recipient_name: '', notes: '' })

// computed so option labels re-render when the locale switches
const payTypes = computed(() => [
  { id: 'zakat', label: t('zakat.typeZakat') },
  { id: 'sadaqah', label: t('zakat.typeSadaqah') },
  { id: 'fitrah', label: t('zakat.typeFitrah') },
])

const recipientOptions = computed(() => [
  { id: '', label: t('zakat.enterNameManually') },
  ...recipients.value.map((r) => ({ id: r.id, label: r.name })),
])

const load = async () => {
  if (!fapi.hasFamily()) return
  const [calcRes, payRes, recRes] = await Promise.all([
    fapi.get(`/zakat/${props.id}`),
    fapi.get(`/zakat/${props.id}/payments`).catch(() => null),
    fapi.get('/zakat/recipients').catch(() => null),
  ])
  calc.value = record(calcRes)
  payments.value = payRes ? items(payRes) : []
  recipients.value = recRes ? items(recRes) : []
}

onMounted(load)

const submitPayment = async () => {
  if (payBusy.value) return
  payBusy.value = true
  try {
    const payload = { ...payForm.value, amount: Number(payForm.value.amount) }
    if (payload.recipient_id) delete payload.recipient_name
    else delete payload.recipient_id
    await fapi.post(`/zakat/${props.id}/payments`, payload)
    notice.value = { color: 'success', text: t('zakat.paymentRecorded') }
    payModal.value = false
    payForm.value = { amount: '', type: 'zakat', recipient_id: '', recipient_name: '', notes: '' }
    await load()
  } catch (err) {
    notice.value = { color: 'danger', text: extractErrors(err)._message }
  } finally {
    payBusy.value = false
  }
}

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString(locale.value))

const assetRows = computed(() => {
  if (!calc.value) return []
  const c = calc.value
  return [
    [t('zakat.cashInHand'), c.cash_in_hand],
    [t('zakat.cashInBank'), c.cash_in_bank],
    [t('zakat.gold'), c.gold_value],
    [t('zakat.silver'), c.silver_value],
    [t('zakat.businessInventory'), c.business_inventory],
    [t('zakat.investments'), c.investments],
    [t('zakat.loansReceivable'), c.loans_receivable],
    [t('zakat.otherAssets'), c.other_assets],
  ].filter(([, v]) => Number(v) > 0)
})

const remaining = computed(() => {
  if (!calc.value) return 0
  const paid = payments.value.reduce((s, p) => s + Number(p.amount || 0), 0)
  return Math.max(Number(calc.value.zakat_amount || 0) - paid, 0)
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiHandCoin"
        :title="calc ? t('zakat.detailHeading', { year: calc.hijri_year }) : t('zakat.detailTitle')"
        main
      >
        <BaseButtons>
          <BaseButton
            :icon="mdiDownload"
            :label="downloadingCertificate ? t('zakat.downloading') : t('zakat.downloadCertificate')"
            color="info"
            outline
            rounded-full
            small
            :disabled="downloadingCertificate"
            @click="downloadCertificate"
          />
          <BaseButton to="/zakat" :label="t('zakat.back')" color="whiteDark" rounded-full small />
        </BaseButtons>
      </SectionTitleLineWithButton>

      <NotificationBar v-if="notice" :color="notice.color" @dismiss="notice = null">
        {{ notice.text }}
      </NotificationBar>

      <!-- Payment modal -->
      <CardBoxModal
        v-model="payModal"
        :title="t('zakat.recordZakatPayment')"
        button="success"
        :button-label="t('zakat.recordPayment')"
        has-cancel
        is-form
        :is-processing="payBusy"
        @confirm="submitPayment"
      >
        <FormField :label="t('zakat.amount')">
          <FormControl v-model="payForm.amount" type="number" inputmode="decimal" required />
        </FormField>
        <FormField :label="t('zakat.type')">
          <FormControl v-model="payForm.type" :options="payTypes" />
        </FormField>
        <FormField :label="t('zakat.recipient')">
          <FormControl v-model="payForm.recipient_id" :options="recipientOptions" />
        </FormField>
        <FormField v-if="!payForm.recipient_id" :label="t('zakat.recipientName')">
          <FormControl v-model="payForm.recipient_name" :placeholder="t('zakat.recipientNamePlaceholder')" />
        </FormField>
        <FormField :label="t('zakat.notes')">
          <FormControl v-model="payForm.notes" />
        </FormField>
      </CardBoxModal>

      <div v-if="calc" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Summary -->
        <CardBox>
          <h3 class="mb-4 text-lg font-semibold">{{ t('zakat.summary') }}</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-slate-400">{{ t('zakat.netWealth') }}</span>
              <b>{{ fmt(calc.net_wealth ?? calc.total_wealth) }}</b>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-slate-400">
                {{ t('zakat.nisabWithType', { type: t(`zakat.metal.${calc.nisab_type}`) }) }}
              </span>
              <b>{{ fmt(calc.nisab_amount) }}</b>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-slate-400">{{ t('zakat.zakatDue') }}</span>
              <b class="text-emerald-600 dark:text-emerald-400">{{ fmt(calc.zakat_amount) }}</b>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-slate-400">{{ t('zakat.remaining') }}</span>
              <b :class="remaining > 0 ? 'text-amber-500' : 'text-emerald-500'">{{ fmt(remaining) }}</b>
            </div>
            <div class="pt-2">
              <PillTag
                :color="remaining <= 0 ? 'success' : 'warning'"
                :label="remaining <= 0 ? t('zakat.fullyPaid') : t('zakat.paymentPending')"
              />
            </div>
          </div>
          <div class="mt-5">
            <BaseButton
              :icon="mdiCashPlus"
              :label="t('zakat.recordPayment')"
              color="success"
              small
              @click="payModal = true"
            />
          </div>
        </CardBox>

        <!-- Asset breakdown -->
        <CardBox>
          <h3 class="mb-4 text-lg font-semibold">{{ t('zakat.assets') }}</h3>
          <div class="space-y-2 text-sm">
            <div v-for="[label, value] in assetRows" :key="label" class="flex justify-between">
              <span class="text-gray-500 dark:text-slate-400">{{ label }}</span>
              <b>{{ fmt(value) }}</b>
            </div>
            <hr class="border-gray-100 dark:border-slate-700" />
            <div class="flex justify-between">
              <span class="text-gray-500 dark:text-slate-400">{{ t('zakat.debts') }}</span>
              <b class="text-red-500">−{{ fmt(calc.debts) }}</b>
            </div>
          </div>
        </CardBox>

        <!-- Payments -->
        <CardBox has-table>
          <h3 class="px-6 pt-6 pb-2 text-lg font-semibold">{{ t('zakat.payments') }}</h3>
          <table v-if="payments.length">
            <thead>
              <tr>
                <th>{{ t('zakat.date') }}</th>
                <th>{{ t('zakat.recipient') }}</th>
                <th>{{ t('zakat.amount') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in payments" :key="p.id">
                <td :data-label="t('zakat.date')">{{ p.payment_date }}</td>
                <td :data-label="t('zakat.recipient')">{{ p.recipient?.name || p.recipient_name || '—' }}</td>
                <td :data-label="t('zakat.amount')" class="font-semibold">{{ fmt(p.amount) }}</td>
              </tr>
            </tbody>
          </table>
          <CardBoxComponentEmpty v-else :message="t('zakat.noPayments')" />
        </CardBox>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>
