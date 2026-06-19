<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div>
      <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-shopping-bag text-violet-600"></i> คำสั่งซื้อ
      </h2>
      <p class="text-sm text-slate-500 mt-0.5">จัดการคำสั่งซื้อของกลุ่ม — ยืนยัน/เตรียมสินค้า/บันทึกการจัดส่ง</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 overflow-x-auto pb-1">
      <button v-for="t in tabs" :key="t.key" class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition" :class="tab === t.key ? 'bg-violet-600 text-white' : 'box-card text-slate-600'" @click="setTab(t.key)">{{ t.label }}</button>
    </div>

    <div class="box-card overflow-hidden">
      <DataTable :value="rows" :loading="loading" stripedRows>
        <template #empty><div class="text-center py-10 text-slate-400"><i class="fi fi-rr-shopping-bag text-3xl"></i><p class="mt-2">ไม่มีคำสั่งซื้อ</p></div></template>
        <Column header="เลขที่">
          <template #body="{ data }">
            <p class="font-medium text-slate-700">{{ data.order_no }}</p>
            <p class="text-xs text-slate-400">{{ fmtDate(data.created_at) }}</p>
          </template>
        </Column>
        <Column header="ลูกค้า"><template #body="{ data }">{{ data.user?.name || data.shipping_name }}</template></Column>
        <Column header="รายการ"><template #body="{ data }">{{ data.items_count }}</template></Column>
        <Column header="ยอด"><template #body="{ data }"><span class="font-semibold text-fuchsia-700">฿{{ fmt(data.total) }}</span></template></Column>
        <Column header="สถานะ"><template #body="{ data }"><OrderStatusChip :status="data.status" /></template></Column>
        <Column header="" style="width:170px">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <Button icon="fi fi-rr-eye" text rounded size="small" @click="openDetail(data)" v-tooltip.top="'รายละเอียด'" />
              <Button v-if="data.status==='confirmed'" label="เตรียม" size="small" outlined @click="setStatus(data,'processing')" />
              <Button v-if="['confirmed','processing'].includes(data.status)" label="จัดส่ง" icon="fi fi-rr-truck-side" size="small" @click="openShip(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <!-- Ship dialog -->
    <Dialog v-model:visible="shipOpen" modal header="บันทึกการจัดส่ง" :style="{ width: '26rem' }">
      <div class="space-y-3">
        <div><label class="form-label">ขนส่ง</label><InputText v-model="ship.carrier" class="w-full" placeholder="ไปรษณีย์ไทย / Flash / Kerry" /></div>
        <div><label class="form-label">เลขพัสดุ</label><InputText v-model="ship.tracking_no" class="w-full" /></div>
        <div><label class="form-label">หมายเหตุ</label><Textarea v-model="ship.note" rows="2" class="w-full" /></div>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="shipOpen=false" />
        <Button label="ยืนยันจัดส่ง" icon="fi fi-rr-check" :loading="busy" @click="doShip" />
      </template>
    </Dialog>

    <!-- Detail dialog -->
    <Dialog v-model:visible="detailOpen" modal :header="detail?.order_no" :style="{ width: '34rem' }" :breakpoints="{ '960px': '95vw' }">
      <div v-if="detail" class="space-y-3 text-sm">
        <div class="flex items-center justify-between"><OrderStatusChip :status="detail.status" /><span class="font-bold text-fuchsia-700">฿{{ fmt(detail.total) }}</span></div>
        <div class="box-card p-3">
          <p class="font-medium text-slate-700 mb-1">รายการสินค้า</p>
          <div v-for="it in detail.items" :key="it.id" class="flex justify-between text-slate-600"><span>{{ it.product_name }} ×{{ it.qty }}</span><span>฿{{ fmt(it.line_total) }}</span></div>
        </div>
        <div class="box-card p-3">
          <p class="font-medium text-slate-700 mb-1">ผู้รับ</p>
          <p class="text-slate-600">{{ detail.shipping_name }} · {{ detail.shipping_phone }}</p>
          <p class="text-slate-500">{{ [detail.shipping_address, detail.shipping_sub_district, detail.shipping_district, detail.shipping_province, detail.shipping_zipcode].filter(Boolean).join(' ') }}</p>
        </div>
        <div v-if="detail.payments?.length" class="box-card p-3">
          <p class="font-medium text-slate-700 mb-1">สลิป</p>
          <img v-if="detail.payments[0].slip_url" :src="detail.payments[0].slip_url" class="max-h-40 rounded border" />
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
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip
const toast = useToast()

const rows = ref([])
const meta = ref({})
const loading = ref(false)
const page = ref(1)
const tab = ref('all')
const tabs = [
  { key: 'all', label: 'ทั้งหมด' },
  { key: 'to_pay', label: 'รอชำระ/ยืนยัน' },
  { key: 'to_ship', label: 'ที่ต้องจัดส่ง' },
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
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('th-TH') : '' }

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
