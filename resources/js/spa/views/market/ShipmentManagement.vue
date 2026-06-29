<template>
  <div class="p-3 sm:p-5 space-y-4">
    <div>
      <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-truck-side text-violet-600"></i> การจัดส่ง
      </h2>
      <p class="text-xs text-slate-400 mt-0.5">บันทึกและติดตามพัสดุ</p>
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
        <i class="fi fi-rr-truck-side text-4xl"></i>
        <p class="mt-2 text-sm">ไม่มีรายการ</p>
      </div>
      <div v-else v-for="row in rows" :key="row.id" class="box-card p-4 space-y-3">
        <!-- Order info -->
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="font-semibold text-slate-800">{{ row.order_no }}</p>
            <p class="text-xs text-slate-400">{{ fmtDate(row.created_at) }}</p>
          </div>
          <OrderStatusChip :status="row.status" />
        </div>
        <!-- Recipient -->
        <div class="flex items-center gap-2">
          <i class="fi fi-rr-marker text-violet-400 text-sm shrink-0"></i>
          <div class="min-w-0">
            <p class="text-sm text-slate-700 font-medium">{{ row.shipping_name }}</p>
            <p class="text-xs text-slate-400 truncate">{{ [row.shipping_district, row.shipping_province].filter(Boolean).join(', ') }}</p>
          </div>
          <span class="ml-auto font-bold text-fuchsia-700 text-sm shrink-0">฿{{ fmt(row.total) }}</span>
        </div>
        <!-- Tracking info -->
        <div class="rounded-xl px-3 py-2.5 flex items-center gap-3"
          :class="row.shipment ? 'bg-violet-50 border border-violet-100' : 'bg-slate-50 border border-slate-200'">
          <i class="fi fi-rr-box-open text-lg shrink-0" :class="row.shipment ? 'text-violet-500' : 'text-slate-300'"></i>
          <div class="flex-1 min-w-0">
            <template v-if="row.shipment">
              <p class="text-xs text-violet-600 font-medium">{{ row.shipment.carrier || 'ไม่ระบุขนส่ง' }}</p>
              <p class="font-mono text-sm text-violet-800 font-semibold truncate">{{ row.shipment.tracking_no || 'ยังไม่มีเลขพัสดุ' }}</p>
            </template>
            <p v-else class="text-sm text-slate-400">ยังไม่บันทึกข้อมูลการจัดส่ง</p>
          </div>
        </div>
        <!-- Actions -->
        <div class="flex gap-2 pt-1 border-t border-slate-100">
          <button class="flex-1 h-9 rounded-xl bg-slate-100 hover:bg-violet-100 text-slate-600 hover:text-violet-700 text-xs font-medium transition flex items-center justify-center gap-1.5"
            @click="openDetail(row)">
            <i class="fi fi-rr-eye text-[11px]"></i> ดูรายละเอียด
          </button>
          <button v-if="['confirmed','processing'].includes(row.status)"
            class="flex-1 h-9 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold transition flex items-center justify-center gap-1.5"
            @click="openShip(row)">
            <i class="fi fi-rr-truck-side text-[11px]"></i> บันทึกจัดส่ง
          </button>
          <button v-else-if="row.status === 'shipped'"
            class="flex-1 h-9 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium transition flex items-center justify-center gap-1.5"
            @click="openShip(row)">
            <i class="fi fi-rr-pencil text-[11px]"></i> แก้ไขข้อมูล
          </button>
        </div>
      </div>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <!-- Ship dialog -->
    <Dialog v-model:visible="shipOpen" modal header="บันทึกการจัดส่ง" :style="{ width: '95vw', maxWidth: '28rem' }">
      <div class="space-y-3 text-sm">
        <div v-if="shipTarget" class="rounded-xl p-3 bg-slate-50 border border-slate-200 text-xs">
          <p class="font-semibold text-slate-700">{{ shipTarget.order_no }}</p>
          <p class="text-slate-500 mt-0.5">{{ shipTarget.shipping_name }} · {{ shipTarget.shipping_phone }}</p>
          <p class="text-slate-400 mt-0.5 truncate">{{ [shipTarget.shipping_address, shipTarget.shipping_district, shipTarget.shipping_province].filter(Boolean).join(' ') }}</p>
        </div>
        <div><label class="form-label">ขนส่ง</label>
          <InputText v-model="ship.carrier" class="w-full" placeholder="ไปรษณีย์ไทย / Flash / Kerry / J&T" /></div>
        <div><label class="form-label">เลขพัสดุ *</label>
          <InputText v-model="ship.tracking_no" class="w-full" placeholder="TH123456789" /></div>
        <div><label class="form-label">หมายเหตุ (ไม่บังคับ)</label>
          <Textarea v-model="ship.note" rows="2" class="w-full" /></div>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="shipOpen = false" />
        <Button label="บันทึกการจัดส่ง" icon="fi fi-rr-check" :loading="busy" @click="doShip" />
      </template>
    </Dialog>

    <!-- Detail dialog -->
    <Dialog v-model:visible="detailOpen" modal :header="detail?.order_no"
      :style="{ width: '95vw', maxWidth: '34rem' }" :breakpoints="{ '640px': '100vw' }">
      <div v-if="detail" class="space-y-3 text-sm">
        <div class="flex items-center justify-between">
          <OrderStatusChip :status="detail.status" />
          <span class="font-bold text-fuchsia-700 text-base">฿{{ fmt(detail.total) }}</span>
        </div>
        <div class="box-card p-3 bg-slate-50">
          <p class="text-xs uppercase tracking-wide text-slate-400 mb-1.5 flex items-center gap-1"><i class="fi fi-rr-marker text-violet-400"></i> ที่อยู่จัดส่ง</p>
          <p class="font-medium text-slate-700">{{ detail.shipping_name }} · {{ detail.shipping_phone }}</p>
          <p class="text-slate-500 text-xs mt-0.5 leading-relaxed">{{ [detail.shipping_address, detail.shipping_sub_district, detail.shipping_district, detail.shipping_province, detail.shipping_zipcode].filter(Boolean).join(' ') }}</p>
          <p v-if="detail.shipping_note" class="text-xs text-amber-600 mt-1">หมายเหตุ: {{ detail.shipping_note }}</p>
        </div>
        <div class="box-card p-3 bg-slate-50">
          <p class="text-xs uppercase tracking-wide text-slate-400 mb-2 flex items-center gap-1"><i class="fi fi-rr-box-open text-violet-400"></i> รายการสินค้า</p>
          <div v-for="it in detail.items" :key="it.id" class="flex justify-between py-1.5 border-b border-slate-100 last:border-0">
            <span class="text-slate-600 truncate pr-2">{{ it.product_name }} <span class="text-slate-400 text-xs">×{{ it.qty }}</span></span>
            <span class="font-medium shrink-0">฿{{ fmt(it.line_total) }}</span>
          </div>
        </div>
        <div v-if="detail.shipment" class="rounded-xl p-3 bg-violet-50 border border-violet-100">
          <p class="text-xs uppercase tracking-wide text-violet-400 mb-2 flex items-center gap-1"><i class="fi fi-rr-truck-side text-violet-500"></i> ข้อมูลพัสดุ</p>
          <div class="grid grid-cols-2 gap-y-1 text-sm">
            <span class="text-slate-400">ขนส่ง:</span><span class="text-slate-700">{{ detail.shipment.carrier || '—' }}</span>
            <span class="text-slate-400">เลขพัสดุ:</span><span class="font-mono text-violet-700 font-semibold">{{ detail.shipment.tracking_no || '—' }}</span>
            <span class="text-slate-400">วันที่ส่ง:</span><span class="text-slate-700">{{ fmtDate(detail.shipment.shipped_at) }}</span>
          </div>
        </div>
        <div v-else class="text-center text-slate-400 py-4 bg-slate-50 rounded-xl text-sm">
          <i class="fi fi-rr-truck-side text-2xl mb-1 block"></i> ยังไม่ได้บันทึกข้อมูลจัดส่ง
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
const tab = ref('to_ship')
const tabs = [
  { key: 'to_ship',   label: 'รอจัดส่ง' },
  { key: 'shipped',   label: 'กำลังจัดส่ง' },
  { key: 'completed', label: 'ส่งสำเร็จ' },
]

