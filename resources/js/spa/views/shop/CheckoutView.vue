<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
    <Breadcrumb :items="[{ label: 'ตะกร้า', to: '/shop/cart' }, { label: 'ชำระเงิน' }]" class="mb-4" />

    <div v-if="!cart.items.value.length" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-shopping-cart text-4xl"></i>
      <p class="mt-3">ไม่มีสินค้าในตะกร้า</p>
      <RouterLink to="/shop/products" class="text-violet-600 hover:underline mt-2 inline-block">เลือกซื้อสินค้า</RouterLink>
    </div>

    <div v-else class="grid lg:grid-cols-3 gap-5">
      <!-- Shipping form -->
      <div class="lg:col-span-2">
        <div class="box-card p-5">
          <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2"><i class="fi fi-rr-marker text-violet-600"></i> ที่อยู่จัดส่ง</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="form-label">ชื่อผู้รับ *</label>
              <input v-model="form.shipping_name" class="inp" :class="{ 'border-rose-400': err.shipping_name }" />
            </div>
            <div>
              <label class="form-label">เบอร์โทร *</label>
              <input v-model="form.shipping_phone" class="inp" :class="{ 'border-rose-400': err.shipping_phone }" />
            </div>
            <div class="sm:col-span-2">
              <label class="form-label">ที่อยู่ (บ้านเลขที่ หมู่ ถนน) *</label>
              <input v-model="form.shipping_address" class="inp" :class="{ 'border-rose-400': err.shipping_address }" />
            </div>
            <div>
              <label class="form-label">ตำบล/แขวง</label>
              <input v-model="form.shipping_sub_district" class="inp" />
            </div>
            <div>
              <label class="form-label">อำเภอ/เขต</label>
              <input v-model="form.shipping_district" class="inp" />
            </div>
            <div>
              <label class="form-label">จังหวัด</label>
              <input v-model="form.shipping_province" class="inp" />
            </div>
            <div>
              <label class="form-label">รหัสไปรษณีย์</label>
              <input v-model="form.shipping_zipcode" class="inp" maxlength="5" />
            </div>
            <div class="sm:col-span-2">
              <label class="form-label">หมายเหตุ (ไม่บังคับ)</label>
              <textarea v-model="form.shipping_note" rows="2" class="inp"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div class="lg:col-span-1">
        <div class="box-card p-5 sticky top-20">
          <h3 class="font-bold text-slate-800 mb-3">สรุปคำสั่งซื้อ</h3>
          <div v-for="g in cart.groups.value" :key="g.group_id" class="mb-3 pb-3 border-b border-slate-100 last:border-0">
            <p class="text-xs font-semibold text-violet-700 mb-1"><i class="fi fi-rr-shop"></i> {{ g.group_name }}</p>
            <div v-for="item in g.items" :key="item.product_id" class="flex justify-between text-sm text-slate-600">
              <span class="truncate pr-2">{{ item.name }} ×{{ item.qty }}</span>
              <span class="shrink-0">฿{{ fmt(item.price * item.qty) }}</span>
            </div>
          </div>
          <div class="flex justify-between items-end pt-2">
            <span class="text-slate-600">ยอดสุทธิ</span>
            <span class="text-2xl font-bold text-fuchsia-700">฿{{ fmt(cart.subtotal.value) }}</span>
          </div>
          <p v-if="error" class="text-sm text-rose-500 mt-2">{{ error }}</p>
          <button :disabled="loading" class="btn-sheen w-full mt-4 h-12 rounded-full btn-orange font-semibold disabled:opacity-60" @click="placeOrder">
            {{ loading ? 'กำลังสั่งซื้อ...' : 'ยืนยันสั่งซื้อ' }}
          </button>
          <p class="text-xs text-slate-400 mt-2 text-center">ขั้นตอนถัดไปคือแจ้งโอนเงิน + แนบสลิป</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useCart } from '../../composables/useCart.js'
import { useAuth } from '../../composables/useAuth.js'
import Breadcrumb from './components/Breadcrumb.vue'

const router = useRouter()
const toast = useToast()
const cart = useCart()
const { user } = useAuth()

const form = reactive({
  shipping_name: '', shipping_phone: '', shipping_address: '',
  shipping_sub_district: '', shipping_district: '', shipping_province: 'นครราชสีมา',
  shipping_zipcode: '', shipping_note: '',
})
const err = reactive({})
const error = ref('')
const loading = ref(false)

onMounted(() => { if (user.value?.name) form.shipping_name = user.value.name })

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }

async function placeOrder() {
  loading.value = true
  error.value = ''
  Object.keys(err).forEach(k => delete err[k])
  try {
    const items = cart.items.value.map(i => ({ product_id: i.product_id, qty: i.qty }))
    const { data } = await api.post('/shop/checkout', { ...form, items })
    cart.clear()
    toast.add({ severity: 'success', summary: 'สั่งซื้อสำเร็จ', detail: `สร้าง ${data.order_nos.length} คำสั่งซื้อ`, life: 2500 })
    // ไปแจ้งชำระเงินของออเดอร์แรก
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
</script>

<style scoped>
.inp { width: 100%; height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
textarea.inp { height: auto; padding: 0.5rem 0.75rem; }
</style>
