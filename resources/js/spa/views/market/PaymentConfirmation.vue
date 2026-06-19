<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div>
      <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-receipt text-violet-600"></i> ยืนยันการชำระเงิน
      </h2>
      <p class="text-sm text-slate-500 mt-0.5">ตรวจสลิปที่ลูกค้าแจ้งโอน แล้วยืนยันหรือปฏิเสธ</p>
    </div>

    <div class="box-card overflow-hidden">
      <DataTable :value="rows" :loading="loading" stripedRows>
        <template #empty><div class="text-center py-10 text-slate-400"><i class="fi fi-rr-check-circle text-3xl"></i><p class="mt-2">ไม่มีสลิปที่รอยืนยัน</p></div></template>
        <Column header="สลิป" style="width:80px">
          <template #body="{ data }">
            <img v-if="data.slip_url" :src="data.slip_url" class="w-14 h-14 rounded-lg object-cover cursor-pointer hover:ring-2 ring-violet-300" @click="preview = data.slip_url" />
            <span v-else class="text-slate-300 text-xs">ไม่มีสลิป</span>
          </template>
        </Column>
        <Column header="คำสั่งซื้อ">
          <template #body="{ data }">
            <p class="font-medium text-slate-700">{{ data.order?.order_no }}</p>
            <p class="text-xs text-slate-400">{{ data.order?.user?.name || data.order?.shipping_name }}</p>
          </template>
        </Column>
        <Column header="ยอด">
          <template #body="{ data }">
            <p class="font-semibold text-slate-700">฿{{ fmt(data.amount) }}</p>
            <p class="text-xs" :class="Number(data.amount) === Number(data.order?.total) ? 'text-emerald-600' : 'text-rose-500'">
              ออเดอร์ ฿{{ fmt(data.order?.total) }}
            </p>
          </template>
        </Column>
        <Column header="ตรวจสลิปอัตโนมัติ">
          <template #body="{ data }">
            <span class="px-2 py-0.5 rounded-full text-xs border" :class="verifyCls(data.verify_status)">{{ verifyLabel(data.verify_status) }}</span>
            <p v-if="data.bank_ref" class="text-[11px] text-slate-400 mt-0.5">ref: {{ data.bank_ref }}</p>
          </template>
        </Column>
        <Column header="" style="width:160px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button label="ยืนยัน" icon="fi fi-rr-check" size="small" severity="success" @click="confirm(data)" />
              <Button label="ปฏิเสธ" icon="fi fi-rr-cross" size="small" severity="danger" outlined @click="openReject(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <!-- Slip preview -->
    <Dialog v-model:visible="previewOpen" modal header="สลิปโอนเงิน" :style="{ width: 'auto', maxWidth: '90vw' }">
      <img :src="preview" class="max-h-[75vh] rounded-lg" />
    </Dialog>

    <!-- Reject -->
    <Dialog v-model:visible="rejectOpen" modal header="ปฏิเสธการชำระเงิน" :style="{ width: '26rem' }">
      <label class="form-label">เหตุผล *</label>
      <Textarea v-model="rejectReason" rows="3" class="w-full" />
      <template #footer>
        <Button label="ยกเลิก" text @click="rejectOpen = false" />
        <Button label="ยืนยันปฏิเสธ" severity="danger" :loading="busy" @click="doReject" />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import Pagination from '../components/Pagination.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'

const toast = useToast()
const rows = ref([])
const meta = ref({})
const loading = ref(false)
const page = ref(1)
const preview = ref(null)
const previewOpen = computed({ get: () => !!preview.value, set: (v) => { if (!v) preview.value = null } })
const rejectOpen = ref(false)
const rejectReason = ref('')
const rejectTarget = ref(null)
const busy = ref(false)

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function verifyLabel(s) { return { passed: 'ผ่าน', failed: 'ไม่ผ่าน', skipped: 'ตรวจเอง', unchecked: 'ยังไม่ตรวจ' }[s] || s }
function verifyCls(s) { return {
  passed: 'bg-emerald-50 text-emerald-700 border-emerald-300',
  failed: 'bg-rose-50 text-rose-700 border-rose-300',
}[s] || 'bg-slate-100 text-slate-500 border-slate-300' }

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/market/payments', { params: { page: page.value } })
    rows.value = data.data || []
    meta.value = data
  } finally { loading.value = false }
}
function goPage(p) { page.value = p; load() }

async function confirm(p) {
  try {
    await api.post(`/market/payments/${p.id}/confirm`)
    toast.add({ severity: 'success', summary: 'ยืนยันแล้ว', life: 2000 })
    load()
  } catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
}

function openReject(p) { rejectTarget.value = p; rejectReason.value = ''; rejectOpen.value = true }
async function doReject() {
  if (!rejectReason.value.trim()) return
  busy.value = true
  try {
    await api.post(`/market/payments/${rejectTarget.value.id}/reject`, { reason: rejectReason.value })
    toast.add({ severity: 'success', summary: 'ปฏิเสธแล้ว', life: 2000 })
    rejectOpen.value = false
    load()
  } catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
  finally { busy.value = false }
}

onMounted(load)
</script>
