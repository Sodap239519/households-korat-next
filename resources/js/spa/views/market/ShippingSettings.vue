<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <Toast />

    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-truck-side text-violet-600"></i> ตั้งค่าบริการจัดส่ง
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">บริการจัดส่งที่ใช้ร่วมกันทุกกลุ่มผู้ขาย</p>
      </div>
    </div>

    <!-- Current shipping options -->
    <div class="box-card overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
        <i class="fi fi-rr-list-check text-violet-500 text-sm"></i>
        <span class="font-semibold text-slate-700 text-sm">บริการจัดส่งปัจจุบัน</span>
      </div>

      <div v-if="loading" class="p-10 text-center text-slate-400">
        <i class="fi fi-rr-spinner animate-spin text-xl"></i>
      </div>
      <div v-else-if="!shipRows.length" class="p-8 text-center text-slate-400">
        <i class="fi fi-rr-truck-side text-3xl mb-2"></i>
        <p class="text-sm">ยังไม่มีบริการจัดส่ง — กรอกฟอร์มด้านล่างเพื่อเพิ่มตัวเลือกแรก</p>
      </div>
      <div v-else class="divide-y divide-slate-50">
        <div v-for="opt in shipRows" :key="opt.id"
          class="flex items-center gap-3 px-4 py-3"
          :class="opt.is_active ? '' : 'opacity-60 bg-slate-50/50'">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-medium text-slate-800 text-sm">{{ opt.name }}</span>
              <span v-if="opt.carrier" class="text-[10px] bg-sky-50 text-sky-600 border border-sky-100 px-1.5 py-0.5 rounded">{{ opt.carrier }}</span>
              <span v-if="opt.is_default" class="text-[10px] bg-violet-100 text-violet-700 px-1.5 py-0.5 rounded font-semibold">ค่าเริ่มต้น</span>
              <span v-if="!opt.is_active" class="text-[10px] bg-slate-100 text-slate-400 px-1.5 py-0.5 rounded">ปิด</span>
            </div>
            <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-3 flex-wrap">
              <span :class="Number(opt.fee) === 0 ? 'text-emerald-600 font-medium' : ''">
                {{ Number(opt.fee) === 0 ? 'ฟรี' : `฿${Number(opt.fee).toLocaleString()}` }}
              </span>
              <span>
                <i class="fi fi-rr-clock text-[10px]"></i>
                {{ opt.days_min === opt.days_max ? `${opt.days_min} วัน` : `${opt.days_min}–${opt.days_max} วัน` }}
              </span>
              <span v-if="(opt.allowed_payment_methods || ['online']).includes('cod')"
                class="text-[10px] bg-emerald-50 text-emerald-600 border border-emerald-100 px-1.5 py-0.5 rounded">
                COD
              </span>
              <span v-if="(opt.allowed_payment_methods || ['online']).includes('online')"
                class="text-[10px] bg-violet-50 text-violet-600 border border-violet-100 px-1.5 py-0.5 rounded">
                โอนเงิน
              </span>
            </div>
          </div>
          <div v-if="isAdmin" class="flex gap-1 shrink-0">
            <button v-if="!opt.is_default" @click="setShipDefault(opt)"
              class="text-xs text-slate-400 hover:text-violet-600 px-2 py-1 rounded-lg hover:bg-violet-50 transition">ตั้งเป็นหลัก</button>
            <button @click="openShipEdit(opt)"
              class="text-xs text-violet-600 hover:text-violet-800 px-2 py-1 rounded-lg hover:bg-violet-50 transition flex items-center gap-1">
              <i class="fi fi-rr-edit text-[10px]"></i> แก้
            </button>
            <button @click="deleteShip(opt)"
              class="text-xs text-rose-400 hover:text-rose-600 px-2 py-1 rounded-lg hover:bg-rose-50 transition flex items-center gap-1">
              <i class="fi fi-rr-trash text-[10px]"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add / Edit form (admin เท่านั้น) -->
    <div v-if="isAdmin" class="box-card p-4">
      <p class="text-sm font-semibold text-violet-700 mb-4 flex items-center gap-2">
        <i class="fi fi-rr-truck-side"></i>
        {{ shipEditId ? 'แก้ไขบริการจัดส่ง' : 'เพิ่มบริการจัดส่ง' }}
      </p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="sm:col-span-2">
          <label class="form-label">ชื่อบริการ *</label>
          <InputText v-model="shipForm.name" class="w-full" placeholder="เช่น จัดส่งโดยผู้ขาย, ส่งด่วน Kerry" :invalid="!!shipErr.name" />
          <small v-if="shipErr.name" class="text-rose-500">{{ shipErr.name }}</small>
        </div>
        <div class="sm:col-span-2">
          <label class="form-label">บริษัทขนส่ง</label>
          <Select v-model="shipForm.carrier" :options="CARRIERS" placeholder="— เลือกบริษัทขนส่ง —" showClear class="w-full" />
        </div>
        <div>
          <label class="form-label">ค่าจัดส่ง (฿)</label>
          <InputNumber v-model="shipForm.fee" class="w-full" :min="0" :max="9999" :minFractionDigits="0" :maxFractionDigits="2" placeholder="0 = ฟรี" />
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="form-label">วันต่ำสุด</label>
            <InputNumber v-model="shipForm.days_min" class="w-full" :min="0" :max="90" />
          </div>
          <div>
            <label class="form-label">วันสูงสุด</label>
            <InputNumber v-model="shipForm.days_max" class="w-full" :min="0" :max="90" />
          </div>
        </div>
        <div class="sm:col-span-2">
          <label class="form-label">วิธีชำระเงินที่รองรับ *</label>
          <div class="flex flex-wrap gap-3 mt-1">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" value="online" v-model="shipForm.allowed_payment_methods"
                class="w-4 h-4 accent-violet-600 cursor-pointer" />
              <span class="text-sm text-slate-700">
                <i class="fi fi-rr-bank text-violet-500 mr-1"></i> ชำระเงินในระบบ (โอน + สลิป)
              </span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" value="cod" v-model="shipForm.allowed_payment_methods"
                class="w-4 h-4 accent-violet-600 cursor-pointer" />
              <span class="text-sm text-slate-700">
                <i class="fi fi-rr-money-bill-wave text-emerald-500 mr-1"></i> ชำระเงินปลายทาง (COD)
              </span>
            </label>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="shipForm.is_default" inputId="shipdef" />
          <label for="shipdef" class="text-sm text-slate-600">ตั้งเป็นค่าเริ่มต้น</label>
        </div>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="shipForm.is_active" inputId="shipon" />
          <label for="shipon" class="text-sm text-slate-600">เปิดใช้งาน</label>
        </div>
      </div>
      <p v-if="shipErrMsg" class="text-xs text-rose-500 mt-2">{{ shipErrMsg }}</p>
      <div class="flex gap-2 mt-4 justify-end">
        <Button v-if="shipEditId" label="ยกเลิกแก้ไข" text size="small" @click="resetShipForm" />
        <Button :label="shipEditId ? 'บันทึกการแก้ไข' : 'เพิ่มบริการนี้'" icon="fi fi-rr-disk" size="small" :loading="shipSaving" @click="saveShip" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../../api/index.js'
