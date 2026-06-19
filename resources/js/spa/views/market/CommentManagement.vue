<template>
  <div class="p-3 sm:p-6 space-y-4">
    <div>
      <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2"><i class="fi fi-rr-comment text-violet-600"></i> จัดการคอมเมนต์</h2>
      <p class="text-sm text-slate-500 mt-0.5">ตอบกลับ / ซ่อนคอมเมนต์สินค้า</p>
    </div>

    <div class="box-card overflow-hidden">
      <DataTable :value="rows" :loading="loading" stripedRows>
        <template #empty><div class="py-10 text-center text-slate-400">ไม่มีคอมเมนต์</div></template>
        <Column header="สินค้า"><template #body="{ data }"><span class="text-sm font-medium text-slate-700">{{ data.product?.name }}</span></template></Column>
        <Column header="ผู้ใช้"><template #body="{ data }"><span class="text-sm">{{ data.user?.name }}</span></template></Column>
        <Column header="ข้อความ"><template #body="{ data }"><p class="text-sm text-slate-600 line-clamp-2">{{ data.body }}</p></template></Column>
        <Column header="ตอบกลับ">
          <template #body="{ data }">
            <span class="text-xs text-slate-400">{{ data.replies?.length || 0 }} ตอบ</span>
          </template>
        </Column>
        <Column header="สถานะ">
          <template #body="{ data }">
            <span class="px-2 py-0.5 rounded-full text-xs" :class="data.status==='published'?'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-500'">
              {{ data.status === 'published' ? 'เผยแพร่' : 'ซ่อน' }}
            </span>
          </template>
        </Column>
        <Column header="" style="width:110px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="fi fi-rr-comment" text rounded size="small" v-tooltip.top="'ตอบกลับ'" @click="open(data)" />
              <Button v-if="data.status==='published'" icon="fi fi-rr-eye-crossed" text rounded size="small" severity="secondary" v-tooltip.top="'ซ่อน'" @click="toggle(data,'hidden')" />
              <Button v-else icon="fi fi-rr-eye" text rounded size="small" severity="success" v-tooltip.top="'แสดง'" @click="toggle(data,'published')" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <Dialog v-model:visible="dlgOpen" modal header="ตอบกลับคอมเมนต์" :style="{ width: '28rem' }">
      <div v-if="target" class="space-y-3">
        <div class="bg-slate-50 rounded-lg p-3 text-sm text-slate-600">
          <p class="font-medium text-slate-700 mb-1">{{ target.user?.name }}:</p>
          <p>{{ target.body }}</p>
        </div>
        <div v-if="target.replies?.length" class="space-y-2">
          <p class="text-xs text-slate-400 font-medium">ตอบกลับก่อนหน้า:</p>
          <div v-for="r in target.replies" :key="r.id" class="pl-3 border-l-2 border-violet-200 text-xs text-slate-600">
            <span class="font-semibold text-violet-700">{{ r.user?.name }}:</span> {{ r.body }}
          </div>
        </div>
        <Textarea v-model="replyText" rows="3" class="w-full" placeholder="พิมพ์คำตอบ..." />
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="dlgOpen=false" />
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
const dlgOpen = ref(false)
const target = ref(null)
const replyText = ref('')
const busy = ref(false)

async function load() {
  loading.value = true
  try { const { data } = await api.get('/market/comments', { params: { page: page.value } }); rows.value = data.data || []; meta.value = data }
  finally { loading.value = false }
}
function goPage(p) { page.value = p; load() }

async function toggle(c, status) {
  try { await api.post(`/market/comments/${c.id}/status`, { status }); load() }
  catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
}

function open(c) { target.value = c; replyText.value = ''; dlgOpen.value = true }
async function submitReply() {
  busy.value = true
  try { await api.post(`/market/comments/${target.value.id}/reply`, { body: replyText.value }); toast.add({ severity: 'success', summary: 'ตอบกลับแล้ว', life: 2000 }); dlgOpen.value = false; load() }
  catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
  finally { busy.value = false }
}
onMounted(load)
</script>
