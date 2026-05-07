<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-list-check text-violet-600"></i>
          ติดตามผลผลิต
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">บันทึกผลผลิตและรายได้จากการเพาะเห็ดของแต่ละครัวเรือน</p>
      </div>
      <Button label="เพิ่มการติดตาม" icon="fi fi-rr-plus" @click="openCreate" />
    </div>

    <div class="box-card p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <Select v-model="filters.sale_channel" :options="channelOptions" optionLabel="label" optionValue="value"
                placeholder="ทุกช่องทาง" showClear class="w-full" @change="onFilterChange" />
      </div>
    </div>

    <div class="box-card p-0 overflow-hidden">
      <DataTable :value="items" :loading="loading" stripedRows scrollable scrollHeight="62vh">
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="fi fi-rr-info text-3xl"></i>
            <p class="mt-2">ยังไม่มีการติดตามผล</p>
          </div>
        </template>
        <template #footer>
          <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-2">
            <span class="text-slate-600">รวม <span class="font-semibold text-violet-700">{{ Number(meta.total || 0).toLocaleString() }}</span> รายการ</span>
            <div class="flex gap-4 text-xs">
              <span>ผลผลิตในหน้านี้: <span class="font-semibold text-emerald-700">{{ totalHarvestPage.toFixed(2) }}</span> กก.</span>
              <span>ขาย: <span class="font-semibold text-orange-700">{{ totalSoldPage.toFixed(2) }}</span> กก.</span>
              <span>รายได้: <span class="font-semibold text-amber-700">{{ totalRevenuePage.toLocaleString() }}</span> บาท</span>
            </div>
          </div>
        </template>

        <Column header="#" :style="{ width: '60px' }">
          <template #body="{ index }">
            <span class="text-xs text-slate-400 font-medium">{{ ((meta.current_page || 1) - 1) * 20 + index + 1 }}</span>
          </template>
        </Column>
        <Column header="ครัวเรือน" :style="{ minWidth: '200px' }">
          <template #body="{ data }">
            <div class="font-medium text-slate-700">
              {{ data.allocation?.household?.first_name }} {{ data.allocation?.household?.last_name }}
            </div>
            <div class="font-mono text-xs text-violet-600">{{ data.allocation?.household?.household_code }}</div>
          </template>
        </Column>
        <Column field="followup_round" header="รอบ" :style="{ width: '70px' }">
          <template #body="{ data }">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-violet-100 text-violet-700 text-xs font-semibold">
              {{ data.followup_round }}
            </span>
          </template>
        </Column>
        <Column field="followup_date" header="วันที่ติดตาม" sortable :style="{ minWidth: '160px' }">
          <template #body="{ data }">{{ fmtThaiDate(data.followup_date) }}</template>
        </Column>
        <Column field="harvest_kg" header="ผลผลิต (กก.)" sortable :style="{ width: '120px' }">
          <template #body="{ data }">{{ Number(data.harvest_kg || 0).toFixed(2) }}</template>
        </Column>
        <Column field="sold_kg" header="ขาย (กก.)" sortable :style="{ width: '110px' }">
          <template #body="{ data }">{{ Number(data.sold_kg || 0).toFixed(2) }}</template>
        </Column>
        <Column field="revenue" header="รายได้ (บาท)" sortable :style="{ width: '130px' }">
          <template #body="{ data }">
            <span class="font-semibold text-emerald-700">{{ Number(data.revenue || 0).toLocaleString() }}</span>
          </template>
        </Column>
        <Column header="ช่องทาง" :style="{ width: '120px' }">
          <template #body="{ data }">
            <StatusBadge v-if="data.sale_channel" :status="data.sale_channel" :label="channelLabels[data.sale_channel] || data.sale_channel" />
            <span v-else class="text-slate-300 text-xs">-</span>
          </template>
        </Column>
        <Column header="จัดการ" :style="{ width: '120px' }">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="fi fi-rr-edit" severity="info" text rounded @click="openEdit(data)" v-tooltip.top="'แก้ไข'" />
              <Button icon="fi fi-rr-trash" severity="danger" text rounded @click="confirmDelete(data)" v-tooltip.top="'ลบ'" />
            </div>
          </template>
        </Column>
      </DataTable>
      <Pagination :meta="meta" @change="onPage" class="p-4" />
    </div>

    <FollowupFormDialog v-model="dialogOpen" :followupId="editId" @saved="onSaved" />

    <ConfirmDialog />
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Select from 'primevue/select'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

import Pagination from '../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import FollowupFormDialog from './FollowupFormDialog.vue'
import { fmtThaiDate } from '../../utils/date.js'

const vTooltip = Tooltip
const confirm = useConfirm()
const toast = useToast()

const items = ref([])
const meta = ref({})
const loading = ref(false)
const filters = ref({ sale_channel: null })
const dialogOpen = ref(false)
const editId = ref(null)
let currentPage = 1, filterTimer = null

function openCreate() { editId.value = null; dialogOpen.value = true }
function openEdit(item) { editId.value = item.id; dialogOpen.value = true }
function onSaved() {
  toast.add({ severity: 'success', summary: 'สำเร็จ', detail: editId.value ? 'แก้ไขข้อมูลแล้ว' : 'เพิ่มการติดตามแล้ว', life: 2000 })
  fetchData()
}

const channelOptions = [
  { label: 'ขายตรง',   value: 'direct' },
  { label: 'ออนไลน์', value: 'online' },
  { label: 'วิสาหกิจ', value: 'enterprise' },
  { label: 'ตลาด',     value: 'market' },
]
const channelLabels = {
  direct: 'ขายตรง', online: 'ออนไลน์', enterprise: 'วิสาหกิจ', market: 'ตลาด',
}

const totalHarvestPage = computed(() => items.value.reduce((a, x) => a + Number(x.harvest_kg || 0), 0))
const totalSoldPage    = computed(() => items.value.reduce((a, x) => a + Number(x.sold_kg    || 0), 0))
const totalRevenuePage = computed(() => items.value.reduce((a, x) => a + Number(x.revenue    || 0), 0))

async function fetchData() {
  loading.value = true
  try {
    const params = { page: currentPage, per_page: 20 }
    if (filters.value.sale_channel) params.sale_channel = filters.value.sale_channel
    const { data } = await api.get('/mushroom-followups', { params })
    items.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch {
    toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: 'โหลดข้อมูลไม่สำเร็จ', life: 3000 })
  } finally {
    loading.value = false
  }
}

function onFilterChange() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => { currentPage = 1; fetchData() }, 300)
}
function onPage(p) { currentPage = p; fetchData() }

function confirmDelete(item) {
  confirm.require({
    message: `ลบการติดตามรอบ ${item.followup_round} ใช่หรือไม่?`,
    header: 'ยืนยันการลบ',
    icon: 'fi fi-rr-triangle-warning',
    rejectLabel: 'ยกเลิก', acceptLabel: 'ลบ',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/mushroom-followups/${item.id}`)
        toast.add({ severity: 'success', summary: 'ลบสำเร็จ', life: 2000 })
        fetchData()
      } catch (e) {
        toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: e.response?.data?.message || '', life: 3000 })
      }
    },
  })
}

onMounted(fetchData)
</script>
