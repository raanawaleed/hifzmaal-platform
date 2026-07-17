<script setup>
import { ref, onMounted } from 'vue'
import { mdiEmailOutline, mdiSend } from '@mdi/js'
import api from '@/utils/api'
import { useNotificationsStore } from '@/stores/notifications'
import LayoutAdmin from '@/layouts/LayoutAdmin.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import BaseButton from '@/components/BaseButton.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import PillTag from '@/components/PillTag.vue'

const props = defineProps({ id: { type: [String, Number], required: true } })

const notifications = useNotificationsStore()

const statusMeta = {
  new: { color: 'info', label: 'New' },
  in_progress: { color: 'warning', label: 'In progress' },
  resolved: { color: 'success', label: 'Resolved' },
  closed: { color: 'light', label: 'Closed' },
}

const statusOptions = [
  { id: 'new', label: 'New' },
  { id: 'in_progress', label: 'In progress' },
  { id: 'resolved', label: 'Resolved' },
  { id: 'closed', label: 'Closed' },
]

const inquiry = ref(null)
const loading = ref(true)
const statusValue = ref('')
const statusSaving = ref(false)
const replyBody = ref('')
const replySending = ref(false)

onMounted(async () => {
  try {
    const response = await api.get(`/admin/contact-messages/${props.id}`)
    inquiry.value = response.data.data
    statusValue.value = inquiry.value.status
  } finally {
    loading.value = false
  }
})

const changeStatus = async () => {
  if (!inquiry.value || statusValue.value === inquiry.value.status) {
    return
  }
  statusSaving.value = true
  try {
    const response = await api.put(`/admin/contact-messages/${props.id}`, { status: statusValue.value })
    inquiry.value.status = response.data.data?.status ?? statusValue.value
    notifications.success(response.data.message)
  } catch (err) {
    statusValue.value = inquiry.value.status
    if (err.response?.status === 422) {
      notifications.error(err.response.data.message)
    }
  } finally {
    statusSaving.value = false
  }
}

const sendReply = async () => {
  if (!replyBody.value.trim()) {
    return
  }
  replySending.value = true
  try {
    const response = await api.post(`/admin/contact-messages/${props.id}/reply`, { body: replyBody.value })
    inquiry.value.replies.push(response.data.data)
    if (inquiry.value.status === 'new') {
      inquiry.value.status = 'in_progress'
      statusValue.value = 'in_progress'
    }
    replyBody.value = ''
    notifications.success(response.data.message)
  } catch (err) {
    if (err.response?.status === 422) {
      notifications.error(err.response.data.message)
    }
  } finally {
    replySending.value = false
  }
}
</script>

<template>
  <LayoutAdmin>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiEmailOutline" :title="inquiry?.subject || 'Inquiry'" main>
        <BaseButton to="/admin/inquiries" label="Back to inquiries" color="whiteDark" small />
      </SectionTitleLineWithButton>

      <div v-if="inquiry" class="space-y-6">
        <CardBox>
          <div class="space-y-3 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p class="font-semibold">{{ inquiry.name }}</p>
                <p class="text-sm text-gray-500">{{ inquiry.email }}</p>
              </div>
              <div class="flex items-center gap-2">
                <PillTag
                  :color="statusMeta[inquiry.status]?.color || 'light'"
                  :label="statusMeta[inquiry.status]?.label || inquiry.status"
                  small
                />
                <FormControl v-model="statusValue" :options="statusOptions" :disabled="statusSaving" class="w-40" @change="changeStatus" />
              </div>
            </div>
            <p class="text-sm text-gray-500">
              Received {{ new Date(inquiry.created_at).toLocaleString() }}
            </p>
            <p class="whitespace-pre-wrap border-t border-gray-100 pt-3 dark:border-slate-700">{{ inquiry.message }}</p>
          </div>
        </CardBox>

        <CardBox>
          <div class="space-y-4 p-4">
            <h3 class="text-lg font-semibold">Replies</h3>
            <p v-if="!inquiry.replies.length" class="text-gray-500">No replies yet.</p>
            <div
              v-for="reply in inquiry.replies"
              :key="reply.id"
              class="rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-slate-700 dark:bg-slate-800"
            >
              <div class="mb-1 flex items-center justify-between text-sm text-gray-500">
                <span class="font-medium">{{ reply.admin_name }}</span>
                <span>{{ new Date(reply.created_at).toLocaleString() }}</span>
              </div>
              <p class="whitespace-pre-wrap">{{ reply.body }}</p>
            </div>

            <form class="space-y-3 border-t border-gray-100 pt-4 dark:border-slate-700" @submit.prevent="sendReply">
              <FormField label="Reply" help="Your reply will be emailed to the submitter.">
                <FormControl v-model="replyBody" type="textarea" placeholder="Write your reply…" />
              </FormField>
              <BaseButton
                type="submit"
                color="info"
                :icon="mdiSend"
                :label="replySending ? 'Sending…' : 'Send reply'"
                :disabled="replySending || !replyBody.trim()"
              />
            </form>
          </div>
        </CardBox>
      </div>
    </SectionMain>
  </LayoutAdmin>
</template>
