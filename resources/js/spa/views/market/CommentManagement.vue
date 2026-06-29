<template>
  <div class="p-3 sm:p-5 space-y-4">
    <div>
      <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-comment text-violet-600"></i> จัดการคอมเมนต์
      </h2>
      <p class="text-xs text-slate-400 mt-0.5">ตอบกลับและซ่อนคอมเมนต์สินค้า</p>
    </div>

    <!-- Card list -->
    <div class="space-y-2.5">
      <template v-if="loading">
        <div v-for="n in 4" :key="n" class="box-card p-4 skeleton h-24"></div>
      </template>
      <div v-else-if="!rows.length" class="box-card py-14 text-center text-slate-400">
        <i class="fi fi-rr-comment text-4xl"></i>
        <p class="mt-2 text-sm">ไม่มีคอมเมนต์</p>
      </div>
      <div v-else v-for="row in rows" :key="row.id" class="box-card p-4 space-y-3">
        <!-- User + product + status -->
        <div class="flex items-start gap-3">
          <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-bold shrink-0">
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
        <!-- Comment body -->
        <div class="rounded-xl px-3 py-2.5 bg-slate-50 border border-slate-100">
          <p class="text-sm text-slate-700 line-clamp-3">{{ row.body }}</p>
        </div>
        <!-- Reply count -->
        <div v-if="(row.replies?.length || 0) > 0" class="flex items-center gap-1.5 text-xs text-violet-600">
          <i class="fi fi-rr-comment-dots"></i>
          <span>{{ row.replies.length }} ตอบกลับแล้ว</span>
        </div>
        <!-- Actions -->
        <div class="flex gap-2 pt-1 border-t border-slate-100">
          <button class="flex-1 h-9 rounded-xl bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-medium transition flex items-center justify-center gap-1.5"
            @click="open(row)">
            <i class="fi fi-rr-comment text-[11px]"></i> ตอบกลับ
          </button>
          <button v-if="row.status === 'published'"
            class="h-9 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium transition flex items-center gap-1.5"
            @click="toggle(row, 'hidden')">
            <i class="fi fi-rr-eye-crossed text-[11px]"></i> ซ่อน
          </button>
          <button v-else
            class="h-9 px-3 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-xs font-medium transition flex items-center gap-1.5"
            @click="toggle(row, 'published')">
            <i class="fi fi-rr-eye text-[11px]"></i> แสดง
          </button>
        </div>
      </div>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <!-- Reply dialog -->
    <Dialog v-model:visible="dlgOpen" modal header="ตอบกลับคอมเมนต์" :style="{ width: '95vw', maxWidth: '28rem' }">
      <div v-if="target" class="space-y-3">
        <div class="rounded-xl p-3 bg-slate-50 border border-slate-100">
          <p class="text-xs font-semibold text-slate-500 mb-1">{{ target.user?.name }}:</p>
          <p class="text-sm text-slate-700">{{ target.body }}</p>
        </div>
        <div v-if="target.replies?.length" class="space-y-2">
          <p class="text-xs text-slate-400 font-medium">ตอบกลับก่อนหน้า:</p>
          <div v-for="r in target.replies" :key="r.id"
            class="pl-3 border-l-2 border-violet-200 text-xs text-slate-600">
            <span class="font-semibold text-violet-700">{{ r.user?.name }}:</span> {{ r.body }}
          </div>
        </div>
        <Textarea v-model="replyText" rows="4" class="w-full" placeholder="พิมพ์คำตอบ..." />
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="dlgOpen=false" />
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
  try {
    await api.post(`/market/comments/${target.value.id}/reply`, { body: replyText.value })
    toast.add({ severity: 'success', summary: 'ตอบกลับแล้ว', life: 2000 })
    dlgOpen.value = false; load()
  } catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
  finally { busy.value = false }
}

onMounted(load)
</script>
