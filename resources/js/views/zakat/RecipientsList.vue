<script setup>
import { ref, onMounted } from 'vue'
import { mdiAccountHeart, mdiPlus, mdiPencil } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const rows = ref([])
const loading = ref(false)

const categoryLabels = {
  fuqara: 'The Poor (الفقراء)',
  masakin: 'The Needy (المساكين)',
  amilin: 'Administrators (العاملين)',
  muallaf: 'New Muslims (المؤلفة قلوبهم)',
  riqab: 'Freeing Captives (في الرقاب)',
  gharimin: 'In Debt (الغارمين)',
  fisabilillah: 'In Allah\'s Cause (في سبيل الله)',
  ibnus_sabil: 'Travelers (ابن السبيل)',
}

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
      <SectionTitleLineWithButton :icon="mdiAccountHeart" title="Zakat Recipients" main>
        <BaseButtons>
          <BaseButton to="/zakat" label="Back to Zakat" color="whiteDark" rounded-full small />
          <BaseButton to="/zakat/recipients/create" :icon="mdiPlus" label="New Recipient" color="success" rounded-full small />
        </BaseButtons>
      </SectionTitleLineWithButton>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>Name</th>
              <th>Category</th>
              <th>Contact</th>
              <th>Total Received</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="Name" class="font-medium">{{ row.name }}</td>
              <td data-label="Category">
                <PillTag color="info" :label="categoryLabels[row.category] || row.category" small />
              </td>
              <td data-label="Contact">{{ row.contact || '—' }}</td>
              <td data-label="Total Received" class="font-semibold">
                {{ Number(row.total_received || 0).toLocaleString() }}
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiPencil" small :to="`/zakat/recipients/${row.id}/edit`" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No recipients yet — add people or organizations you give Zakat to" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
