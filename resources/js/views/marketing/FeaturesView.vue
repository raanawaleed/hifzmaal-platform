<script setup>
import LayoutMarketing from '@/layouts/LayoutMarketing.vue'

const cards = [
  {
    icon: '👪',
    arabic: 'الأسرة',
    title: 'Family & household',
    desc: 'Invite your household into one shared workspace with role-based access.',
    tags: ['Owner · Editor · Viewer · Approver'],
    highlight: false,
  },
  {
    icon: '💳',
    arabic: 'الدخل والمصروف',
    title: 'Income & expense management',
    desc: 'Log transactions in seconds and track every account in one place.',
    tags: ['Voice input (Urdu / English)', 'Multi-currency'],
    highlight: false,
  },
  {
    icon: '🧾',
    arabic: 'الفواتير',
    title: 'Utility & bill tracking',
    desc: 'Never miss a due date — recurring bills and fair splitting across the family.',
    tags: ['Auto recurring', 'Bill splitting'],
    highlight: false,
  },
  {
    icon: '🌙',
    arabic: 'الزكاة',
    title: 'Zakat & Islamic finance',
    desc: 'Nisab tracked against live gold and silver rates, calculated on the lunar year.',
    tags: ['Distribution tracking', 'Recipient management'],
    highlight: true,
  },
  {
    icon: '🎯',
    arabic: 'البچت',
    title: 'Savings & goals',
    desc: 'Set a Bachat target and watch the whole family\'s progress toward it.',
    tags: ['Visual progress', 'Dua reminders'],
    highlight: false,
  },
  {
    icon: '✨',
    arabic: 'البصيرة',
    title: 'Smart insights',
    desc: 'A household health score and gentle, Barakah-minded spending tips.',
    tags: ['Health score', 'Barakah tips'],
    highlight: false,
  },
  {
    icon: '📋',
    arabic: 'السجل',
    title: 'Activity log & audit trail',
    desc: 'Every change any family member makes is logged — full transparency, always.',
    tags: ['Per-family history', 'Who changed what, when'],
    highlight: false,
  },
  {
    icon: '🔒',
    arabic: 'الأمان',
    title: 'Bank-grade security',
    desc: 'Two-factor authentication, encrypted backups and no data ever sold.',
    tags: ['TOTP 2FA', 'Daily encrypted backups'],
    highlight: false,
  },
]

const languages = ['🇬🇧 English', '🇵🇰 Urdu', '🇮🇳 Hindi', '🇧🇩 Bangla']
const roadmap = [
  'Halal bank account integration',
  'Halal investment screener',
  'Direct links to trusted Zakat organizations',
  '"Mufti Mode" — an AI advisor for Zakat questions',
]
</script>

<template>
  <LayoutMarketing>
    <section class="mx-auto max-w-4xl px-6 pt-20 pb-14 text-center">
      <h1 class="text-4xl font-bold sm:text-5xl" style="color: #f4eede">Everything your household needs</h1>
      <p class="mx-auto mt-5 max-w-lg text-lg" style="color: #a9bcae">
        Built for the way Muslim families actually manage money — together, transparently, and with Zakat done
        properly.
      </p>
    </section>

    <section class="mx-auto max-w-5xl px-6 pb-20">
      <div class="grid gap-5 sm:grid-cols-2">
        <div
          v-for="card in cards"
          :key="card.title"
          class="rounded-2xl border p-8"
          :style="
            card.highlight
              ? 'border: 1.5px solid #d8b26a; background: linear-gradient(160deg, rgba(216,178,106,.1), rgba(12,44,34,1));'
              : 'border-color: rgba(216,178,106,.14); background: #0c2c22;'
          "
        >
          <div class="flex items-start justify-between">
            <p class="text-2xl">{{ card.icon }}</p>
            <p class="text-sm" style="font-family: 'Amiri', serif; color: #d8b26a">{{ card.arabic }}</p>
          </div>
          <h3 class="mt-4 text-lg font-bold" style="color: #f4eede">{{ card.title }}</h3>
          <p class="mt-3 text-sm leading-relaxed" style="color: #a9bcae">{{ card.desc }}</p>
          <div class="mt-5 flex flex-wrap gap-2">
            <span
              v-for="tag in card.tags"
              :key="tag"
              class="rounded-full px-3 py-1 text-xs"
              style="background: rgba(216, 178, 106, 0.1); color: #c9d6cd"
            >
              {{ tag }}
            </span>
          </div>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-5xl px-6 pb-20">
      <div class="grid gap-5 sm:grid-cols-2">
        <div class="rounded-2xl border p-8" style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22">
          <p class="text-2xl">🌍</p>
          <h3 class="mt-3 text-lg font-bold" style="color: #f4eede">Localization</h3>
          <p class="mt-3 text-sm leading-relaxed" style="color: #a9bcae">HifzMaal speaks your household's language.</p>
          <div class="mt-5 flex flex-wrap gap-2">
            <span
              v-for="lang in languages"
              :key="lang"
              class="rounded-full px-3 py-1 text-xs"
              style="background: rgba(216, 178, 106, 0.1); color: #c9d6cd"
            >
              {{ lang }}
            </span>
          </div>
        </div>

        <div class="rounded-2xl border p-8" style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22">
          <p class="text-2xl">🕌</p>
          <h3 class="mt-3 text-lg font-bold" style="color: #f4eede">On the roadmap</h3>
          <ul class="mt-4 space-y-2 text-sm" style="color: #a9bcae">
            <li v-for="item in roadmap" :key="item" class="flex items-start gap-2">
              <span style="color: #d8b26a">·</span> {{ item }}
            </li>
          </ul>
        </div>
      </div>
    </section>

    <section class="px-6 pb-24 text-center">
      <router-link to="/pricing" class="text-lg font-semibold" style="color: #d8b26a">
        Choose your plan →
      </router-link>
    </section>
  </LayoutMarketing>
</template>
