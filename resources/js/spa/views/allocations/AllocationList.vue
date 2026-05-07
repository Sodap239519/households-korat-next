<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-seedling text-violet-600"></i>
          การจัดสรรถุงเห็ด
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">บันทึกการจัดสรรโควต้าเห็ดให้แต่ละครัวเรือน</p>
      </div>
      <Button label="เพิ่มการจัดสรร" icon="fi fi-rr-plus" @click="$router.push('/app/mushroom/allocations/create')" />
    </div>

    <!-- Filter -->
    <div class="box-card p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
                placeholder="ทุกสถานะ" showClear class="w-full" @change="onFilterChange" />
      </div>
    </div>

    <!-- DataTable -->
    <div class="box-card p-0 overflow-hidden">
      <DataTable :value="items" :loading="loading" stripedRows scrollable scrollHeight="62vh">
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="fi fi-rr-info text-3xl"></i>
            <p class="mt-2">ยังไม่มีการจัดสรร</p>
          </div>
        </template>
        <template #footer>
          <div class="flex items-center justify-between text-sm px-2">
            <span class="text-slate-600">รวม <span class="font-semibold text-violet-700">{{ Number(meta.total || 0).toLocaleString() }}</span> รายการ</span>
            <span class="text-xs">รวมในหน้านี้: <span class="font-semibold text-fuchsia-700">{{ totalBagsPage.toLocaleString() }}</span> ถุง</span>
          </div>
        </template>

        <Column header="#" :style="{ width: '60px' }">
          <template #body="{ index }">
            <span class="text-xs text-slate-400 font-medium">{{ ((meta.current_page || 1) - 1) * 20 + index + 1 }}</span>
          </template>
        </Column>
        <Column header="ครัวเรือน" :style="{ minWidth: '220px' }">
          <template #body="{ data }">
            <div class="font-medium text-slate-700">
              {{ data.household?.prefix }} {{ data.household?.first_name }} {{ data.household?.last_name }}
            </div>
            <div class="font-mono text-xs text-violet-600">{{ data.household?.household_code }}</div>
          </template>
        </Column>
        <Column header="โควต้า (อำเภอ/ปี/รอบ)" :style="{ minWidth: '200px' }">
          <template #body="{ data }">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-violet-50 text-violet-700 text-xs border border-violet-200">
              {{ data.quota?.district }} · {{ data.quota?.year }} · รอบ {{ data.quota?.round }}
            </span>
          </template>
        </Column>
        <Column field="bags" header="จำนวนถุง" sortable :style="{ width: '120px' }">
          <template #body="{ data }">
            <span class="font-semibold text-slate-700">{{ Number(data.bags).toLocaleString() }}</span>
          </template>
        </Column>
        <Column field="allocated_date" header="วันที่จัดสรร" sortable :style="{ width: '140px' }">
          <template #body="{ data }">{{ data.allocated_date ?? '-' }}</template>
        </Column>
        <Column header="สถานะ" :style="{ width: '160px' }">
          <template #body="{ data }">
            <StatusBadge :status="badgeStatus(data.status)" :label="statusLabels[data.status] || data.status" />
          </template>
        </Column>
        <Column header="จัดการ" :style="{ width: '140px' }">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="fi fi-rr-edit" severity="info" text rounded @click="$router.push(`/app/mushroom/allocations/${data.id}/edit`)" v-tooltip.top="'แก้ไข'" />
              <Button icon="fi fi-rr-trash" severity="danger" text rounded @click="confirmDelete(data)" v-tooltip.top="'ลบ'" />
            </div>
          </template>
        </Column>
      </DataTable>
      <Pagination :meta="meta" @change="onPage" class="p-4" />
    </div>

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

const vTooltip = Tooltip
const confirm = useConfirm()
const toast = useToast()

const items = ref([])
const meta = ref({})
const loading = ref(false)
const filters = ref({ status: null })
let currentPage = 1, filterTimer = null

const statusOptions = [
  { label: 'รอดำเนินการ',     value: 'pending' },
  { label: 'กำลังดำเนินการ', value: 'active' },
  { label: 'เสร็จสิ้น',         value: 'completed' },
]
const statusLabels = {
  pending:   'รอดำเนินการ',
  active:    'กำลังดำเนินการ',
  completed: 'เสร็จสิ้น',
}
function badgeStatus(s) {
  return s === 'completed' ? 'completed' : s === 'active' ? 'active' : 'pending'
}

const totalBagsPage = computed(() => items.value.reduce((a, x) => a + Number(x.bags || 0), 0))

async function fetchData() {
  loading.value = true
  try {
    const params = { page: currentPage, per_page: 20 }
    if (filters.value.status) params.status = filters.value.status
    const { data } = await api.get('/mushroom-allocations', { params })
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
    message: `ลบการจัดสรร ${item.bags} ถุง ของ ${item.household?.first_name} ${item.household?.last_name} ใช่หรือไม่?`,
    header: 'ยืนยันการลบ',
    icon: 'fi fi-rr-triangle-warning',
    rejectLabel: 'ยกเลิก', acceptLabel: 'ลบ',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/mushroom-allocations/${item.id}`)
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
