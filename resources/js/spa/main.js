import axios from 'axios'
import { createApp } from 'vue'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import App from './App.vue'
import router from './router/index.js'
import { HouseholdPreset } from './theme.js'
import { reveal } from './directives/reveal.js'

import 'primeicons/primeicons.css'
import '@flaticon/flaticon-uicons/css/regular/rounded.css'
import '@flaticon/flaticon-uicons/css/solid/rounded.css'

window.axios = axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const app = createApp(App)

app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: HouseholdPreset,
    options: {
      darkModeSelector: '.app-dark',
      cssLayer: {
        name: 'primevue',
        order: 'theme, base, primevue, utilities',
      },
    },
  },
  ripple: true,
})
app.use(ToastService)
app.use(ConfirmationService)
app.directive('reveal', reveal)

app.mount('#app')

// ลงทะเบียน service worker (PWA) — เฉพาะ origin ที่รองรับ
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {})
  })
}
