<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6">
    <Breadcrumb :items="[{ label: 'บัญชีของฉัน' }, { label: 'ที่อยู่จัดส่ง' }]" class="mb-4" />

    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-bold text-slate-800">ที่อยู่จัดส่งของฉัน</h1>
      <button @click="openAdd" class="flex items-center gap-2 btn-orange btn-sheen px-4 h-9 rounded-full text-sm font-semibold">
        <i class="fi fi-rr-map-marker-plus"></i> เพิ่มที่อยู่
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 2" :key="i" class="box-card p-5 animate-pulse">
        <div class="h-4 bg-slate-100 rounded w-1/3 mb-2"></div>
        <div class="h-3 bg-slate-100 rounded w-2/3"></div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="!addresses.length" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-map-marker text-4xl mb-3 block"></i>
      <p class="font-medium">ยังไม่มีที่อยู่จัดส่ง</p>
      <p class="text-sm mt-1">เพิ่มที่อยู่เพื่อให้การสั่งซื้อสะดวกขึ้น</p>
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div v-for="addr in addresses" :key="addr.id" class="box-card p-4 relative">
        <div class="flex items-start gap-3">
          <div class="w-9 h-9 rounded-full bg-violet-50 flex items-center justify-center shrink-0 mt-0.5">
            <i class="fi fi-rr-home text-violet-500"></i>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-semibold text-slate-800">{{ addr.name }}</span>
              <span class="text-slate-400 text-sm">{{ addr.phone }}</span>
              <span v-if="addr.is_default" class="text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full font-medium">
                <i class="fi fi-rr-star text-[10px]"></i> ค่าเริ่มต้น
              </span>
            </div>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">
              {{ addr.address }}<template v-if="addr.sub_district">, {{ addr.sub_district }}</template><template v-if="addr.district">, {{ addr.district }}</template><template v-if="addr.province">, {{ addr.province }}</template><template v-if="addr.postal_code"> {{ addr.postal_code }}</template>
            </p>
            <span class="inline-block mt-1.5 text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">{{ addr.label }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3 mt-3 pt-3 border-t border-slate-100">
          <button @click="openEdit(addr)" class="text-sm text-violet-600 hover:text-violet-800 flex items-center gap-1">
            <i class="fi fi-rr-edit"></i> แก้ไข
          </button>
          <button v-if="!addr.is_default" @click="setDefault(addr)" class="text-sm text-slate-400 hover:text-slate-700 flex items-center gap-1">
            <i class="fi fi-rr-star"></i> ตั้งเป็นหลัก
          </button>
          <button @click="confirmDelete(addr)" class="text-sm text-rose-400 hover:text-rose-600 flex items-center gap-1 ml-auto">
            <i class="fi fi-rr-trash"></i> ลบ
          </button>
        </div>
      </div>
    </div>

    <!-- ===== Add/Edit Modal ===== -->
    <div v-if="formOpen" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm" @click.self="formOpen = false">
      <div class="w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-100 shrink-0">
          <h2 class="font-bold text-slate-800">{{ addrForm.id ? 'แก้ไขที่อยู่' : 'เพิ่มที่อยู่ใหม่' }}</h2>
          <button @click="formOpen = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400">
            <i class="fi fi-rr-cross-small text-lg"></i>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label">ชื่อที่อยู่</label>
              <input v-model="addrForm.label" class="inp w-full" placeholder="บ้าน / ที่ทำงาน" />
            </div>
            <div>
              <label class="form-label">ชื่อผู้รับ *</label>
              <input v-model="addrForm.name" class="inp w-full" />
            </div>
            <div class="col-span-2">
              <label class="form-label">เบอร์โทร *</label>
              <input v-model="addrForm.phone" class="inp w-full" type="tel" />
            </div>
            <div class="col-span-2">
              <label class="form-label">บ้านเลขที่ หมู่ ถนน *</label>
              <input v-model="addrForm.address" class="inp w-full" />
            </div>
            <ThaiAddressFields
              v-model:subDistrict="addrForm.sub_district"
              v-model:district="addrForm.district"
              v-model:province="addrForm.province"
              v-model:postalCode="addrForm.postal_code"
            />
          </div>
          <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
            <input type="checkbox" v-model="addrForm.is_default" class="accent-violet-500" />
            ตั้งเป็นที่อยู่หลัก
          </label>
          <p v-if="formErr" class="text-sm text-rose-500">{{ formErr }}</p>
        </div>

        <div class="p-4 border-t border-slate-100 shrink-0 flex gap-2">
          <button @click="formOpen = false" class="flex-1 h-11 rounded-full border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50">ยกเลิก</button>
          <button @click="saveAddr" :disabled="saving" class="flex-1 h-11 rounded-full btn-orange btn-sheen font-semibold text-sm disabled:opacity-60">
            {{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuth } from '../../composables/useAuth.js'
import api from '../../api/index.js'
import Breadcrumb from './components/Breadcrumb.vue'
import ThaiAddressFields from './components/ThaiAddressFields.vue'

const toast = useToast()
const { user } = useAuth()

const addresses = ref([])
const loading   = ref(true)
const formOpen  = ref(false)
const saving    = ref(false)
const formErr   = ref('')

const blankForm = () => ({ id: null, label: 'บ้าน', name: user.value?.name || '', phone: '', address: '', sub_district: '', district: '', province: 'นครราชสีมา', postal_code: '', is_default: false })
const addrForm = reactive(blankForm())

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/shop/my/addresses')
    addresses.value = data
  } finally {
    loading.value = false
  }
}

function openAdd() {
  Object.assign(addrForm, blankForm())
  formErr.value = ''
  formOpen.value = true
}

function openEdit(addr) {
  Object.assign(addrForm, { ...addr })
  formErr.value = ''
  formOpen.value = true
}

async function setDefault(addr) {
  await api.post(`/shop/my/addresses/${addr.id}/default`)
  await load()
  toast.add({ severity: 'success', summary: 'ตั้งที่อยู่หลักแล้ว', life: 2000 })
}

async function confirmDelete(addr) {
  if (!confirm(`ลบที่อยู่ "${addr.label}" ออก?`)) return
  await api.delete(`/shop/my/addresses/${addr.id}`)
  await load()
  toast.add({ severity: 'info', summary: 'ลบที่อยู่แล้ว', life: 2000 })
}

async function saveAddr() {
  if (!addrForm.name || !addrForm.phone || !addrForm.address) {
    formErr.value = 'กรุณากรอกชื่อผู้รับ เบอร์โทร และที่อยู่'
    return
  }
  saving.value = true
  formErr.value = ''
  try {
    const payload = { label: addrForm.label, name: addrForm.name, phone: addrForm.phone, address: addrForm.address, sub_district: addrForm.sub_district, district: addrForm.district, province: addrForm.province, postal_code: addrForm.postal_code, is_default: addrForm.is_default }
    if (addrForm.id) {
      await api.put(`/shop/my/addresses/${addrForm.id}`, payload)
    } else {
      await api.post('/shop/my/addresses', payload)
    }
    formOpen.value = false
    await load()
    toast.add({ severity: 'success', summary: addrForm.id ? 'แก้ไขที่อยู่แล้ว' : 'เพิ่มที่อยู่แล้ว', life: 2000 })
  } catch (e) {
    formErr.value = e.response?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.inp { height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
</style>
