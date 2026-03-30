import { ref, readonly } from 'vue'
import api from '../api/index.js'

const user = ref(null)
const loading = ref(false)

export function useAuth() {
    async function fetchUser() {
        try {
            loading.value = true
            const { data } = await api.get('/user')
            user.value = data.user
        } catch {
            user.value = null
        } finally {
            loading.value = false
        }
    }

    async function login(email, password) {
        // First get CSRF cookie
        await axios.get('/sanctum/csrf-cookie')
        const { data } = await api.post('/login', { email, password })
        user.value = data.user
        return data.user
    }

    async function logout() {
        await api.post('/logout')
        user.value = null
    }

    return {
        user: readonly(user),
        loading: readonly(loading),
        fetchUser,
        login,
        logout,
    }
}
