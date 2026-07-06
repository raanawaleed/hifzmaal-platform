<script setup>
import { ref, onMounted } from 'vue'
import { mdiWallet, mdiPlus, mdiPencil, mdiTrashCan } from '@mdi/js'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import PillTag from '@/components/PillTag.vue'
import { useFamilyStore } from '@/stores/family'
import { useFamilyApi, items } from '@/utils/familyApi'

const fapi = useFamilyApi()
const familyStore = useFamilyStore()
const rows = ref([])
const loading = ref(false)
const deleteTarget = ref(null)

const typeColors = {
  cash: 'success',
  bank: 'info',
  wallet: 'warning',
  savings: 'success',
  investment: 'danger',
}

const load = async () => {
  if (!fapi.hasFamily()) return
  loading.value = true
  try {
    rows.value = items(await fapi.get('/accounts'))
  } finally {
    loading.value = false
  }
}

onMounted(load)

const confirmDelete = async () => {
  await fapi.delete(`/accounts/${deleteTarget.value.id}`)
  deleteTarget.value = null
  await load()
}

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiWallet" title="Accounts" main>
        <BaseButton v-if="familyStore.canEdit" to="/accounts/create" :icon="mdiPlus" label="New Account" color="success" rounded-full small />
      </SectionTitleLineWithButton>

      <CardBoxModal
        :model-value="!!deleteTarget"
        title="Delete account?"
        button="danger"
        button-label="Delete"
        has-cancel
        @update:model-value="deleteTarget = null"
        @confirm="confirmDelete"
      >
        <p>
          Delete <b>{{ deleteTarget?.name }}</b
          >? This cannot be undone.
        </p>
      </CardBoxModal>

      <CardBox has-table>
        <table v-if="rows.length">
          <thead>
            <tr>
              <th>Name</th>
              <th>Type</th>
              <th>Bank</th>
              <th>Balance</th>
              <th>In Zakat</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td data-label="Name" class="font-medium">{{ row.name }}</td>
              <td data-label="Type">
                <PillTag :color="typeColors[row.type] || 'info'" :label="row.type" small />
              </td>
              <td data-label="Bank">{{ row.bank_name || '—' }}</td>
              <td data-label="Balance" class="font-semibold">
                {{ fmt(row.current_balance ?? row.balance) }} {{ row.currency }}
              </td>
              <td data-label="In Zakat">{{ row.include_in_zakat ? 'Yes' : 'No' }}</td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiPencil" small :to="`/accounts/${row.id}/edit`" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteTarget = row" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-else-if="!loading" message="No accounts yet — create your first account" />
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
