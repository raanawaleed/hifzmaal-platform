import { createI18n } from 'vue-i18n'
import en from '@/locales/en.json'
import ar from '@/locales/ar.json'
import ur from '@/locales/ur.json'
import hi from '@/locales/hi.json'
import bn from '@/locales/bn.json'
import fr from '@/locales/fr.json'
import de from '@/locales/de.json'
import es from '@/locales/es.json'

// Arabic and Urdu are the RTL scripts among our locales — hi and bn use
// left-to-right scripts, so only 'ar' and 'ur' flip direction.
export const RTL_LOCALES = ['ar', 'ur']

// Keep in sync with SettingController::SUPPORTED_LANGUAGES on the backend.
export const SUPPORTED_LOCALES = [
  { id: 'en', label: 'English' },
  { id: 'ar', label: 'العربية (Arabic)' },
  { id: 'ur', label: 'اردو (Urdu)' },
  { id: 'hi', label: 'हिन्दी (Hindi)' },
  { id: 'bn', label: 'বাংলা (Bengali)' },
  { id: 'fr', label: 'Français (French)' },
  { id: 'de', label: 'Deutsch (German)' },
  { id: 'es', label: 'Español (Spanish)' },
]

const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale: 'en',
  fallbackLocale: 'en',
  messages: { en, ar, ur, hi, bn, fr, de, es },
})

export default i18n
