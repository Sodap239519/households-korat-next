<template>
  <div class="p-6 max-w-3xl mx-auto space-y-5">
    <!-- Header -->
    <div class="flex items-center gap-4">
      <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-600 text-white flex items-center justify-center text-xl font-bold shadow-lg shadow-violet-500/30">
        {{ initials }}
      </div>
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-user text-violet-600"></i> โปรไฟล์ของฉัน
        </h2>
        <p class="text-sm text-slate-500">แก้ไขข้อมูลและรหัสผ่านของบัญชี</p>
      </div>
    </div>

    <!-- Update profile -->
    <FormSection title="ข้อมูลบัญชี" icon="fi fi-rr-id-badge" tone="violet">
      <Message v-if="profileMsg" :severity="profileMsgSev" :closable="false" class="mb-4">{{ profileMsg }}</Message>
      <form @submit.prevent="saveProfile" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium text-slate-700 mb-1 block">ชื่อ-สกุล</label>
          <InputText v-model="form.name" required class="w-full" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล</label>
          <InputText v-model="form.email" type="email" required class="w-full" />
        </div>
        <div class="md:col-span-2 flex items-center gap-2">
          <span class="text-xs text-slate-500">บทบาท:</span>
          <StatusBadge v-if="user?.role" :status="user.role" :label="user.role === 'superadmin' ? 'ผู้ดูแลระบบ' : 'เจ้าหน้าที่'" />
          <span class="text-xs text-slate-400 ml-auto">บทบาทเปลี่ยนได้โดย superadmin เท่านั้น</span>
        </div>
        <div class="md:col-span-2 flex justify-end">
          <Button :loading="savingProfile" label="บันทึกข้อมูล" icon="fi fi-rr-disk" type="submit" />
        </div>
      </form>
    </FormSection>

    <!-- Change password -->
    <FormSection title="เปลี่ยนรหัสผ่าน" icon="fi fi-rr-key" tone="fuchsia">
      <Message v-if="pwdMsg" :severity="pwdMsgSev" :closable="false" class="mb-4">{{ pwdMsg }}</Message>
      <form @submit.prevent="savePassword" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-3">
          <label class="text-sm font-medium text-slate-700 mb-1 block">รหัสผ่านปัจจุบัน <span class="text-rose-500">*</span></label>
          <Password v-model="pwd.current" :feedback="false" toggleMask fluid inputClass="w-full" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700 mb-1 block">รหัสผ่านใหม่ <span class="text-rose-500">*</span></label>
          <Password v-model="pwd.new" toggleMask fluid inputClass="w-full" />
          <p class="text-[11px] text-slate-400 mt-1">อย่างน้อย 8 ตัวอักษร</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700 mb-1 block">ยืนยันรหัสใหม่ <span class="text-rose-500">*</span></label>
          <Password v-model="pwd.confirm" :feedback="false" toggleMask fluid inputClass="w-full" />
        </div>
        <div class="md:col-span-3 flex justify-end">
          <Button :loading="savingPwd" :disabled="!pwdValid" label="เปลี่ยนรหัสผ่าน" icon="fi fi-rr-key" severity="warn" type="submit" />
        </div>
      </form>
    </FormSection>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api/index.js'
import { useAuth } from '../composables/useAuth.js'

import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'

import FormSection from '../components/FormSection.vue'
import StatusBadge from '../components/StatusBadge.vue'

const { user, fetchUser } = useAuth()

const form = ref({ name: '', email: '' })
const pwd = ref({ current: '', new: '', confirm: '' })

const savingProfile = ref(false)
const savingPwd = ref(false)
const profileMsg = ref('')
const profileMsgSev = ref('success')
const pwdMsg = ref('')
const pwdMsgSev = ref('success')

const initials = computed(() => {
  const n = user.value?.name || ''
  const parts = n.trim().split(/\s+/)
  return (parts[0]?.[0] || '?') + (parts[1]?.[0] || '')
})

const pwdValid = computed(() => {
  return pwd.value.current.length > 0
    && pwd.value.new.length >= 8
    && pwd.value.new === pwd.value.confirm
})

async function saveProfile() {
  savingProfile.value = true
  profileMsg.value = ''
  try {
    await api.put('/profile', form.value)
    await fetchUser()
    profileMsgSev.value = 'success'
    profileMsg.value = 'บันทึกข้อมูลสำเร็จ'
  } catch (e) {
    profileMsgSev.value = 'error'
    const errs = e.response?.data?.errors
    profileMsg.value = errs ? Object.values(errs).flat().join(', ') : (e.response?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    savingProfile.value = false
  }
}

async function savePassword() {
  if (!pwdValid.value) return
  savingPwd.value = true
  pwdMsg.value = ''
  try {
    await api.put('/profile/password', {
      current_password:           pwd.value.current,
      new_password:               pwd.value.new,
      new_password_confirmation:  pwd.value.confirm,
    })
    pwdMsgSev.value = 'success'
    pwdMsg.value = 'เปลี่ยนรหัสผ่านสำเร็จ'
    pwd.value = { current: '', new: '', confirm: '' }
  } catch (e) {
    pwdMsgSev.value = 'error'
    const errs = e.response?.data?.errors
    pwdMsg.value = errs ? Object.values(errs).flat().join(', ') : (e.response?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    savingPwd.value = false
  }
}

onMounted(async () => {
  if (!user.value) await fetchUser()
  form.value = { name: user.value?.name || '', email: user.value?.email || '' }
})
</script>
