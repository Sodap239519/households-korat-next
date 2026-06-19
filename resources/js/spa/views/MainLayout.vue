<template>
  <div class="flex h-screen overflow-hidden relative">
    <!-- Mobile backdrop -->
    <div
      v-if="mobileOpen"
      class="md:hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-30"
      @click="mobileOpen = false"
    ></div>

    <!-- Sidebar -->
    <aside
      :class="[
        'flex flex-col bg-gradient-to-b from-violet-700 via-purple-800 to-fuchsia-800 text-white shadow-xl',
        // Position
        'fixed md:relative inset-y-0 left-0 z-40 transition-all duration-300',
        // Width on desktop (collapsed vs full)
        collapsed ? 'md:w-20' : 'md:w-64',
        // On mobile, always w-64 when open, off-screen when closed
        'w-64',
        mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
      ]"
    >
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent_50%)] pointer-events-none"></div>

      <!-- Brand -->
      <div class="relative px-4 py-5 border-b border-white/10 flex items-center gap-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center text-xl shadow">
          <i class="fi fi-rr-house-blank"></i>
        </div>
        <div v-if="!collapsedDisplay" class="overflow-hidden flex-1">
          <h1 class="text-base font-bold leading-tight whitespace-nowrap">Households KORAT</h1>
          <p class="text-violet-200 text-[10px] mt-0.5 whitespace-nowrap leading-tight">ระบบวิเคราะห์คุณสมบัติ<br/>ครัวเรือนเปราะบาง</p>
        </div>
        <button
          @click="mobileOpen = false"
          class="md:hidden flex-shrink-0 w-8 h-8 rounded-lg hover:bg-white/10 flex items-center justify-center text-white/80"
        >
          <i class="fi fi-rr-cross-small"></i>
        </button>
      </div>

      <!-- Nav -->
      <nav class="relative flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <template v-for="(item, idx) in navItems" :key="idx">
          <router-link v-if="!item.children" :to="item.to" v-slot="{ isActive }" custom>
            <a
              @click.prevent="goNav(item.to)"
              :class="[
                'group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer transition',
                isActive
                  ? 'bg-white text-violet-700 font-semibold shadow-md shadow-fuchsia-900/20'
                  : 'text-violet-100 hover:bg-white/10 hover:text-white',
              ]"
            >
              <i :class="[item.icon, 'text-lg flex-shrink-0']"></i>
              <span v-if="!collapsedDisplay" class="whitespace-nowrap">{{ item.label }}</span>
            </a>
          </router-link>

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
              <span v-if="!collapsedDisplay" class="flex-1 text-left whitespace-nowrap">{{ item.label }}</span>
              <i v-if="!collapsedDisplay" :class="['text-xs transition-transform', openGroups[idx] ? 'fi fi-rr-angle-small-down' : 'fi fi-rr-angle-small-right']"></i>
            </button>
            <div v-if="!collapsedDisplay && openGroups[idx]" class="mt-1 ml-3 pl-4 border-l border-white/15 space-y-0.5">
              <router-link
                v-for="child in item.children"
                :key="child.to"
                :to="child.to"
                v-slot="{ isActive }"
                custom
              >
                <a
                  @click.prevent="goNav(child.to)"
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

      <!-- Collapse handle (desktop only) -->
      <div class="relative px-3 py-3 border-t border-white/10 hidden md:block">
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
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
      <!-- Topbar -->
      <header class="h-14 sm:h-16 flex items-center justify-between px-3 sm:px-6 bg-white/70 backdrop-blur-xl border-b border-violet-200/50 z-10 gap-2">
        <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
          <!-- Mobile hamburger -->
          <button
            @click="mobileOpen = true"
            class="md:hidden flex-shrink-0 w-9 h-9 rounded-lg hover:bg-violet-100 flex items-center justify-center text-violet-700 transition"
            aria-label="เปิดเมนู"
          >
            <i class="fi fi-rr-menu-burger"></i>
          </button>
          <div class="min-w-0">
            <h2 class="text-sm sm:text-base font-semibold text-slate-800 truncate">{{ pageTitle }}</h2>
            <p class="text-[10px] sm:text-xs text-slate-400 truncate hidden sm:block">
              <i class="fi fi-rr-time-past text-violet-500 mr-1"></i>
              <span v-if="lastUpdatedText">{{ lastUpdatedText }}</span>
              <span v-else>{{ today }}</span>
            </p>
          </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
          <!-- Bell (admin+) -->
          <button
            v-if="isAdmin"
            @click="toggleBell"
            class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg hover:bg-violet-100 flex items-center justify-center text-violet-700 transition relative"
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

          <Popover ref="bellPanel" :pt="{ root: { class: 'mt-2' } }" :breakpoints="{ '640px': '95vw' }">
            <div class="w-[90vw] sm:w-96 max-w-md">
              <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                <div>
                  <h3 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fi fi-rr-bell text-violet-600"></i> การแจ้งเตือน
                    <span v-if="unreadTotal > 0" class="ml-1 px-1.5 py-0 rounded-full bg-fuchsia-100 text-fuchsia-700 text-[10px] font-bold">
                      {{ unreadTotal }} ใหม่
                    </span>
                  </h3>
                  <p class="text-[11px] text-slate-500 mt-0.5">กิจกรรมล่าสุดในระบบ</p>
                </div>
                <button v-if="unreadTotal > 0" @click="markAllRead" class="text-[11px] text-violet-600 hover:underline whitespace-nowrap">
                  อ่านทั้งหมด
                </button>
              </div>

              <div v-if="loadingNotifs" class="p-6 text-center text-violet-400">
                <i class="fi fi-rr-loading text-2xl animate-spin"></i>
              </div>
              <div v-else-if="notifs.length === 0" class="p-6 text-center text-slate-400 text-sm">
                <i class="fi fi-rr-check-circle text-2xl text-emerald-400"></i>
                <p class="mt-2">ยังไม่มีการแจ้งเตือน</p>
              </div>
              <div v-else class="max-h-[60vh] overflow-y-auto">
                <div
                  v-for="n in notifs"
                  :key="n.id"
                  :class="['px-4 py-3 border-b border-slate-100 cursor-pointer transition relative',
                    n.read_at ? 'bg-white hover:bg-slate-50' : 'bg-violet-50/40 hover:bg-violet-100/50']"
                  @click="onNotifClick(n)"
                >
                  <div class="flex items-start gap-3">
                    <div :class="['flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-white shadow-sm',
                      typeStyle(n.type).bg]">
                      <i :class="typeStyle(n.type).icon"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                      <div class="flex items-center gap-1.5">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ n.title }}</p>
                        <span v-if="!n.read_at" class="w-1.5 h-1.5 rounded-full bg-fuchsia-500 flex-shrink-0"></span>
                      </div>
                      <p class="text-xs text-slate-500 truncate">{{ n.message }}</p>
                      <p class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1">
                        <i class="fi fi-rr-clock"></i> {{ relativeTime(n.created_at) }}
                        <span v-if="n.actor" class="ml-1 truncate">· โดย {{ n.actor.name }}</span>
                      </p>
                    </div>

                    <div v-if="n.type === 'user_registered' && n.meta?.user_id" class="flex gap-1 flex-shrink-0">
                      <Button icon="fi fi-rr-check" severity="success" rounded size="small"
                              v-tooltip.top="'อนุมัติ'"
                              @click.stop="approveById(n.meta.user_id, n.id)"
                              :loading="processingId === n.meta.user_id" />
                      <Button icon="fi fi-rr-cross-small" severity="danger" rounded size="small"
                              v-tooltip.top="'ปฏิเสธ'"
                              @click.stop="rejectById(n.meta.user_id, n.id)"
                              :loading="processingId === n.meta.user_id" />
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
            class="flex items-center gap-2 pl-1 pr-1 sm:pr-3 py-1 rounded-full bg-white border border-violet-200 hover:border-violet-400 hover:shadow-md hover:shadow-violet-200/50 transition"
          >
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center font-semibold text-xs shadow flex-shrink-0">
              {{ initials }}
            </div>
            <div class="text-left hidden sm:block">
              <p class="text-xs font-semibold text-slate-700 leading-none truncate max-w-[140px]">{{ user?.name || 'ผู้ใช้' }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5 leading-none">{{ roleLabel }}</p>
            </div>
            <i class="fi fi-rr-angle-small-down text-slate-400 text-xs hidden sm:inline"></i>
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
import { fmtThaiDateLong, relativeTime as relTimeUtil } from '../utils/date.js'

import Menu from 'primevue/menu'
import Popover from 'primevue/popover'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const route = useRoute()
const router = useRouter()
const { user, logout, isAdmin, isAreaStaff, isMarketStaff, canManageUsers, roleLabel: roleLabelFn } = useAuth()
const toast = useToast()

const collapsed = ref(false)
const mobileOpen = ref(false)

// "collapsedDisplay" controls labels visibility — true on desktop+collapsed only.
// On mobile-open, drawer is full width so labels should always show.
const collapsedDisplay = computed(() => collapsed.value && !mobileOpen.value)

// Close drawer when navigating
watch(() => route.path, () => { mobileOpen.value = false })

const allNavItems = [
  // visible to all roles (staff +)
  { to: '/app/dashboard', icon: 'fi fi-rr-dashboard', label: 'แดชบอร์ด', show: () => true },
  {
    label: 'รายการครัวเรือน',
    icon: 'fi fi-rr-house-blank',
    matchPrefix: '/app/households',
    show: () => true,
    children: [
      { to: '/app/households',        icon: 'fi fi-rr-list',         label: 'แสดงรายการครัวเรือน' },
      { to: '/app/households/create', icon: 'fi fi-rr-add-document', label: 'เพิ่มรายการครัวเรือน' },
    ],
  },
  { to: '/app/tracking', icon: 'fi fi-rr-search', label: 'การติดตาม', show: () => true },

  // mushroom — area_staff and above
  {
    label: 'โควต้าเห็ด',
    icon: 'fi fi-rr-mushroom',
    matchPrefix: '/app/mushroom',
    show: () => isAreaStaff.value,
    children: [
      { to: '/app/mushroom/quotas',      icon: 'fi fi-rr-clipboard-list', label: 'โควต้าอำเภอ' },
      { to: '/app/mushroom/allocations', icon: 'fi fi-rr-mushroom',       label: 'การจัดสรร' },
      { to: '/app/mushroom/followups',   icon: 'fi fi-rr-list-check',     label: 'ติดตามผลผลิต' },
    ],
  },

  // ระบบตลาด — เจ้าหน้าที่ที่สังกัดกลุ่ม และ admin
  {
    label: 'ระบบตลาด',
    icon: 'fi fi-rr-shop',
    matchPrefix: '/app/market',
    show: () => isMarketStaff.value,
    children: [
      { to: '/app/market',              icon: 'fi fi-rr-chart-histogram', label: 'แดชบอร์ดตลาด' },
      { to: '/app/market/products',     icon: 'fi fi-rr-box-open',        label: 'สินค้า' },
      { to: '/app/market/categories',   icon: 'fi fi-rr-apps',            label: 'หมวดหมู่' },
      { to: '/app/market/orders',       icon: 'fi fi-rr-shopping-bag',    label: 'คำสั่งซื้อ' },
      { to: '/app/market/payments',     icon: 'fi fi-rr-receipt',         label: 'ยืนยันชำระเงิน' },
      { to: '/app/market/returns',      icon: 'fi fi-rr-undo',            label: 'คืน/เคลม' },
      { to: '/app/market/reviews',      icon: 'fi fi-rr-star',            label: 'รีวิว' },
      { to: '/app/market/comments',     icon: 'fi fi-rr-comment',         label: 'คอมเมนต์' },
      { to: '/app/market/seller-groups', icon: 'fi fi-rr-users-alt',      label: 'กลุ่มผู้ขาย' },
    ],
  },

  { to: '/app/reports',   icon: 'fi fi-rr-chart-pie', label: 'รายงาน',   show: () => isAreaStaff.value },
]

const navItems = computed(() => allNavItems.filter(item => item.show()))

const openGroups = reactive({})
navItems.value.forEach((item, idx) => {
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

function goNav(to) {
  router.push(to)
  mobileOpen.value = false
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
  return pageTitleMap[seg] || 'Households KORAT'
})

const today = computed(() => fmtThaiDateLong(new Date()))

// "<หัวข้อ> อัพเดตล่าสุดวันที่ X เวลา Y" — scope ตามหน้าปัจจุบัน
const lastUpdatedAt = ref(null)

// Map route segment → scope + label
// Route paths look like /app/<seg>[/<sub>]
const SCOPE_MAP = {
  households:    { scope: 'households',  label: 'รายการครัวเรือน' },
  tracking:      { scope: 'followups',   label: 'การติดตาม' },
  mushroom:      { scope: 'all',         label: 'ข้อมูล' },         // overridden by sub-page
  marketing:     { scope: 'followups',   label: 'การตลาด' },
  market:        { scope: 'all',         label: 'ระบบตลาด' },
  reports:       { scope: 'all',         label: 'รายงาน' },
  admin:         { scope: 'users',       label: 'ผู้ใช้' },
  'login-history': { scope: 'users',     label: 'ประวัติเข้าใช้งาน' },
  profile:       { scope: 'users',       label: 'โปรไฟล์' },
  dashboard:     { scope: 'all',         label: 'ภาพรวมระบบ' },
}
// Sub-pages under /app/mushroom/*
const MUSHROOM_SUB = {
  quotas:      { scope: 'quotas',      label: 'โควต้าเห็ด' },
  allocations: { scope: 'allocations', label: 'การจัดสรร' },
}

const currentScope = computed(() => {
  const parts = route.path.split('/').filter(Boolean)   // e.g. ['app','mushroom','quotas']
  const seg = parts[1] || 'dashboard'
  if (seg === 'mushroom' && parts[2] && MUSHROOM_SUB[parts[2]]) {
    return MUSHROOM_SUB[parts[2]]
  }
  return SCOPE_MAP[seg] || { scope: 'all', label: 'ข้อมูล' }
})

const lastUpdatedText = computed(() => {
  if (!lastUpdatedAt.value) return ''
  const d = new Date(lastUpdatedAt.value)
  if (isNaN(d.getTime())) return ''
  const datePart = fmtThaiDateLong(d)            // "วันศุกร์ที่ 8 พฤษภาคม 2569"
  const hh = String(d.getHours()).padStart(2, '0')
  const mm = String(d.getMinutes()).padStart(2, '0')
  return `อัพเดตล่าสุด ${datePart} เวลา ${hh}:${mm} น.`
})

async function fetchLastUpdated() {
  try {
    const { data } = await api.get('/system/last-updated', {
      params: { scope: currentScope.value.scope },
    })
    lastUpdatedAt.value = data.iso || data.at || null
  } catch {
    lastUpdatedAt.value = null
  }
}

const initials = computed(() => {
  const n = user.value?.name || ''
  const parts = n.trim().split(/\s+/)
  return (parts[0]?.[0] || '?') + (parts[1]?.[0] || '')
})

const roleLabel = computed(() => roleLabelFn(user.value?.role))

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

  if (canManageUsers.value) {
    items.push({
      label: 'ผู้ดูแลระบบ',
      items: [
        { label: 'จัดการผู้ใช้', icon: 'fi fi-rr-users-alt', command: () => router.push('/app/admin/users') },
      ],
    })
  }

  items.push({ separator: true })
  items.push({
    label: 'ออกจากระบบ',
    icon: 'fi fi-rr-sign-out-alt',
    command: handleLogout,
  })

  return items
})

