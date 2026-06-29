<template>
  <div class="min-h-[calc(100vh-4rem)] bg-slate-100 pb-4">

    <!-- ===== กรณียังไม่ login ===== -->
    <template v-if="!user">
      <div class="bg-gradient-to-br from-violet-600 via-violet-700 to-fuchsia-700 px-5 pt-10 pb-14 text-center text-white">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/20 text-4xl mb-4">
          <i class="fi fi-rr-user"></i>
        </div>
        <h2 class="font-bold text-xl mb-1">เข้าสู่ระบบ</h2>
        <p class="text-white/70 text-sm">เพื่อจัดการคำสั่งซื้อและบัญชีของคุณ</p>
      </div>
      <div class="max-w-sm mx-auto px-5 -mt-6 space-y-3">
        <RouterLink to="/shop/login"
          class="flex items-center justify-center gap-2 h-12 w-full rounded-xl btn-orange font-semibold shadow-lg">
          <i class="fi fi-rr-sign-in-alt"></i> เข้าสู่ระบบ
        </RouterLink>
        <RouterLink to="/shop/register"
          class="flex items-center justify-center gap-2 h-12 w-full rounded-xl bg-white border border-violet-300 text-violet-700 font-semibold hover:bg-violet-50 transition">
          <i class="fi fi-rr-user-add"></i> สมัครสมาชิกใหม่
        </RouterLink>
      </div>
    </template>

    <!-- ===== กรณี login แล้ว ===== -->
    <template v-else>
      <!-- Profile header -->
      <div class="bg-gradient-to-br from-violet-600 via-violet-700 to-fuchsia-700 px-5 pt-8 pb-14 text-white">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 rounded-full bg-white/20 border-2 border-white/40 overflow-hidden flex items-center justify-center text-3xl shrink-0">
            <img v-if="user.avatar_path" :src="`/storage/${user.avatar_path}`" :alt="user.name" class="w-full h-full object-cover" />
            <i v-else class="fi fi-rr-user"></i>
          </div>
          <div class="flex-1 min-w-0">
            <h2 class="font-bold text-lg truncate">{{ user.name }}</h2>
            <p class="text-white/60 text-xs truncate">{{ user.email }}</p>
          </div>
          <RouterLink to="/shop/account/profile"
            class="shrink-0 w-9 h-9 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center transition">
            <i class="fi fi-rr-settings text-sm"></i>
          </RouterLink>
        </div>
      </div>

      <!-- Order status cards -->
      <div class="max-w-2xl mx-auto px-4 -mt-6">
        <div class="box-card rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <span class="font-semibold text-slate-700 text-sm">การซื้อของฉัน</span>
            <RouterLink to="/shop/account/orders" class="text-xs text-violet-600 hover:underline flex items-center gap-1">
              ดูประวัติทั้งหมด <i class="fi fi-rr-angle-small-right"></i>
            </RouterLink>
          </div>
          <div class="grid grid-cols-4 py-4">
            <RouterLink to="/shop/account/orders?status=to_pay"
              class="flex flex-col items-center gap-2 group">
              <span class="relative">
                <i class="fi fi-rr-wallet text-2xl text-slate-600 group-hover:text-violet-600 transition"></i>
                <span v-if="counts.to_pay > 0"
                  class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
                  {{ counts.to_pay }}
                </span>
              </span>
              <span class="text-[11px] text-slate-600 text-center leading-tight">ที่ต้องชำระ</span>
            </RouterLink>
            <RouterLink to="/shop/account/orders?status=to_ship"
              class="flex flex-col items-center gap-2 group">
              <span class="relative">
                <i class="fi fi-rr-box-open text-2xl text-slate-600 group-hover:text-violet-600 transition"></i>
                <span v-if="counts.to_ship > 0"
                  class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
                  {{ counts.to_ship }}
                </span>
              </span>
              <span class="text-[11px] text-slate-600 text-center leading-tight">ที่ต้องจัดส่ง</span>
            </RouterLink>
            <RouterLink to="/shop/account/orders?status=to_receive"
              class="flex flex-col items-center gap-2 group">
              <span class="relative">
                <i class="fi fi-rr-truck-side text-2xl text-slate-600 group-hover:text-violet-600 transition"></i>
                <span v-if="counts.to_receive > 0"
                  class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center">
                  {{ counts.to_receive }}
                </span>
              </span>
              <span class="text-[11px] text-slate-600 text-center leading-tight">ที่ต้องได้รับ</span>
            </RouterLink>
            <RouterLink to="/shop/account/orders?status=completed"
              class="flex flex-col items-center gap-2 group">
              <span class="relative">
                <i class="fi fi-rr-star text-2xl text-slate-600 group-hover:text-violet-600 transition"></i>
              </span>
              <span class="text-[11px] text-slate-600 text-center leading-tight">ให้คะแนน</span>
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Quick links -->
      <div class="max-w-2xl mx-auto px-4 mt-4 space-y-3">
        <!-- Section 1: บัญชีของฉัน -->
        <div class="box-card rounded-2xl overflow-hidden">
          <div class="px-4 py-2.5 border-b border-slate-50">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">บัญชีของฉัน</span>
          </div>
          <RouterLink to="/shop/account/profile"
            class="flex items-center gap-3 px-4 py-3.5 hover:bg-violet-50 transition">
            <span class="w-9 h-9 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
              <i class="fi fi-rr-user-pen text-sm"></i>
            </span>
            <span class="flex-1 text-sm font-medium text-slate-700">ข้อมูลส่วนตัว</span>
            <i class="fi fi-rr-angle-small-right text-slate-300"></i>
          </RouterLink>
          <RouterLink to="/shop/account/addresses"
            class="flex items-center gap-3 px-4 py-3.5 hover:bg-violet-50 transition border-t border-slate-50">
            <span class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
              <i class="fi fi-rr-marker text-sm"></i>
            </span>
            <span class="flex-1 text-sm font-medium text-slate-700">ที่อยู่จัดส่ง</span>
            <i class="fi fi-rr-angle-small-right text-slate-300"></i>
          </RouterLink>
        </div>

        <!-- Section 2: กิจกรรม -->
        <div class="box-card rounded-2xl overflow-hidden">
          <div class="px-4 py-2.5 border-b border-slate-50">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">กิจกรรม</span>
          </div>
          <RouterLink to="/shop/wishlist"
            class="flex items-center gap-3 px-4 py-3.5 hover:bg-rose-50 transition">
            <span class="w-9 h-9 rounded-full bg-rose-100 text-rose-500 flex items-center justify-center shrink-0">
              <i class="fi fi-rr-heart text-sm"></i>
            </span>
            <span class="flex-1 text-sm font-medium text-slate-700">รายการโปรด</span>
            <span v-if="wishlistCount > 0"
              class="text-xs bg-rose-100 text-rose-600 rounded-full px-2 py-0.5 font-semibold mr-1">
              {{ wishlistCount }}
            </span>
            <i class="fi fi-rr-angle-small-right text-slate-300"></i>
          </RouterLink>
          <RouterLink to="/shop/chat"
            class="flex items-center gap-3 px-4 py-3.5 hover:bg-violet-50 transition border-t border-slate-50">
            <span class="w-9 h-9 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
              <i class="fi fi-rr-comment-alt text-sm"></i>
            </span>
            <span class="flex-1 text-sm font-medium text-slate-700">ข้อความ</span>
            <span v-if="chatUnread > 0"
              class="text-xs bg-orange-100 text-orange-600 rounded-full px-2 py-0.5 font-semibold mr-1">
              {{ chatUnread }}
            </span>
            <i class="fi fi-rr-angle-small-right text-slate-300"></i>
          </RouterLink>
        </div>

        <!-- Logout -->
        <button @click="onLogout"
          class="w-full box-card rounded-2xl px-4 py-3.5 flex items-center gap-3 hover:bg-rose-50 transition text-left">
          <span class="w-9 h-9 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
            <i class="fi fi-rr-sign-out-alt text-sm"></i>
          </span>
          <span class="text-sm font-medium text-rose-600">ออกจากระบบ</span>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth.js'
