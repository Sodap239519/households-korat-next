<template>
  <div class="shop-storefront min-h-screen flex flex-col">
    <!-- ===== Header ===== -->
    <header class="sticky top-0 z-50 shadow-lg shop-header" style="background:linear-gradient(to right,#2e1065,#4c1d95,#5b21b6)">

      <!-- Single header row — all sizes -->
      <div class="max-w-7xl mx-auto px-3 sm:px-6">
        <div class="h-11 sm:h-14 flex items-center gap-2 sm:gap-4">

          <!-- Logo: Desktop only -->
          <RouterLink to="/shop" class="hidden md:flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 text-white text-lg shadow backdrop-blur">
              <i class="fi fi-rr-shop"></i>
            </span>
            <span>
              <span class="block font-bold text-white leading-tight">ตลาดชุมชนโคราช</span>
              <span class="block text-[11px] text-white/55 leading-tight">สินค้าจากกลุ่มแก้จน</span>
            </span>
          </RouterLink>

          <!-- Desktop nav -->
          <nav class="hidden lg:flex items-center gap-1 text-sm font-medium shrink-0">
            <RouterLink to="/shop" class="px-3 py-2 rounded-lg text-white/75 hover:text-white hover:bg-white/10" active-class="text-white bg-white/15">หน้าแรก</RouterLink>
            <RouterLink to="/shop/products" class="px-3 py-2 rounded-lg text-white/75 hover:text-white hover:bg-white/10" active-class="text-white bg-white/15">สินค้า</RouterLink>
          </nav>

          <!-- ── SEARCH (flex-1, all sizes) ── -->
          <div class="flex-1 relative min-w-0" ref="searchRef">
            <form @submit.prevent="doSearch">
              <div class="relative">
                <i class="fi fi-rr-search absolute left-3 top-1/2 -translate-y-1/2 text-white/50 text-sm pointer-events-none"></i>
                <input
                  v-model="search"
                  type="text"
                  :placeholder="mobilePlaceholder"
                  class="w-full pl-9 pr-8 h-8 sm:h-10 rounded-full bg-white/15 border border-white/20 text-white placeholder:text-white/50 text-sm focus:outline-none focus:bg-white/22 focus:border-white/40 transition"
                  @focus="searchFocused = true"
                  @keydown.escape="searchFocused = false"
                />
                <button v-if="search" type="button"
                  @mousedown.prevent="search = ''; searchFocused = false; router.replace({ path: '/shop/products' })"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/80 transition">
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
              <button class="w-8 h-8 sm:w-9 sm:h-9 rounded-full hover:bg-white/15 text-white/75 hover:text-white transition flex items-center justify-center" title="การแสดงผล" @click="accessOpen = !accessOpen">
                <i class="fi fi-rr-settings-sliders text-sm sm:text-base"></i>
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
            <RouterLink to="/shop/cart" class="relative w-8 h-8 sm:w-9 sm:h-9 rounded-full hover:bg-white/15 text-white/75 hover:text-white transition flex items-center justify-center">
              <i class="fi fi-rr-shopping-cart text-sm sm:text-base"></i>
              <span v-if="cart.count.value > 0" class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ cart.count.value }}
              </span>
            </RouterLink>

            <!-- ข้อความ (all sizes when logged in) -->
            <RouterLink v-if="user" to="/shop/chat" class="relative w-8 h-8 sm:w-9 sm:h-9 rounded-full hover:bg-white/15 text-white/75 hover:text-white transition flex items-center justify-center">
              <i class="fi fi-rr-comment-alt text-sm sm:text-base"></i>
              <span v-if="chatUnread > 0" class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ chatUnread }}
              </span>
            </RouterLink>

            <!-- แจ้งเตือนลูกค้า (Desktop: dropdown panel) -->
            <div v-if="user" class="relative hidden lg:block" ref="notifRef">
              <button class="relative w-8 h-8 sm:w-9 sm:h-9 rounded-full hover:bg-white/15 text-white/75 hover:text-white transition flex items-center justify-center"
                title="การแจ้งเตือน" @click="openNotif">
                <i class="fi fi-rr-bell text-base"></i>
                <span v-if="customerNotifCount > 0"
                  class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
                  {{ customerNotifCount > 99 ? '99+' : customerNotifCount }}
                </span>
              </button>
              <!-- Dropdown -->
              <div v-if="notifOpen"
                class="absolute top-full right-0 mt-1 w-80 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                  <span class="font-semibold text-slate-700 flex items-center gap-2">
                    <i class="fi fi-rr-bell text-violet-500 text-sm"></i> การแจ้งเตือน
                  </span>
                  <button v-if="customerNotifCount > 0" @click="markAllNotifRead"
                    class="text-[11px] text-violet-600 hover:text-violet-800 font-medium transition">
                    อ่านทั้งหมด
                  </button>
                </div>
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-50">
                  <div v-if="!notifications.length" class="px-4 py-8 text-center text-sm text-slate-400">
                    <i class="fi fi-rr-bell-slash block text-2xl mb-2 opacity-40"></i>
                    ไม่มีการแจ้งเตือน
                  </div>
                  <RouterLink v-for="n in notifications" :key="n.id"
                    :to="n.link || '/shop/account/orders'"
                    class="flex gap-3 px-4 py-3 hover:bg-slate-50 transition"
                    :class="!n.read_at ? 'bg-violet-50/50' : ''"
                    @click="readNotif(n); notifOpen = false">
                    <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center mt-0.5"
                      :class="{
                        'bg-orange-100 text-orange-500': n.type === 'promotion',
                        'bg-violet-100 text-violet-500': n.type === 'abandoned_cart',
                        'bg-sky-100 text-sky-500':       n.type === 'shipping_reminder',
                        'bg-green-100 text-green-600':   n.type === 'shipped',
                      }">
                      <i class="text-sm" :class="{
                        'fi fi-rr-tags':           n.type === 'promotion',
                        'fi fi-rr-shopping-bag':   n.type === 'abandoned_cart',
                        'fi fi-rr-box-open':       n.type === 'shipping_reminder',
                        'fi fi-rr-truck-side':     n.type === 'shipped',
                      }"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-slate-700 leading-tight" :class="!n.read_at ? 'font-semibold' : ''">{{ n.title }}</p>
                      <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ n.message }}</p>
                      <p class="text-[10px] text-slate-400 mt-1">{{ formatNotifTime(n.created_at) }}</p>
                    </div>
                    <span v-if="!n.read_at" class="shrink-0 w-2 h-2 rounded-full bg-orange-400 mt-2"></span>
                  </RouterLink>
                </div>
              </div>
            </div>

            <!-- Wishlist (Desktop only) -->
            <RouterLink v-if="user" to="/shop/wishlist" class="relative hidden md:flex w-9 h-9 rounded-full hover:bg-white/15 text-white/75 hover:text-white transition items-center justify-center" title="รายการโปรด">
              <i class="fi fi-rr-heart text-base"></i>
              <span v-if="wishlist.count.value > 0" class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ wishlist.count.value }}
              </span>
            </RouterLink>

            <!-- บัญชี — desktop only; mobile ใช้ bottom nav sheet แทน -->
            <div class="relative hidden lg:block" ref="accountRef">
              <button class="flex items-center gap-2 p-1.5 sm:pl-2 sm:pr-3 rounded-full hover:bg-white/15 text-white transition" @click="accountOpen = !accountOpen">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/20 text-white text-sm">
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
                  <hr class="my-1 border-slate-100" />
                  <RouterLink to="/shop/seller-apply" class="flex items-center gap-2 px-3 py-2 hover:bg-violet-50 text-slate-600" @click="accountOpen = false">
                    <i class="fi fi-rr-shop text-violet-500"></i> สมัครเป็นผู้ขาย
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
    <main class="pb-20 lg:pb-0">
      <RouterView v-slot="{ Component }">
        <Transition name="fade-slide">
          <component :is="Component" :key="route.path" />
        </Transition>
      </RouterView>
    </main>

    <!-- ===== Mobile bottom nav (5 items) ===== -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 h-16 border-t border-violet-900/40 shadow-[0_-4px_24px_-8px_rgba(76,29,149,.65)] shop-bottom-nav" style="background:linear-gradient(to right,#2e1065,#4c1d95)">
      <div class="grid grid-cols-5 h-full">
        <RouterLink to="/shop" exact-active-class="shop-nav-active"
          class="relative flex flex-col items-center justify-center gap-0.5 text-white/45 h-full shrink-0">
          <i class="fi fi-rr-home shrink-0" style="font-size:18px;line-height:1"></i>
          <span style="font-size:10px;line-height:1">หน้าแรก</span>
        </RouterLink>

        <RouterLink to="/shop/products" active-class="shop-nav-active"
          class="relative flex flex-col items-center justify-center gap-0.5 text-white/45 h-full shrink-0">
          <i class="fi fi-rr-grid shrink-0" style="font-size:18px;line-height:1"></i>
          <span style="font-size:10px;line-height:1">สินค้า</span>
        </RouterLink>

        <!-- รายการโปรด -->
        <RouterLink :to="user ? '/shop/wishlist' : '/shop/login'" active-class="shop-nav-active"
          class="relative flex flex-col items-center justify-center gap-0.5 text-white/45 h-full shrink-0">
          <span class="relative">
            <i class="fi fi-rr-heart shrink-0" style="font-size:18px;line-height:1"></i>
            <span v-if="user && wishlist.count.value > 0"
              class="absolute -top-1.5 -right-2 min-w-[16px] h-[16px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center">
              {{ wishlist.count.value }}
            </span>
          </span>
          <span style="font-size:10px;line-height:1">รายการโปรด</span>
        </RouterLink>

        <!-- แจ้งเตือน -->
        <RouterLink :to="user ? '/shop/notifications' : '/shop/login'"
          active-class="shop-nav-active"
          class="relative flex flex-col items-center justify-center gap-0.5 text-white/45 h-full shrink-0">
          <span class="relative">
            <i class="fi fi-rr-bell shrink-0" style="font-size:18px;line-height:1"></i>
            <span v-if="customerNotifCount > 0"
              class="absolute -top-1.5 -right-2 min-w-[16px] h-[16px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
              {{ customerNotifCount > 99 ? '99+' : customerNotifCount }}
            </span>
          </span>
          <span style="font-size:10px;line-height:1">แจ้งเตือน</span>
        </RouterLink>

        <!-- บัญชี → หน้า AccountHome -->
        <RouterLink to="/shop/account" active-class="shop-nav-active"
          class="relative flex flex-col items-center justify-center gap-0.5 text-white/45 h-full shrink-0">
          <i class="fi fi-rr-user shrink-0" style="font-size:18px;line-height:1"></i>
          <span style="font-size:10px;line-height:1">{{ user ? 'บัญชี' : 'เข้าสู่ระบบ' }}</span>
        </RouterLink>
      </div>
    </nav>

    <Toast position="top-center" />

    <!-- ===== PWA: ชวนติดตั้งลงหน้าจอหลัก (มือถือที่ยังไม่ได้ติดตั้ง) ===== -->
    <PwaInstallPrompt />

    <!-- ===== Scroll-to-top button (ซ่อนในหน้าแชท + หน้า product detail) ===== -->
    <Transition name="fade-up">
      <button v-if="showScrollTop && !route.path.includes('/chat') && !isProductDetail" @click="scrollToTop"
        class="fixed bottom-20 lg:bottom-6 right-4 z-30 w-10 h-10 rounded-full bg-white/70 hover:bg-white/95 active:scale-95 text-violet-600 border border-violet-200/80 shadow-lg backdrop-blur-sm flex items-center justify-center transition">
        <i class="fi fi-rr-angle-up text-sm"></i>
      </button>
    </Transition>

    <!-- ===== Footer + spacer (เฉพาะหน้า login และ account เท่านั้น) ===== -->
    <template v-if="showFooter">
      <footer class="shop-footer" style="background:linear-gradient(to right,#2e1065,#4c1d95);color:rgba(196,181,253,0.70)">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-2 flex flex-wrap items-center justify-between gap-2 text-xs">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-white/20 text-white text-[11px]">
              <i class="fi fi-rr-shop"></i>
            </span>
            <span class="font-medium" style="color:rgba(233,213,255,0.90)">ตลาดชุมชนโคราช</span>
          </div>
          <div class="hidden sm:flex items-center gap-4">
            <RouterLink to="/shop" class="hover:text-white transition">หน้าแรก</RouterLink>
            <RouterLink to="/shop/products" class="hover:text-white transition">สินค้าทั้งหมด</RouterLink>
            <RouterLink to="/shop/account/orders" class="hover:text-white transition">คำสั่งซื้อ</RouterLink>
          </div>
          <span style="color:rgba(167,139,250,0.50)">© {{ year }} · households-korat</span>
        </div>
      </footer>
      <div class="lg:hidden h-16 shrink-0" style="background:linear-gradient(to right,#2e1065,#4c1d95)"></div>
    </template>

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
import PwaInstallPrompt from './components/PwaInstallPrompt.vue'
import Toast from 'primevue/toast'

const router = useRouter()
const route  = useRoute()
const cart   = useCart()
const { user, fetchUser, logout } = useAuth()
const display = useDisplaySettings()

const chatUnread         = ref(0)
const notifCount         = ref(0)
const customerNotifCount = ref(0)
const notifOpen          = ref(false)
const notifRef           = ref(null)
const notifications      = ref([])
const wishlist           = useWishlist()
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
  { value: 'light', label: 'โทนสว่าง', swatch: 'background:linear-gradient(120deg,#f5f3ff,#fce7f3);border-color:#e9d5ff' },
  { value: 'dark',  label: 'โทนม่วง',  swatch: 'background:linear-gradient(120deg,#ede9fe,#c4b5fd);border-color:#a78bfa' },
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

const isProductDetail = computed(() => /\/shop\/products\/.+/.test(route.path))
const showFooter = computed(() => {
  const p = route.path
  return p === '/shop/login' || p === '/shop/register' || p.startsWith('/shop/forgot')
})

const STAFF_ROLES = ['superadmin', 'admin', 'staff', 'area_staff']

async function onLogout() {
  accountOpen.value = false
  const wasRole    = user.value?.role
  const savedEmail = user.value?.email
  await logout()
  if (savedEmail) localStorage.setItem('shop_last_email', savedEmail)
  router.push(wasRole && STAFF_ROLES.includes(wasRole) ? '/login' : '/shop')
}

function onClickOutside(e) {
  if (accountRef.value && !accountRef.value.contains(e.target)) accountOpen.value = false
  if (accessRef.value  && !accessRef.value.contains(e.target))  accessOpen.value  = false
  if (searchRef.value  && !searchRef.value.contains(e.target))  searchFocused.value = false
  if (notifRef.value   && !notifRef.value.contains(e.target))   notifOpen.value   = false
}

watch(() => route.query.q, (q) => { search.value = q || '' }, { immediate: true })

// ซิงค์ตะกร้า + โหลด notif เมื่อ login
watch(user, (newUser, oldUser) => {
  if (newUser && !oldUser) {
    cart.syncToServer()
    fetchCustomerNotifCount()
    fetchChatUnread()
    wishlist.fetchIds(newUser)
  }
})

watch(search, (v) => {
  if (v === '' && route.path === '/shop/products' && route.query.q) {
    router.replace({ path: '/shop/products' })
  }
})

let chatTimer         = null
let notifTimer        = null
let customerNotifTimer = null
let placeholderTimer  = null

async function fetchChatUnread() {
  if (!user.value) return
  try { const { data } = await api.get('/shop/chat/unread'); chatUnread.value = data.count } catch { /* ignore */ }
}

async function fetchNotifCount() {
  if (!user.value) return
  try { const { data } = await api.get('/shop/my/notifications'); notifCount.value = data.count } catch { /* ignore */ }
}

async function fetchCustomerNotifCount() {
  if (!user.value) return
  try {
    const { data } = await api.get('/shop/my/customer-notifications/count')
    customerNotifCount.value = data.count
  } catch { /* ignore */ }
}

async function openNotif() {
  notifOpen.value = !notifOpen.value
  if (notifOpen.value) {
    try {
      const { data } = await api.get('/shop/my/customer-notifications', { params: { per_page: 10 } })
      notifications.value = data.data ?? data
    } catch { /* ignore */ }
  }
}

async function markAllNotifRead() {
  try {
    await api.post('/shop/my/customer-notifications/read-all')
    notifications.value.forEach(n => { n.read_at = new Date().toISOString() })
    customerNotifCount.value = 0
  } catch { /* ignore */ }
}

async function readNotif(notif) {
  if (notif.read_at) return
  try {
    await api.post(`/shop/my/customer-notifications/${notif.id}/read`)
    notif.read_at = new Date().toISOString()
    customerNotifCount.value = Math.max(0, customerNotifCount.value - 1)
  } catch { /* ignore */ }
}

function formatNotifTime(isoStr) {
  if (!isoStr) return ''
  const diff = Math.floor((Date.now() - new Date(isoStr)) / 60000)
  if (diff < 1)   return 'เมื่อกี้'
  if (diff < 60)  return `${diff} นาทีที่แล้ว`
  if (diff < 1440) return `${Math.floor(diff / 60)} ชั่วโมงที่แล้ว`
  return `${Math.floor(diff / 1440)} วันที่แล้ว`
}

router.beforeEach(() => { navigating.value = true })
router.afterEach(() => { setTimeout(() => { navigating.value = false }, 150) })

onMounted(() => {
  if (user.value === null) fetchUser()
  document.addEventListener('click', onClickOutside)
  window.addEventListener('scroll', handleScroll, { passive: true })

  fetchChatUnread()
  fetchNotifCount()
  fetchCustomerNotifCount()
  wishlist.fetchIds(user.value)
  chatTimer         = setInterval(fetchChatUnread,         30000)
  notifTimer        = setInterval(fetchNotifCount,         30000)
  customerNotifTimer = setInterval(fetchCustomerNotifCount, 60000)
  placeholderTimer = setInterval(() => {
    placeholderIndex.value = (placeholderIndex.value + 1) % POPULAR_TAGS.length
  }, 2500)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  window.removeEventListener('scroll', handleScroll)
  clearInterval(chatTimer)
  clearInterval(notifTimer)
  clearInterval(customerNotifTimer)
  clearInterval(placeholderTimer)
})
</script>
