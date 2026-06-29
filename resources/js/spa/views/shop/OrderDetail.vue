<template>
  <div class="max-w-2xl mx-auto px-4 py-5 space-y-4">
    <Breadcrumb :items="[{ label: 'คำสั่งซื้อ', to: '/shop/account/orders' }, { label: orderNo }]" class="mb-2" />

    <div v-if="loading" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-spinner animate-spin text-3xl"></i>
    </div>

    <template v-else-if="order">
      <!-- Header -->
      <div class="box-card p-5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <div>
            <h1 class="text-base font-bold text-slate-800">{{ order.order_no }}</h1>
            <p class="text-xs text-slate-400 mt-0.5">{{ order.seller_group?.name }}</p>
          </div>
          <OrderStatusChip :status="order.status" />
        </div>
      </div>

      <!-- Items -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-3">รายการสินค้า</h3>
        <div v-for="it in order.items" :key="it.id"
          class="flex justify-between py-2 text-sm border-b border-slate-50 last:border-0">
          <span class="text-slate-600 pr-3">{{ it.product_name }} <span class="text-slate-400">×{{ it.qty }}</span></span>
          <span class="text-slate-700 shrink-0">฿{{ fmt(it.line_total) }}</span>
        </div>
        <div class="flex justify-between mt-3 pt-3 border-t border-slate-100 font-bold">
          <span class="text-slate-700">ยอดสุทธิ</span>
          <span class="text-fuchsia-700 text-lg">฿{{ fmt(order.total) }}</span>
        </div>
      </div>

      <!-- Shipping -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-2 flex items-center gap-2">
          <i class="fi fi-rr-marker text-violet-600"></i> ที่อยู่จัดส่ง
        </h3>
        <p class="text-sm text-slate-600">{{ order.shipping_name }} · {{ order.shipping_phone }}</p>
        <p class="text-sm text-slate-500 mt-0.5 leading-relaxed">
          {{ [order.shipping_address, order.shipping_sub_district, order.shipping_district, order.shipping_province, order.shipping_zipcode].filter(Boolean).join(' ') }}
        </p>
        <div v-if="order.shipment?.tracking_no"
          class="mt-3 px-3 py-2.5 rounded-xl bg-indigo-50 border border-indigo-100 text-sm text-indigo-700 flex items-center gap-2">
          <i class="fi fi-rr-truck-side"></i>
          <span class="font-medium">{{ order.shipment.carrier }}</span>
          <span class="font-mono text-indigo-800">{{ order.shipment.tracking_no }}</span>
        </div>
      </div>

      <!-- ===== Payment section ===== -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-3 flex items-center gap-2">
          <i class="fi fi-rr-receipt text-violet-600"></i> การชำระเงิน
        </h3>

        <!-- Existing payment status -->
        <div v-if="latestPayment" class="rounded-xl p-4 mb-4 flex gap-3"
          :class="payBoxClass">
          <!-- Slip thumbnail (tappable → fullscreen) -->
          <button v-if="latestPayment.slip_url"
            @click="fullscreenSlip = latestPayment.slip_url"
            class="shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-white/60 shadow-sm">
            <img :src="latestPayment.slip_url" class="w-full h-full object-cover" />
          </button>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <i class="fi text-xl"
                :class="{
                  'fi-rr-clock text-sky-500': latestPayment.status === 'pending',
                  'fi-rr-check-circle text-emerald-500': latestPayment.status === 'confirmed',
                  'fi-rr-cross-circle text-rose-500': latestPayment.status === 'rejected',
                }"></i>
              <p class="font-semibold text-sm">{{ payStatusLabel }}</p>
            </div>
            <p class="text-sm font-bold mt-1">฿{{ fmt(latestPayment.amount) }}</p>
            <p v-if="latestPayment.status === 'rejected' && latestPayment.reject_reason"
              class="text-xs text-rose-600 mt-1 bg-rose-50 rounded-lg px-2 py-1">
              เหตุผล: {{ latestPayment.reject_reason }}
            </p>
          </div>
        </div>

        <!-- Bank account info (when payable) -->
        <template v-if="canPay">
          <div class="rounded-2xl bg-gradient-to-br from-violet-50 to-fuchsia-50 border border-violet-100 p-4 mb-4">
            <p class="text-xs font-semibold text-violet-500 uppercase tracking-wider mb-2">โอนเงินมาที่</p>
            <template v-if="order.seller_group?.bank_account_no || order.seller_group?.promptpay_id">
              <p v-if="order.seller_group?.bank_name" class="text-sm text-slate-600 font-medium">{{ order.seller_group.bank_name }}</p>
              <p v-if="order.seller_group?.bank_account_no" class="text-lg font-bold text-slate-800 font-mono tracking-wider mt-1">{{ order.seller_group.bank_account_no }}</p>
              <p v-if="order.seller_group?.bank_account_name" class="text-sm text-slate-600 mt-0.5">{{ order.seller_group.bank_account_name }}</p>
              <p v-if="order.seller_group?.promptpay_id" class="text-sm text-violet-600 mt-1.5 flex items-center gap-1.5"><i class="fi fi-rr-smartphone"></i> พร้อมเพย์: {{ order.seller_group.promptpay_id }}</p>
            </template>
            <p v-else class="text-sm text-slate-500">ติดต่อผู้ขาย: {{ order.seller_group?.contact_phone || '-' }}</p>
            <div class="mt-3 pt-3 border-t border-violet-100 flex items-center justify-between">
              <span class="text-sm text-slate-500">ยอดที่ต้องโอน</span>
              <span class="text-xl font-bold text-fuchsia-700">฿{{ fmt(order.total) }}</span>
            </div>
          </div>

          <!-- Slip uploader -->
          <div class="space-y-3">
            <p class="text-sm font-semibold text-slate-700 flex items-center gap-2">
              <i class="fi fi-rr-camera text-violet-500"></i> แนบสลิปโอนเงิน
              <span class="text-xs font-normal text-slate-400">(ระบบอ่าน QR อัตโนมัติ)</span>
            </p>
            <SlipUploader :orderTotal="order.total" @change="onSlipChange" />
            <p v-if="payError" class="text-sm text-rose-500 flex items-center gap-1">
              <i class="fi fi-rr-exclamation"></i> {{ payError }}
            </p>
            <button
              :disabled="submitting || !pay.file"
              class="w-full h-12 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition shadow-lg"
              :class="pay.file
                ? 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white hover:opacity-90 shadow-violet-500/30'
                : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
              @click="submitPayment">
              <i v-if="submitting" class="fi fi-rr-spinner animate-spin"></i>
              <i v-else class="fi fi-rr-paper-plane"></i>
              {{ submitting ? 'กำลังส่ง...' : 'แจ้งชำระเงิน' }}
            </button>
          </div>
        </template>
      </div>

      <!-- Actions -->
      <div v-if="canReceive || canReturn" class="box-card p-5 space-y-2">
        <button v-if="canReceive"
          class="w-full h-12 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm flex items-center justify-center gap-2 transition"
          @click="receive">
          <i class="fi fi-rr-box-check"></i> ฉันได้รับสินค้าแล้ว
        </button>
        <button v-if="canReturn"
          class="w-full h-12 rounded-2xl border-2 border-rose-300 text-rose-600 hover:bg-rose-50 font-semibold text-sm flex items-center justify-center gap-2 transition"
          @click="returnOpen = true">
          <i class="fi fi-rr-undo"></i> ขอคืน / เคลมสินค้า
        </button>
      </div>

      <!-- Status timeline -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-4 flex items-center gap-2">
          <i class="fi fi-rr-time-past text-violet-600"></i> ประวัติสถานะ
        </h3>
        <ol class="relative ml-3">
          <li v-for="(h, idx) in order.status_histories" :key="h.id"
            class="mb-4 pl-5 relative">
            <!-- Line -->
            <div v-if="idx < order.status_histories.length - 1"
              class="absolute left-0 top-3 bottom-0 w-px bg-slate-200"></div>
            <!-- Dot -->
            <div class="absolute left-0 top-1.5 -translate-x-1/2 w-3 h-3 rounded-full border-2 border-white"
              :class="idx === 0 ? 'bg-violet-500 shadow-md shadow-violet-300' : 'bg-slate-300'"></div>
            <!-- Content -->
            <div class="flex items-center justify-between flex-wrap gap-1">
              <OrderStatusChip :status="h.status" />
              <span class="text-[11px] text-slate-400">{{ formatDate(h.created_at) }}</span>
            </div>
            <p v-if="h.note" class="text-xs text-slate-500 mt-1">{{ h.note }}</p>
          </li>
          <li v-if="!order.status_histories?.length" class="text-sm text-slate-400 pl-5">ยังไม่มีประวัติ</li>
        </ol>
      </div>

      <!-- Slip status tracking -->
      <div v-if="order.payments?.length" class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-4 flex items-center gap-2">
          <i class="fi fi-rr-receipt text-violet-600"></i> ประวัติสลิป
        </h3>
        <ol class="space-y-3">
          <li v-for="(pmt, idx) in order.payments" :key="pmt.id"
            class="flex gap-3 p-3 rounded-xl border"
            :class="{
              'border-emerald-200 bg-emerald-50': pmt.status === 'confirmed',
              'border-rose-200 bg-rose-50': pmt.status === 'rejected',
              'border-sky-200 bg-sky-50': pmt.status === 'pending',
            }">
            <!-- Thumbnail -->
            <button v-if="pmt.slip_url" @click="fullscreenSlip = pmt.slip_url"
              class="shrink-0 w-14 h-14 rounded-lg overflow-hidden border border-white/60 shadow-sm">
              <img :src="pmt.slip_url" class="w-full h-full object-cover" />
            </button>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                  :class="{
                    'bg-emerald-100 text-emerald-700': pmt.status === 'confirmed',
                    'bg-rose-100 text-rose-700': pmt.status === 'rejected',
                    'bg-sky-100 text-sky-700': pmt.status === 'pending',
                  }">
                  {{ { confirmed: 'ยืนยันแล้ว', rejected: 'ปฏิเสธ', pending: 'รอยืนยัน' }[pmt.status] || pmt.status }}
                </span>
                <span class="text-xs text-slate-500">{{ formatDate(pmt.paid_at || pmt.created_at) }}</span>
              </div>
              <p class="text-sm font-bold mt-1">฿{{ fmt(pmt.amount) }}</p>
              <p v-if="pmt.reject_reason" class="text-xs text-rose-600 mt-1">เหตุผล: {{ pmt.reject_reason }}</p>
            </div>
            <span class="text-xs text-slate-400 shrink-0">#{{ idx + 1 }}</span>
          </li>
        </ol>
      </div>
    </template>

    <!-- Return dialog -->
    <Dialog v-model:visible="returnOpen" modal header="ขอคืน / เคลมสินค้า"
      :style="{ width: '95vw', maxWidth: '26rem' }">
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

    <!-- Fullscreen slip viewer -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="fullscreenSlip"
          class="fixed inset-0 z-[9999] bg-black/95 flex items-center justify-center"
          style="touch-action: pinch-zoom;"
          @click.self="fullscreenSlip = null">
          <img :src="fullscreenSlip"
            class="max-w-full max-h-full object-contain select-none"
            style="touch-action: pinch-zoom;" />
          <button @click="fullscreenSlip = null"
            class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition backdrop-blur-sm">
            <i class="fi fi-rr-cross text-lg"></i>
          </button>
          <p class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/40 text-xs">
            Pinch เพื่อ zoom · กดนอกภาพเพื่อปิด
          </p>
        </div>
      </Transition>
    </Teleport>

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
import SlipUploader from './components/SlipUploader.vue'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'

