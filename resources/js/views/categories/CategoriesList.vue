<script setup>
import { ref, onMounted } from 'vue'
import { mdiTagMultiple, mdiPlus, mdiPencil, mdiTrashCan } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const rows = ref([])
const loading = ref(false)
const deleteTarget = ref(null)

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    rows.value = items(await fapi.get('/categories'))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const confirmDelete = async () => {
  await fapi.delete(`/categories/${deleteTarget.value.id}`)
  deleteTarget.value = null
  await load()
}
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTagMultiple" title="Categories" main>
        <BaseButton to="/categories/create" :icon="mdiPlus" label="New Category" color="success" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBoxModal
        :model-value="!!deleteTarget"
        title="Delete category?"
        button="danger"
        button-label="Delete"
        has-cancel
        @update:model-value="deleteTarget = null"
        @confirm="confirmDelete"
      >
        <p>Delete <b>{{ deleteTarget?.name }}</b>? Transactions using it keep their history.</p>
      </CardBoxModal>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>Category</th>
              <th>Urdu Name</th>
              <th>Type</th>
              <th>Color</th>
              <th>Halal</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="Category" class="font-medium">{{ row.name }}</td>
              <td data-label="Urdu Name">{{ row.name_ur || '—' }}</td>
              <td data-label="Type">
                <PillTag :color="row.type === 'income' ? 'success' : 'danger'" :label="row.type" small />
              </td>
              <td data-label="Color">
                <span
                  class="inline-block h-5 w-5 rounded-full border border-gray-200 align-middle dark:border-slate-600"
                  :style="{ backgroundColor: row.color || '#e5e7eb' }"
                />
              </td>
              <td data-label="Halal">{{ row.is_halal ? 'Yes' : 'No' }}</td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiPencil" small :to="`/categories/${row.id}/edit`" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteTarget = row" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No categories yet" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
