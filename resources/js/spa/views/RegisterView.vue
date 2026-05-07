<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-gradient-to-br from-violet-100 via-fuchsia-50 to-purple-100">
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-violet-300/40 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-[28rem] h-[28rem] rounded-full bg-fuchsia-300/40 blur-3xl pointer-events-none"></div>

    <div class="relative w-full max-w-md">
      <div class="glass rounded-3xl shadow-2xl shadow-violet-500/20 p-8 sm:p-10">
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-600 text-white text-3xl shadow-lg shadow-violet-500/40 mb-4">
            <i class="fi fi-rr-house-blank"></i>
          </div>
          <h1 class="text-2xl font-bold bg-gradient-to-r from-violet-700 via-purple-700 to-fuchsia-700 bg-clip-text text-transparent tracking-tight">
            สมัครสมาชิก
          </h1>
          <p class="text-slate-500 text-sm mt-1">Households Korat — สำหรับเจ้าหน้าที่</p>
        </div>

        <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>
        <Message v-if="success" severity="success" :closable="false" class="mb-4">
          <p class="font-semibold">{{ success }}</p>
          <p class="text-xs mt-1">ระบบจะแจ้งเตือนผู้ดูแล หลังจากผู้ดูแลยืนยันสิทธิ์ คุณจึงจะเข้าใช้งานได้</p>
        </Message>

        <form @submit.prevent="handleRegister" class="space-y-5" v-if="!success">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
              <i class="fi fi-rr-user text-violet-600"></i> ชื่อ-สกุล
            </label>
            <InputText v-model="form.name" required class="w-full" placeholder="ชื่อจริง นามสกุล" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
              <i class="fi fi-rr-envelope text-violet-600"></i> อีเมล
            </label>
            <InputText v-model="form.email" type="email" required class="w-full" placeholder="your@email.com" autocomplete="email" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
              <i class="fi fi-rr-key text-violet-600"></i> รหัสผ่าน
            </label>
            <Password v-model="form.password" toggleMask required fluid inputClass="w-full" placeholder="อย่างน้อย 8 ตัวอักษร" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
              <i class="fi fi-rr-shield-check text-violet-600"></i> ยืนยันรหัสผ่าน
            </label>
            <Password v-model="form.password_confirmation" :feedback="false" toggleMask required fluid inputClass="w-full" />
          </div>

          <Button
            type="submit"
            :loading="loading"
            label="สมัครสมาชิก"
            icon="fi fi-rr-user-add"
            iconPos="right"
            class="w-full"
            size="large"
          />
        </form>

        <div class="text-center text-xs text-slate-400 mt-8 space-x-2">
          <span>มีบัญชีอยู่แล้ว?</span>
          <router-link to="/app/login" class="text-violet-600 hover:underline font-medium">เข้าสู่ระบบ</router-link>
          <span>·</span>
          <router-link to="/app" class="text-violet-600 hover:underline font-medium">กลับหน้าหลัก</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/index.js'

import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'

const router = useRouter()
const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const loading = ref(false)
const error = ref('')
const success = ref('')

async function handleRegister() {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'รหัสผ่านไม่ตรงกัน'
    return
  }
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.post('/register', form.value)
    success.value = data.message || 'สมัครสมาชิกสำเร็จ'
    setTimeout(() => router.push('/app/login'), 4000)
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(' • ') : (e.response?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    loading.value = false
  }
}
</script>
