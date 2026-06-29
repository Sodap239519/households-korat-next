<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
    <Breadcrumb :items="[{ label: 'ตะกร้า', to: '/shop/cart' }, { label: 'ชำระเงิน' }]" class="mb-4" />

    <div v-if="!activeItems.length" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-shopping-cart text-4xl"></i>
      <p class="mt-3">ไม่มีสินค้าในตะกร้า</p>
      <RouterLink to="/shop/products" class="text-violet-600 hover:underline mt-2 inline-block">เลือกซื้อสินค้า</RouterLink>
    </div>

    <div v-else class="grid lg:grid-cols-3 gap-5">
      <!-- Shipping -->
      <div class="lg:col-span-2 space-y-4">

        <!-- Saved address picker -->
        <div class="box-card p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
              <i class="fi fi-rr-marker text-violet-600"></i> ที่อยู่จัดส่ง
            </h3>
            <button @click="openPicker" class="text-sm text-violet-600 hover:text-violet-800 font-medium flex items-center gap-1">
              <i class="fi fi-rr-edit"></i> เปลี่ยน / จัดการ
            </button>
          </div>

          <!-- ที่อยู่ที่เลือก -->
          <div v-if="selectedAddr" class="bg-violet-50 border border-violet-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <i class="fi fi-rr-home text-violet-500 mt-0.5"></i>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-800">{{ selectedAddr.name }}
                  <span class="text-slate-400 font-normal">· {{ selectedAddr.phone }}</span>
                </p>
                <p class="text-sm text-slate-600 mt-0.5 leading-relaxed">
                  {{ selectedAddr.address }}
                  <template v-if="selectedAddr.sub_district">, {{ selectedAddr.sub_district }}</template>
                  <template v-if="selectedAddr.district">, {{ selectedAddr.district }}</template>
                  <template v-if="selectedAddr.province">, {{ selectedAddr.province }}</template>
                  <template v-if="selectedAddr.postal_code"> {{ selectedAddr.postal_code }}</template>
                </p>
                <span class="inline-block mt-1.5 text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">
                  <i class="fi fi-rr-label text-[10px]"></i> {{ selectedAddr.label }}
                </span>
              </div>
            </div>
          </div>

          <!-- ยังไม่มีที่อยู่ -->
          <div v-else class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center text-slate-400">
            <i class="fi fi-rr-map-marker-plus text-2xl mb-2"></i>
            <p class="text-sm">ยังไม่มีที่อยู่จัดส่ง</p>
            <button @click="openAddNew" class="mt-2 text-sm text-violet-600 hover:underline font-medium">+ เพิ่มที่อยู่ใหม่</button>
          </div>

          <!-- หมายเหตุ -->
          <div class="mt-3">
            <label class="form-label">หมายเหตุ (ไม่บังคับ)</label>
            <textarea v-model="form.shipping_note" rows="2" class="inp w-full"></textarea>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div class="lg:col-span-1">
        <div class="box-card p-5 sticky top-20">
          <h3 class="font-bold text-slate-800 mb-3">สรุปคำสั่งซื้อ</h3>
          <div v-for="g in activeGroups" :key="g.group_id" class="mb-3 pb-3 border-b border-slate-100 last:border-0">
            <p class="text-xs font-semibold text-violet-700 mb-1"><i class="fi fi-rr-shop"></i> {{ g.group_name }}</p>
            <div v-for="item in g.items" :key="item.product_id" class="flex justify-between text-sm text-slate-600">
              <span class="truncate pr-2">{{ item.name }} ×{{ item.qty }}</span>
              <span class="shrink-0">฿{{ fmt(item.price * item.qty) }}</span>
            </div>
          </div>
          <div class="flex justify-between items-end pt-2">
            <span class="text-slate-600">ยอดสุทธิ</span>
            <span class="text-2xl font-bold text-fuchsia-700">฿{{ fmt(activeTotal) }}</span>
          </div>
          <p v-if="error" class="text-sm text-rose-500 mt-2">{{ error }}</p>
          <button :disabled="loading || !selectedAddr" class="btn-sheen w-full mt-4 h-12 rounded-full btn-orange font-semibold disabled:opacity-60" @click="placeOrder">
            {{ loading ? 'กำลังสั่งซื้อ...' : 'ยืนยันสั่งซื้อ' }}
          </button>
          <p class="text-xs text-slate-400 mt-2 text-center">ขั้นตอนถัดไปคือแจ้งโอนเงิน + แนบสลิป</p>
        </div>
      </div>
    </div>

    <!-- ===== Address Picker Modal ===== -->
    <div v-if="pickerOpen" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm" @click.self="pickerOpen = false">
      <div class="w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[85vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-slate-100 shrink-0">
          <h2 class="font-bold text-slate-800">ที่อยู่จัดส่ง</h2>
          <button @click="pickerOpen = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400">
            <i class="fi fi-rr-cross-small text-lg"></i>
          </button>
        </div>

        <!-- Address list -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <div v-for="addr in addresses" :key="addr.id"
            @click="selectAddr(addr)"
            class="relative border-2 rounded-xl p-4 cursor-pointer transition"
            :class="selectedAddr?.id === addr.id ? 'border-violet-400 bg-violet-50' : 'border-slate-200 hover:border-violet-200'">
            <div class="flex items-start gap-3">
              <div class="mt-0.5">
                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition"
                  :class="selectedAddr?.id === addr.id ? 'border-violet-500 bg-violet-500' : 'border-slate-300'">
                  <div v-if="selectedAddr?.id === addr.id" class="w-2 h-2 rounded-full bg-white"></div>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-semibold text-slate-800">{{ addr.name }}</span>
                  <span class="text-slate-400 text-sm">{{ addr.phone }}</span>
                  <span v-if="addr.is_default" class="text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded font-medium">ค่าเริ่มต้น</span>
                  <span class="text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">{{ addr.label }}</span>
                </div>
                <p class="text-sm text-slate-500 mt-1 leading-relaxed">
                  {{ addr.address }}<template v-if="addr.sub_district">, {{ addr.sub_district }}</template><template v-if="addr.district">, {{ addr.district }}</template><template v-if="addr.province">, {{ addr.province }}</template><template v-if="addr.postal_code"> {{ addr.postal_code }}</template>
                </p>
              </div>
            </div>
            <!-- Edit/Delete -->
            <div class="flex gap-2 mt-3 justify-end">
              <button @click.stop="editAddr(addr)" class="text-xs text-violet-600 hover:underline flex items-center gap-1"><i class="fi fi-rr-edit"></i> แก้ไข</button>
              <button @click.stop="setDefault(addr)" v-if="!addr.is_default" class="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1"><i class="fi fi-rr-star"></i> ตั้งเป็นหลัก</button>
              <button @click.stop="deleteAddr(addr)" class="text-xs text-rose-400 hover:text-rose-600 flex items-center gap-1"><i class="fi fi-rr-trash"></i> ลบ</button>
            </div>
          </div>

          <!-- Add new button -->
          <button @click="openAddNew" class="w-full border-2 border-dashed border-slate-200 rounded-xl p-4 text-slate-400 hover:border-violet-300 hover:text-violet-600 transition flex items-center justify-center gap-2">
            <i class="fi fi-rr-map-marker-plus"></i> เพิ่มที่อยู่ใหม่
          </button>
        </div>
      </div>
    </div>

    <!-- ===== Add/Edit Address Modal ===== -->
    <div v-if="formOpen" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50 backdrop-blur-sm" @click.self="formOpen = false">
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
          <p v-if="addrErr" class="text-sm text-rose-500">{{ addrErr }}</p>
        </div>

        <div class="p-4 border-t border-slate-100 shrink-0 flex gap-2">
          <button @click="formOpen = false" class="flex-1 h-11 rounded-full border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50">ยกเลิก</button>
          <button @click="saveAddr" :disabled="addrSaving" class="flex-1 h-11 rounded-full btn-orange btn-sheen font-semibold text-sm disabled:opacity-60">
            {{ addrSaving ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useCart } from '../../composables/useCart.js'
import { useBuyNow } from '../../composables/useBuyNow.js'
import { useAuth } from '../../composables/useAuth.js'
import Breadcrumb from './components/Breadcrumb.vue'
import ThaiAddressFields from './components/ThaiAddressFields.vue'

const router  = useRouter()
const route   = useRoute()
const toast   = useToast()
const cart    = useCart()
const buyNow  = useBuyNow()
const { user } = useAuth()

const isBuyNow    = computed(() => route.query.buynow === '1')
const activeItems  = computed(() => isBuyNow.value ? buyNow.items.value  : cart.items.value)
const activeGroups = computed(() => isBuyNow.value ? buyNow.groups.value : cart.groups.value)
const activeTotal  = computed(() => isBuyNow.value ? buyNow.subtotal.value : cart.subtotal.value)

// ========== Addresses ==========
const addresses    = ref([])
const selectedAddr = ref(null)
const pickerOpen   = ref(false)
const formOpen     = ref(false)
const addrErr      = ref('')
const addrSaving   = ref(false)

const blankAddr = () => ({ id: null, label: 'บ้าน', name: user.value?.name || '', phone: '', address: '', sub_district: '', district: '', province: 'นครราชสีมา', postal_code: '', is_default: false })
const addrForm = reactive(blankAddr())

async function loadAddresses() {
  try {
    const { data } = await api.get('/shop/my/addresses')
    addresses.value = data
    if (!selectedAddr.value) {
      selectedAddr.value = data.find(a => a.is_default) || data[0] || null
    }
  } catch { /* not logged in or no addresses */ }
}

function selectAddr(addr) {
  selectedAddr.value = addr
  pickerOpen.value = false
}

function openPicker() { pickerOpen.value = true }

function openAddNew() {
  Object.assign(addrForm, blankAddr())
  pickerOpen.value = false
  formOpen.value = true
}

function editAddr(addr) {
  Object.assign(addrForm, { ...addr })
  pickerOpen.value = false
  formOpen.value = true
}

async function setDefault(addr) {
  await api.post(`/shop/my/addresses/${addr.id}/default`)
  await loadAddresses()
}

async function deleteAddr(addr) {
  if (!confirm(`ลบที่อยู่ "${addr.label}" ออก?`)) return
  await api.delete(`/shop/my/addresses/${addr.id}`)
  if (selectedAddr.value?.id === addr.id) selectedAddr.value = null
  await loadAddresses()
}

async function saveAddr() {
  if (!addrForm.name || !addrForm.phone || !addrForm.address) {
    addrErr.value = 'กรุณากรอกชื่อผู้รับ เบอร์โทร และที่อยู่'
    return
  }
  addrSaving.value = true
  addrErr.value = ''
  try {
    const payload = { label: addrForm.label, name: addrForm.name, phone: addrForm.phone, address: addrForm.address, sub_district: addrForm.sub_district, district: addrForm.district, province: addrForm.province, postal_code: addrForm.postal_code, is_default: addrForm.is_default }
    if (addrForm.id) {
      const { data } = await api.put(`/shop/my/addresses/${addrForm.id}`, payload)
      if (selectedAddr.value?.id === addrForm.id) selectedAddr.value = data
    } else {
      const { data } = await api.post('/shop/my/addresses', payload)
      selectedAddr.value = data
    }
    formOpen.value = false
    await loadAddresses()
  } catch (e) {
    addrErr.value = e.response?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    addrSaving.value = false
  }
}

// ========== Checkout ==========
const form    = reactive({ shipping_note: '' })
const err     = reactive({})
const error   = ref('')
const loading = ref(false)

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }

