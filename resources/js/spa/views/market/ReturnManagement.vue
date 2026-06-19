<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div>
      <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-undo text-violet-600"></i> คืน/เคลมสินค้า
      </h2>
      <p class="text-sm text-slate-500 mt-0.5">ตรวจสอบและดำเนินการคำขอคืนสินค้า/คืนเงิน/เคลม</p>
    </div>

    <div class="box-card overflow-hidden">
      <DataTable :value="rows" :loading="loading" stripedRows>
        <template #empty><div class="text-center py-10 text-slate-400"><i class="fi fi-rr-undo text-3xl"></i><p class="mt-2">ไม่มีคำขอ</p></div></template>
        <Column header="ออเดอร์"><template #body="{ data }">{{ data.order?.order_no }}</template></Column>
        <Column header="ลูกค้า"><template #body="{ data }">{{ data.user?.name }}</template></Column>
        <Column header="ประเภท"><template #body="{ data }">{{ typeLabel(data.type) }}</template></Column>
        <Column header="เหตุผล"><template #body="{ data }"><span class="text-sm text-slate-600">{{ data.reason }}</span></template></Column>
        <Column header="รูป">
          <template #body="{ data }">
            <div class="flex gap-1">
              <img v-for="(u,i) in (data.image_urls||[])" :key="i" :src="u" class="w-9 h-9 rounded object-cover cursor-pointer" @click="preview=u" />
            </div>
          </template>
        </Column>
        <Column header="สถานะ"><template #body="{ data }"><span class="px-2 py-0.5 rounded-full text-xs border" :class="stCls(data.status)">{{ stLabel(data.status) }}</span></template></Column>
        <Column header="" style="width:90px">
          <template #body="{ data }">
            <Button v-if="data.status==='requested'" label="จัดการ" size="small" @click="open(data)" />
          </template>
        </Column>
      </DataTable>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <Dialog :visible="!!preview" modal header="รูปประกอบ" @update:visible="v => { if (!v) preview = null }" :style="{ width:'auto', maxWidth:'90vw' }">
      <img v-if="preview" :src="preview" class="max-h-[70vh] rounded" />
    </Dialog>

    <Dialog v-model:visible="dlgOpen" modal header="ดำเนินการคำขอ" :style="{ width: '26rem' }">
      <div v-if="target" class="space-y-3">
        <p class="text-sm text-slate-600">{{ typeLabel(target.type) }} · ออเดอร์ {{ target.order?.order_no }}</p>
        <p class="text-sm"><span class="text-slate-400">เหตุผล:</span> {{ target.reason }}</p>
        <p v-if="target.description" class="text-sm"><span class="text-slate-400">รายละเอียด:</span> {{ target.description }}</p>
        <div>
          <label class="form-label">การตัดสินใจ</label>
          <select v-model="form.action" class="inp">
            <option value="approve">อนุมัติคำขอ</option>
            <option value="refunded">คืนเงินแล้ว</option>
            <option value="reject">ปฏิเสธ</option>
          </select>
        </div>
        <div v-if="form.action==='refunded'">
          <label class="form-label">จำนวนเงินคืน (บาท)</label>
          <input v-model.number="form.refund_amount" type="number" class="inp" />
        </div>
        <div>
          <label class="form-label">ข้อความถึงลูกค้า</label>
          <textarea v-model="form.admin_response" rows="2" class="inp"></textarea>
        </div>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="dlgOpen=false" />
        <Button label="บันทึก" :loading="busy" @click="submit" />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import Pagination from '../components/Pagination.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'

const toast = useToast()
const rows = ref([])
const meta = ref({})
const loading = ref(false)
const page = ref(1)
const preview = ref(null)
const dlgOpen = ref(false)
const target = ref(null)
const busy = ref(false)
const form = reactive({ action: 'approve', refund_amount: null, admin_response: '' })

function typeLabel(t) { return { return: 'ขอคืนสินค้า', refund: 'ขอคืนเงิน', claim: 'เคลม' }[t] || t }
function stLabel(s) { return { requested: 'รอดำเนินการ', approved: 'อนุมัติ', rejected: 'ปฏิเสธ', refunded: 'คืนเงินแล้ว', completed: 'เสร็จสิ้น' }[s] || s }
function stCls(s) { return { requested: 'bg-amber-50 text-amber-700 border-amber-300', approved: 'bg-violet-50 text-violet-700 border-violet-300', refunded: 'bg-emerald-50 text-emerald-700 border-emerald-300', rejected: 'bg-slate-100 text-slate-500 border-slate-300' }[s] || 'bg-slate-100 text-slate-500 border-slate-300' }

async function load() {
  loading.value = true
  try { const { data } = await api.get('/market/returns', { params: { page: page.value } }); rows.value = data.data || []; meta.value = data }
  finally { loading.value = false }
}
function goPage(p) { page.value = p; load() }

function open(r) { target.value = r; Object.assign(form, { action: 'approve', refund_amount: Number(r.order?.total) || null, admin_response: '' }); dlgOpen.value = true }

async function submit() {
  busy.value = true
  try {
    await api.post(`/market/returns/${target.value.id}/resolve`, form)
    toast.add({ severity: 'success', summary: 'ดำเนินการแล้ว', life: 2000 })
    dlgOpen.value = false; load()
  } catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
  finally { busy.value = false }
}

onMounted(load)
</script>

<style scoped>
.inp { width: 100%; height: 2.5rem; padding: 0 0.6rem; border-radius: 0.6rem; border: 1px solid rgb(226 232 240); }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
textarea.inp { height: auto; padding: 0.45rem 0.6rem; }
</style>
