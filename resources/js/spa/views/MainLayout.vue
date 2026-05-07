<template>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside
      :class="[
        'relative transition-all duration-300 flex flex-col bg-gradient-to-b from-violet-700 via-purple-800 to-fuchsia-800 text-white shadow-xl z-20',
        collapsed ? 'w-20' : 'w-64'
      ]"
    >
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent_50%)] pointer-events-none"></div>

      <!-- Brand -->
      <div class="relative px-4 py-5 border-b border-white/10 flex items-center gap-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center text-xl shadow">
          <i class="fi fi-rr-leaf"></i>
        </div>
        <div v-if="!collapsed" class="overflow-hidden">
          <h1 class="text-base font-bold leading-tight whitespace-nowrap">Households Korat</h1>
          <p class="text-violet-200 text-[10px] mt-0.5 whitespace-nowrap">นครราชสีมา</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="relative flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <template v-for="(item, idx) in navItems" :key="idx">
          <!-- Single link -->
          <router-link
            v-if="!item.children"
            :to="item.to"
            v-slot="{ isActive }"
            custom
          >
            <a
              @click.prevent="$router.push(item.to)"
              :class="[
                'group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer transition',
                isActive
                  ? 'bg-white text-violet-700 font-semibold shadow-md shadow-fuchsia-900/20'
                  : 'text-violet-100 hover:bg-white/10 hover:text-white',
              ]"
            >
              <i :class="[item.icon, 'text-lg flex-shrink-0']"></i>
              <span v-if="!collapsed" class="whitespace-nowrap">{{ item.label }}</span>
            </a>
          </router-link>

          <!-- Group with children -->
          <div v-else>
            <button
              @click="toggleGroup(idx)"
              :class="[
                'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition',
                isGroupActive(item)
                  ? 'bg-white/15 text-white font-medium'
                  : 'text-violet-100 hover:bg-white/10 hover:text-white',
              ]"
            >
              <i :class="[item.icon, 'text-lg flex-shrink-0']"></i>
              <span v-if="!collapsed" class="flex-1 text-left whitespace-nowrap">{{ item.label }}</span>
              <i v-if="!collapsed" :class="['text-xs transition-transform', openGroups[idx] ? 'fi fi-rr-angle-small-down' : 'fi fi-rr-angle-small-right']"></i>
            </button>
            <div
              v-if="!collapsed && openGroups[idx]"
              class="mt-1 ml-3 pl-4 border-l border-white/15 space-y-0.5"
            >
              <router-link
                v-for="child in item.children"
                :key="child.to"
                :to="child.to"
                v-slot="{ isActive }"
                custom
              >
                <a
                  @click.prevent="$router.push(child.to)"
                  :class="[
                    'flex items-center gap-2 px-3 py-2 rounded-lg text-xs cursor-pointer transition',
                    isActive
                      ? 'bg-white text-violet-700 font-semibold'
                      : 'text-violet-200 hover:bg-white/10 hover:text-white',
                  ]"
                >
                  <i :class="[child.icon, 'text-sm flex-shrink-0']"></i>
                  <span class="whitespace-nowrap">{{ child.label }}</span>
                </a>
              </router-link>
            </div>
          </div>
        </template>
      </nav>

      <!-- Collapse handle / footer -->
      <div class="relative px-3 py-3 border-t border-white/10">
        <button
          @click="collapsed = !collapsed"
          :class="['w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs text-violet-100 hover:bg-white/10 hover:text-white transition', collapsed ? 'justify-center' : '']"
        >
          <i :class="collapsed ? 'fi fi-rr-angle-double-small-right' : 'fi fi-rr-angle-double-small-left'"></i>
          <span v-if="!collapsed">ย่อแถบเมนู</span>
        </button>
      </div>
    </aside>

    <!-- Main wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Topbar -->
      <header class="h-16 flex items-center justify-between px-6 bg-white/70 backdrop-blur-xl border-b border-violet-200/50 z-10">
        <div class="flex items-center gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-800">{{ pageTitle }}</h2>
            <p class="text-xs text-slate-400">{{ today }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Bell -->
          <button
            v-if="user?.role === 'superadmin'"
            @click="toggleBell"
            class="w-10 h-10 rounded-lg hover:bg-violet-100 flex items-center justify-center text-violet-700 transition relative"
            v-tooltip.bottom="'การแจ้งเตือน'"
          >
            <i class="fi fi-rr-bell"></i>
            <span
              v-if="pendingCount > 0"
              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-fuchsia-500 text-white text-[10px] font-bold flex items-center justify-center shadow"
            >
              {{ pendingCount > 99 ? '99+' : pendingCount }}
            </span>
          </button>

          <Popover ref="bellPanel" :pt="{ root: { class: 'mt-2' } }">
            <div class="w-80">
              <div class="px-4 py-3 border-b border-slate-200">
                <h3 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                  <i class="fi fi-rr-bell text-violet-600"></i> การแจ้งเตือน
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">ผู้สมัครรอการยืนยันสิทธิ์</p>
              </div>

              <div v-if="loadingPending" class="p-6 text-center text-violet-400">
                <i class="fi fi-rr-loading text-2xl animate-spin"></i>
              </div>
              <div v-else-if="pendingUsers.length === 0" class="p-6 text-center text-slate-400 text-sm">
                <i class="fi fi-rr-check-circle text-2xl text-emerald-400"></i>
                <p class="mt-2">ไม่มีคำขอที่รอดำเนินการ</p>
              </div>
              <div v-else class="max-h-80 overflow-y-auto">
                <div v-for="u in pendingUsers" :key="u.id" class="px-4 py-3 border-b border-slate-100 hover:bg-violet-50/40">
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                      <p class="text-sm font-medium text-slate-800 truncate">{{ u.name }}</p>
                      <p class="text-xs text-slate-500 truncate">{{ u.email }}</p>
                      <p class="text-[10px] text-slate-400 mt-0.5">
                        <i class="fi fi-rr-clock"></i> {{ relativeTime(u.created_at) }}
                      </p>
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                      <Button icon="fi fi-rr-check" severity="success" rounded size="small"
                              v-tooltip.top="'อนุมัติ'" @click="approve(u)" :loading="processingId === u.id" />
                      <Button icon="fi fi-rr-cross-small" severity="danger" rounded size="small"
                              v-tooltip.top="'ปฏิเสธ'" @click="reject(u)" :loading="processingId === u.id" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="px-4 py-2 border-t border-slate-200 text-center">
                <button @click="goManageUsers" class="text-xs text-violet-600 hover:underline">
                  จัดการผู้ใช้ทั้งหมด <i class="fi fi-rr-arrow-small-right"></i>
                </button>
              </div>
            </div>
          </Popover>

          <!-- User chip -> dropdown -->
          <button
            ref="userMenuBtn"
            @click="toggleUserMenu"
            class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-white border border-violet-200 hover:border-violet-400 hover:shadow-md hover:shadow-violet-200/50 transition"
          >
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center font-semibold text-xs shadow">
              {{ initials }}
            </div>
            <div class="text-left">
              <p class="text-xs font-semibold text-slate-700 leading-none">{{ user?.name || 'ผู้ใช้' }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5 leading-none">{{ roleLabel }}</p>
            </div>
            <i class="fi fi-rr-angle-small-down text-slate-400 text-xs"></i>
          </button>

          <Menu ref="userMenu" :model="userMenuItems" :popup="true" :pt="{ root: { class: 'mt-2' } }" />
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-auto">
        <router-view />
      </main>
    </div>

    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import { useToast } from 'primevue/usetoast'
import api from '../api/index.js'

import Menu from 'primevue/menu'
import Popover from 'primevue/popover'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const route = useRoute()
const router = useRouter()
const { user, logout } = useAuth()
const toast = useToast()

const collapsed = ref(false)

const navItems = [
  { to: '/app/dashboard', icon: 'fi fi-rr-dashboard', label: 'แดชบอร์ด' },
  {
    label: 'รายการครัวเรือน',
    icon: 'fi fi-rr-house-blank',
    matchPrefix: '/app/households',
    children: [
      { to: '/app/households',        icon: 'fi fi-rr-list',           label: 'แสดงรายการครัวเรือน' },
      { to: '/app/households/create', icon: 'fi fi-rr-add-document',   label: 'เพิ่มรายการครัวเรือน' },
    ],
  },
  { to: '/app/tracking', icon: 'fi fi-rr-search', label: 'การติดตาม' },
  {
    label: 'โควต้าเห็ด',
    icon: 'fi fi-rr-leaf',
    matchPrefix: '/app/mushroom',
    children: [
      { to: '/app/mushroom/quotas',      icon: 'fi fi-rr-clipboard-list', label: 'โควต้าอำเภอ' },
      { to: '/app/mushroom/allocations', icon: 'fi fi-rr-seedling',       label: 'การจัดสรร' },
      { to: '/app/mushroom/followups',   icon: 'fi fi-rr-list-check',     label: 'ติดตามผลผลิต' },
    ],
  },
  { to: '/app/marketing', icon: 'fi fi-rr-shop',       label: 'การตลาด' },
  { to: '/app/reports',   icon: 'fi fi-rr-chart-pie',  label: 'รายงาน' },
]

const openGroups = reactive({})
// Auto-open the group whose prefix matches current route
navItems.forEach((item, idx) => {
  if (item.children && item.matchPrefix && route.path.startsWith(item.matchPrefix)) {
    openGroups[idx] = true
  }
})

function toggleGroup(idx) {
  openGroups[idx] = !openGroups[idx]
}

function isGroupActive(item) {
  return item.matchPrefix ? route.path.startsWith(item.matchPrefix) : false
}

const pageTitleMap = {
  dashboard: 'แดชบอร์ด',
  households: 'รายการครัวเรือน',
  tracking: 'การติดตาม',
  mushroom: 'โควต้าเห็ด',
  marketing: 'การตลาด',
  reports: 'รายงาน',
  profile: 'โปรไฟล์ของฉัน',
  'login-history': 'ประวัติการเข้าใช้งาน',
  admin: 'จัดการผู้ใช้',
}
const pageTitle = computed(() => {
  const seg = route.path.split('/')[2] || 'dashboard'
  return pageTitleMap[seg] || 'Households Korat'
})

const today = computed(() =>
  new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' })
)

const initials = computed(() => {
  const n = user.value?.name || ''
  const parts = n.trim().split(/\s+/)
  return (parts[0]?.[0] || '?') + (parts[1]?.[0] || '')
})

const roleLabel = computed(() => user.value?.role === 'superadmin' ? 'ผู้ดูแลระบบ' : 'เจ้าหน้าที่')

// User dropdown menu
const userMenu = ref(null)
function toggleUserMenu(e) {
  userMenu.value?.toggle(e)
}

const userMenuItems = computed(() => {
  const items = [
    {
      label: user.value?.name || 'ผู้ใช้',
      items: [
        { label: 'โปรไฟล์ของฉัน',       icon: 'fi fi-rr-user',     command: () => router.push('/app/profile') },
        { label: 'ประวัติการเข้าใช้งาน', icon: 'fi fi-rr-time-past', command: () => router.push('/app/login-history') },
      ],
    },
  ]

  if (user.value?.role === 'superadmin') {
    items.push({
      label: 'ผู้ดูแลระบบ',
      items: [
        { label: 'จัดการผู้ใช้', icon: 'fi fi-rr-users-alt', command: () => router.push('/app/admin/users') },
      ],
    })
  }

  items.push({
    separator: true,
  })
  items.push({
    label: 'ออกจากระบบ',
    icon: 'fi fi-rr-sign-out-alt',
    command: handleLogout,
  })

  return items
})

async function handleLogout() {
  await logout()
  router.push('/app/login')
}

// ===== Notification bell (superadmin only) =====
const bellPanel = ref(null)
const pendingCount = ref(0)
const pendingUsers = ref([])
const loadingPending = ref(false)
const processingId = ref(null)
let pollTimer = null

async function fetchPendingCount() {
  if (user.value?.role !== 'superadmin') return
  try {
    const { data } = await api.get('/admin/notifications/counts')
    pendingCount.value = data.pending_users
  } catch {}
}

async function fetchPendingUsers() {
  loadingPending.value = true
  try {
    const { data } = await api.get('/admin/notifications/pending', { params: { per_page: 10 } })
    pendingUsers.value = data.data
  } finally {
    loadingPending.value = false
  }
}

async function toggleBell(e) {
  bellPanel.value?.toggle(e)
  await fetchPendingUsers()
}

async function approve(u) {
  processingId.value = u.id
  try {
    await api.post(`/admin/users/${u.id}/approve`)
    toast.add({ severity: 'success', summary: 'อนุมัติแล้ว', detail: u.name, life: 2000 })
    pendingUsers.value = pendingUsers.value.filter(x => x.id !== u.id)
    pendingCount.value = Math.max(0, pendingCount.value - 1)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: e.response?.data?.message || '', life: 3000 })
  } finally {
    processingId.value = null
  }
}

