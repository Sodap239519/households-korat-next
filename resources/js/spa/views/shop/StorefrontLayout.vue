<template>
  <div class="min-h-screen flex flex-col bg-white">
    <!-- ===== Header ===== -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-violet-100 shadow-sm">

      <!-- Single header row — all sizes -->
      <div class="max-w-7xl mx-auto px-3 sm:px-6">
        <div class="h-14 flex items-center gap-2 sm:gap-4">

          <!-- Logo: Desktop only -->
          <RouterLink to="/shop" class="hidden md:flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl brand-gradient text-white text-lg shadow-md shadow-violet-500/30">
              <i class="fi fi-rr-shop"></i>
            </span>
            <span>
              <span class="block font-bold text-slate-800 leading-tight">ตลาดชุมชนโคราช</span>
              <span class="block text-[11px] text-slate-400 leading-tight">สินค้าจากกลุ่มแก้จน</span>
            </span>
          </RouterLink>

          <!-- Desktop nav -->
          <nav class="hidden lg:flex items-center gap-1 text-sm font-medium text-slate-600 shrink-0">
            <RouterLink to="/shop" class="px-3 py-2 rounded-lg hover:text-violet-700 hover:bg-violet-50" active-class="text-violet-700">หน้าแรก</RouterLink>
            <RouterLink to="/shop/products" class="px-3 py-2 rounded-lg hover:text-violet-700 hover:bg-violet-50" active-class="text-violet-700">สินค้า</RouterLink>
          </nav>

          <!-- ── SEARCH (flex-1, all sizes) ── -->
          <div class="flex-1 relative min-w-0" ref="searchRef">
            <form @submit.prevent="doSearch">
              <div class="relative">
                <i class="fi fi-rr-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                <input
                  v-model="search"
                  type="text"
                  :placeholder="mobilePlaceholder"
                  class="w-full pl-9 pr-8 h-10 rounded-full border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-violet-400 focus:bg-white transition"
                  @focus="searchFocused = true"
                  @keydown.escape="searchFocused = false"
                />
                <button v-if="search" type="button"
                  @mousedown.prevent="search = ''; searchFocused = false; router.replace({ path: '/shop/products' })"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                  <i class="fi fi-rr-cross-circle text-sm"></i>
                </button>
              </div>
            </form>
            <!-- Search suggestions panel -->
            <div v-if="searchFocused"
              class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 p-4">
              <SearchPanel
                :history="searchHistory"
                :popular="POPULAR_TAGS"
                @go="searchAndGo"
                @remove="removeHistory"
                @clear="clearHistory"
              />
            </div>
          </div>

          <!-- ── ICONS: ตัวกรอง | ตะกร้า | ข้อความ | [wishlist md+] | บัญชี ── -->
          <div class="flex items-center gap-0.5 shrink-0">

            <!-- ตัวกรอง (Display settings) -->
            <div class="relative" ref="accessRef">
              <button class="w-9 h-9 rounded-full hover:bg-violet-50 text-slate-600 hover:text-violet-700 transition flex items-center justify-center" title="การแสดงผล" @click="accessOpen = !accessOpen">
                <i class="fi fi-rr-settings-sliders text-base"></i>
              </button>
              <div v-if="accessOpen" class="absolute top-full right-0 mt-1 w-60 bg-white rounded-xl shadow-xl border border-slate-100 p-3 text-sm z-50">
                <p class="font-semibold text-slate-700 mb-2 flex items-center gap-2"><i class="fi fi-rr-text-size text-violet-500"></i> ขนาดตัวอักษร</p>
                <div class="flex items-center gap-2 mb-3">
                  <button class="flex-1 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600" @click="display.decreaseFont()">ก-</button>
                  <button class="flex-1 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-500 text-xs" @click="display.resetFont()">รีเซ็ต</button>
                  <button class="flex-1 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 text-lg font-bold" @click="display.increaseFont()">ก+</button>
                </div>
                <p class="font-semibold text-slate-700 mb-2 flex items-center gap-2"><i class="fi fi-rr-palette text-orange-500"></i> โหมดการแสดงผล</p>
                <div class="space-y-1.5">
                  <button v-for="m in modes" :key="m.value"
                    class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg border transition"
                    :class="display.mode.value === m.value ? 'border-violet-400 bg-violet-50 text-violet-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                    @click="display.setMode(m.value)">
                    <span class="w-5 h-5 rounded-full border" :style="m.swatch"></span>
                    {{ m.label }}
                    <i v-if="display.mode.value === m.value" class="fi fi-rr-check ml-auto text-violet-600"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- ตะกร้า (all sizes) -->
            <RouterLink to="/shop/cart" class="relative w-9 h-9 rounded-full hover:bg-violet-50 text-slate-600 hover:text-violet-700 transition flex items-center justify-center">
              <i class="fi fi-rr-shopping-cart text-base"></i>
              <span v-if="cart.items.value.length > 0" class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ cart.items.value.length }}
              </span>
            </RouterLink>

            <!-- ข้อความ (all sizes when logged in) -->
            <RouterLink v-if="user" to="/shop/chat" class="relative w-9 h-9 rounded-full hover:bg-violet-50 text-slate-600 hover:text-violet-700 transition flex items-center justify-center">
              <i class="fi fi-rr-comment-alt text-base"></i>
              <span v-if="chatUnread > 0" class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ chatUnread }}
              </span>
            </RouterLink>

            <!-- Wishlist (Desktop only) -->
            <RouterLink v-if="user" to="/shop/wishlist" class="relative hidden md:flex w-9 h-9 rounded-full hover:bg-rose-50 text-slate-500 hover:text-rose-500 transition items-center justify-center" title="รายการโปรด">
              <i class="fi fi-rr-heart text-base"></i>
              <span v-if="wishlist.count.value > 0" class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ wishlist.count.value }}
              </span>
            </RouterLink>

            <!-- บัญชี — desktop only; mobile ใช้ bottom nav sheet แทน -->
            <div class="relative hidden lg:block" ref="accountRef">
              <button class="flex items-center gap-2 p-1.5 sm:pl-2 sm:pr-3 rounded-full hover:bg-violet-50 text-slate-700 transition" @click="accountOpen = !accountOpen">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-violet-100 text-violet-700 text-sm">
                  <i class="fi fi-rr-user"></i>
                </span>
                <span class="hidden sm:block text-sm font-medium max-w-[120px] truncate">{{ user ? user.name : 'เข้าสู่ระบบ' }}</span>
              </button>
              <div v-if="accountOpen" class="absolute top-full right-0 mt-1 w-52 bg-white rounded-xl shadow-lg border border-slate-100 py-1.5 text-sm z-50">
                <template v-if="user">
                  <div class="px-3 py-2 border-b border-slate-100">
                    <p class="font-semibold text-slate-700 truncate">{{ user.name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ user.email }}</p>
                  </div>
                  <RouterLink to="/shop/account/profile" class="flex items-center gap-2 px-3 py-2 hover:bg-violet-50 text-slate-600" @click="accountOpen = false">
                    <i class="fi fi-rr-user-pen text-violet-500"></i> โปรไฟล์ของฉัน
                  </RouterLink>
                  <RouterLink to="/shop/account/orders" class="flex items-center gap-2 px-3 py-2 hover:bg-violet-50 text-slate-600" @click="accountOpen = false">
                    <i class="fi fi-rr-box-open text-violet-500"></i> คำสั่งซื้อของฉัน
                  </RouterLink>
                  <RouterLink to="/shop/account/addresses" class="flex items-center gap-2 px-3 py-2 hover:bg-violet-50 text-slate-600" @click="accountOpen = false">
                    <i class="fi fi-rr-marker text-violet-500"></i> ที่อยู่จัดส่ง
                  </RouterLink>
                  <RouterLink to="/shop/wishlist" class="flex items-center gap-2 px-3 py-2 hover:bg-rose-50 text-slate-600" @click="accountOpen = false">
                    <i class="fi fi-rr-heart text-rose-400"></i> รายการโปรด
                    <span v-if="wishlist.count.value > 0" class="ml-auto text-[11px] bg-rose-500 text-white rounded-full px-1.5 py-0.5 font-bold">{{ wishlist.count.value }}</span>
                  </RouterLink>
                  <RouterLink to="/shop/chat" class="flex items-center gap-2 px-3 py-2 hover:bg-violet-50 text-slate-600" @click="accountOpen = false">
                    <i class="fi fi-rr-comment-alt text-violet-500"></i> ข้อความ
                    <span v-if="chatUnread > 0" class="ml-auto text-[11px] bg-orange-500 text-white rounded-full px-1.5 py-0.5 font-bold">{{ chatUnread }}</span>
                  </RouterLink>
                  <button class="w-full flex items-center gap-2 px-3 py-2 hover:bg-rose-50 text-rose-600" @click="onLogout">
                    <i class="fi fi-rr-sign-out-alt"></i> ออกจากระบบ
                  </button>
                </template>
                <template v-else>
                  <RouterLink to="/shop/login" class="flex items-center gap-2 px-3 py-2 hover:bg-violet-50 text-slate-600" @click="accountOpen = false">
                    <i class="fi fi-rr-sign-in-alt text-violet-500"></i> เข้าสู่ระบบ
                  </RouterLink>
                  <RouterLink to="/shop/register" class="flex items-center gap-2 px-3 py-2 hover:bg-violet-50 text-slate-600" @click="accountOpen = false">
                    <i class="fi fi-rr-user-add text-violet-500"></i> สมัครสมาชิก
                  </RouterLink>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Navigation loading bar -->
    <div class="fixed top-0 inset-x-0 z-[999] h-[3px] pointer-events-none">
      <div class="h-full bg-gradient-to-r from-violet-500 to-fuchsia-500 transition-all duration-300 ease-out"
        :style="navigating ? 'width:85%;opacity:1' : 'width:0%;opacity:0'"></div>
    </div>

    <!-- ===== Content ===== -->
    <main class="flex-1 pb-16 lg:pb-0">
      <RouterView v-slot="{ Component }">
        <Transition name="fade-slide">
          <component :is="Component" :key="route.path" />
        </Transition>
      </RouterView>
    </main>

    <!-- ===== Mobile bottom nav (5 items) ===== -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-violet-100 shadow-[0_-4px_20px_-8px_rgba(124,58,237,.25)]">
      <div class="grid grid-cols-5 h-16">
        <RouterLink to="/shop" exact-active-class="text-violet-700"
          class="flex flex-col items-center justify-center gap-0.5 text-slate-500">
          <i class="fi fi-rr-home text-[18px]"></i>
          <span class="text-[10px]">หน้าแรก</span>
        </RouterLink>

        <RouterLink to="/shop/products" active-class="text-violet-700"
          class="flex flex-col items-center justify-center gap-0.5 text-slate-500">
          <i class="fi fi-rr-grid text-[18px]"></i>
          <span class="text-[10px]">สินค้า</span>
        </RouterLink>

        <!-- รายการโปรด -->
        <RouterLink :to="user ? '/shop/wishlist' : '/shop/login'" active-class="text-violet-700"
          class="relative flex flex-col items-center justify-center gap-0.5 text-slate-500">
          <span class="relative">
            <i class="fi fi-rr-heart text-[18px]"></i>
            <span v-if="user && wishlist.count.value > 0"
              class="absolute -top-1.5 -right-2 min-w-[16px] h-[16px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center">
              {{ wishlist.count.value }}
            </span>
          </span>
          <span class="text-[10px]">รายการโปรด</span>
        </RouterLink>

        <!-- แจ้งเตือน -->
        <RouterLink :to="user ? '/shop/account/orders' : '/shop/login'"
          active-class="text-violet-700"
          class="relative flex flex-col items-center justify-center gap-0.5 text-slate-500">
          <span class="relative">
            <i class="fi fi-rr-bell text-[18px]"></i>
            <span v-if="notifCount > 0"
              class="absolute -top-1.5 -right-2 min-w-[16px] h-[16px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
              {{ notifCount }}
            </span>
          </span>
          <span class="text-[10px]">แจ้งเตือน</span>
        </RouterLink>

        <!-- บัญชี → หน้า AccountHome -->
        <RouterLink to="/shop/account" active-class="text-violet-700"
          class="flex flex-col items-center justify-center gap-0.5 text-slate-500">
          <i class="fi fi-rr-user text-[18px]"></i>
          <span class="text-[10px]">{{ user ? 'บัญชี' : 'เข้าสู่ระบบ' }}</span>
        </RouterLink>
      </div>
    </nav>

    <Toast position="top-center" />

    <!-- ===== Scroll-to-top button ===== -->
    <Transition name="fade-up">
      <button v-if="showScrollTop" @click="scrollToTop"
        class="fixed bottom-20 lg:bottom-6 right-4 z-30 w-10 h-10 rounded-full bg-violet-600 hover:bg-violet-700 active:scale-95 text-white shadow-lg shadow-violet-500/30 flex items-center justify-center transition">
        <i class="fi fi-rr-arrow-up text-sm"></i>
      </button>
    </Transition>

    <!-- ===== Footer (slim strip) ===== -->
    <footer class="bg-slate-800 text-slate-400 pb-20 lg:pb-0">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white text-[11px]">
            <i class="fi fi-rr-shop"></i>
          </span>
          <span class="text-slate-300 font-medium">ตลาดชุมชนโคราช</span>
        </div>
        <div class="hidden sm:flex items-center gap-4">
          <RouterLink to="/shop" class="hover:text-white transition">หน้าแรก</RouterLink>
          <RouterLink to="/shop/products" class="hover:text-white transition">สินค้าทั้งหมด</RouterLink>
          <RouterLink to="/shop/account/orders" class="hover:text-white transition">คำสั่งซื้อ</RouterLink>
        </div>
        <span class="text-slate-500">© {{ year }} · households-korat</span>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useCart } from '../../composables/useCart.js'
