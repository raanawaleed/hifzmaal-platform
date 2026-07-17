<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  mdiHomeGroup, mdiWallet, mdiAccountGroup, mdiCheckCircle,
  mdiArrowRight, mdiPlus,
} from '@mdi/js'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseIcon from '@/components/BaseIcon.vue'
import NotificationBarInCard from '@/components/NotificationBarInCard.vue'
import { useFamilyStore } from '@/stores/family'
import { useFamilyApi, extractErrors } from '@/utils/familyApi'
import api from '@/utils/api'

const router = useRouter()
const familyStore = useFamilyStore()
const fapi = useFamilyApi()

const step = ref(1)
const loading = ref(false)
const errors = ref({})

const steps = [
  { id: 1, label: 'Family', icon: mdiHomeGroup },
  { id: 2, label: 'Account', icon: mdiWallet },
  { id: 3, label: 'Invite', icon: mdiAccountGroup },
  { id: 4, label: 'Done', icon: mdiCheckCircle },
]

// Step 1 — family
const familyForm = reactive({ name: '', currency: 'USD', locale: 'en' })
const currencies = ['USD', 'PKR', 'EUR', 'GBP', 'SAR', 'AED', 'INR', 'BDT']
const locales = [
  { id: 'en', label: 'English' },
  { id: 'ur', label: 'اردو (Urdu)' },
  { id: 'hi', label: 'हिन्दी (Hindi)' },
  { id: 'bn', label: 'বাংলা (Bangla)' },
]

const createFamily = async () => {
  loading.value = true
  errors.value = {}
  try {
    const res = await api.post('/families', familyForm)
    const family = res.data.data
    familyStore.families.push(family)
    familyStore.setFamilyId(family.id)
    step.value = 2
  } catch (err) {
    errors.value = extractErrors(err)
  } finally {
    loading.value = false
  }
}

// Step 2 — first account. Defaults to 'cash' since it's the only type with
// no other required field — 'bank' needs bank_name (see StoreAccountRequest).
const accountForm = reactive({ name: '', type: 'cash', initial_balance: '0', bank_name: '' })
const accountTypes = [
  { id: 'cash', label: 'Cash' },
  { id: 'bank', label: 'Bank Account' },
  { id: 'wallet', label: 'Mobile Wallet' },
  { id: 'savings', label: 'Savings' },
  { id: 'investment', label: 'Investment' },
]

const createAccount = async () => {
  loading.value = true
  errors.value = {}
  try {
    const payload = { ...accountForm }
    if (payload.type !== 'bank') delete payload.bank_name
    await fapi.post('/accounts', payload)
    step.value = 3
  } catch (err) {
    errors.value = extractErrors(err)
  } finally {
    loading.value = false
  }
}

// Step 3 — invite members (optional, can add several, or skip)
const memberDraft = reactive({ name: '', email: '', relationship: 'spouse', role: 'member' })
const relationships = [
  { id: 'spouse', label: 'Spouse' },
  { id: 'son', label: 'Son' },
  { id: 'daughter', label: 'Daughter' },
  { id: 'father', label: 'Father' },
  { id: 'mother', label: 'Mother' },
  { id: 'brother', label: 'Brother' },
  { id: 'sister', label: 'Sister' },
  { id: 'dependent', label: 'Dependent' },
]
const roles = [
  { id: 'member', label: 'Member' },
  { id: 'viewer', label: 'Viewer' },
]
const invited = ref([])

const addMember = async () => {
  loading.value = true
  errors.value = {}
  try {
    await fapi.post('/members', memberDraft)
    invited.value.push({ ...memberDraft })
    memberDraft.name = ''
    memberDraft.email = ''
  } catch (err) {
    errors.value = extractErrors(err)
  } finally {
    loading.value = false
  }
}

const finish = () => router.push('/dashboard')
const canSubmitMember = computed(() => memberDraft.name.trim().length > 1)
</script>