async function reject(u) {
  if (!confirm(`ปฏิเสธบัญชี ${u.name} (${u.email})? บัญชีจะถูกลบออกจากระบบ`)) return
  processingId.value = u.id
  try {
    await api.post(`/admin/users/${u.id}/reject`)
    toast.add({ severity: 'success', summary: 'ปฏิเสธแล้ว', life: 2000 })
    pendingUsers.value = pendingUsers.value.filter(x => x.id !== u.id)
    pendingCount.value = Math.max(0, pendingCount.value - 1)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: e.response?.data?.message || '', life: 3000 })
  } finally {
    processingId.value = null
  }
}

function goManageUsers() {
  bellPanel.value?.hide()
  router.push('/app/admin/users')
}

function relativeTime(iso) {
  if (!iso) return ''
  const diff = Date.now() - new Date(iso).getTime()
  const m = Math.round(diff / 60000)
  if (m < 1)   return 'เมื่อสักครู่'
  if (m < 60)  return `${m} นาทีที่แล้ว`
  const h = Math.round(m / 60)
  if (h < 24)  return `${h} ชั่วโมงที่แล้ว`
  const d = Math.round(h / 24)
  if (d < 30)  return `${d} วันที่แล้ว`
  return new Date(iso).toLocaleDateString('th-TH')
}

watch(() => user.value?.role, (role) => {
  if (role === 'superadmin') fetchPendingCount()
}, { immediate: true })

onMounted(() => {
  // Poll every 60s for new pending users
  pollTimer = setInterval(fetchPendingCount, 60_000)
})
onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})
</script>