import { useAuth } from '../../composables/useAuth.js'
import { useDisplaySettings } from '../../composables/useDisplaySettings.js'
import { useWishlist } from '../../composables/useWishlist.js'
import api from '../../api/index.js'
import SearchPanel from './components/SearchPanel.vue'
import Toast from 'primevue/toast'

const router = useRouter()
const route  = useRoute()
const cart   = useCart()
const { user, fetchUser, logout } = useAuth()
const display = useDisplaySettings()

const chatUnread  = ref(0)
const notifCount  = ref(0)
const wishlist    = useWishlist()
const search      = ref('')
const accountOpen = ref(false)
const accountRef  = ref(null)
const accessOpen  = ref(false)
const accessRef   = ref(null)
const searchRef     = ref(null)
const searchFocused = ref(false)
const placeholderIndex    = ref(0)
const showScrollTop = ref(false)
const navigating    = ref(false)
const year = new Date().getFullYear() + 543

function handleScroll() { showScrollTop.value = window.scrollY > 300 }
function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }) }

const POPULAR_TAGS = ['เห็ด', 'ผักสด', 'ข้าว', 'น้ำพริก', 'ผลิตภัณฑ์แปรรูป', 'สมุนไพร', 'ผลไม้', 'ขนมไทย']
const HISTORY_KEY  = 'shop_search_history'
const searchHistory = ref(JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]'))

