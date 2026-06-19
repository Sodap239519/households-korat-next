<template>
  <div class="p-3 sm:p-6 space-y-4">
    <div>
      <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2"><i class="fi fi-rr-star text-violet-600"></i> จัดการรีวิว</h2>
      <p class="text-sm text-slate-500 mt-0.5">ตอบกลับ / ซ่อนรีวิวสินค้า</p>
    </div>

    <div class="flex gap-2">
      <select v-model="filterStatus" class="h-9 px-3 rounded-lg border border-slate-200 text-sm" @change="load">
        <option value="">ทุกสถานะ</option>
        <option value="published">เผยแพร่</option>
        <option value="hidden">ซ่อน</option>
      </select>
    </div>

    <div class="box-card overflow-hidden">
      <DataTable :value="rows" :loading="loading" stripedRows>
        <template #empty><div class="py-10 text-center text-slate-400">ไม่มีรีวิว</div></template>
        <Column header="สินค้า">
          <template #body="{ data }">
            <p class="text-sm font-medium text-slate-700 line-clamp-1">{{ data.product?.name }}</p>
          </template>
        </Column>
        <Column header="ผู้รีวิว"><template #body="{ data }"><span class="text-sm">{{ data.user?.name }}</span></template></Column>
        <Column header="คะแนน">
          <template #body="{ data }">
            <div class="flex items-center gap-1">
              <span class="font-bold text-amber-500">{{ data.rating }}</span>
              <i class="fi fi-sr-star text-amber-400 text-xs"></i>
            </div>
          </template>
        </Column>
        <Column header="ความคิดเห็น">
          <template #body="{ data }">
            <p v-if="data.title" class="text-xs font-semibold text-slate-700">{{ data.title }}</p>
            <p class="text-xs text-slate-500 line-clamp-2">{{ data.comment }}</p>
          </template>
        </Column>
        <Column header="สถานะ">
          <template #body="{ data }">
            <span class="px-2 py-0.5 rounded-full text-xs" :class="data.status==='published'?'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-500'">
              {{ data.status === 'published' ? 'เผยแพร่' : 'ซ่อน' }}
            </span>
          </template>
        </Column>
        <Column header="" style="width:120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="fi fi-rr-comment" text rounded size="small" v-tooltip.top="'ตอบกลับ'" @click="openReply(data)" />
              <Button v-if="data.status==='published'" icon="fi fi-rr-eye-crossed" text rounded size="small" severity="secondary" v-tooltip.top="'ซ่อน'" @click="toggleStatus(data,'hidden')" />
              <Button v-else icon="fi fi-rr-eye" text rounded size="small" severity="success" v-tooltip.top="'แสดง'" @click="toggleStatus(data,'published')" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <Dialog v-model:visible="replyOpen" modal header="ตอบกลับรีวิว" :style="{ width: '26rem' }">
      <div v-if="target" class="space-y-2 text-sm mb-3">
        <p class="text-slate-600 bg-slate-50 p-3 rounded-lg">{{ target.comment || '(ไม่มีข้อความ)' }}</p>
      </div>
      <Textarea v-model="replyText" rows="3" class="w-full" placeholder="พิมพ์คำตอบ..." />
      <template #footer>
        <Button label="ยกเลิก" text @click="replyOpen=false" />
        <Button label="ส่งคำตอบ" :loading="busy" @click="submitReply" />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import Pagination from '../components/Pagination.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip
const toast = useToast()
const rows = ref([])
const meta = ref({})
const loading = ref(false)
const page = ref(1)
const filterStatus = ref('')
const replyOpen = ref(false)
const target = ref(null)
const replyText = ref('')
const busy = ref(false)

async function load() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (filterStatus.value) params.status = filterStatus.value
    const { data } = await api.get('/market/reviews', { params })
    rows.value = data.data || []
    meta.value = data
  } finally { loading.value = false }
}
function goPage(p) { page.value = p; load() }

async function toggleStatus(r, status) {
  try { await api.post(`/market/reviews/${r.id}/status`, { status }); load() }
  catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
}

function openReply(r) { target.value = r; replyText.value = r.reply || ''; replyOpen.value = true }
async function submitReply() {
  busy.value = true
  try { await api.post(`/market/reviews/${target.value.id}/reply`, { reply: replyText.value }); toast.add({ severity: 'success', summary: 'ตอบกลับแล้ว', life: 2000 }); replyOpen.value = false; load() }
  catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
  finally { busy.value = false }
}
onMounted(load)
</script>
