<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-emerald-100">
    <!-- Decorative blobs -->
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-emerald-300/30 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-[28rem] h-[28rem] rounded-full bg-teal-300/30 blur-3xl pointer-events-none"></div>

    <div class="relative w-full max-w-md">
      <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/60 p-8 sm:p-10">
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-white text-3xl shadow-lg shadow-emerald-500/30 mb-4">
            <i class="pi pi-leaf"></i>
          </div>
          <h1 class="text-2xl font-bold text-slate-800 tracking-tight">ระบบโควต้าเห็ด</h1>
          <p class="text-slate-500 text-sm mt-1">จังหวัดนครราชสีมา</p>
        </div>

        <Message v-if="error" severity="error" :closable="false" class="mb-4">
          {{ error }}
        </Message>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">อีเมล</label>
            <IconField>
              <InputIcon class="pi pi-envelope" />
              <InputText
                v-model="form.email"
                type="email"
                required
                placeholder="your@email.com"
                class="w-full"
                autocomplete="username"
              />
            </IconField>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">รหัสผ่าน</label>
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
            icon="pi pi-sign-in"
            iconPos="right"
            class="w-full"
            size="large"
          />
        </form>

        <p class="text-center text-xs text-slate-400 mt-8">
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
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

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