import { useAuth } from '../../composables/useAuth.js'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'

const CARRIERS = [
  'ไปรษณีย์ไทย EMS',
  'ไปรษณีย์ไทย ลงทะเบียน',
  'Kerry Express',
  'Flash Express',
  'J&T Express',
  'Ninja Van',
  'Lalamove',
  'ส่งเอง/พิกอัพ',
]

const { isAdmin } = useAuth()
const toast = useToast()

const shipRows = ref([])
const loading = ref(false)
const shipForm = reactive({ name: '', carrier: null, fee: 0, days_min: 1, days_max: 7, is_default: false, is_active: true, allowed_payment_methods: ['online'] })
const shipEditId = ref(null)
const shipErr = reactive({})
const shipErrMsg = ref('')
const shipSaving = ref(false)

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/market/shipping')
    shipRows.value = data || []
  } finally {
    loading.value = false
  }
}

function resetShipForm() {
  Object.assign(shipForm, { name: '', carrier: null, fee: 0, days_min: 1, days_max: 7, is_default: false, is_active: true, allowed_payment_methods: ['online'] })
  shipEditId.value = null
  Object.keys(shipErr).forEach(k => delete shipErr[k])
  shipErrMsg.value = ''
}

function openShipEdit(opt) {
  shipEditId.value = opt.id
  Object.assign(shipForm, {
    name: opt.name, carrier: opt.carrier || null, fee: Number(opt.fee),
    days_min: opt.days_min, days_max: opt.days_max,
    is_default: opt.is_default, is_active: opt.is_active,
    allowed_payment_methods: [...(opt.allowed_payment_methods || ['online'])],
  })
  shipErrMsg.value = ''
  Object.keys(shipErr).forEach(k => delete shipErr[k])
}

async function saveShip() {
  if (!shipForm.name?.trim()) { shipErr.name = 'กรุณากรอกชื่อบริการ'; return }
  shipSaving.value = true
  shipErrMsg.value = ''
  Object.keys(shipErr).forEach(k => delete shipErr[k])
  try {
    const payload = {
      name: shipForm.name.trim(), carrier: shipForm.carrier || null,
      fee: shipForm.fee ?? 0,
      days_min: shipForm.days_min ?? 1, days_max: shipForm.days_max ?? 7,
      is_default: shipForm.is_default, is_active: shipForm.is_active,
      allowed_payment_methods: shipForm.allowed_payment_methods.length ? shipForm.allowed_payment_methods : ['online'],
    }
    if (shipEditId.value) {
      await api.put(`/market/shipping/${shipEditId.value}`, payload)
    } else {
      await api.post('/market/shipping', payload)
    }
    toast.add({ severity: 'success', summary: shipEditId.value ? 'แก้ไขแล้ว' : 'เพิ่มแล้ว', life: 1800 })
    resetShipForm()
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      Object.entries(e.response.data.errors || {}).forEach(([k, v]) => shipErr[k] = v[0])
      shipErrMsg.value = 'ตรวจสอบข้อมูล'
    } else {
      shipErrMsg.value = e.response?.data?.message || 'เกิดข้อผิดพลาด'
    }
  } finally {
    shipSaving.value = false
  }
}

async function deleteShip(opt) {
  if (!confirm(`ลบ "${opt.name}" ออก?`)) return
  await api.delete(`/market/shipping/${opt.id}`)
  toast.add({ severity: 'info', summary: 'ลบแล้ว', life: 1500 })
  await load()
}

async function setShipDefault(opt) {
  await api.put(`/market/shipping/${opt.id}`, { is_default: true })
  await load()
}

onMounted(load)
</script>
