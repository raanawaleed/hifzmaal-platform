<script setup>
import { ref, onMounted } from 'vue'
import { mdiHomeGroup, mdiPlus, mdiPencil, mdiTrashCan, mdiCheckCircle } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import api from '@/utils/api'
import { items } from '@/utils/familyApi'
import { useFamilyStore } from '@/stores/family'

const familyStore = useFamilyStore()
const rows = ref([])
const loading = ref(false)
const deleteTarget = ref(null)

const load = async () => {
  loading.value = true
  try {
    rows.value = items(await api.get('/families'))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const confirmDelete = async () => {
  await api.delete(`/families/${deleteTarget.value.id}`)
  deleteTarget.value = null
  await familyStore.loadUserFamilies()
  await load()
}

const switchTo = (row) => {
  familyStore.setFamilyId(row.id)
}
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiHomeGroup" title="Families" main>
        <BaseButton to="/families/create" :icon="mdiPlus" label="New Family" color="success" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBoxModal
        :model-value="!!deleteTarget"
        title="Delete family?"
        button="danger"
        button-label="Delete"
        has-cancel
        @update:model-value="deleteTarget = null"
        @confirm="confirmDelete"
      >
        <p>
          Delete <b>{{ deleteTarget?.name }}</b> and all its data? This cannot be undone.
        </p>
      </CardBoxModal>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>Family</th>
              <th>Currency</th>
              <th>Members</th>
              <th>Balance</th>
              <th>Active</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="Family" class="font-medium">{{ row.name }}</td>
              <td data-label="Currency">{{ row.currency }}</td>
              <td data-label="Members">{{ row.active_members ?? '—' }}</td>
              <td data-label="Balance" class="font-semibold">
                {{ Number(row.total_balance || 0).toLocaleString() }}
              </td>
              <td data-label="Active">
                <PillTag
                  v-if="row.id === familyStore.currentFamilyId"
                  color="success"
                  label="Active"
                  small
                  :icon="mdiCheckCircle"
                />
                <BaseButton v-else label="Switch" small color="whiteDark" @click="switchTo(row)" />
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiPencil" small :to="`/families/${row.id}/edit`" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteTarget = row" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No families yet — create your first family" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
