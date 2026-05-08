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
          <p class="text-slate-500 text-sm mt-1">Households KORAT — สำหรับเจ้าหน้าที่</p>
        </div>

        <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>
        <Message v-if="success" severity="warn" :closable="false" class="mb-4" :pt="{ root: { class: 'border-2 border-amber-300' } }">
          <div class="flex items-start gap-2">
            <i class="fi fi-rr-time-twenty-four text-amber-600 text-xl mt-0.5"></i>
            <div>
              <p class="font-bold text-amber-800">{{ success }}</p>
              <p class="text-sm font-semibold text-amber-700 mt-1">
                <i class="fi fi-rr-info"></i> กรุณารอ Admin ยืนยันก่อนเข้าสู่ระบบ
              </p>
              <p class="text-xs text-slate-600 mt-1">
                ระบบจะแจ้งเตือนผู้ดูแล เมื่อ Admin ตรวจสอบและอนุมัติบัญชีของคุณแล้ว คุณจึงจะเข้าใช้งานได้
              </p>
              <p class="text-[11px] text-slate-500 mt-2">กำลังกลับไปหน้าเข้าสู่ระบบใน 5 วินาที...</p>
            </div>
          </div>
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

          <!-- Role selection -->
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
              <i class="fi fi-rr-id-badge text-violet-600"></i> บทบาท <span class="text-rose-500">*</span>
            </label>
            <Select
              v-model="form.role"
              :options="roleOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="-- เลือกบทบาท --"
              class="w-full"
            />
            <p v-if="form.role" class="text-[11px] text-slate-500 mt-1">{{ ROLE_HELP[form.role] }}</p>
          </div>

          <!-- Assigned districts (only for area_staff) -->
          <div v-if="form.role === 'area_staff'" class="rounded-xl border-2 border-violet-200 bg-violet-50/40 p-3">
            <label class="text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
              <i class="fi fi-rr-marker text-violet-600"></i> พื้นที่ดูแล (สูงสุด 4 อำเภอ)
              <span class="text-[11px] text-slate-500 ml-1 font-normal">— เลือก {{ (form.assigned_districts || []).length }}/4</span>
            </label>
            <MultiSelect
              v-model="form.assigned_districts"
              :options="districtOptions"
              placeholder="-- เลือกอำเภอ --"
              filter
              display="chip"
              :selectionLimit="4"
              class="w-full"
            />
            <p class="text-[11px] text-slate-500 mt-1">
              <i class="fi fi-rr-info"></i> เจ้าหน้าที่ประจำพื้นที่จะจัดสรรโควต้า/ติดตามผลได้เฉพาะอำเภอที่ดูแล
            </p>
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

          <!-- Notice about admin approval -->
          <div class="rounded-xl bg-amber-50/60 border-2 border-amber-200 p-3 flex items-start gap-2">
            <i class="fi fi-rr-info text-amber-600 text-lg flex-shrink-0 mt-0.5"></i>
            <p class="text-xs text-amber-800">
              <span class="font-semibold">หมายเหตุ:</span> หลังสมัครสมาชิกแล้ว
              บัญชีของคุณจะยังไม่สามารถเข้าใช้งานได้จนกว่า <span class="font-semibold">Admin จะอนุมัติบัญชี</span>
            </p>
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/index.js'

import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import Button from 'primevue/button'
import Message from 'primevue/message'

const router = useRouter()
const form = ref({
  name: '', email: '',
  role: 'staff',
  assigned_districts: [],
  password: '', password_confirmation: '',
})
const loading = ref(false)
const error = ref('')
const success = ref('')
const districtOptions = ref([])

const roleOptions = [
  { label: 'เจ้าหน้าที่',                 value: 'staff' },
  { label: 'เจ้าหน้าที่ประจำพื้นที่', value: 'area_staff' },
]
const ROLE_HELP = {
  staff:      'กรอกข้อมูลครัวเรือน · ดู Dashboard ทั่วไป',
  area_staff: 'จัดสรรโควต้า + ติดตามผล เฉพาะอำเภอที่ได้รับมอบหมาย',
}

async function loadDistricts() {
  try {
    const { data } = await api.get('/locations/districts')
    districtOptions.value = data
  } catch {}
}

async function handleRegister() {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'รหัสผ่านไม่ตรงกัน'
    return
  }
  if (form.value.role === 'area_staff' && (!form.value.assigned_districts || form.value.assigned_districts.length === 0)) {
    error.value = 'กรุณาเลือกอำเภอที่ดูแลอย่างน้อย 1 อำเภอ'
    return
  }
  loading.value = true
  error.value = ''
  try {
    const payload = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role,
      assigned_districts: form.value.role === 'area_staff' ? form.value.assigned_districts : null,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    }
    const { data } = await api.post('/register', payload)
    success.value = data.message || 'สมัครสมาชิกสำเร็จ'
    setTimeout(() => router.push('/app/login'), 5000)
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(' • ') : (e.response?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    loading.value = false
  }
}

onMounted(loadDistricts)
</script>