const route   = useRoute()
const toast   = useToast()
const confirm = useConfirm()
const orderNo = route.params.orderNo

const order      = ref(null)
const loading    = ref(true)
const submitting = ref(false)
const payError   = ref('')
const fullscreenSlip = ref(null)

// pay: file + auto-detected amount (or fallback to order.total)
const pay = reactive({ file: null, amount: null })

function onSlipChange({ file, amount }) {
  pay.file   = file
  pay.amount = amount
}

const returnOpen   = ref(false)
const retBusy      = ref(false)
const retError     = ref('')
const retImgInput  = ref(null)
const ret = reactive({ type: 'return', reason: '', description: '', images: [] })

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function formatDate(d) { return d ? new Date(d).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' }) : '' }

const latestPayment = computed(() => order.value?.payments?.[0] || null)
const canPay = computed(() => {
  if (!order.value) return false
  if (['pending_payment'].includes(order.value.status)) return true
  if (latestPayment.value?.status === 'rejected') return true
  return false
})
const canReceive = computed(() => ['shipped', 'delivered'].includes(order.value?.status))
const canReturn  = computed(() => ['shipped', 'delivered', 'completed'].includes(order.value?.status))

const payStatusLabel = computed(() => ({
  pending:   'แจ้งชำระแล้ว — รอผู้ขายยืนยัน',
  confirmed: 'ยืนยันการชำระเงินแล้ว ✓',
  rejected:  'สลิปไม่ผ่าน — กรุณาแจ้งชำระใหม่',
}[latestPayment.value?.status] || ''))

const payBoxClass = computed(() => ({
  pending:   'bg-sky-50 border border-sky-200 text-sky-800',
  confirmed: 'bg-emerald-50 border border-emerald-200 text-emerald-800',
  rejected:  'bg-rose-50 border border-rose-200 text-rose-800',
}[latestPayment.value?.status] || 'bg-slate-50 border border-slate-200'))

async function load() {
  loading.value = true
  try { order.value = (await api.get(`/shop/my/orders/${orderNo}`)).data }
  finally { loading.value = false }
}

async function submitPayment() {
  if (!pay.file) { payError.value = 'กรุณาแนบสลิป'; return }
  submitting.value = true
  payError.value = ''
  try {
    const fd = new FormData()
    fd.append('amount', pay.amount ?? order.value.total)
    fd.append('slip', pay.file)
    await api.post(`/shop/orders/${orderNo}/payment`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'แจ้งชำระเงินแล้ว', detail: 'รอผู้ขายยืนยัน', life: 3000 })
    pay.file = null; pay.amount = null
    await load()
  } catch (e) {
    payError.value = e.response?.data?.message || e.response?.data?.errors?.slip?.[0] || 'แจ้งชำระไม่สำเร็จ'
  } finally { submitting.value = false }
}

function receive() {
  confirm.require({
    message: 'ยืนยันว่าได้รับสินค้าครบถ้วนแล้ว?',
    header: 'ยืนยันรับสินค้า', icon: 'fi fi-rr-box-check',
    acceptLabel: 'ได้รับแล้ว', rejectLabel: 'ยกเลิก',
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
    returnOpen.value = false; ret.reason = ''; ret.description = ''; ret.images = []
    await load()
  } catch (e) { retError.value = e.response?.data?.message || 'ส่งคำขอไม่สำเร็จ' }
  finally { retBusy.value = false }
}

onMounted(load)
</script>

<style scoped>
.inp { width: 100%; height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); font-size: 0.875rem; }
.inp:focus { outline: none; border-color: rgb(167 139 250); box-shadow: 0 0 0 3px rgb(167 139 250 / 0.15); }
textarea.inp { height: auto; padding: 0.6rem 0.75rem; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