async function placeOrder() {
  if (!selectedAddr.value) { error.value = 'กรุณาเลือกที่อยู่จัดส่ง'; return }
  loading.value = true
  error.value = ''
  try {
    const a = selectedAddr.value
    const items = activeItems.value.map(i => ({ product_id: i.product_id, qty: i.qty }))
    const { data } = await api.post('/shop/checkout', {
      shipping_name: a.name, shipping_phone: a.phone, shipping_address: a.address,
      shipping_sub_district: a.sub_district, shipping_district: a.district,
      shipping_province: a.province, shipping_zipcode: a.postal_code,
      shipping_note: form.shipping_note, items,
    })
    isBuyNow.value ? buyNow.clear() : cart.clear()
    toast.add({ severity: 'success', summary: 'สั่งซื้อสำเร็จ', detail: `สร้าง ${data.order_nos.length} คำสั่งซื้อ`, life: 2500 })
    router.push(`/shop/account/orders/${data.order_nos[0]}`)
  } catch (e) {
    if (e.response?.status === 422) {
      Object.entries(e.response.data.errors || {}).forEach(([k, v]) => { err[k] = v[0] })
      error.value = e.response.data.message || 'กรุณาตรวจสอบข้อมูล'
    } else {
      error.value = e.response?.data?.message || 'สั่งซื้อไม่สำเร็จ'
    }
  } finally {
    loading.value = false
  }
}

onMounted(loadAddresses)
</script>

<style scoped>
.inp { height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
textarea.inp { height: auto; padding: 0.5rem 0.75rem; }
</style>