const shipOpen = ref(false)
const shipTarget = ref(null)
const ship = reactive({ id: null, carrier: '', tracking_no: '', note: '' })
const busy = ref(false)
const detailOpen = ref(false)
const detail = ref(null)

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) : '' }

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/market/orders', { params: { page: page.value, status_group: tab.value } })
    rows.value = data.data || []
    meta.value = data
  } finally { loading.value = false }
}
function setTab(k) { tab.value = k; page.value = 1; load() }
function goPage(p) { page.value = p; load() }

function openShip(o) {
  shipTarget.value = o
  Object.assign(ship, { id: o.id, carrier: o.shipment?.carrier || '', tracking_no: o.shipment?.tracking_no || '', note: o.shipment?.note || '' })
  shipOpen.value = true
}

async function doShip() {
  if (!ship.tracking_no.trim()) { toast.add({ severity: 'warn', summary: 'กรุณากรอกเลขพัสดุ', life: 2500 }); return }
  busy.value = true
  try {
    await api.post(`/market/orders/${ship.id}/ship`, { carrier: ship.carrier, tracking_no: ship.tracking_no, note: ship.note })
    toast.add({ severity: 'success', summary: 'บันทึกการจัดส่งแล้ว', life: 2000 })
    shipOpen.value = false; load()
  } catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
  finally { busy.value = false }
}

async function openDetail(o) {
  try { const { data } = await api.get(`/market/orders/${o.id}`); detail.value = data; detailOpen.value = true } catch { /* ignore */ }
}

onMounted(load)
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
