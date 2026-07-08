<script setup>
import { ref, onMounted } from 'vue'
import { mdiAccountGroup, mdiPlus, mdiPencil, mdiTrashCan, mdiEmailSync } from '@mdi/js'
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
import { useNotificationsStore } from '@/stores/notifications'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const familyStore = useFamilyStore()
const notifications = useNotificationsStore()
const rows = ref([])
const loading = ref(false)
const deleteTarget = ref(null)
const resendingId = ref(null)

const roleColors = { owner: 'success', member: 'info', viewer: 'light' }
const invitationColors = { accepted: 'success', pending: 'info', expired: 'warning' }
const invitationLabels = { accepted: 'joined', pending: 'invited', expired: 'invite expired' }

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

const resendInvitation = async (row) => {
  resendingId.value = row.id
  try {
    await fapi.post(`/members/${row.id}/resend-invitation`)
    notifications.success(`Invitation re-sent to ${row.email}.`)
    await load()
  } catch (err) {
    notifications.error(err.response?.data?.message || 'Could not resend the invitation.')
  } finally {
    resendingId.value = null
  }
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
              <th>Invite</th>
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
              <td data-label="Invite">
                <PillTag
                  v-if="row.invitation_status"
                  :color="invitationColors[row.invitation_status] || 'light'"
                  :label="invitationLabels[row.invitation_status] || row.invitation_status"
                  small
                />
                <span v-else class="text-xs text-gray-400 dark:text-slate-500">—</span>
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    v-if="row.invitation_status === 'pending' || row.invitation_status === 'expired'"
                    color="info"
                    :icon="mdiEmailSync"
                    small
                    :disabled="resendingId === row.id"
                    @click="resendInvitation(row)"
                  />
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
