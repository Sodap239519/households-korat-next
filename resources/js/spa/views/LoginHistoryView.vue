<template>
  <div class="p-6 space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-time-past text-violet-600"></i>
          ประวัติการเข้าใช้งาน
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">บันทึกทุกครั้งที่ลงชื่อเข้าระบบ</p>
      </div>
      <Button label="รีเฟรช" icon="fi fi-rr-refresh" severity="secondary" outlined @click="fetchData" />
    </div>

    <div class="box-card p-0 overflow-hidden">
      <DataTable
        :value="items"
        :loading="loading"
        stripedRows
        scrollable
        scrollHeight="65vh"
      >
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="fi fi-rr-time-past text-3xl"></i>
            <p class="mt-2">ไม่มีประวัติ</p>
          </div>
        </template>

        <Column header="#" :style="{ width: '60px' }">
          <template #body="{ index }">{{ (meta.current_page - 1) * 20 + index + 1 }}</template>
        </Column>
        <Column header="เข้าระบบเมื่อ">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <i class="fi fi-rr-sign-in-alt text-emerald-600"></i>
              <span class="font-medium text-slate-700">{{ fmtDate(data.logged_in_at) }}</span>
            </div>
          </template>
        </Column>
        <Column header="ออกจากระบบ">
          <template #body="{ data }">
            <span v-if="data.logged_out_at" class="flex items-center gap-2 text-slate-600">
              <i class="fi fi-rr-sign-out-alt text-rose-500"></i>
              {{ fmtDate(data.logged_out_at) }}
            </span>
            <StatusBadge v-else status="active" label="ยังออนไลน์" />
          </template>
        </Column>
        <Column header="ระยะเวลา">
          <template #body="{ data }">
            <span class="text-slate-600 text-xs">{{ duration(data) }}</span>
          </template>
        </Column>
        <Column field="ip_address" header="IP Address">
          <template #body="{ data }">
            <span class="font-mono text-xs text-violet-700">{{ data.ip_address || '-' }}</span>
          </template>
        </Column>
        <Column header="อุปกรณ์">
          <template #body="{ data }">
            <span class="text-xs text-slate-500" :title="data.user_agent">{{ shortUA(data.user_agent) }}</span>
          </template>
        </Column>
      </DataTable>

      <Pagination :meta="meta" @change="onPage" class="p-4" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/index.js'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'

import Pagination from './components/Pagination.vue'
import StatusBadge from '../components/StatusBadge.vue'

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
let currentPage = 1

function fmtDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleString('th-TH', {
    year: 'numeric', month: 'short', day: '2-digit',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  })
}

function duration(item) {
  if (!item.logged_out_at) return '-'
  const ms = new Date(item.logged_out_at) - new Date(item.logged_in_at)
  const min = Math.round(ms / 60000)
  if (min < 60) return `${min} นาที`
  const h = Math.floor(min / 60), m = min % 60
  return `${h} ชม. ${m} นาที`
}

function shortUA(ua) {
  if (!ua) return '-'
  if (/Edg\//.test(ua))     return 'Edge'
  if (/Chrome/.test(ua))    return 'Chrome'
  if (/Firefox/.test(ua))   return 'Firefox'
  if (/Safari/.test(ua))    return 'Safari'
  return ua.substring(0, 30) + '...'
}

async function fetchData() {
  loading.value = true
  try {
    const { data } = await api.get('/login-history', { params: { page: currentPage, per_page: 20 } })
    items.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally {
    loading.value = false
  }
}

function onPage(p) { currentPage = p; fetchData() }

onMounted(fetchData)
</script>
