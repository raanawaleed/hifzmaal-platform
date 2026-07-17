import { defineStore } from 'pinia'
import axios from 'axios'
import i18n, { RTL_LOCALES, SUPPORTED_LOCALES } from '@/i18n'

export const localeKey = 'uiLocale'

const storedChoice = () =>
  (typeof localStorage !== 'undefined' && localStorage.getItem(localeKey)) || null

export const useLocaleStore = defineStore('locale', {
  state: () => ({
    // The user's own explicit choice (persisted in localStorage).
    // null = the user has never picked a language themselves.
    chosen: storedChoice(),
    // The locale the UI is actually rendering right now.
    current: storedChoice() || 'en',
  }),

  getters: {
    isRtl: (state) => RTL_LOCALES.includes(state.current),
  },

  actions: {
    init() {
      this.apply(this.current)

      // Locale resolution order: the user's own choice wins; otherwise
      // the platform default configured by the superadmin in Zakat
      // Settings (zakat.default_language); otherwise English. The
      // platform default is applied but never persisted, so a later
      // change by the superadmin still reaches these users.
      if (!this.chosen) {
        // Plain axios on purpose: the '@/utils/api' instance drives the
        // global loading bar and error toasts, which a silent background
        // bootstrap call should not touch.
        axios
          .get('/api/settings/default-language', { withCredentials: true })
          .then(({ data }) => {
            const fallback = data?.data?.default_language
            if (!this.chosen && fallback && SUPPORTED_LOCALES.some((l) => l.id === fallback)) {
              this.current = fallback
              this.apply(fallback)
            }
          })
          .catch(() => {
            // Offline or endpoint unavailable — stay on English.
          })
      }
    },

    set(locale) {
      this.chosen = locale
      this.current = locale
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem(localeKey, locale)
      }
      this.apply(locale)
    },

    apply(locale) {
      i18n.global.locale.value = locale
      if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('lang', locale)
        document.documentElement.setAttribute('dir', RTL_LOCALES.includes(locale) ? 'rtl' : 'ltr')
      }
    },
  },
})
