<script setup>
import LayoutMarketing from '@/layouts/LayoutMarketing.vue'

const items = [
  {
    icon: '🔒',
    title: 'End-to-end encryption',
    desc: 'Data is encrypted in transit (TLS) and at rest. Backups are encrypted too, so a stolen disk reveals nothing.',
  },
  {
    icon: '🔑',
    title: 'Two-factor authentication',
    desc: 'Protect your account with a TOTP authenticator app (Google Authenticator, Authy, etc.) and one-time recovery codes.',
  },
  {
    icon: '🚫',
    title: 'No data selling — ever',
    desc: 'Your household\'s financial data is never sold, shared with advertisers, or used to train external models. Full stop.',
  },
  {
    icon: '☁️',
    title: 'Cloud & local backups',
    desc: 'Automated daily backups, monitored for failure, so your family\'s financial history is never one bad day from gone.',
  },
]
</script>

<template>
  <LayoutMarketing>
    <section class="mx-auto max-w-3xl px-6 pt-20 pb-14 text-center">
      <h1 class="text-4xl font-bold sm:text-5xl" style="color: #f4eede">Privacy is a trust we keep</h1>
      <p class="mx-auto mt-5 max-w-md text-lg" style="color: #a9bcae">
        An Amanah isn't just about money — it's about how carefully you're trusted with it.
      </p>
    </section>

    <section class="mx-auto max-w-4xl px-6 pb-20">
      <div class="grid gap-5 sm:grid-cols-2">
        <div v-for="item in items" :key="item.title" class="rounded-2xl border p-8" style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22">
          <p class="text-2xl">{{ item.icon }}</p>
          <h3 class="mt-3 text-lg font-bold" style="color: #f4eede">{{ item.title }}</h3>
          <p class="mt-3 text-sm leading-relaxed" style="color: #a9bcae">{{ item.desc }}</p>
        </div>
      </div>
    </section>

    <section class="relative overflow-hidden px-6 py-24 text-center" style="background: #061c16">
      <div
        class="pointer-events-none absolute inset-0"
        style="background: radial-gradient(circle at 50% 0%, rgba(216, 178, 106, 0.1), transparent 50%)"
      />
      <div class="relative mx-auto max-w-2xl">
        <p class="text-2xl leading-relaxed sm:text-3xl" style="font-family: 'Amiri', serif; color: #d8b26a">
          لا ضرر ولا ضرار
        </p>
        <p class="mt-4 text-sm" style="color: #8fb0a0">"No harm shall be inflicted or reciprocated" — a foundational hadith of Islamic law.</p>
        <p class="mx-auto mt-6 max-w-lg text-sm leading-relaxed" style="color: #a9bcae">
          We build HifzMaal the same way we'd want our own family's finances handled: nothing sold, nothing careless,
          nothing hidden. Security isn't a feature we bolted on — it's the reason the app is trustworthy enough to
          hold a family's Amanah in the first place.
        </p>
      </div>
    </section>
  </LayoutMarketing>
</template>
