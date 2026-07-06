<script setup>
import { ref, onMounted } from 'vue'
import { mdiAccount } from '@mdi/js'
import api from '@/utils/api'
import LayoutAdmin from '@/layouts/LayoutAdmin.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import PillTag from '@/components/PillTag.vue'
import BaseButton from '@/components/BaseButton.vue'

const props = defineProps({ id: { type: [String, Number], required: true } })

const detail = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await api.get(`/admin/users/${props.id}`)
    detail.value = response.data.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <LayoutAdmin>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccount" :title="detail?.user?.name || 'User'" main>
        <BaseButton to="/admin/users" label="Back to users" color="whiteDark" small />
      </SectionTitleLineWithButton>

      <div v-if="detail" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <CardBox>
          <div class="space-y-3 p-4">
            <h3 class="text-lg font-semibold">Profile</h3>
            <p><span class="text-gray-500">Email:</span> {{ detail.user.email }}</p>
            <p><span class="text-gray-500">Locale:</span> {{ detail.user.locale || '—' }}</p>
            <p><span class="text-gray-500">Joined:</span> {{ new Date(detail.user.created_at).toLocaleDateString() }}</p>
            <p class="flex items-center gap-2">
              <span class="text-gray-500">Status:</span>
              <PillTag :color="detail.user.is_active ? 'success' : 'danger'" :label="detail.user.is_active ? 'Active' : 'Suspended'" small />
              <span v-if="detail.suspended_at" class="text-sm text-gray-500">
                since {{ new Date(detail.suspended_at).toLocaleDateString() }}
              </span>
            </p>
            <p v-if="detail.user.roles?.length" class="flex items-center gap-2">
              <span class="text-gray-500">Platform roles:</span>
              <PillTag v-for="role in detail.user.roles" :key="role" color="warning" :label="role" small />
            </p>
          </div>
        </CardBox>

        <CardBox>
          <div class="space-y-3 p-4">
            <h3 class="text-lg font-semibold">Families</h3>
            <div v-if="detail.owned_families.length">
              <p class="mb-1 text-sm font-medium text-gray-500 uppercase">Owns</p>
              <ul class="list-inside list-disc space-y-1">
                <li v-for="f in detail.owned_families" :key="f.id">
                  {{ f.name }} — {{ f.members_count }} members
                </li>
              </ul>
            </div>
            <div v-if="detail.memberships.length">
              <p class="mb-1 text-sm font-medium text-gray-500 uppercase">Member of</p>
              <ul class="list-inside list-disc space-y-1">
                <li v-for="m in detail.memberships" :key="m.family_id">
                  {{ m.family_name || '—' }} — {{ m.role }}
                  <span v-if="!m.is_active" class="text-sm text-red-500">(inactive)</span>
                </li>
              </ul>
            </div>
            <p v-if="!detail.owned_families.length && !detail.memberships.length" class="text-gray-500">
              Not part of any family yet.
            </p>
          </div>
        </CardBox>
      </div>
    </SectionMain>
  </LayoutAdmin>
</template>
