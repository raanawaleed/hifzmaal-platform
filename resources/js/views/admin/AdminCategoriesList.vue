<script setup>
import { ref, reactive, onMounted } from 'vue'
import { mdiTagMultiple, mdiPlus } from '@mdi/js'
import api from '@/utils/api'
import { useNotificationsStore } from '@/stores/notifications'
import LayoutAdmin from '@/layouts/LayoutAdmin.vue'
import SectionMain from '@/components/SectionMain.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import PillTag from '@/components/PillTag.vue'
import CardBoxComponentEmpty from '@/components/CardBoxComponentEmpty.vue'

const notifications = useNotificationsStore()

const rows = ref([])
const loading = ref(false)
const deleteTarget = ref(null)
const editorOpen = ref(false)
const editing = ref(null)
const errors = ref({})

const form = reactive({ name: '', name_ur: '', type: 'expense', icon: '', color: '#10b981', sort_order: 0 })

const typeOptions = [
  { id: 'expense', label: 'Expense' },
  { id: 'income', label: 'Income' },
]

const load = async () => {
  loading.value = true
  try {
    const response = await api.get('/admin/categories')
    rows.value = response.data.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

const openCreate = () => {
  editing.value = null
  Object.assign(form, { name: '', name_ur: '', type: 'expense', icon: '', color: '#10b981', sort_order: 0 })
  errors.value = {}
  editorOpen.value = true
}

const openEdit = (category) => {
  editing.value = category
  Object.assign(form, {
    name: category.name,
    name_ur: category.name_ur || '',
    type: category.type,
    icon: category.icon || '',
    color: category.color || '#10b981',
    sort_order: category.sort_order ?? 0,
  })
  errors.value = {}
  editorOpen.value = true
}

const save = async () => {
  errors.value = {}
  try {
    const payload = { ...form, name_ur: form.name_ur || null, icon: form.icon || null }
    if (editing.value) {
      await api.put(`/admin/categories/${editing.value.id}`, payload)
      notifications.success('Category updated.')
    } else {
      await api.post('/admin/categories', payload)
      notifications.success('Category created.')
    }
    editorOpen.value = false
    await load()
  } catch (err) {
    const responseErrors = err.response?.data?.errors || {}
    errors.value = Object.fromEntries(
      Object.entries(responseErrors).map(([key, messages]) => [key, messages[0]])
    )
  }
}

const confirmDelete = async () => {
  const category = deleteTarget.value
  deleteTarget.value = null
  try {
    await api.delete(`/admin/categories/${category.id}`)
    notifications.success('Category deleted.')
    await load()
  } catch (err) {
    if (err.response?.status === 422) {
      notifications.error(err.response.data.message)
    }
  }
}
</script>

<template>
  <LayoutAdmin>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTagMultiple" title="System Categories" main>
        <BaseButton :icon="mdiPlus" color="info" label="New category" small @click="openCreate" />
      </SectionTitleLineWithButton>

      <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">
        These default categories are available to every family. Families can add their own
        custom categories on top.
      </p>

      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Urdu</th>
              <th>Type</th>
              <th>Color</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="category in rows" :key="category.id">
              <td data-label="Name">{{ category.name }}</td>
              <td data-label="Urdu">{{ category.name_ur || '—' }}</td>
              <td data-label="Type">
                <PillTag :color="category.type === 'income' ? 'success' : 'warning'" :label="category.type" small />
              </td>
              <td data-label="Color">
                <span class="inline-flex items-center gap-2">
                  <span class="inline-block h-4 w-4 rounded-full border border-gray-200" :style="{ background: category.color || '#ccc' }" />
                  {{ category.color || '—' }}
                </span>
              </td>
              <td class="whitespace-nowrap before:hidden lg:w-1">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" label="Edit" small @click="openEdit(category)" />
                  <BaseButton color="danger" label="Delete" small @click="deleteTarget = category" />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>
        <CardBoxComponentEmpty v-if="!rows.length && !loading" />
      </CardBox>

      <CardBoxModal
        v-model="editorOpen"
        :title="editing ? 'Edit category' : 'New system category'"
        button="info"
        :button-label="editing ? 'Save' : 'Create'"
        has-cancel
        @confirm="save"
      >
        <FormField label="Name" :help="errors.name">
          <FormControl v-model="form.name" placeholder="e.g. Groceries" />
        </FormField>
        <FormField label="Urdu name (optional)" :help="errors.name_ur">
          <FormControl v-model="form.name_ur" placeholder="اردو نام" />
        </FormField>
        <FormField label="Type" :help="errors.type">
          <FormControl v-model="form.type" :options="typeOptions" />
        </FormField>
        <FormField label="Color" :help="errors.color">
          <FormControl v-model="form.color" type="color" />
        </FormField>
      </CardBoxModal>

      <CardBoxModal
        :model-value="!!deleteTarget"
        title="Delete system category?"
        button="danger"
        has-cancel
        @update:model-value="deleteTarget = null"
        @confirm="confirmDelete"
      >
        <p>
          <b>{{ deleteTarget?.name }}</b> will be removed. If any transactions use it,
          the delete will be rejected.
        </p>
      </CardBoxModal>
    </SectionMain>
  </LayoutAdmin>
</template>
