<template>
  <div class="min-h-screen bg-slate-50 flex flex-col items-start justify-start pt-10 p-4">

    <!-- Card หลัก -->
    <div class="w-full max-w-sm bg-white rounded-3xl shadow-xl overflow-hidden">

      <!-- Header gradient -->
      <div class="bg-gradient-to-br from-violet-600 via-fuchsia-500 to-pink-500 px-6 pt-8 pb-10">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
            <i class="fi fi-rr-key text-white text-lg"></i>
          </div>
          <div>
            <h1 class="text-white font-bold text-base leading-tight">ตั้งรหัสผ่านใหม่</h1>
            <p class="text-white/70 text-xs mt-0.5">กรุณาเปลี่ยนก่อนเข้าใช้งาน</p>
          </div>
        </div>

        <!-- User info pill -->
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-4 py-3 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-white/25 flex items-center justify-center shrink-0">
            <i class="fi fi-rr-user text-white text-sm"></i>
          </div>
          <div class="min-w-0">
            <p class="text-white font-semibold text-sm truncate">{{ user?.name }}</p>
            <p class="text-white/65 text-xs truncate">{{ user?.email }}</p>
          </div>
        </div>
      </div>

      <!-- Form — ยกขึ้นบน header ด้วย negative margin -->
      <div class="mx-4 -mt-5 bg-white rounded-2xl shadow-lg shadow-slate-200 px-5 py-5 space-y-4">

        <!-- Notice -->
        <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2.5">
          <i class="fi fi-rr-exclamation text-amber-500 text-sm shrink-0 mt-0.5"></i>
          <p class="text-amber-700 text-xs leading-relaxed">บัญชีนี้ถูกสร้างโดยผู้ดูแลระบบ กรุณาตั้งรหัสผ่านใหม่ก่อนใช้งาน</p>
        </div>

        <!-- รหัสผ่านชั่วคราว -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-600 flex items-center gap-1">
            รหัสผ่านชั่วคราว <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.current"
              :type="show.current ? 'text' : 'password'"
              class="field pr-10"
              placeholder="กรอกรหัสผ่านที่ได้รับจากระบบ"
              autocomplete="current-password"
            />
            <button type="button" @click="show.current = !show.current" tabindex="-1"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
              <i class="fi text-sm" :class="show.current ? 'fi-rr-eye-crossed' : 'fi-rr-eye'"></i>
            </button>
          </div>
        </div>

        <!-- รหัสผ่านใหม่ -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-600 flex items-center gap-1">
            รหัสผ่านใหม่ <span class="text-rose-500">*</span>
            <span class="ml-auto text-[11px] font-normal text-slate-400">อย่างน้อย 8 ตัวอักษร</span>
          </label>
          <div class="relative">
            <input
              v-model="form.new"
              :type="show.new ? 'text' : 'password'"
              class="field pr-10"
              :class="form.new && form.new.length < 8 ? 'border-amber-400 focus:border-amber-500' : ''"
              placeholder="ตั้งรหัสผ่านใหม่"
              autocomplete="new-password"
            />
            <button type="button" @click="show.new = !show.new" tabindex="-1"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
              <i class="fi text-sm" :class="show.new ? 'fi-rr-eye-crossed' : 'fi-rr-eye'"></i>
            </button>
          </div>
          <!-- Strength meter -->
          <div v-if="form.new" class="space-y-1">
            <div class="flex gap-1">
              <div v-for="n in 4" :key="n"
                class="h-1 flex-1 rounded-full transition-all duration-300"
                :class="strength >= n ? strengthColor : 'bg-slate-200'"></div>
            </div>
            <p class="text-[11px]" :class="strengthTextColor">{{ strengthLabel }}</p>
          </div>
        </div>

        <!-- ยืนยันรหัสผ่าน -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-600 flex items-center gap-1">
            ยืนยันรหัสผ่านใหม่ <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.confirm"
              :type="show.confirm ? 'text' : 'password'"
              class="field pr-10"
              :class="form.confirm
                ? form.confirm === form.new
                  ? 'border-emerald-400 focus:border-emerald-500'
                  : 'border-rose-400 focus:border-rose-500'
                : ''"
              placeholder="กรอกรหัสผ่านใหม่อีกครั้ง"
              autocomplete="new-password"
              @keyup.enter="submit"
            />
            <button type="button" @click="show.confirm = !show.confirm" tabindex="-1"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
              <i class="fi text-sm" :class="show.confirm ? 'fi-rr-eye-crossed' : 'fi-rr-eye'"></i>
            </button>
          </div>
          <p v-if="form.confirm" class="text-[11px] flex items-center gap-1">
            <template v-if="form.confirm === form.new">
              <i class="fi fi-rr-check-circle text-emerald-500"></i>
              <span class="text-emerald-600 font-medium">รหัสผ่านตรงกัน</span>
            </template>
            <template v-else>
              <i class="fi fi-rr-cross-circle text-rose-500"></i>
              <span class="text-rose-600 font-medium">รหัสผ่านไม่ตรงกัน</span>
            </template>
          </p>
        </div>

        <!-- Error -->
        <p v-if="errorMsg" class="text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2.5 flex items-start gap-2">
          <i class="fi fi-rr-exclamation shrink-0 mt-0.5"></i> {{ errorMsg }}
        </p>

        <!-- Submit -->
        <button
          @click="submit"
          :disabled="!canSubmit || saving"
          class="w-full h-11 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all shadow"
          :class="canSubmit && !saving
            ? 'bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white hover:opacity-90 shadow-violet-400/30'
            : 'bg-slate-100 text-slate-400 cursor-not-allowed shadow-none'">
          <i :class="saving ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-lock-alt'"></i>
          {{ saving ? 'กำลังบันทึก...' : 'ตั้งรหัสผ่านและเข้าใช้งาน' }}
        </button>
      </div>

      <!-- Bottom padding -->
      <div class="h-5"></div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/index.js'