import { useWishlist } from '../../composables/useWishlist.js'
import api from '../../api/index.js'

const router  = useRouter()
const { user, logout } = useAuth()
const wishlist = useWishlist()

const wishlistCount = computed(() => wishlist.count.value)
const chatUnread = ref(0)
const counts = ref({ to_pay: 0, to_ship: 0, to_receive: 0 })

const STAFF_ROLES = ['superadmin', 'admin', 'staff', 'area_staff']

async function onLogout() {
  const wasRole = user.value?.role
  await logout()
  router.push(wasRole && STAFF_ROLES.includes(wasRole) ? '/login' : '/shop')
}

async function loadData() {
  if (!user.value) return
  try {
    const [chatRes, payRes, shipRes, recvRes] = await Promise.all([
      api.get('/shop/chat/unread'),
      api.get('/shop/my/orders', { params: { status_group: 'to_pay',     per_page: 1 } }),
      api.get('/shop/my/orders', { params: { status_group: 'to_ship',    per_page: 1 } }),
      api.get('/shop/my/orders', { params: { status_group: 'to_receive', per_page: 1 } }),
    ])
    chatUnread.value  = chatRes.data.count || 0
    counts.value.to_pay     = payRes.data.total  || 0
    counts.value.to_ship    = shipRes.data.total  || 0
    counts.value.to_receive = recvRes.data.total  || 0
  } catch { /* ignore */ }
}

onMounted(loadData)
</script>
