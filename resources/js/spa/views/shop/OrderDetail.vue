<template>
  <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6">
    <Breadcrumb :items="[{ label: 'คำสั่งซื้อ', to: '/shop/account/orders' }, { label: orderNo }]" class="mb-4" />

    <div v-if="loading" class="box-card p-12 text-center text-slate-400"><i class="fi fi-rr-spinner animate-spin text-3xl"></i></div>

    <div v-else-if="order" class="space-y-4">
      <!-- Header -->
      <div class="box-card p-5">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <h1 class="text-lg font-bold text-slate-800">{{ order.order_no }}</h1>
            <p class="text-xs text-slate-400">{{ order.seller_group?.name }}</p>
          </div>
          <OrderStatusChip :status="order.status" />
        </div>
      </div>

      <!-- Items -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 mb-3">รายการสินค้า</h3>
        <div v-for="it in order.items" :key="it.id" class="flex justify-between py-1.5 text-sm border-b border-slate-50 last:border-0">
          <span class="text-slate-600">{{ it.product_name }} <span class="text-slate-400">×{{ it.qty }}</span></span>
          <span class="text-slate-700">฿{{ fmt(it.line_total) }}</span>
        </div>
        <div class="flex justify-between mt-3 pt-3 border-t border-slate-100 font-bold">
          <span class="text-slate-700">ยอดสุทธิ</span>
          <span class="text-fuchsia-700 text-lg">฿{{ fmt(order.total) }}</span>
        </div>
      </div>

      <!-- Shipping -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 mb-2 flex items-center gap-2"><i class="fi fi-rr-marker text-violet-600"></i> ที่อยู่จัดส่ง</h3>
        <p class="text-sm text-slate-600">{{ order.shipping_name }} · {{ order.shipping_phone }}</p>
        <p class="text-sm text-slate-500">{{ [order.shipping_address, order.shipping_sub_district, order.shipping_district, order.shipping_province, order.shipping_zipcode].filter(Boolean).join(' ') }}</p>
        <div v-if="order.shipment?.tracking_no" class="mt-2 text-sm text-indigo-600">
          <i class="fi fi-rr-truck-side"></i> {{ order.shipment.carrier }} · เลขพัสดุ {{ order.shipment.tracking_no }}
        </div>
      </div>

      <!-- Payment -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 mb-3 flex items-center gap-2"><i class="fi fi-rr-receipt text-violet-600"></i> การชำระเงิน</h3>

        <!-- already paid (latest payment) -->
        <div v-if="latestPayment" class="mb-3 p-3 rounded-lg" :class="payBoxClass">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium">{{ payStatusLabel }}</span>
            <span class="text-sm">฿{{ fmt(latestPayment.amount) }}</span>
          </div>
          <p v-if="latestPayment.status === 'rejected'" class="text-xs text-rose-600 mt-1">เหตุผล: {{ latestPayment.reject_reason }}</p>
          <img v-if="latestPayment.slip_url" :src="latestPayment.slip_url" class="mt-2 max-h-48 rounded-lg border" />
        </div>

        <!-- bank info + submit (when payable) -->
        <template v-if="canPay">
          <div class="p-3 rounded-lg bg-violet-50/60 text-sm mb-3">
            <p class="font-medium text-violet-700 mb-1">โอนเงินมาที่</p>
            <template v-if="order.seller_group?.bank_account_no || order.seller_group?.promptpay_id">
              <p v-if="order.seller_group?.bank_name" class="text-slate-600">{{ order.seller_group.bank_name }}</p>
              <p v-if="order.seller_group?.bank_account_no" class="text-slate-700 font-semibold">{{ order.seller_group.bank_account_no }}</p>
              <p v-if="order.seller_group?.bank_account_name" class="text-slate-600">ชื่อบัญชี: {{ order.seller_group.bank_account_name }}</p>
              <p v-if="order.seller_group?.promptpay_id" class="text-slate-600">พร้อมเพย์: {{ order.seller_group.promptpay_id }}</p>
            </template>
            <p v-else class="text-slate-500">กรุณาติดต่อผู้ขายเพื่อขอข้อมูลบัญชี ({{ order.seller_group?.contact_phone || '-' }})</p>
            <p class="mt-1 text-slate-700">ยอดที่ต้องโอน: <span class="font-bold text-fuchsia-700">฿{{ fmt(order.total) }}</span></p>
          </div>

          <div class="space-y-2">
            <div>
              <label class="form-label">จำนวนเงินที่โอน</label>
              <input v-model.number="pay.amount" type="number" class="inp" />
            </div>
            <div>
              <label class="form-label">เลขอ้างอิง/ธนาคาร (ไม่บังคับ)</label>
              <input v-model="pay.bank_ref" class="inp" />
            </div>
            <div>
              <label class="form-label">แนบสลิปโอนเงิน *</label>
              <input ref="slipInput" type="file" accept="image/*" class="block w-full text-sm" @change="onSlip" />
            </div>
            <p v-if="payError" class="text-sm text-rose-500">{{ payError }}</p>
            <button :disabled="submitting" class="btn-sheen w-full h-11 rounded-full btn-orange font-semibold disabled:opacity-60" @click="submitPayment">
              {{ submitting ? 'กำลังส่ง...' : 'แจ้งชำระเงิน' }}
            </button>
          </div>
        </template>
      </div>

      <!-- Actions -->
      <div v-if="canReceive || canReturn" class="box-card p-5 flex flex-wrap gap-2">
        <button v-if="canReceive" class="btn-orange px-5 py-2.5 rounded-full font-semibold" @click="receive">
          <i class="fi fi-rr-box-check mr-1"></i> ฉันได้รับสินค้าแล้ว
        </button>
        <button v-if="canReturn" class="px-5 py-2.5 rounded-full font-medium border border-rose-300 text-rose-600 hover:bg-rose-50" @click="returnOpen = true">
          <i class="fi fi-rr-undo mr-1"></i> ขอคืน/เคลมสินค้า
        </button>
      </div>

      <!-- Timeline -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 mb-3 flex items-center gap-2"><i class="fi fi-rr-time-past text-violet-600"></i> ประวัติสถานะ</h3>
        <ol class="relative border-l border-slate-200 ml-2">
          <li v-for="h in order.status_histories" :key="h.id" class="mb-4 ml-4">
            <span class="absolute -left-1.5 w-3 h-3 rounded-full bg-violet-400"></span>
            <OrderStatusChip :status="h.status" />
            <p v-if="h.note" class="text-xs text-slate-500 mt-1">{{ h.note }}</p>
            <p class="text-[11px] text-slate-400">{{ formatDate(h.created_at) }}</p>
          </li>
        </ol>
      </div>
    </div>

    <!-- Return/claim dialog -->
    <Dialog v-model:visible="returnOpen" modal header="ขอคืน / เคลมสินค้า" :style="{ width: '28rem' }">
      <div class="space-y-3">
        <div>
          <label class="form-label">ประเภท</label>
          <select v-model="ret.type" class="inp">
            <option value="return">ขอคืนสินค้า</option>
            <option value="refund">ขอคืนเงิน</option>
            <option value="claim">เคลม (สินค้าชำรุด)</option>
          </select>
        </div>
        <div>
          <label class="form-label">เหตุผล *</label>
          <input v-model="ret.reason" class="inp" placeholder="เช่น สินค้าชำรุด / ไม่ตรงปก" />
        </div>
        <div>
          <label class="form-label">รายละเอียดเพิ่มเติม</label>
          <textarea v-model="ret.description" rows="3" class="inp"></textarea>
        </div>
        <div>
          <label class="form-label">รูปประกอบ (ไม่บังคับ)</label>
          <input ref="retImgInput" type="file" accept="image/*" multiple class="block w-full text-sm" @change="onRetImgs" />
        </div>
        <p v-if="retError" class="text-sm text-rose-500">{{ retError }}</p>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="returnOpen = false" />
        <Button label="ส่งคำขอ" severity="danger" :loading="retBusy" @click="submitReturn" />
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import api from '../../api/index.js'
import Breadcrumb from './components/Breadcrumb.vue'
import OrderStatusChip from './components/OrderStatusChip.vue'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'

