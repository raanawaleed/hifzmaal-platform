<script setup>
import { reactive, ref } from 'vue'
import LayoutMarketing from '@/layouts/LayoutMarketing.vue'
import { useAuthStore } from '@/stores/auth'
import api, { ensureCsrfCookie } from '@/utils/api'
import { extractErrors } from '@/utils/familyApi'

const authStore = useAuthStore()

// Prefill for signed-in visitors so they don't retype what we already know.
const form = reactive({
  name: authStore.user?.name || '',
  email: authStore.user?.email || '',
  subject: '',
  message: '',
})

const loading = ref(false)
const sent = ref(false)
const errors = ref({})
const bannerError = ref('')

const inputStyle = 'background: rgba(244,238,222,.04); border-color: rgba(216,178,106,.2); color: #f4eede;'
const inputErrorStyle = 'background: rgba(244,238,222,.04); border-color: rgba(232,122,122,.55); color: #f4eede;'

const submit = async () => {
  loading.value = true
  errors.value = {}
  bannerError.value = ''
  try {
    // A fresh visitor has no XSRF-TOKEN cookie yet; Sanctum's stateful
    // middleware would 419 the POST without it.
    await ensureCsrfCookie()
    await api.post('/contact', form)
    sent.value = true
  } catch (err) {
    const status = err.response?.status
    if (status === 429) {
      bannerError.value = "You've sent a few messages in a short time — please wait a couple of minutes and try again."
    } else if (status === 422) {
      errors.value = extractErrors(err)
    } else {
      bannerError.value = err.response?.data?.message || "We couldn't send your message. Please try again, or email us directly."
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <LayoutMarketing>
    <section class="mx-auto max-w-5xl px-6 py-20">
      <div class="grid gap-14 lg:grid-cols-2">
        <div>
          <h1 class="text-4xl font-bold" style="color: #f4eede">Let's protect your wealth together</h1>
          <p class="mt-5 text-lg leading-relaxed" style="color: #a9bcae">
            Questions about Zakat calculations, family workspaces, or getting your masjid set up? We'd love to hear
            from you.
          </p>

          <div class="mt-10 space-y-6">
            <div class="flex items-center gap-4">
              <span class="text-xl">✉️</span>
              <a href="mailto:salam@hifzmaal.app" class="text-sm" style="color: #c9d6cd">salam@hifzmaal.app</a>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-xl">💬</span>
              <span class="text-sm" style="color: #c9d6cd">WhatsApp — Urdu & English</span>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-xl">🕌</span>
              <span class="text-sm" style="color: #c9d6cd">Karachi · Lahore · Dubai · London</span>
            </div>
          </div>
        </div>

        <div
          class="rounded-2xl border p-8"
          style="border-color: rgba(216, 178, 106, 0.14); background: #0c2c22"
        >
          <div v-if="sent">
            <div class="text-3xl">🌙</div>
            <h2 class="mt-4 text-xl font-bold" style="color: #f4eede">JazakAllah khair — message received</h2>
            <p class="mt-3 text-sm leading-relaxed" style="color: #a9bcae">
              Thanks — we've emailed you a confirmation and will reply soon.
            </p>
            <button
              type="button"
              class="mt-6 rounded-[10px] border px-5 py-3 text-sm font-semibold"
              style="border-color: rgba(216, 178, 106, 0.35); color: #d8b26a"
              @click="sent = false; form.subject = ''; form.message = ''"
            >
              Send another message
            </button>
          </div>

          <form v-else @submit.prevent="submit">
            <div
              v-if="bannerError"
              class="mb-5 rounded-[10px] border px-4 py-3 text-sm"
              style="border-color: rgba(232, 122, 122, 0.4); background: rgba(232, 122, 122, 0.08); color: #f1b8b8"
            >
              {{ bannerError }}
            </div>

            <div>
              <label for="contact-name" class="mb-2 block text-xs font-semibold tracking-wide uppercase" style="color: #a9bcae">Name</label>
              <input
                id="contact-name"
                v-model="form.name"
                type="text"
                name="name"
                autocomplete="name"
                required
                class="w-full rounded-[10px] border px-4 py-3 text-sm outline-none"
                :style="errors.name ? inputErrorStyle : inputStyle"
              />
              <p v-if="errors.name" class="mt-2 text-xs" style="color: #f1b8b8">{{ errors.name }}</p>
            </div>

            <div class="mt-5">
              <label for="contact-email" class="mb-2 block text-xs font-semibold tracking-wide uppercase" style="color: #a9bcae">Email</label>
              <input
                id="contact-email"
                v-model="form.email"
                type="email"
                name="email"
                autocomplete="email"
                required
                class="w-full rounded-[10px] border px-4 py-3 text-sm outline-none"
                :style="errors.email ? inputErrorStyle : inputStyle"
              />
              <p v-if="errors.email" class="mt-2 text-xs" style="color: #f1b8b8">{{ errors.email }}</p>
            </div>

            <div class="mt-5">
              <label for="contact-subject" class="mb-2 block text-xs font-semibold tracking-wide uppercase" style="color: #a9bcae">Subject</label>
              <input
                id="contact-subject"
                v-model="form.subject"
                type="text"
                name="subject"
                required
                class="w-full rounded-[10px] border px-4 py-3 text-sm outline-none"
                :style="errors.subject ? inputErrorStyle : inputStyle"
              />
              <p v-if="errors.subject" class="mt-2 text-xs" style="color: #f1b8b8">{{ errors.subject }}</p>
            </div>

            <div class="mt-5">
              <label for="contact-message" class="mb-2 block text-xs font-semibold tracking-wide uppercase" style="color: #a9bcae">Message</label>
              <textarea
                id="contact-message"
                v-model="form.message"
                name="message"
                rows="5"
                required
                class="w-full resize-none rounded-[10px] border px-4 py-3 text-sm outline-none"
                :style="errors.message ? inputErrorStyle : inputStyle"
              />
              <p v-if="errors.message" class="mt-2 text-xs" style="color: #f1b8b8">{{ errors.message }}</p>
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="mt-6 w-full rounded-[10px] py-3 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
              style="background: #d8b26a; color: #072019"
            >
              {{ loading ? 'Sending…' : 'Send message' }}
            </button>
          </form>
        </div>
      </div>
    </section>
  </LayoutMarketing>
</template>
