<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-gradient-to-br from-violet-100 via-fuchsia-50 to-purple-100">
    <!-- Decorative blobs -->
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-violet-300/40 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-[28rem] h-[28rem] rounded-full bg-fuchsia-300/40 blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[20rem] h-[20rem] rounded-full bg-purple-200/30 blur-3xl pointer-events-none"></div>

    <!-- Top-left back link -->
    <button
      @click="$router.push('/app')"
      class="absolute top-4 left-4 z-10 flex items-center gap-2 px-3 py-2 rounded-lg bg-white/70 hover:bg-white/90 backdrop-blur border border-violet-200 text-violet-700 text-sm font-medium shadow-sm transition"
    >
      <i class="fi fi-rr-arrow-small-left"></i>
      <span>กลับหน้าแรก</span>
    </button>

    <div class="relative w-full max-w-md">
      <div class="glass rounded-3xl shadow-2xl shadow-violet-500/20 p-8 sm:p-10">
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-600 text-white text-3xl shadow-lg shadow-violet-500/40 mb-4">
            <i class="fi fi-rr-leaf"></i>
          </div>
          <h1 class="text-2xl font-bold bg-gradient-to-r from-violet-700 via-purple-700 to-fuchsia-700 bg-clip-text text-transparent tracking-tight">
            Households Korat
          </h1>
          <p class="text-slate-500 text-sm mt-1">ระบบจัดการครัวเรือนเปราะบาง · จังหวัดนครราชสีมา</p>
        </div>

        <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
              <i class="fi fi-rr-envelope text-violet-600"></i>
              อีเมล
            </label>
            <InputText
              v-model="form.email"
              type="email"
              required
              placeholder="your@email.com"
              class="w-full"
              autocomplete="username"
            />
          </div>

          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
              <i class="fi fi-rr-key text-violet-600"></i>
              รหัสผ่าน
            </label>
            <Password
              v-model="form.password"
              :feedback="false"
              toggleMask
              required
              placeholder="••••••••"
              fluid
              inputClass="w-full"
              autocomplete="current-password"
            />
          </div>

          <Button
            type="submit"
            :loading="loading"
            label="เข้าสู่ระบบ"
            icon="fi fi-rr-sign-in-alt"
            iconPos="right"
            class="w-full"
            size="large"
          />
        </form>

        <div class="text-center text-xs text-slate-400 mt-8 space-x-2">
          <span>ยังไม่มีบัญชี?</span>
          <router-link to="/app/register" class="text-violet-600 hover:underline font-medium">สมัครสมาชิก</router-link>
          <span>·</span>
          <router-link to="/app" class="text-violet-600 hover:underline font-medium">กลับหน้าแรก</router-link>
        </div>
        <p class="text-center text-[10px] text-slate-300 mt-3">
          © {{ new Date().getFullYear() }} Households Korat
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import axios from 'axios'

import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Message from 'primevue/message'

const router = useRouter()
const { login } = useAuth()

const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await axios.get('/sanctum/csrf-cookie')
    await login(form.value.email, form.value.password)
    router.push('/app/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.email?.[0]
      || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
  } finally {
    loading.value = false
  }
}
</script>