async function handleLogout() {
  await logout()
  router.push('/app')
}

// ===== Notification bell (superadmin only) =====
const bellPanel = ref(null)
const pendingCount = ref(0)
const unreadTotal = ref(0)
const notifs = ref([])
const loadingNotifs = ref(false)
const processingId = ref(null)
let pollTimer = null

const TYPE_STYLES = {
  user_registered:    { bg: 'bg-violet-500',  icon: 'fi fi-rr-user-add' },
  household_created:  { bg: 'bg-indigo-500',  icon: 'fi fi-rr-house-blank' },
  quota_created:      { bg: 'bg-fuchsia-500', icon: 'fi fi-rr-clipboard-list' },
  allocation_created: { bg: 'bg-emerald-500', icon: 'fi fi-rr-seedling' },
  followup_created:   { bg: 'bg-orange-500',  icon: 'fi fi-rr-list-check' },
}
function typeStyle(t) { return TYPE_STYLES[t] || { bg: 'bg-slate-500', icon: 'fi fi-rr-bell' } }

async function fetchCounts() {
  if (!isAdmin.value) return
  try {
    const { data } = await api.get('/admin/notifications/counts')
    pendingCount.value = data.unread_total
    unreadTotal.value = data.unread_total
  } catch {}
}

async function fetchNotifs() {
  loadingNotifs.value = true
  try {
    const { data } = await api.get('/admin/notifications', { params: { per_page: 15 } })
    notifs.value = data.data
  } finally {
    loadingNotifs.value = false
  }
}

