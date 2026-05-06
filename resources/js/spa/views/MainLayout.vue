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
          <button class="w-10 h-10 rounded-lg hover:bg-violet-100 flex items-center justify-center text-violet-700 transition relative" v-tooltip.bottom="'แจ้งเตือน'">
            <i class="fi fi-rr-bell"></i>
            <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-fuchsia-500"></span>
          </button>

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
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

import Menu from 'primevue/menu'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const route = useRoute()
const router = useRouter()
const { user, logout } = useAuth()

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
</script>
