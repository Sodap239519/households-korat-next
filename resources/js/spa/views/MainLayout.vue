<template>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside
      :class="[
        'relative transition-all duration-300 flex flex-col bg-gradient-to-b from-violet-700 via-purple-800 to-fuchsia-800 text-white shadow-xl z-20',
        collapsed ? 'w-20' : 'w-64'
      ]"
    >
      <!-- Decorative overlay -->
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent_50%)] pointer-events-none"></div>

      <!-- Brand -->
      <div class="relative px-4 py-5 border-b border-white/10 flex items-center gap-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center text-xl shadow">
          <i class="fi fi-rr-leaf"></i>
        </div>
        <div v-if="!collapsed" class="overflow-hidden">
          <h1 class="text-base font-bold leading-tight whitespace-nowrap">โควต้าเห็ด</h1>
          <p class="text-violet-200 text-[11px] mt-0.5 whitespace-nowrap">นครราชสีมา</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="relative flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          v-slot="{ isActive }"
          custom
        >
          <a
            @click.prevent="$router.push(item.to)"
            :class="[
              'group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm cursor-pointer transition relative',
              isActive
                ? 'bg-white text-violet-700 font-semibold shadow-md shadow-fuchsia-900/20'
                : 'text-violet-100 hover:bg-white/10 hover:text-white',
            ]"
          >
            <i :class="[item.icon, 'text-lg flex-shrink-0']"></i>
            <span v-if="!collapsed" class="whitespace-nowrap">{{ item.label }}</span>
            <span v-if="isActive && !collapsed" class="ml-auto"><i class="fi fi-rr-angle-small-right"></i></span>
          </a>
        </router-link>
      </nav>

      <!-- Profile / Logout -->
      <div class="relative px-3 py-3 border-t border-white/10">
        <div :class="['flex items-center gap-3 px-2 py-2 rounded-lg bg-white/10 backdrop-blur', collapsed ? 'justify-center' : '']">
          <div class="w-9 h-9 rounded-full bg-gradient-to-br from-fuchsia-400 to-violet-400 flex items-center justify-center font-semibold text-sm shadow flex-shrink-0">
            {{ initials }}
          </div>
          <div v-if="!collapsed" class="flex-1 min-w-0">
            <p class="text-xs font-medium text-white truncate">{{ user?.name || 'ผู้ใช้' }}</p>
            <p class="text-[10px] text-violet-200 truncate">{{ user?.email }}</p>
          </div>
        </div>
        <button
          @click="handleLogout"
          :class="['mt-2 w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs text-violet-100 hover:bg-rose-500/30 hover:text-white transition', collapsed ? 'justify-center' : '']"
        >
          <i class="fi fi-rr-sign-out-alt"></i>
          <span v-if="!collapsed">ออกจากระบบ</span>
        </button>
      </div>
    </aside>

    <!-- Main wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Topbar -->
      <header class="h-16 flex items-center justify-between px-6 bg-white/70 backdrop-blur-xl border-b border-violet-200/50 z-10">
        <div class="flex items-center gap-3">
          <button
            @click="collapsed = !collapsed"
            class="w-10 h-10 rounded-lg hover:bg-violet-100 flex items-center justify-center text-violet-700 transition"
          >
            <i :class="collapsed ? 'fi fi-rr-menu-burger' : 'fi fi-rr-angle-double-small-left'"></i>
          </button>
          <div>
            <h2 class="text-base font-semibold text-slate-800">{{ pageTitle }}</h2>
            <p class="text-xs text-slate-400">{{ today }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button class="w-10 h-10 rounded-lg hover:bg-violet-100 flex items-center justify-center text-violet-700 transition relative">
            <i class="fi fi-rr-bell"></i>
            <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-fuchsia-500"></span>
          </button>
          <StatusBadge v-if="user?.role" :status="user.role" :label="roleLabel" />
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
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import StatusBadge from '../components/StatusBadge.vue'

const route = useRoute()
const router = useRouter()
const { user, logout } = useAuth()

const collapsed = ref(false)

const navItems = [
  { to: '/app/dashboard',   icon: 'fi fi-rr-dashboard',     label: 'แดชบอร์ด' },
  { to: '/app/quotas',      icon: 'fi fi-rr-clipboard-list', label: 'โควต้าอำเภอ' },
  { to: '/app/allocations', icon: 'fi fi-rr-seedling',      label: 'การจัดสรร' },
  { to: '/app/followups',   icon: 'fi fi-rr-list-check',    label: 'ติดตามผล' },
  { to: '/app/marketing',   icon: 'fi fi-rr-shop',          label: 'การตลาด' },
  { to: '/app/reports',     icon: 'fi fi-rr-chart-pie',     label: 'รายงาน' },
]

const pageTitleMap = {
  dashboard: 'แดชบอร์ด',
  quotas: 'โควต้าอำเภอ',
  allocations: 'การจัดสรรเห็ด',
  followups: 'การติดตามผล',
  marketing: 'การตลาด',
  reports: 'รายงาน',
}
const pageTitle = computed(() => {
  const seg = route.path.split('/')[2] || 'dashboard'
  return pageTitleMap[seg] || ''
})

const today = computed(() =>
  new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' })
)

const initials = computed(() => {
  const n = user.value?.name || ''
  const parts = n.trim().split(/\s+/)
  return (parts[0]?.[0] || '?') + (parts[1]?.[0] || '')
})

const roleLabel = computed(() => {
  return user.value?.role === 'superadmin' ? 'ผู้ดูแลระบบ' : 'เจ้าหน้าที่'
})

async function handleLogout() {
  await logout()
  router.push('/app/login')
}
</script>