const mobilePlaceholder = computed(() => `ค้นหา "${POPULAR_TAGS[placeholderIndex.value]}"...`)

const modes = [
  { value: 'light', label: 'โทนสว่าง', swatch: 'background:linear-gradient(120deg,#f3e8ff,#fed7aa);border-color:#e9d5ff' },
  { value: 'dark',  label: 'โทนมืด',   swatch: 'background:linear-gradient(120deg,#2e1065,#0f172a);border-color:#1e1b3a' },
]

function saveHistory(term) {
  if (!term.trim()) return
  const h = [term, ...searchHistory.value.filter(h => h !== term)].slice(0, 8)
  searchHistory.value = h
  localStorage.setItem(HISTORY_KEY, JSON.stringify(h))
}

function doSearch() {
  const term = search.value.trim() || POPULAR_TAGS[placeholderIndex.value]
  search.value = term
  saveHistory(term)
  searchFocused.value = false
  router.push({ path: '/shop/products', query: { q: term } })
}

function searchAndGo(term) {
  search.value = term
  saveHistory(term)
  searchFocused.value = false
  router.push({ path: '/shop/products', query: { q: term } })
}

function removeHistory(index) {
  searchHistory.value.splice(index, 1)
  localStorage.setItem(HISTORY_KEY, JSON.stringify(searchHistory.value))
}

