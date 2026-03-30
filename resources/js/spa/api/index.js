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

// Handle 401 – redirect to login
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            window.location.href = '/app/login'
        }
        return Promise.reject(error)
    }
)

export default api
