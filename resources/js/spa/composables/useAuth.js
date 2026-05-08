import { ref, readonly, computed } from 'vue'
import axios from 'axios'
import api from '../api/index.js'

const user = ref(null)
const loading = ref(false)

// ===== Permission flags (computed off the shared user ref) =====
const role = computed(() => user.value?.role || null)

const isSuperAdmin = computed(() => role.value === 'superadmin')
const isAdmin      = computed(() => ['admin', 'superadmin'].includes(role.value))
const isAreaStaff  = computed(() => ['area_staff', 'admin', 'superadmin'].includes(role.value))
const isStaff      = computed(() => !!role.value)

const canManageQuotas    = isAdmin
const canManageUsers     = isAdmin
const canCreateAllocation = isAreaStaff
const canCreateFollowup   = isAreaStaff
const canEditHouseholds   = isStaff

const assignedDistricts = computed(() => user.value?.assigned_districts || [])

function canActInDistrict(district) {
  if (isAdmin.value) return true
  if (role.value !== 'area_staff') return false
  if (!district) return false
  return assignedDistricts.value.includes(district)
}

function roleLabel(r) {
  return {
    staff:       'เจ้าหน้าที่',
    area_staff:  'เจ้าหน้าที่ประจำพื้นที่',
    admin:       'ผู้ดูแลระบบ',
    superadmin:  'ผู้ดูแลระบบสูงสุด',
  }[r] || r || '-'
}

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
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
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
        // role flags
        role,
        isStaff,
        isAreaStaff,
        isAdmin,
        isSuperAdmin,
        // permissions
        canManageQuotas,
        canManageUsers,
        canCreateAllocation,
        canCreateFollowup,
        canEditHouseholds,
        // districts
        assignedDistricts,
        canActInDistrict,
        // helpers
        roleLabel,
    }
}