import { useAuth } from '../composables/useAuth.js'

const router = useRouter()
const { user, fetchUser } = useAuth()

const form    = reactive({ current: '', new: '', confirm: '' })
const show    = reactive({ current: false, new: false, confirm: false })
const saving  = ref(false)
const errorMsg = ref('')

const strength = computed(() => {
  const p = form.new
  if (!p) return 0
  let s = 0
  if (p.length >= 8)                               s++
  if (p.length >= 12)                              s++
  if (/[A-Z]/.test(p) && /[a-z]/.test(p))        s++
  if (/[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p)) s++
  return Math.min(s, 4)
})
const strengthColor     = computed(() => ['bg-red-400', 'bg-amber-400', 'bg-yellow-400', 'bg-emerald-500'][strength.value - 1] || 'bg-slate-200')
const strengthTextColor = computed(() => ['text-red-500', 'text-amber-500', 'text-yellow-600', 'text-emerald-600'][strength.value - 1] || 'text-slate-400')
const strengthLabel     = computed(() => ['อ่อนมาก', 'พอใช้', 'ดี', 'แข็งแกร่ง'][strength.value - 1] || '')

const canSubmit = computed(() =>
  form.current.length >= 1 &&
  form.new.length >= 8 &&
  form.confirm === form.new
)

async function submit() {
  if (!canSubmit.value || saving.value) return
  errorMsg.value = ''
  saving.value = true
  try {
    await api.put('/profile/password', {
      current_password:          form.current,
      new_password:              form.new,
      new_password_confirmation: form.confirm,
    })
    await fetchUser()
    router.replace('/app/market')
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors?.current_password)   errorMsg.value = errors.current_password[0]
    else if (errors?.new_password)  errorMsg.value = errors.new_password[0]
    else                            errorMsg.value = e.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
  } finally { saving.value = false }
}
</script>

<style scoped>
.field {
  display: block;
  width: 100%;
  height: 2.75rem;
  padding: 0 2.5rem 0 0.875rem;
  border-radius: 0.875rem;
  border: 1.5px solid rgb(203 213 225);
  background: #fff;
  font-size: 0.875rem;
  color: rgb(15 23 42);
  outline: none;
  transition: border-color .15s, box-shadow .15s;
}
.field:focus {
  border-color: rgb(139 92 246);
  box-shadow: 0 0 0 3px rgba(139, 92, 246, .12);
}
.field::placeholder { color: rgb(148 163 184); }
</style>