function clearHistory() {
  searchHistory.value = []
  localStorage.removeItem(HISTORY_KEY)
}

const STAFF_ROLES = ['superadmin', 'admin', 'staff', 'area_staff']

async function onLogout() {
  accountOpen.value = false
  const wasRole = user.value?.role
  await logout()
  router.push(wasRole && STAFF_ROLES.includes(wasRole) ? '/login' : '/shop')
}

function onClickOutside(e) {
  if (accountRef.value && !accountRef.value.contains(e.target)) accountOpen.value = false
  if (accessRef.value  && !accessRef.value.contains(e.target))  accessOpen.value  = false
  if (searchRef.value  && !searchRef.value.contains(e.target))  searchFocused.value = false
}

watch(() => route.query.q, (q) => { search.value = q || '' }, { immediate: true })

watch(search, (v) => {
  if (v === '' && route.path === '/shop/products' && route.query.q) {
    router.replace({ path: '/shop/products' })
  }
})

let chatTimer   = null
let notifTimer  = null
let placeholderTimer = null

async function fetchChatUnread() {
  if (!user.value) return
  try { const { data } = await api.get('/shop/chat/unread'); chatUnread.value = data.count } catch { /* ignore */ }
}

async function fetchNotifCount() {
  if (!user.value) return
  try { const { data } = await api.get('/shop/my/notifications'); notifCount.value = data.count } catch { /* ignore */ }
}

router.beforeEach(() => { navigating.value = true })
router.afterEach(() => { setTimeout(() => { navigating.value = false }, 150) })

onMounted(() => {
  if (user.value === null) fetchUser()
  document.addEventListener('click', onClickOutside)
  window.addEventListener('scroll', handleScroll, { passive: true })

  fetchChatUnread()
  fetchNotifCount()
  wishlist.fetchIds(user.value)
  chatTimer  = setInterval(fetchChatUnread,  30000)
  notifTimer = setInterval(fetchNotifCount,  30000)
  placeholderTimer = setInterval(() => {
    placeholderIndex.value = (placeholderIndex.value + 1) % POPULAR_TAGS.length
  }, 2500)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  window.removeEventListener('scroll', handleScroll)
  clearInterval(chatTimer)
  clearInterval(notifTimer)
  clearInterval(placeholderTimer)
})
</script>