async function toggleBell(e) {
  bellPanel.value?.toggle(e)
  await fetchNotifs()
}

async function onNotifClick(n) {
  if (!n.read_at) {
    try {
      await api.post(`/admin/notifications/${n.id}/read`)
      n.read_at = new Date().toISOString()
      pendingCount.value = Math.max(0, pendingCount.value - 1)
      unreadTotal.value  = Math.max(0, unreadTotal.value  - 1)
    } catch {}
  }
  if (n.link) {
    bellPanel.value?.hide()
    router.push(n.link)
  }
}

async function markAllRead() {
  try {
    await api.post('/admin/notifications/read-all')
    notifs.value.forEach(n => { if (!n.read_at) n.read_at = new Date().toISOString() })
    pendingCount.value = 0
    unreadTotal.value = 0
    toast.add({ severity: 'success', summary: 'อ่านทั้งหมดแล้ว', life: 1500 })
  } catch {}
}

async function approveById(userId, notifId) {
  processingId.value = userId
  try {
    await api.post(`/admin/users/${userId}/approve`)
    toast.add({ severity: 'success', summary: 'อนุมัติแล้ว', life: 1800 })
    const n = notifs.value.find(x => x.id === notifId)
    if (n && !n.read_at) {
      n.read_at = new Date().toISOString()
      pendingCount.value = Math.max(0, pendingCount.value - 1)
      unreadTotal.value = Math.max(0, unreadTotal.value - 1)
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: e.response?.data?.message || '', life: 3000 })
  } finally {
    processingId.value = null
  }
}

