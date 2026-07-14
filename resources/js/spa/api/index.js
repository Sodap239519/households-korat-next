import axios from 'axios'

// ให้ raw axios (เช่น ดึง /sanctum/csrf-cookie, login) ส่ง cookie + XSRF header ด้วย
axios.defaults.withCredentials = true
axios.defaults.xsrfCookieName = 'XSRF-TOKEN'
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN'

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

// อ่าน XSRF-TOKEN จาก cookie (Laravel sync กับ session ให้อัตโนมัติ — ไม่ stale เหมือน meta tag)
function readXsrfCookie() {
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
    return m ? decodeURIComponent(m[1]) : null
}

// Attach CSRF token (จาก cookie) + fix Content-Type for multipart (FormData) requests
api.interceptors.request.use((config) => {
    const xsrf = readXsrfCookie()
    if (xsrf) {
        config.headers['X-XSRF-TOKEN'] = xsrf
    }
    // Let the browser set Content-Type (with boundary) for FormData uploads
    if (config.data instanceof FormData) {
        delete config.headers['Content-Type']
    }
    return config
})

// Pages where 401 is expected / harmless – do not auto-redirect
const AUTH_FREE_PATHS = ['/app', '/app/public', '/app/login']
// API URLs where 401 should be silent (auth check probes / login attempts)
const SILENT_AUTH_URLS = ['/user', '/login']

api.interceptors.response.use(
    (response) => response,
    async (error) => {
        // 419 = CSRF token mismatch → รีเฟรช cookie แล้วลองใหม่ 1 ครั้ง
        if (error.response?.status === 419 && error.config && !error.config._csrfRetried) {
            error.config._csrfRetried = true
            try {
                await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
                const xsrf = readXsrfCookie()
                if (xsrf) error.config.headers['X-XSRF-TOKEN'] = xsrf
                return api.request(error.config)
            } catch { /* fall through */ }
        }
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
