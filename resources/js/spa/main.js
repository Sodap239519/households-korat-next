import axios from 'axios'
import { createApp } from 'vue'
import App from './App.vue'
import router from './router/index.js'

// Configure axios globally for CSRF
window.axios = axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const app = createApp(App)
app.use(router)
app.mount('#app')