async function rejectById(userId, notifId) {
  if (!confirm('ปฏิเสธและลบบัญชีนี้ออกจากระบบ?')) return
  processingId.value = userId
  try {
    await api.post(`/admin/users/${userId}/reject`)
    toast.add({ severity: 'success', summary: 'ปฏิเสธแล้ว', life: 1800 })
    const n = notifs.value.find(x => x.id === notifId)
    if (n && !n.read_at) {
      n.read_at = new Date().toISOString()
      pendingCount.value = Math.max(0, pendingCount.value - 1)
      unreadTotal.value = Math.max(0, unreadTotal.value - 1)
    }
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

const relativeTime = relTimeUtil

watch(() => user.value?.role, () => {
  if (isAdmin.value) fetchCounts()
}, { immediate: true })

let lastUpdatedTimer = null
onMounted(() => {
  pollTimer = setInterval(fetchCounts, 60_000)
  // Show "data last updated" timestamp in header — refresh every minute
  fetchLastUpdated()
  lastUpdatedTimer = setInterval(fetchLastUpdated, 60_000)
})
onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (lastUpdatedTimer) clearInterval(lastUpdatedTimer)
})

// Re-fetch on every route change (any navigation may have edited data)
watch(() => route.fullPath, () => fetchLastUpdated())
</script>
