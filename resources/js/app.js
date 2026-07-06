import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router/index.js'
import { useDarkModeStore } from './stores/darkMode.js'

const pinia = createPinia()
const app = createApp(App)

app.use(pinia)
app.use(router)

useDarkModeStore(pinia).init()

app.mount('#app')
