<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-bell text-violet-500"></i>
        การแจ้งเตือน
      </h1>
      <button v-if="unreadCount > 0" @click="markAllRead"
        class="text-sm text-violet-600 hover:text-violet-800 font-medium transition flex items-center gap-1">
        <i class="fi fi-rr-check-double"></i> อ่านทั้งหมด
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 overflow-x-auto pb-2 -mx-1 px-1 mb-3">
      <button v-for="t in tabs" :key="t.key"
        class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap"
        :class="tab === t.key ? 'bg-violet-600 text-white shadow-sm' : 'box-card text-slate-600'"
        @click="setTab(t.key)">
        {{ t.label }}
        <span v-if="t.key === 'unread' && unreadCount > 0"
          class="ml-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold">
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
      </button>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-2">
      <div v-for="n in 5" :key="n" class="box-card h-20 skeleton"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!items.length" class="box-card p-14 text-center text-slate-400 mt-2">
      <i class="fi fi-rr-bell-slash text-4xl block mb-3 opacity-40"></i>
      <p class="text-sm">{{ tab === 'unread' ? 'ไม่มีการแจ้งเตือนที่ยังไม่ได้อ่าน' : 'ไม่มีการแจ้งเตือน' }}</p>
    </div>

    <!-- Notification list -->
    <div v-else class="space-y-1">
      <RouterLink v-for="n in items" :key="n.id"
        :to="n.link || '/shop/account/orders'"
        class="flex gap-3 box-card px-4 py-3 transition hover:shadow-md"
        :class="!n.read_at ? 'border-l-4 border-violet-400 bg-violet-50/60' : ''"
        @click="markRead(n)">

        <!-- Icon bubble -->
        <span class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center mt-0.5 text-base"
          :class="iconStyle(n.type)">
          <i :class="iconClass(n.type)"></i>
        </span>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm leading-snug text-slate-700"
            :class="!n.read_at ? 'font-semibold' : 'font-medium'">
            {{ n.title }}
          </p>
          <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ n.message }}</p>
          <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
            <i class="fi fi-rr-clock"></i>
            {{ formatTime(n.created_at) }}
          </p>
        </div>

        <!-- Unread dot -->
        <span v-if="!n.read_at" class="shrink-0 w-2.5 h-2.5 rounded-full bg-orange-400 mt-1.5"></span>
      </RouterLink>
    </div>

    <!-- Load more -->
    <div v-if="hasMore && !loading" class="text-center mt-4">
      <button @click="loadMore"
        class="px-6 py-2.5 rounded-full border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition"
        :disabled="loadingMore">
        <span v-if="loadingMore"><i class="fi fi-rr-loading animate-spin mr-1"></i>กำลังโหลด...</span>
        <span v-else>โหลดเพิ่มเติม</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/index.js'

const tabs = [
  { key: 'all',    label: 'ทั้งหมด' },
  { key: 'unread', label: 'ยังไม่อ่าน' },
]
const tab        = ref('all')
const items      = ref([])
const loading    = ref(true)
const loadingMore = ref(false)
const unreadCount = ref(0)
const page       = ref(1)
const hasMore    = ref(false)

async function fetchNotifications(reset = true) {
  if (reset) { page.value = 1; loading.value = true }
  else loadingMore.value = true
  try {
    const { data } = await api.get('/shop/my/customer-notifications', {
      params: { per_page: 15, page: page.value, unread_only: tab.value === 'unread' ? 1 : undefined }
    })
    const list = data.data ?? data
    if (reset) items.value = list
    else items.value.push(...list)
    hasMore.value = list.length === 15
  } catch { /* ignore */ }
  loading.value = false
  loadingMore.value = false
}

async function fetchUnreadCount() {
  try {
    const { data } = await api.get('/shop/my/customer-notifications/count')
    unreadCount.value = data.count ?? 0
  } catch { /* ignore */ }
}

async function markRead(notif) {
  if (notif.read_at) return
  try {
    await api.post(`/shop/my/customer-notifications/${notif.id}/read`)
    notif.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch { /* ignore */ }
}

async function markAllRead() {
  try {
    await api.post('/shop/my/customer-notifications/read-all')
    items.value.forEach(n => { n.read_at = n.read_at || new Date().toISOString() })
    unreadCount.value = 0
  } catch { /* ignore */ }
}

function setTab(key) {
  tab.value = key
  fetchNotifications()
}

function loadMore() {
  page.value++
  fetchNotifications(false)
}

function iconStyle(type) {
  const map = {
    promotion:        'bg-orange-100 text-orange-500',
    flash_sale:       'bg-red-100 text-red-500',
    abandoned_cart:   'bg-violet-100 text-violet-500',
    shipping_reminder:'bg-sky-100 text-sky-500',
    shipped:          'bg-green-100 text-green-600',
    order_confirmed:  'bg-emerald-100 text-emerald-600',
    payment_rejected: 'bg-red-100 text-red-500',
  }
  return map[type] ?? 'bg-slate-100 text-slate-500'
}

function iconClass(type) {
  const map = {
    promotion:        'fi fi-rr-tags',
    flash_sale:       'fi fi-rr-bolt',
    abandoned_cart:   'fi fi-rr-shopping-bag',
    shipping_reminder:'fi fi-rr-box-open',
    shipped:          'fi fi-rr-truck-side',
    order_confirmed:  'fi fi-rr-check-circle',
    payment_rejected: 'fi fi-rr-cross-circle',
  }
  return map[type] ?? 'fi fi-rr-bell'
}

function formatTime(isoStr) {
  if (!isoStr) return ''
  const diff = Math.floor((Date.now() - new Date(isoStr)) / 60000)
  if (diff < 1)    return 'เมื่อกี้'
  if (diff < 60)   return `${diff} นาทีที่แล้ว`
  if (diff < 1440) return `${Math.floor(diff / 60)} ชั่วโมงที่แล้ว`
  return `${Math.floor(diff / 1440)} วันที่แล้ว`
}

onMounted(() => {
  fetchUnreadCount()
  fetchNotifications()
})
</script>