<template>
  <LayoutGuest>
    <SectionFullScreen v-slot="{ cardClass }" bg="emerald">
      <CardBox :class="[cardClass, '!max-w-xl']">
        <!-- Step indicator -->
        <div class="mb-6 flex items-center justify-center gap-2">
          <template v-for="(s, i) in steps" :key="s.id">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
              :class="step >= s.id ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-400 dark:bg-slate-700'"
            >
              <BaseIcon v-if="step > s.id" :path="mdiCheckCircle" size="16" />
              <span v-else>{{ s.id }}</span>
            </div>
            <div v-if="i < steps.length - 1" class="h-0.5 w-6" :class="step > s.id ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-slate-700'" />
          </template>
        </div>

        <NotificationBarInCard v-if="errors._message" color="danger">
          {{ errors._message }}
        </NotificationBarInCard>

        <!-- Step 1: Family -->
        <template v-if="step === 1">
          <h1 class="mb-1 text-xl font-bold">Create your family workspace</h1>
          <p class="mb-6 text-sm text-gray-500 dark:text-slate-400">
            This is the shared space where you'll track accounts, bills, and Zakat together.
          </p>
          <FormField label="Family name" :help="errors.name">
            <FormControl v-model="familyForm.name" placeholder="e.g. Raza Family" />
          </FormField>
          <FormField label="Currency" :help="errors.currency">
            <FormControl v-model="familyForm.currency" :options="currencies.map((c) => ({ id: c, label: c }))" />
          </FormField>
          <FormField label="Preferred language" :help="errors.locale">
            <FormControl v-model="familyForm.locale" :options="locales" />
          </FormField>
          <BaseButtons>
            <BaseButton
              color="success"
              :icon="mdiArrowRight"
              :label="loading ? 'Creating…' : 'Continue'"
              :disabled="loading || !familyForm.name"
              @click="createFamily"
            />
          </BaseButtons>
        </template>

        <!-- Step 2: First account -->
        <template v-else-if="step === 2">
          <h1 class="mb-1 text-xl font-bold">Add your first account</h1>
          <p class="mb-6 text-sm text-gray-500 dark:text-slate-400">
            A bank account, cash, or wallet — you can add more any time.
          </p>
          <FormField label="Account name" :help="errors.name">
            <FormControl v-model="accountForm.name" placeholder="e.g. Main Checking" />
          </FormField>
          <FormField label="Type" :help="errors.type">
            <FormControl v-model="accountForm.type" :options="accountTypes" />
          </FormField>
          <FormField v-if="accountForm.type === 'bank'" label="Bank name" :help="errors.bank_name">
            <FormControl v-model="accountForm.bank_name" placeholder="e.g. HBL" />
          </FormField>
          <FormField label="Starting balance" :help="errors.initial_balance">
            <FormControl v-model="accountForm.initial_balance" type="number" step="0.01" min="0" />
          </FormField>
          <BaseButtons>
            <BaseButton
              color="success"
              :icon="mdiArrowRight"
              :label="loading ? 'Saving…' : 'Continue'"
              :disabled="loading || !accountForm.name || (accountForm.type === 'bank' && !accountForm.bank_name)"
              @click="createAccount"
            />
            <BaseButton color="whiteDark" outline label="Skip" @click="step = 3" />
          </BaseButtons>
        </template>

        <!-- Step 3: Invite members -->
        <template v-else-if="step === 3">
          <h1 class="mb-1 text-xl font-bold">Invite your family (optional)</h1>
          <p class="mb-6 text-sm text-gray-500 dark:text-slate-400">
            Add a spouse, child, or dependent now, or skip and do this later from Members.
          </p>

          <div v-if="invited.length" class="mb-4 flex flex-wrap gap-2">
            <span
              v-for="(m, i) in invited"
              :key="i"
              class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"
            >
              {{ m.name }} ({{ m.relationship }})
            </span>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <FormField label="Name" :help="errors.name">
              <FormControl v-model="memberDraft.name" placeholder="e.g. Fatima" />
            </FormField>
            <FormField label="Email (optional)" :help="errors.email">
              <FormControl v-model="memberDraft.email" type="email" placeholder="for an invite link" />
            </FormField>
            <FormField label="Relationship" :help="errors.relationship">
              <FormControl v-model="memberDraft.relationship" :options="relationships" />
            </FormField>
            <FormField label="Role" :help="errors.role">
              <FormControl v-model="memberDraft.role" :options="roles" />
            </FormField>
          </div>

          <BaseButtons>
            <BaseButton
              color="info"
              outline
              :icon="mdiPlus"
              :label="loading ? 'Adding…' : 'Add & invite'"
              :disabled="loading || !canSubmitMember"
              @click="addMember"
            />
            <BaseButton color="success" :icon="mdiArrowRight" label="Continue" @click="step = 4" />
          </BaseButtons>
        </template>

        <!-- Step 4: Done -->
        <template v-else>
          <div class="py-4 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40">
              <BaseIcon :path="mdiCheckCircle" size="32" />
            </div>
            <h1 class="mb-2 text-xl font-bold">You're all set!</h1>
            <p class="mb-6 text-sm text-gray-500 dark:text-slate-400">
              {{ familyForm.name }} is ready. Start tracking transactions, set a budget, or
              calculate this year's Zakat whenever you're ready.
            </p>
            <BaseButtons class="justify-center">
              <BaseButton color="success" label="Go to Dashboard" @click="finish" />
            </BaseButtons>
          </div>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