const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const orderNo = route.params.orderNo

const returnOpen = ref(false)
const retBusy = ref(false)
const retError = ref('')
const retImgInput = ref(null)
const ret = reactive({ type: 'return', reason: '', description: '', images: [] })

const order = ref(null)
const loading = ref(true)
const submitting = ref(false)
const payError = ref('')
const slipInput = ref(null)
const pay = reactive({ amount: 0, bank_ref: '', slip: null })

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function formatDate(d) { return d ? new Date(d).toLocaleString('th-TH') : '' }

const latestPayment = computed(() => order.value?.payments?.[0] || null)
const canPay = computed(() => ['pending_payment'].includes(order.value?.status))
const canReceive = computed(() => ['shipped', 'delivered'].includes(order.value?.status))
const canReturn = computed(() => ['shipped', 'delivered', 'completed'].includes(order.value?.status))
const payStatusLabel = computed(() => ({
  pending: 'แจ้งชำระแล้ว — รอผู้ขายยืนยัน',
  confirmed: 'ยืนยันการชำระเงินแล้ว',
  rejected: 'สลิปไม่ผ่าน — กรุณาแจ้งชำระใหม่',
}[latestPayment.value?.status] || ''))
const payBoxClass = computed(() => ({
  pending: 'bg-sky-50 text-sky-700',
  confirmed: 'bg-emerald-50 text-emerald-700',
  rejected: 'bg-rose-50 text-rose-700',
}[latestPayment.value?.status] || 'bg-slate-50'))

