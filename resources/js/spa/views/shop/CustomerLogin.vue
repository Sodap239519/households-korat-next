<template>
  <!-- Overlay backdrop -->
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="goBack">

    <!-- Modal card -->
    <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden animate-modal">

      <!-- Close button -->
      <button @click="goBack" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition z-10">
        <i class="fi fi-rr-cross-small text-lg"></i>
      </button>

      <div class="p-7">
        <!-- Header -->
        <div class="text-center mb-6">
          <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl brand-gradient text-white text-2xl shadow-lg shadow-violet-500/30 mb-3">
            <i class="fi fi-rr-user"></i>
          </div>
          <h1 class="text-xl font-bold text-slate-800">เข้าสู่ระบบ</h1>
          <p class="text-sm text-slate-400 mt-1">สั่งซื้อและติดตามคำสั่งซื้อของคุณ</p>
        </div>

        <!-- Login form -->
        <form v-if="mode === 'login'" @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="form-label">อีเมล</label>
            <input v-model="email" type="email" required autofocus
              class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-100 transition"
              placeholder="your@email.com" />
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="form-label mb-0">รหัสผ่าน</label>
              <button type="button" @click="mode = 'forgot'" class="text-xs text-violet-500 hover:text-violet-700 hover:underline">ลืมรหัสผ่าน?</button>
            </div>
            <div class="relative">
              <input v-model="password" :type="showPw ? 'text' : 'password'" required
                class="w-full h-11 pl-3 pr-10 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-100 transition"
                placeholder="••••••••" />
              <button type="button" @click="showPw = !showPw"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                <i :class="showPw ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye'"></i>
              </button>
            </div>
          </div>

          <p v-if="error" class="text-sm text-rose-500 flex items-center gap-1.5">
            <i class="fi fi-rr-circle-xmark"></i> {{ error }}
          </p>

          <button type="submit" :disabled="loading"
            class="btn-sheen w-full h-11 rounded-full btn-orange font-semibold disabled:opacity-60 mt-1">
            {{ loading ? 'กำลังเข้าสู่ระบบ...' : 'เข้าสู่ระบบ' }}
          </button>
        </form>

        <!-- Forgot password form -->
        <div v-else-if="mode === 'forgot'" class="space-y-4">
          <div class="text-center mb-2">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 text-orange-500 text-xl mb-2">
              <i class="fi fi-rr-lock"></i>
            </div>
            <h2 class="font-bold text-slate-700">ลืมรหัสผ่าน?</h2>
            <p class="text-sm text-slate-400 mt-1">กรอกอีเมลที่ใช้สมัคร เราจะส่งลิงก์รีเซ็ตให้</p>
          </div>

          <template v-if="!forgotSent">
            <div>
              <label class="form-label">อีเมล</label>
              <input v-model="forgotEmail" type="email" required autofocus
                class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-100 transition"
                placeholder="your@email.com" />
            </div>
            <p v-if="forgotError" class="text-sm text-rose-500">{{ forgotError }}</p>
            <button @click="submitForgot" :disabled="forgotLoading"
              class="btn-sheen w-full h-11 rounded-full btn-orange font-semibold disabled:opacity-60">
              {{ forgotLoading ? 'กำลังส่ง...' : 'ส่งลิงก์รีเซ็ต' }}
            </button>
            <button @click="mode = 'login'" class="w-full text-sm text-slate-400 hover:text-slate-600 text-center py-1">
              ← กลับเข้าสู่ระบบ
            </button>
          </template>

          <template v-else>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
              <i class="fi fi-rr-envelope-check text-emerald-500 text-2xl"></i>
              <p class="text-sm font-medium text-emerald-700 mt-2">ส่งลิงก์รีเซ็ตแล้ว!</p>
              <p class="text-xs text-emerald-600 mt-1">โปรดตรวจสอบอีเมล <strong>{{ forgotEmail }}</strong></p>
            </div>
            <button @click="mode = 'login'; forgotSent = false" class="w-full text-sm text-violet-600 hover:underline text-center py-1">
              กลับเข้าสู่ระบบ
            </button>
          </template>
        </div>

        <!-- Divider + register -->
        <div v-if="mode === 'login'" class="mt-5">
          <div class="relative flex items-center gap-3 text-xs text-slate-300 mb-4">
            <div class="flex-1 h-px bg-slate-100"></div>หรือ<div class="flex-1 h-px bg-slate-100"></div>
          </div>
          <RouterLink :to="{ path: '/shop/register', query: $route.query }"
            class="flex items-center justify-center gap-2 w-full h-11 rounded-full border border-slate-200 text-sm font-medium text-slate-600 hover:border-violet-300 hover:text-violet-700 transition">
            <i class="fi fi-rr-user-add text-violet-500"></i> สมัครสมาชิกใหม่
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth.js'
import api from '../../api/index.js'

const route  = useRoute()
const router = useRouter()
const { login } = useAuth()

const LAST_EMAIL_KEY = 'shop_last_email'
const mode      = ref('login')
const email     = ref('')

onMounted(() => {
  email.value = localStorage.getItem(LAST_EMAIL_KEY) || ''
})
const password  = ref('')
const showPw    = ref(false)
const loading   = ref(false)
const error     = ref('')

const forgotEmail   = ref('')
const forgotLoading = ref(false)
const forgotError   = ref('')
const forgotSent    = ref(false)

function goBack() {
  const redirect = route.query.redirect
  if (redirect && !redirect.startsWith('/shop/login')) {
    router.push(redirect)
  } else {
    router.push('/shop')
  }
}

const STAFF_ROLES = ['superadmin', 'admin', 'staff', 'area_staff']

async function submit() {
  loading.value = true
  error.value   = ''
  try {
    const loggedUser = await login(email.value, password.value)
    localStorage.setItem(LAST_EMAIL_KEY, email.value)
    // เจ้าหน้าที่/แอดมิน → เด้งไปหลังบ้านทันที
    if (loggedUser && STAFF_ROLES.includes(loggedUser.role)) {
      router.push('/app/market')
    } else {
      router.push(route.query.redirect || '/shop')
    }
  } catch (e) {
    error.value = e.response?.data?.errors?.email?.[0]
      || e.response?.data?.message
      || 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'
  } finally {
    loading.value = false
  }
}

async function submitForgot() {
  if (!forgotEmail.value) return
  forgotLoading.value = true
  forgotError.value   = ''
  try {
    await api.post('/customer/forgot-password', { email: forgotEmail.value })
    forgotSent.value = true
  } catch (e) {
    forgotError.value = e.response?.data?.message || 'ไม่พบอีเมลนี้ในระบบ'
  } finally {
    forgotLoading.value = false
  }
}
</script>

<style scoped>
.animate-modal {
  animation: modal-in .2s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes modal-in {
  from { opacity: 0; transform: scale(.92) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
