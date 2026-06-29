<template>
  <div class="p-3 sm:p-5 space-y-4">
    <!-- Header -->
    <div>
      <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-shopping-bag text-violet-600"></i> คำสั่งซื้อ
      </h2>
      <p class="text-xs text-slate-400 mt-0.5">ยืนยัน / เตรียมสินค้า / บันทึกจัดส่ง</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
      <button v-for="t in tabs" :key="t.key"
        class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium transition"
        :class="tab === t.key ? 'bg-violet-600 text-white shadow' : 'bg-white border border-slate-200 text-slate-600'"
        @click="setTab(t.key)">
        {{ t.label }}
      </button>
    </div>

    <!-- Card list -->
    <div class="space-y-2.5">
      <template v-if="loading">
        <div v-for="n in 4" :key="n" class="box-card p-4 skeleton h-28"></div>
      </template>
      <div v-else-if="!rows.length" class="box-card py-14 text-center text-slate-400">
        <i class="fi fi-rr-shopping-bag text-4xl"></i>
        <p class="mt-2 text-sm">ไม่มีคำสั่งซื้อ</p>
      </div>
      <div v-else v-for="row in rows" :key="row.id"
        class="box-card p-4 space-y-3 active:bg-violet-50/40 transition">
        <!-- Row 1: order no + date + status -->
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="font-semibold text-slate-800 text-sm">{{ row.order_no }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">{{ fmtDate(row.created_at) }}</p>
          </div>
          <OrderStatusChip :status="row.status" />
        </div>
        <!-- Row 2: customer + amount -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2 min-w-0">
            <div class="w-7 h-7 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center text-xs font-bold shrink-0">
              {{ (row.user?.name || row.shipping_name || '?')[0] }}
            </div>
            <span class="text-sm text-slate-700 truncate">{{ row.user?.name || row.shipping_name }}</span>
          </div>
          <span class="font-bold text-fuchsia-700 text-base shrink-0">฿{{ fmt(row.total) }}</span>
        </div>
        <!-- Row 3: items count + actions -->
        <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100">
          <span class="text-xs text-slate-400">{{ row.items_count }} รายการ</span>
          <div class="flex gap-1.5">
            <button class="h-8 px-3 rounded-lg bg-slate-100 hover:bg-violet-100 text-slate-600 hover:text-violet-700 text-xs font-medium transition flex items-center gap-1"
              @click="openDetail(row)">
              <i class="fi fi-rr-eye text-[11px]"></i> ดู
            </button>
            <button v-if="row.status === 'confirmed'"
              class="h-8 px-3 rounded-lg bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-medium transition flex items-center gap-1"
              @click="setStatus(row, 'processing')">
              <i class="fi fi-rr-box-open text-[11px]"></i> เตรียม
            </button>
            <button v-if="['confirmed','processing'].includes(row.status)"
              class="h-8 px-3 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-xs font-medium transition flex items-center gap-1"
              @click="openShip(row)">
              <i class="fi fi-rr-truck-side text-[11px]"></i> จัดส่ง
            </button>
          </div>
        </div>
      </div>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <!-- Ship dialog -->
    <Dialog v-model:visible="shipOpen" modal header="บันทึกการจัดส่ง" :style="{ width: '95vw', maxWidth: '26rem' }">
      <div class="space-y-3">
        <div><label class="form-label">ขนส่ง</label>
          <InputText v-model="ship.carrier" class="w-full" placeholder="ไปรษณีย์ไทย / Flash / Kerry" /></div>
        <div><label class="form-label">เลขพัสดุ</label>
          <InputText v-model="ship.tracking_no" class="w-full" /></div>
        <div><label class="form-label">หมายเหตุ</label>
          <Textarea v-model="ship.note" rows="2" class="w-full" /></div>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="shipOpen=false" />
        <Button label="ยืนยันจัดส่ง" icon="fi fi-rr-check" :loading="busy" @click="doShip" />
      </template>
    </Dialog>

    <!-- Detail bottom sheet -->
    <Dialog v-model:visible="detailOpen" modal :header="detail?.order_no"
      :style="{ width: '95vw', maxWidth: '34rem' }" :breakpoints="{ '640px': '100vw' }">
      <div v-if="detail" class="space-y-3 text-sm">
        <div class="flex items-center justify-between">
          <OrderStatusChip :status="detail.status" />
          <span class="font-bold text-fuchsia-700 text-base">฿{{ fmt(detail.total) }}</span>
        </div>
        <div class="box-card p-3 bg-slate-50">
          <p class="font-medium text-slate-700 mb-2 text-xs uppercase tracking-wide text-slate-400">รายการสินค้า</p>
          <div v-for="it in detail.items" :key="it.id"
            class="flex justify-between text-slate-600 py-1.5 border-b border-slate-100 last:border-0">
            <span class="truncate pr-2">{{ it.product_name }} <span class="text-slate-400 text-xs">×{{ it.qty }}</span></span>
            <span class="font-medium shrink-0">฿{{ fmt(it.line_total) }}</span>
          </div>
        </div>
        <div class="box-card p-3 bg-slate-50">
          <p class="text-xs uppercase tracking-wide text-slate-400 mb-1.5">ผู้รับ</p>
          <p class="font-medium text-slate-700">{{ detail.shipping_name }} · {{ detail.shipping_phone }}</p>
          <p class="text-slate-500 text-xs mt-0.5 leading-relaxed">
            {{ [detail.shipping_address, detail.shipping_sub_district, detail.shipping_district, detail.shipping_province, detail.shipping_zipcode].filter(Boolean).join(' ') }}
          </p>
        </div>
        <div v-if="detail.payments?.length && detail.payments[0].slip_url" class="box-card p-3 bg-slate-50">
          <p class="text-xs uppercase tracking-wide text-slate-400 mb-2">สลิปโอนเงิน</p>
          <img :src="detail.payments[0].slip_url" class="max-h-48 rounded-xl border border-slate-200 w-auto" />
        </div>
      </div>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import OrderStatusChip from '../shop/components/OrderStatusChip.vue'
import Pagination from '../components/Pagination.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'

const toast = useToast()
const rows = ref([])
const meta = ref({})
const loading = ref(false)
const page = ref(1)
const tab = ref('all')
const tabs = [
  { key: 'all', label: 'ทั้งหมด' },
  { key: 'to_pay', label: 'รอชำระ' },
  { key: 'to_ship', label: 'ต้องจัดส่ง' },
  { key: 'shipped', label: 'จัดส่งแล้ว' },
  { key: 'completed', label: 'สำเร็จ' },
  { key: 'return', label: 'คืน/เคลม' },
]

const shipOpen = ref(false)
const ship = reactive({ id: null, carrier: '', tracking_no: '', note: '' })
const busy = ref(false)
const detailOpen = ref(false)
const detail = ref(null)

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) : '' }

async function load() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (tab.value !== 'all') params.status_group = tab.value
    const { data } = await api.get('/market/orders', { params })
    rows.value = data.data || []
    meta.value = data
  } finally { loading.value = false }
}
function setTab(k) { tab.value = k; page.value = 1; load() }
function goPage(p) { page.value = p; load() }

async function setStatus(o, status) {
  try { await api.post(`/market/orders/${o.id}/status`, { status }); toast.add({ severity: 'success', summary: 'อัปเดตแล้ว', life: 2000 }); load() }
  catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
}

function openShip(o) { Object.assign(ship, { id: o.id, carrier: '', tracking_no: '', note: '' }); shipOpen.value = true }
async function doShip() {
  busy.value = true
  try {
    await api.post(`/market/orders/${ship.id}/ship`, { carrier: ship.carrier, tracking_no: ship.tracking_no, note: ship.note })
    toast.add({ severity: 'success', summary: 'บันทึกการจัดส่งแล้ว', life: 2000 })
    shipOpen.value = false; load()
  } catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
  finally { busy.value = false }
}

async function openDetail(o) {
  const { data } = await api.get(`/market/orders/${o.id}`)
  detail.value = data
  detailOpen.value = true
}

onMounted(load)
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
