<template>
  <div class="max-w-md mx-auto px-4 py-10">
    <div class="box-card p-6 sm:p-8">
      <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl brand-gradient text-white text-2xl shadow-lg mb-3"><i class="fi fi-rr-user"></i></div>
        <h1 class="text-xl font-bold text-slate-800">เข้าสู่ระบบลูกค้า</h1>
        <p class="text-sm text-slate-400 mt-1">เข้าสู่ระบบเพื่อสั่งซื้อและติดตามคำสั่งซื้อ</p>
      </div>

      <form @submit.prevent="submit" class="space-y-3">
        <div>
          <label class="form-label">อีเมล</label>
          <input v-model="email" type="email" required class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none" placeholder="your@email.com" />
        </div>
        <div>
          <label class="form-label">รหัสผ่าน</label>
          <input v-model="password" type="password" required class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none" placeholder="••••••••" />
        </div>
        <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
        <button type="submit" :disabled="loading" class="btn-sheen w-full h-11 rounded-full btn-orange font-semibold disabled:opacity-60">
          {{ loading ? 'กำลังเข้าสู่ระบบ...' : 'เข้าสู่ระบบ' }}
        </button>
      </form>

      <p class="text-center text-sm text-slate-500 mt-5">
        ยังไม่มีบัญชี?
        <RouterLink :to="{ path: '/shop/register', query: $route.query }" class="text-violet-600 font-medium hover:underline">สมัครสมาชิก</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth.js'

const route = useRoute()
const router = useRouter()
const { login } = useAuth()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await login(email.value, password.value)
    router.push(route.query.redirect || '/shop')
  } catch (e) {
    error.value = e.response?.data?.errors?.email?.[0] || e.response?.data?.message || 'เข้าสู่ระบบไม่สำเร็จ'
  } finally {
    loading.value = false
  }
}
</script>
