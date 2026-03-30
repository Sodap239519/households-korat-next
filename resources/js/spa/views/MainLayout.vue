<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-green-800 text-white flex flex-col">
      <div class="px-6 py-4 border-b border-green-700">
        <h1 class="text-lg font-bold">🍄 ระบบโควต้าเห็ด</h1>
        <p class="text-green-300 text-xs mt-0.5">นครราชสีมา</p>
      </div>

      <nav class="flex-1 px-4 py-4 space-y-1">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-green-700 transition"
          active-class="bg-green-600 font-semibold"
        >
          <span>{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </router-link>
      </nav>

      <div class="px-4 py-4 border-t border-green-700">
        <p class="text-green-300 text-xs mb-2">{{ user?.name }}</p>
        <button
          @click="handleLogout"
          class="w-full text-left text-sm text-green-200 hover:text-white transition"
        >
          ออกจากระบบ
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-auto">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

const router = useRouter()
const { user, logout } = useAuth()

const navItems = [
  { to: '/app/dashboard', icon: '📊', label: 'แดชบอร์ด' },
  { to: '/app/quotas', icon: '📋', label: 'โควต้าอำเภอ' },
  { to: '/app/allocations', icon: '🌱', label: 'การจัดสรร' },
  { to: '/app/followups', icon: '📝', label: 'ติดตามผล' },
  { to: '/app/reports', icon: '📈', label: 'รายงาน' },
]

async function handleLogout() {
  await logout()
  router.push('/app/login')
}
</script>
