<template>
  <div class="p-3 sm:p-5 space-y-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-star text-violet-600"></i> จัดการรีวิว
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">ตอบกลับและซ่อนรีวิวสินค้า</p>
      </div>
      <select v-model="filterStatus" class="h-9 px-3 rounded-xl border border-slate-200 text-xs bg-white text-slate-600" @change="load">
        <option value="">ทุกสถานะ</option>
        <option value="published">เผยแพร่</option>
        <option value="hidden">ซ่อน</option>
      </select>
    </div>

    <!-- Card list -->
    <div class="space-y-2.5">
      <template v-if="loading">
        <div v-for="n in 4" :key="n" class="box-card p-4 skeleton h-28"></div>
      </template>
      <div v-else-if="!rows.length" class="box-card py-14 text-center text-slate-400">
        <i class="fi fi-rr-star text-4xl"></i>
        <p class="mt-2 text-sm">ไม่มีรีวิว</p>
      </div>
      <div v-else v-for="row in rows" :key="row.id" class="box-card p-4 space-y-3">
        <!-- Header -->
        <div class="flex items-start gap-3">
          <div class="w-9 h-9 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center text-sm font-bold shrink-0">
            {{ (row.user?.name || '?')[0] }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <p class="font-semibold text-slate-700 text-sm truncate">{{ row.user?.name }}</p>
              <span class="shrink-0 px-2 py-0.5 rounded-full text-[11px]"
                :class="row.status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                {{ row.status === 'published' ? 'เผยแพร่' : 'ซ่อน' }}
              </span>
            </div>
            <p class="text-xs text-slate-400 truncate mt-0.5">{{ row.product?.name }}</p>
          </div>
        </div>
        <!-- Rating -->
        <div class="flex items-center gap-1">
          <span v-for="s in 5" :key="s" class="text-sm" :class="s <= row.rating ? 'text-amber-400' : 'text-slate-200'">
            <i class="fi fi-sr-star"></i>
          </span>
          <span class="font-bold text-amber-600 text-sm ml-1">{{ row.rating }}</span>
        </div>
        <!-- Comment -->
        <div class="rounded-xl px-3 py-2.5 bg-slate-50 border border-slate-100">
          <p v-if="row.title" class="font-medium text-slate-700 text-xs mb-1">{{ row.title }}</p>
          <p class="text-sm text-slate-600 line-clamp-3">{{ row.comment || '(ไม่มีข้อความ)' }}</p>
        </div>
        <!-- Existing reply -->
        <div v-if="row.reply" class="rounded-xl px-3 py-2.5 bg-violet-50 border border-violet-100">
          <p class="text-[11px] text-violet-500 font-medium mb-1">ตอบกลับแล้ว:</p>
          <p class="text-xs text-slate-600">{{ row.reply }}</p>
        </div>
        <!-- Actions -->
        <div class="flex gap-2 pt-1 border-t border-slate-100">
          <button class="flex-1 h-9 rounded-xl bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-medium transition flex items-center justify-center gap-1.5"
            @click="openReply(row)">
            <i class="fi fi-rr-comment text-[11px]"></i> {{ row.reply ? 'แก้ไขตอบกลับ' : 'ตอบกลับ' }}
          </button>
          <button v-if="row.status === 'published'"
            class="h-9 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium transition flex items-center gap-1.5"
            @click="toggleStatus(row, 'hidden')">
            <i class="fi fi-rr-eye-crossed text-[11px]"></i> ซ่อน
          </button>
          <button v-else
            class="h-9 px-3 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-xs font-medium transition flex items-center gap-1.5"
            @click="toggleStatus(row, 'published')">
            <i class="fi fi-rr-eye text-[11px]"></i> แสดง
          </button>
        </div>
      </div>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <!-- Reply dialog -->
    <Dialog v-model:visible="replyOpen" modal header="ตอบกลับรีวิว" :style="{ width: '95vw', maxWidth: '26rem' }">
      <div v-if="target" class="space-y-3">
        <div class="rounded-xl p-3 bg-slate-50 border border-slate-100">
          <div class="flex items-center gap-1 mb-1.5">
            <span v-for="s in 5" :key="s" class="text-xs" :class="s <= target.rating ? 'text-amber-400' : 'text-slate-200'"><i class="fi fi-sr-star"></i></span>
          </div>
          <p class="text-sm text-slate-600">{{ target.comment || '(ไม่มีข้อความ)' }}</p>
        </div>
        <Textarea v-model="replyText" rows="4" class="w-full" placeholder="พิมพ์คำตอบ..." />
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="replyOpen=false" />
        <Button label="ส่งคำตอบ" icon="fi fi-rr-paper-plane" :loading="busy" @click="submitReply" />
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
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'

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
  try {
    await api.post(`/market/reviews/${target.value.id}/reply`, { reply: replyText.value })
    toast.add({ severity: 'success', summary: 'ตอบกลับแล้ว', life: 2000 })
    replyOpen.value = false; load()
  } catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
  finally { busy.value = false }
}

onMounted(load)
</script>
