<template>
  <div class="p-3 sm:p-5 space-y-4">
    <div>
      <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-undo text-violet-600"></i> คืน / เคลมสินค้า
      </h2>
      <p class="text-xs text-slate-400 mt-0.5">ตรวจสอบและดำเนินการคำขอคืน/คืนเงิน/เคลม</p>
    </div>

    <!-- Card list -->
    <div class="space-y-2.5">
      <template v-if="loading">
        <div v-for="n in 3" :key="n" class="box-card p-4 skeleton h-32"></div>
      </template>
      <div v-else-if="!rows.length" class="box-card py-14 text-center text-slate-400">
        <i class="fi fi-rr-undo text-4xl"></i>
        <p class="mt-2 text-sm">ไม่มีคำขอ</p>
      </div>
      <div v-else v-for="row in rows" :key="row.id" class="box-card p-4 space-y-3">
        <!-- Header row -->
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="font-semibold text-slate-800 text-sm">{{ row.order?.order_no }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ row.user?.name }}</p>
          </div>
          <div class="flex flex-col items-end gap-1">
            <span class="px-2 py-0.5 rounded-full text-[11px] border font-medium"
              :class="typeCls(row.type)">{{ typeLabel(row.type) }}</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] border" :class="stCls(row.status)">{{ stLabel(row.status) }}</span>
          </div>
        </div>
        <!-- Reason -->
        <div class="rounded-xl px-3 py-2.5 bg-amber-50 border border-amber-100">
          <p class="text-xs text-amber-600 font-medium mb-0.5">เหตุผล</p>
          <p class="text-sm text-slate-700">{{ row.reason }}</p>
          <p v-if="row.description" class="text-xs text-slate-500 mt-1">{{ row.description }}</p>
        </div>
        <!-- Images -->
        <div v-if="(row.image_urls||[]).length" class="flex gap-2 flex-wrap">
          <button v-for="(u,i) in row.image_urls" :key="i" @click="preview=u"
            class="w-16 h-16 rounded-xl overflow-hidden border-2 border-slate-200 hover:border-violet-300 transition">
            <img :src="u" class="w-full h-full object-cover" />
          </button>
        </div>
        <!-- Action button -->
        <button v-if="row.status === 'requested'"
          class="w-full h-10 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition flex items-center justify-center gap-2 shadow-sm"
          @click="open(row)">
          <i class="fi fi-rr-settings"></i> จัดการคำขอ
        </button>
      </div>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <!-- Image preview -->
    <Dialog :visible="!!preview" modal header="รูปประกอบ"
      @update:visible="v => { if (!v) preview = null }"
      :style="{ width: '95vw', maxWidth: '28rem' }">
      <img v-if="preview" :src="preview" class="w-full rounded-xl" />
    </Dialog>

    <!-- Manage dialog -->
    <Dialog v-model:visible="dlgOpen" modal header="ดำเนินการคำขอ" :style="{ width: '95vw', maxWidth: '26rem' }">
      <div v-if="target" class="space-y-3">
        <div class="rounded-xl px-3 py-2.5 bg-slate-50 border border-slate-200 text-sm">
          <p class="font-medium text-slate-700">{{ typeLabel(target.type) }} · {{ target.order?.order_no }}</p>
          <p class="text-slate-500 mt-0.5"><span class="text-slate-400">เหตุผล:</span> {{ target.reason }}</p>
          <p v-if="target.description" class="text-slate-500 mt-0.5 text-xs">{{ target.description }}</p>
        </div>
        <div>
          <label class="form-label">การตัดสินใจ</label>
          <select v-model="form.action" class="inp">
            <option value="approve">อนุมัติคำขอ</option>
            <option value="refunded">คืนเงินแล้ว</option>
            <option value="reject">ปฏิเสธ</option>
          </select>
        </div>
        <div v-if="form.action === 'refunded'">
          <label class="form-label">จำนวนเงินคืน (บาท)</label>
          <input v-model.number="form.refund_amount" type="number" class="inp" />
        </div>
        <div>
          <label class="form-label">ข้อความถึงลูกค้า</label>
          <textarea v-model="form.admin_response" rows="3" class="inp"></textarea>
        </div>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="dlgOpen=false" />
        <Button label="บันทึก" icon="fi fi-rr-check" :loading="busy" @click="submit" />
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

function typeLabel(t) { return { return: 'คืนสินค้า', refund: 'คืนเงิน', claim: 'เคลม' }[t] || t }
function typeCls(t) { return { return: 'bg-blue-50 text-blue-700 border-blue-200', refund: 'bg-amber-50 text-amber-700 border-amber-200', claim: 'bg-rose-50 text-rose-700 border-rose-200' }[t] || 'bg-slate-100 text-slate-500 border-slate-200' }
function stLabel(s) { return { requested: 'รอดำเนินการ', approved: 'อนุมัติ', rejected: 'ปฏิเสธ', refunded: 'คืนเงินแล้ว', completed: 'เสร็จสิ้น' }[s] || s }
function stCls(s) { return { requested: 'bg-amber-50 text-amber-700 border-amber-300', approved: 'bg-violet-50 text-violet-700 border-violet-300', refunded: 'bg-emerald-50 text-emerald-700 border-emerald-300', rejected: 'bg-slate-100 text-slate-500 border-slate-300' }[s] || 'bg-slate-100 text-slate-500 border-slate-300' }

async function load() {
  loading.value = true
  try { const { data } = await api.get('/market/returns', { params: { page: page.value } }); rows.value = data.data || []; meta.value = data }
  finally { loading.value = false }
}
function goPage(p) { page.value = p; load() }

function open(r) {
  target.value = r
  Object.assign(form, { action: 'approve', refund_amount: Number(r.order?.total) || null, admin_response: '' })
  dlgOpen.value = true
}

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
.inp { width: 100%; height: 2.5rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); font-size: 0.875rem; }
.inp:focus { outline: none; border-color: rgb(167 139 250); box-shadow: 0 0 0 3px rgb(167 139 250 / 0.15); }
textarea.inp { height: auto; padding: 0.6rem 0.75rem; }
</style>
