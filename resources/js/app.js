import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router/index.js'
import i18n from './i18n.js'
import { useDarkModeStore } from './stores/darkMode.js'
import { useLocaleStore } from './stores/locale.js'

const pinia = createPinia()
const app = createApp(App)

app.use(pinia)
app.use(router)
app.use(i18n)

useDarkModeStore(pinia).init()
useLocaleStore(pinia).init()

app.mount('#app')
