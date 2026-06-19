<template>
  <div class="min-h-screen flex flex-col bg-slate-50">
    <!-- ===== Header ===== -->
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-violet-100 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="h-16 flex items-center gap-3 sm:gap-5">
          <!-- Logo -->
          <RouterLink to="/shop" class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl brand-gradient text-white text-lg shadow-md shadow-violet-500/30">
              <i class="fi fi-rr-shop"></i>
            </span>
            <span class="hidden sm:block">
              <span class="block font-bold text-slate-800 leading-tight">ตลาดชุมชนโคราช</span>
              <span class="block text-[11px] text-slate-400 leading-tight">สินค้าจากกลุ่มแก้จน</span>
            </span>
          </RouterLink>

          <!-- Search -->
          <form class="flex-1 max-w-lg hidden md:flex" @submit.prevent="doSearch">
            <div class="relative w-full">
              <i class="fi fi-rr-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input
                v-model="search"
                type="text"
                placeholder="ค้นหาสินค้า..."
                class="w-full pl-9 pr-3 h-10 rounded-full border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-violet-400 focus:bg-white transition"
              />
            </div>
          </form>

          <!-- Nav -->
          <nav class="hidden lg:flex items-center gap-1 text-sm font-medium text-slate-600">
            <RouterLink to="/shop" class="px-3 py-2 rounded-lg hover:text-violet-700 hover:bg-violet-50" active-class="text-violet-700">หน้าแรก</RouterLink>
            <RouterLink to="/shop/products" class="px-3 py-2 rounded-lg hover:text-violet-700 hover:bg-violet-50" active-class="text-violet-700">สินค้าทั้งหมด</RouterLink>
          </nav>

          <div class="flex items-center gap-1 ml-auto lg:ml-0">
            <!-- Display settings (accessibility) -->
            <div class="relative" ref="accessRef">
              <button class="p-2.5 rounded-full hover:bg-violet-50 text-slate-600 hover:text-violet-700 transition" title="การแสดงผล" @click="accessOpen = !accessOpen">
                <i class="fi fi-rr-settings-sliders text-lg"></i>
              </button>
              <div v-if="accessOpen" class="absolute right-0 mt-2 w-60 bg-white rounded-xl shadow-xl border border-slate-100 p-3 text-sm z-40">
                <p class="font-semibold text-slate-700 mb-2 flex items-center gap-2"><i class="fi fi-rr-text-size text-violet-500"></i> ขนาดตัวอักษร</p>
                <div class="flex items-center gap-2 mb-3">
                  <button class="flex-1 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600" title="เล็กลง" @click="display.decreaseFont()">ก-</button>
                  <button class="flex-1 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-500 text-xs" @click="display.resetFont()">รีเซ็ต</button>
                  <button class="flex-1 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 text-lg font-bold" title="ใหญ่ขึ้น" @click="display.increaseFont()">ก+</button>
                </div>
                <p class="font-semibold text-slate-700 mb-2 flex items-center gap-2"><i class="fi fi-rr-palette text-orange-500"></i> โหมดการแสดงผล</p>
                <div class="space-y-1.5">
                  <button v-for="m in modes" :key="m.value" class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg border transition" :class="display.mode.value === m.value ? 'border-violet-400 bg-violet-50 text-violet-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'" @click="display.setMode(m.value)">
                    <span class="w-5 h-5 rounded-full border" :style="m.swatch"></span>
                    {{ m.label }}
                    <i v-if="display.mode.value === m.value" class="fi fi-rr-check ml-auto text-violet-600"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Cart -->
            <RouterLink to="/shop/cart" class="relative p-2.5 rounded-full hover:bg-violet-50 text-slate-600 hover:text-violet-700 transition">
              <i class="fi fi-rr-shopping-cart text-lg"></i>
              <span v-if="cart.count.value > 0" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-orange-500 text-white text-[11px] font-bold flex items-center justify-center">
                {{ cart.count.value }}
              </span>
            </RouterLink>

            <!-- Account -->
            <div class="relative" ref="accountRef">
              <button class="flex items-center gap-2 p-1.5 sm:pl-2 sm:pr-3 rounded-full hover:bg-violet-50 text-slate-700 transition" @click="accountOpen = !accountOpen">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-violet-100 text-violet-700 text-sm">
                  <i class="fi fi-rr-user"></i>
                </span>
                <span class="hidden sm:block text-sm font-medium max-w-[120px] truncate">{{ user ? user.name : 'เข้าสู่ระบบ' }}</span>
              </button>
              <div v-if="accountOpen" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-slate-100 py-1.5 text-sm">
                <template v-if="user">
                  <div class="px-3 py-2 border-b border-slate-100">
                    <p class="font-semibold text-slate-700 truncate">{{ user.name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ user.email }}</p>
                  </div>
                  <RouterLink to="/shop/account/orders" class="flex items-center gap-2 px-3 py-2 hover:bg-violet-50 text-slate-600" @click="accountOpen = false">
                    <i class="fi fi-rr-box-open text-violet-500"></i> คำสั่งซื้อของฉัน
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

        <!-- Mobile search -->
        <form class="md:hidden pb-3" @submit.prevent="doSearch">
          <div class="relative">
            <i class="fi fi-rr-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input v-model="search" type="text" placeholder="ค้นหาสินค้า..." class="w-full pl-9 pr-3 h-10 rounded-full border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-violet-400 focus:bg-white" />
          </div>
        </form>
      </div>
    </header>

    <!-- ===== Content ===== -->
    <main class="flex-1 pb-16 lg:pb-0">
      <RouterView v-slot="{ Component }">
        <Transition name="fade-slide" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>

    <!-- ===== Mobile bottom nav (app-like) ===== -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-violet-100 shadow-[0_-4px_20px_-8px_rgba(124,58,237,.25)]">
      <div class="grid grid-cols-4 h-16">
        <RouterLink to="/shop" class="flex flex-col items-center justify-center gap-0.5 text-slate-500" active-class="text-violet-700" exact-active-class="text-violet-700">
          <i class="fi fi-rr-home text-lg"></i><span class="text-[11px]">หน้าแรก</span>
        </RouterLink>
        <RouterLink to="/shop/products" class="flex flex-col items-center justify-center gap-0.5 text-slate-500" active-class="text-violet-700">
          <i class="fi fi-rr-grid text-lg"></i><span class="text-[11px]">สินค้า</span>
        </RouterLink>
        <RouterLink to="/shop/cart" class="relative flex flex-col items-center justify-center gap-0.5 text-slate-500" active-class="text-violet-700">
          <span class="relative">
            <i class="fi fi-rr-shopping-cart text-lg"></i>
            <span v-if="cart.count.value > 0" class="absolute -top-1.5 -right-2 min-w-[16px] h-[16px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">{{ cart.count.value }}</span>
          </span>
          <span class="text-[11px]">ตะกร้า</span>
        </RouterLink>
        <RouterLink :to="user ? '/shop/account/orders' : '/shop/login'" class="flex flex-col items-center justify-center gap-0.5 text-slate-500" active-class="text-violet-700">
          <i class="fi fi-rr-user text-lg"></i><span class="text-[11px]">{{ user ? 'บัญชี' : 'เข้าสู่ระบบ' }}</span>
        </RouterLink>
      </div>
    </nav>

    <!-- ===== Footer ===== -->
    <footer class="mt-12 bg-slate-900 text-slate-300">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid grid-cols-1 sm:grid-cols-3 gap-8">
        <div>
          <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white"><i class="fi fi-rr-shop"></i></span>
            <span class="font-bold text-white">ตลาดชุมชนโคราช</span>
          </div>
          <p class="text-sm text-slate-400 leading-relaxed">แพลตฟอร์มจำหน่ายสินค้าจากกลุ่มชุมชนในโครงการแก้จนจังหวัดนครราชสีมา</p>
        </div>
        <div>
          <h4 class="font-semibold text-white mb-3">เมนู</h4>
          <ul class="space-y-2 text-sm">
            <li><RouterLink to="/shop" class="hover:text-white">หน้าแรก</RouterLink></li>
            <li><RouterLink to="/shop/products" class="hover:text-white">สินค้าทั้งหมด</RouterLink></li>
            <li><RouterLink to="/shop/account/orders" class="hover:text-white">คำสั่งซื้อของฉัน</RouterLink></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold text-white mb-3">เกี่ยวกับ</h4>
          <p class="text-sm text-slate-400 leading-relaxed">ดำเนินการภายใต้โครงการวิจัยแก้จนเมืองนครราชสีมา</p>
        </div>
      </div>
      <div class="border-t border-slate-800 py-4 pb-20 lg:pb-4 text-center text-xs text-slate-500">
        © {{ year }} ตลาดชุมชนโคราช · households-korat
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useCart } from '../../composables/useCart.js'
import { useAuth } from '../../composables/useAuth.js'
import { useDisplaySettings } from '../../composables/useDisplaySettings.js'

const router = useRouter()
const cart = useCart()
const { user, fetchUser, logout } = useAuth()
const display = useDisplaySettings()

const search = ref('')
const accountOpen = ref(false)
const accountRef = ref(null)
const accessOpen = ref(false)
const accessRef = ref(null)
const year = new Date().getFullYear() + 543

const modes = [
  { value: 'light', label: 'โทนสว่าง', swatch: 'background:linear-gradient(120deg,#f3e8ff,#fed7aa);border-color:#e9d5ff' },
  { value: 'dark',  label: 'โทนมืด',   swatch: 'background:linear-gradient(120deg,#2e1065,#0f172a);border-color:#1e1b3a' },
]

function doSearch() {
  router.push({ path: '/shop/products', query: search.value ? { q: search.value } : {} })
}

async function onLogout() {
  accountOpen.value = false
  await logout()
  router.push('/shop')
}

function onClickOutside(e) {
  if (accountRef.value && !accountRef.value.contains(e.target)) accountOpen.value = false
  if (accessRef.value && !accessRef.value.contains(e.target)) accessOpen.value = false
}

onMounted(() => {
  if (user.value === null) fetchUser()
  document.addEventListener('click', onClickOutside)
})
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>
