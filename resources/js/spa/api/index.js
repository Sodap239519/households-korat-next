import axios from 'axios'

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

// Attach CSRF token from meta tag if present
api.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token
    }
    return config
})

// Pages where 401 is expected / harmless – do not auto-redirect
const AUTH_FREE_PATHS = ['/app', '/app/public', '/app/login']
// API URLs where 401 should be silent (auth check probes / login attempts)
const SILENT_AUTH_URLS = ['/user', '/login']

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            const url = error.config?.url || ''
            const path = window.location.pathname
            const isSilent = SILENT_AUTH_URLS.some(s => url.endsWith(s))
            // หน้าร้าน (/shop) ส่วนใหญ่เป็น public — 401 ไม่ควรเด้ง ยกเว้นหน้าที่ต้อง login
            const onStorefront = path.startsWith('/shop')
            const onAuthFreePage = AUTH_FREE_PATHS.includes(path) || path.startsWith('/app/public')
            if (!isSilent && !onAuthFreePage) {
                if (onStorefront) {
                    // เด้งไปหน้า login ของลูกค้า เฉพาะหน้าที่ต้องยืนยันตัวตน
                    const needsAuth = /^\/shop\/(checkout|account|orders)/.test(path)
                    if (needsAuth) {
                        const redirect = encodeURIComponent(path + window.location.search)
                        window.location.href = `/shop/login?redirect=${redirect}`
                    }
                } else {
                    window.location.href = '/app/login'
                }
            }
        }
        return Promise.reject(error)
    }
)

export default api