async function load() {
  loading.value = true
  try {
    const { data } = await api.get(`/shop/my/orders/${orderNo}`)
    order.value = data
    pay.amount = Number(data.total)
  } finally {
    loading.value = false
  }
}

function onSlip(e) { pay.slip = e.target.files?.[0] || null }

async function submitPayment() {
  if (!pay.slip) { payError.value = 'กรุณาแนบสลิป'; return }
  submitting.value = true
  payError.value = ''
  try {
    const fd = new FormData()
    fd.append('amount', pay.amount)
    if (pay.bank_ref) fd.append('bank_ref', pay.bank_ref)
    fd.append('slip', pay.slip)
    await api.post(`/shop/orders/${orderNo}/payment`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'แจ้งชำระเงินแล้ว', detail: 'รอผู้ขายยืนยัน', life: 2500 })
    pay.slip = null
    if (slipInput.value) slipInput.value.value = ''
    await load()
  } catch (e) {
    payError.value = e.response?.data?.message || e.response?.data?.errors?.slip?.[0] || 'แจ้งชำระไม่สำเร็จ'
  } finally {
    submitting.value = false
  }
}

function receive() {
  confirm.require({
    message: 'ยืนยันว่าได้รับสินค้าครบถ้วนแล้ว?',
    header: 'ยืนยันรับสินค้า', icon: 'fi fi-rr-box-check', acceptLabel: 'ได้รับแล้ว', rejectLabel: 'ยกเลิก',
    accept: async () => {
      try { await api.post(`/shop/orders/${orderNo}/receive`); toast.add({ severity: 'success', summary: 'ขอบคุณค่ะ', life: 2500 }); await load() }
      catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
    },
  })
}

function onRetImgs(e) { ret.images = Array.from(e.target.files || []) }

async function submitReturn() {
  if (!ret.reason.trim()) { retError.value = 'กรุณาระบุเหตุผล'; return }
  retBusy.value = true; retError.value = ''
  try {
    const fd = new FormData()
    fd.append('type', ret.type); fd.append('reason', ret.reason)
    if (ret.description) fd.append('description', ret.description)
    ret.images.forEach(f => fd.append('images[]', f))
    await api.post(`/shop/orders/${orderNo}/returns`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'ส่งคำขอแล้ว', detail: 'รอผู้ขายตรวจสอบ', life: 2500 })
    returnOpen.value = false
    ret.reason = ''; ret.description = ''; ret.images = []
    await load()
  } catch (e) {
    retError.value = e.response?.data?.message || 'ส่งคำขอไม่สำเร็จ'
  } finally { retBusy.value = false }
}

onMounted(load)
</script>

<style scoped>
.inp { width: 100%; height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
</style>
