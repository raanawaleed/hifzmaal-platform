<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { mdiAccountHeart, mdiPlus, mdiPencil } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import { useFamilyStore } from '@/stores/family'
import { useFamilyApi, items } from '@/utils/familyApi'

const { t, te, locale } = useI18n()
const fapi = useFamilyApi()
const familyStore = useFamilyStore()
const rows = ref([])
const loading = ref(false)

// The 8 asnaf from Surah At-Tawbah 9:60 — translated per locale, with the
// raw category id as fallback for anything unexpected from the API.
const categoryLabel = (category) =>
  te(`zakat.categories.${category}`) ? t(`zakat.categories.${category}`) : category

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    rows.value = items(await fapi.get('/zakat/recipients'))
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccountHeart" :title="t('zakat.recipientsTitle')" main>
        <BaseButtons>
          <BaseButton to="/zakat" :label="t('zakat.backToZakat')" color="whiteDark" rounded-full small />
          <BaseButton v-if="familyStore.canEdit" to="/zakat/recipients/create" :icon="mdiPlus" :label="t('zakat.newRecipient')" color="success" rounded-full small />
        </BaseButtons>
      </SectionTitleLineWithButton>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>{{ t('zakat.name') }}</th>
              <th>{{ t('zakat.category') }}</th>
              <th>{{ t('zakat.contact') }}</th>
              <th>{{ t('zakat.totalReceived') }}</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td :data-label="t('zakat.name')" class="font-medium">{{ row.name }}</td>
              <td :data-label="t('zakat.category')">
                <PillTag color="info" :label="categoryLabel(row.category)" small />
              </td>
              <td :data-label="t('zakat.contact')">{{ row.contact || '—' }}</td>
              <td :data-label="t('zakat.totalReceived')" class="font-semibold">
                {{ Number(row.total_received || 0).toLocaleString(locale) }}
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiPencil" small :to="`/zakat/recipients/${row.id}/edit`" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" :message="t('zakat.emptyRecipients')" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
