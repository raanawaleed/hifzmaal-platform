<script setup>
import LayoutMarketing from '@/layouts/LayoutMarketing.vue'

const bars = [
  { h: 40, peak: false },
  { h: 62, peak: false },
  { h: 48, peak: false },
  { h: 78, peak: true },
  { h: 56, peak: false },
  { h: 88, peak: true },
  { h: 66, peak: false },
]

const stats = [
  { value: '100%', label: 'Shariah-compliant' },
  { value: '0', label: 'Data ever sold' },
  { value: '4', label: 'Languages supported' },
  { value: 'E2E', label: 'Encrypted & private' },
]
</script>

<template>
  <LayoutMarketing>
    <!-- Hero -->
    <section class="mx-auto max-w-4xl px-6 pt-20 pb-10 text-center sm:pt-28">
      <p class="mb-5 text-[30px]" style="font-family: 'Amiri', serif; color: #d8b26a">حفظ المال</p>

      <div
        class="mx-auto mb-7 inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm"
        style="border-color: rgba(216, 178, 106, 0.25); color: #c9d6cd; background: rgba(216, 178, 106, 0.06)"
      >
        <span class="h-1.5 w-1.5 rounded-full" style="background: #2ea87c" />
        Islamic family finance, reimagined
      </div>

      <h1
        class="text-[42px] font-bold sm:text-[56px] lg:text-[70px]"
        style="color: #f4eede; line-height: 1.03; letter-spacing: -0.02em"
      >
        Guard your family's wealth.
        <span style="color: #d8b26a">Grow its Barakah.</span>
      </h1>

      <p class="mx-auto mt-6 max-w-[560px] text-lg leading-relaxed" style="color: #a9bcae">
        One private home for your household's budgets, bills, savings goals and Zakat — calculated on the lunar
        year, compliant with the Shariah, built for the Ummah.
      </p>

      <div class="mt-9 flex flex-wrap items-center justify-center gap-4">
        <router-link
          to="/register"
          class="rounded-[10px] px-7 py-[14px] text-[15px] font-semibold"
          style="background: #d8b26a; color: #072019; box-shadow: 0 16px 32px rgba(216, 178, 106, 0.25)"
        >
          Begin your Amanah
        </router-link>
        <a
          href="#pillars"
          class="rounded-[10px] border px-7 py-[14px] text-[15px] font-semibold"
          style="border-color: rgba(244, 238, 222, 0.25); color: #f4eede"
        >
          See how it works
        </a>
      </div>
    </section>

    <!-- Dashboard mockup -->
    <section class="mx-auto max-w-[900px] px-6 pt-10 pb-20">
      <div
        class="grid gap-5 rounded-[22px] border p-[22px] sm:grid-cols-[1.4fr_1fr]"
        style="background: #0c2c22; border-color: rgba(216, 178, 106, 0.18); box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5)"
      >
        <div class="rounded-2xl p-6" style="background: #0f3527">
          <p class="text-sm" style="color: #8fb0a0">Household balance</p>
          <p class="mt-2 text-3xl font-bold" style="color: #f4eede">₨ 842,500</p>
          <p class="mt-1 text-sm" style="font-family: 'Amiri', serif; color: #2ea87c">بركة</p>

          <div class="mt-8 flex h-28 items-end gap-3">
            <div
              v-for="(bar, i) in bars"
              :key="i"
              class="flex-1 rounded-t-md"
              :style="{
                height: `${bar.h}%`,
                background: bar.peak ? (i % 2 === 0 ? '#2ea87c' : '#d8b26a') : 'rgba(46,168,124,.55)',
              }"
            />
          </div>
        </div>

        <div class="flex flex-col gap-5">
          <div class="rounded-2xl p-5" style="background: #0f3527">
            <p class="text-sm" style="color: #8fb0a0">Zakat due · 1447 AH</p>
            <p class="mt-1 text-xl font-bold" style="color: #f4eede">₨21,062</p>
            <div class="mt-3 h-2 rounded-full" style="background: rgba(216, 178, 106, 0.15)">
              <div class="h-2 rounded-full" style="width: 64%; background: #d8b26a" />
            </div>
          </div>

          <div class="rounded-2xl p-5" style="background: #0f3527">
            <p class="text-sm" style="color: #8fb0a0">Hajj fund</p>
            <p class="mt-1 text-xl font-bold" style="color: #f4eede">62%</p>
            <div class="mt-3 h-2 rounded-full" style="background: rgba(46, 168, 124, 0.15)">
              <div class="h-2 rounded-full" style="width: 62%; background: #2ea87c" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats strip -->
    <section class="mx-auto max-w-5xl px-6 pb-24">
      <div class="grid grid-cols-2 gap-8 border-t border-b py-10 sm:grid-cols-4" style="border-color: rgba(216, 178, 106, 0.14)">
        <div v-for="stat in stats" :key="stat.label" class="text-center">
          <p class="text-3xl font-bold" style="color: #f4eede">{{ stat.value }}</p>
          <p class="mt-1 text-sm" style="color: #8fb0a0">{{ stat.label }}</p>
        </div>
      </div>
    </section>

    <!-- Feature teaser -->
    <section id="pillars" class="mx-auto max-w-5xl px-6 pb-24">
      <div class="mb-14 text-center">
        <p class="mb-3 text-lg" style="font-family: 'Amiri', serif; color: #d8b26a">حفظ ونماء</p>
        <h2 class="text-3xl font-bold sm:text-4xl" style="color: #f4eede">Built on five pillars of family finance</h2>
        <p class="mx-auto mt-4 max-w-md" style="color: #a9bcae">Every feature guided by the objectives of the Shariah.</p>
      </div>

      <div class="grid gap-5 lg:grid-cols-[1.2fr_1fr]">
        <div
          class="relative overflow-hidden rounded-2xl border p-8"
          style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22"
        >
          <span
            class="pointer-events-none absolute -right-4 -bottom-8 text-[140px] leading-none select-none"
            style="font-family: 'Amiri', serif; color: rgba(216, 178, 106, 0.06)"
          >
            اسرة
          </span>
          <p class="relative text-xs font-semibold tracking-wider uppercase" style="color: #d8b26a">Family & household</p>
          <h3 class="relative mt-3 text-xl font-bold" style="color: #f4eede">One workspace, every role</h3>
          <p class="relative mt-3 max-w-sm text-sm leading-relaxed" style="color: #a9bcae">
            Invite your household with Owner, Editor, Viewer or Approver roles — everyone sees what they should,
            nothing more.
          </p>
        </div>

        <div class="rounded-2xl border p-8" style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22">
          <p class="text-2xl">🌙</p>
          <h3 class="mt-3 text-xl font-bold" style="color: #f4eede">Zakat, done right</h3>
          <p class="mt-3 text-sm leading-relaxed" style="color: #a9bcae">
            Nisab tracked against live gold and silver rates, calculated on the lunar year — not guessed.
          </p>
        </div>
      </div>

      <div class="mt-5 grid gap-5 sm:grid-cols-3">
        <div class="rounded-2xl border p-8" style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22">
          <p class="text-2xl">🎯</p>
          <h3 class="mt-3 text-lg font-bold" style="color: #f4eede">Savings & goals</h3>
          <p class="mt-3 text-sm leading-relaxed" style="color: #a9bcae">Bachat targets with visual progress the whole family can see.</p>
        </div>
        <div class="rounded-2xl border p-8" style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22">
          <p class="text-2xl">🧾</p>
          <h3 class="mt-3 text-lg font-bold" style="color: #f4eede">Bills & utilities</h3>
          <p class="mt-3 text-sm leading-relaxed" style="color: #a9bcae">Recurring reminders and splitting, so nothing is paid twice — or late.</p>
        </div>
        <div class="rounded-2xl border p-8" style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22">
          <p class="text-2xl">✨</p>
          <h3 class="mt-3 text-lg font-bold" style="color: #f4eede">Smart insights</h3>
          <p class="mt-3 text-sm leading-relaxed" style="color: #a9bcae">A household health score and gentle Barakah-minded spending tips.</p>
        </div>
      </div>
    </section>

    <!-- CTA band -->
    <section class="relative overflow-hidden px-6 py-24 text-center" style="background: #061c16">
      <div
        class="pointer-events-none absolute inset-0"
        style="background: radial-gradient(circle at 50% 0%, rgba(216, 178, 106, 0.12), transparent 50%)"
      />
      <div class="relative mx-auto max-w-xl">
        <p class="mb-4 text-2xl" style="font-family: 'Amiri', serif; color: #d8b26a">حفظ المال</p>
        <h2 class="text-3xl font-bold sm:text-4xl" style="color: #f4eede">Your wealth is an Amanah.</h2>
        <p class="mt-4" style="color: #a9bcae">
          Start free, invite your family, and let HifzMaal keep the accounting so you can focus on the Barakah.
        </p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
          <router-link
            to="/pricing"
            class="rounded-[10px] px-7 py-[14px] text-[15px] font-semibold"
            style="background: #d8b26a; color: #072019"
          >
            See plans
          </router-link>
          <router-link
            to="/contact"
            class="rounded-[10px] border px-7 py-[14px] text-[15px] font-semibold"
            style="border-color: rgba(244, 238, 222, 0.25); color: #f4eede"
          >
            Book a walkthrough
          </router-link>
        </div>
      </div>
    </section>
  </LayoutMarketing>
</template>
