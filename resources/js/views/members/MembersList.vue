<script setup>
import { ref, onMounted } from 'vue'
import { mdiAccountGroup, mdiPlus, mdiPencil, mdiTrashCan } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { useFamilyStore } from '@/stores/family'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const familyStore = useFamilyStore()
const rows = ref([])
const loading = ref(false)
const deleteTarget = ref(null)

const roleColors = { owner: 'success', member: 'info', viewer: 'light' }

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    rows.value = items(await fapi.get('/members'))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const confirmDelete = async () => {
  await fapi.delete(`/members/${deleteTarget.value.id}`)
  deleteTarget.value = null
  await load()
}

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccountGroup" title="Family Members" main>
        <BaseButton v-if="familyStore.canEdit" to="/family-members/create" :icon="mdiPlus" label="Add Member" color="success" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBoxModal
        :model-value="!!deleteTarget"
        title="Remove member?"
        button="danger"
        button-label="Remove"
        has-cancel
        @update:model-value="deleteTarget = null"
        @confirm="confirmDelete"
      >
        <p>Remove <b>{{ deleteTarget?.name }}</b> from the family?</p>
      </CardBoxModal>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th />
              <th>Name</th>
              <th>Relationship</th>
              <th>Role</th>
              <th>Spending Limit</th>
              <th>Status</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td class="border-b-0 before:hidden lg:w-6">
                <UserAvatar :username="row.name" class="mx-auto h-24 w-24 lg:h-6 lg:w-6" />
              </td>
              <td data-label="Name" class="font-medium">{{ row.name }}</td>
              <td data-label="Relationship">{{ row.relationship }}</td>
              <td data-label="Role">
                <PillTag :color="roleColors[row.role] || 'info'" :label="row.role" small />
              </td>
              <td data-label="Spending Limit">{{ fmt(row.spending_limit) }}</td>
              <td data-label="Status">
                <PillTag
                  :color="row.is_active ? 'success' : 'danger'"
                  :label="row.is_active ? 'active' : 'inactive'"
                  small
                />
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiPencil" small :to="`/family-members/${row.id}/edit`" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteTarget = row" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No members yet — add your family members" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
