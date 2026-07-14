<template>
  <div class="max-w-md mx-auto px-4 py-10">
    <div class="box-card p-6 sm:p-8">
      <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl brand-gradient text-white text-2xl shadow-lg mb-3"><i class="fi fi-rr-user-add"></i></div>
        <h1 class="text-xl font-bold text-slate-800">สมัครสมาชิก</h1>
        <p class="text-sm text-slate-400 mt-1">สมัครฟรี เพื่อสั่งซื้อสินค้าชุมชน</p>
      </div>

      <form @submit.prevent="submit" class="space-y-3">
        <div>
          <label class="form-label">ชื่อ-นามสกุล</label>
          <input v-model="form.name" type="text" required class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none" :class="{ 'border-rose-400': err.name }" />
          <small v-if="err.name" class="text-rose-500">{{ err.name }}</small>
        </div>
        <div>
          <label class="form-label">อีเมล</label>
          <input v-model="form.email" type="email" required class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none" :class="{ 'border-rose-400': err.email }" />
          <small v-if="err.email" class="text-rose-500">{{ err.email }}</small>
        </div>
        <div>
          <label class="form-label">เบอร์โทร (ไม่บังคับ)</label>
          <input v-model="form.phone" type="tel" class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none" />
        </div>
        <div>
          <label class="form-label">รหัสผ่าน</label>
          <input v-model="form.password" type="password" required class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none" :class="{ 'border-rose-400': err.password }" />
          <small v-if="err.password" class="text-rose-500">{{ err.password }}</small>
        </div>
        <div>
          <label class="form-label">ยืนยันรหัสผ่าน</label>
          <input v-model="form.password_confirmation" type="password" required class="w-full h-11 px-3 rounded-xl border border-slate-200 focus:border-violet-400 focus:outline-none" />
        </div>
        <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>
        <button type="submit" :disabled="loading" class="btn-sheen w-full h-11 rounded-full btn-orange font-semibold disabled:opacity-60">
          {{ loading ? 'กำลังสมัคร...' : 'สมัครสมาชิก' }}
        </button>
      </form>

      <p class="text-center text-sm text-slate-500 mt-5">
        มีบัญชีอยู่แล้ว?
        <RouterLink :to="{ path: '/shop/login', query: $route.query }" class="text-violet-600 font-medium hover:underline">เข้าสู่ระบบ</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/index.js'
import { useAuth } from '../../composables/useAuth.js'

const route = useRoute()
const router = useRouter()
const { user, fetchUser } = useAuth()

const form = reactive({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
const err = reactive({})
const error = ref('')
const loading = ref(false)

async function submit() {
  loading.value = true
  error.value = ''
  Object.keys(err).forEach(k => delete err[k])
  try {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
    await api.post('/customer/register', form)
    await fetchUser() // hydrate user หลังสมัคร (auto login)
    router.push(route.query.redirect || '/shop')
  } catch (e) {
    if (e.response?.status === 422) {
      Object.entries(e.response.data.errors || {}).forEach(([k, v]) => { err[k] = v[0] })
      error.value = 'กรุณาตรวจสอบข้อมูลที่กรอก'
    } else {
      error.value = e.response?.data?.message || 'สมัครไม่สำเร็จ'
    }
  } finally {
    loading.value = false
  }
}
</script>
