<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-clipboard-list text-violet-600"></i>
          โควต้าอำเภอ
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">กำหนด/แก้ไขโควต้าเห็ดของแต่ละอำเภอตามปีและรอบ</p>
      </div>
      <Button
        label="เพิ่มโควต้า"
        icon="fi fi-rr-plus"
        size="large"
        @click="openCreate"
      />
    </div>

    <!-- Filter box -->
    <div class="box-card p-4">
      <div class="flex items-center gap-2 mb-3 text-sm text-violet-700 font-semibold">
        <i class="fi fi-rr-filter"></i> ตัวกรอง
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <Select
          v-model="filters.district"
          :options="districtOptions"
          placeholder="ทุกอำเภอ"
          showClear filter
          @change="onFilterChange"
          class="w-full"
        />
        <Select
          v-model="filters.year"
          :options="yearOptions"
          optionLabel="label" optionValue="value"
          placeholder="ทุกปี"
          showClear filter
          @change="onFilterChange"
          class="w-full"
        />
        <Select
          v-model="filters.round"
          :options="roundOptions"
          optionLabel="label" optionValue="value"
          placeholder="ทุกรอบ"
          showClear
          @change="onFilterChange"
          class="w-full"
        />
        <Select
          v-model="filters.is_active"
          :options="statusOptions"
          optionLabel="label" optionValue="value"
          placeholder="ทุกสถานะ"
          showClear
          @change="onFilterChange"
          class="w-full"
        />
      </div>
    </div>

    <!-- DataTable -->
    <div class="box-card p-0 overflow-hidden">
      <DataTable
        :value="items"
        :loading="loading"
        stripedRows
        scrollable
        scrollHeight="60vh"
        :pt="{ table: { class: 'text-sm' } }"
      >
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="fi fi-rr-info text-3xl"></i>
            <p class="mt-2">ไม่พบข้อมูล</p>
          </div>
        </template>
        <template #loading>
          <div class="text-center py-12 text-violet-400">
            <i class="fi fi-rr-loading text-3xl animate-spin"></i>
          </div>
        </template>
        <template #footer>
          <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-2">
            <span class="text-slate-600">รวม <span class="font-semibold text-violet-700">{{ items.length }}</span> รายการในหน้านี้</span>
            <div class="flex items-center gap-4 text-xs">
              <span>โควต้ารวม: <span class="font-semibold text-violet-700">{{ Number(totalQuotaBags).toLocaleString() }}</span> ก้อน</span>
              <span>จัดสรรแล้ว: <span class="font-semibold text-fuchsia-700">{{ Number(totalAllocated).toLocaleString() }}</span> ก้อน</span>
              <span>คงเหลือ: <span :class="['font-semibold', totalRemaining < 0 ? 'text-rose-700' : 'text-emerald-700']">{{ Number(totalRemaining).toLocaleString() }}</span> ก้อน</span>
            </div>
          </div>
        </template>

        <Column header="#" :style="{ width: '60px' }">
          <template #body="{ index }">
            <span class="text-xs text-slate-400 font-medium">{{ ((meta.current_page || 1) - 1) * 20 + index + 1 }}</span>
          </template>
        </Column>
        <Column field="district" header="อำเภอ" sortable>
          <template #body="{ data }">
            <span class="font-medium text-slate-700">{{ data.district }}</span>
          </template>
        </Column>
        <Column header="ปี/รอบ">
          <template #body="{ data }">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-violet-50 text-violet-700 text-xs font-medium border border-violet-200">
              {{ data.year }} / {{ data.round }}
            </span>
          </template>
        </Column>
        <Column field="quota_bags" header="โควต้า (ก้อน)" sortable>
          <template #body="{ data }">
            <span class="font-semibold text-slate-700">{{ Number(data.quota_bags).toLocaleString() }}</span>
          </template>
        </Column>
        <Column header="จัดสรรแล้ว">
          <template #body="{ data }">
            {{ Number(data.allocations_sum_bags ?? 0).toLocaleString() }}
          </template>
        </Column>
        <Column header="คงเหลือ">
          <template #body="{ data }">
            <span :class="['font-semibold', remaining(data) < 0 ? 'text-rose-600' : remaining(data) === 0 ? 'text-amber-600' : 'text-emerald-600']">
              {{ Number(remaining(data)).toLocaleString() }}
            </span>
          </template>
        </Column>
        <Column header="สถานะ">
          <template #body="{ data }">
            <StatusBadge
              :status="data.is_active ? 'active' : 'inactive'"
              :label="data.is_active ? 'เปิดใช้' : 'ปิด'"
            />
          </template>
        </Column>
        <Column header="จัดการ" :style="{ width: '160px' }">
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

    <!-- Form Dialog -->
    <QuotaFormDialog v-model="dialogOpen" :quotaId="editId" @saved="onSaved" />

    <!-- Confirm + Toast -->
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
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

import QuotaFormDialog from './QuotaFormDialog.vue'
import Pagination from '../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const vTooltip = Tooltip
const confirm = useConfirm()
const toast = useToast()

const items = ref([])
const meta = ref({})
const loading = ref(false)
const years = ref([])
const districtOptions = ref([])
const filters = ref({ district: null, year: null, round: null, is_active: null })

const roundOptions = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(n => ({ label: `รอบ ${n}`, value: n }))

const dialogOpen = ref(false)
const editId = ref(null)

let currentPage = 1
let filterTimer = null

const yearOptions = computed(() => years.value.map(y => ({ label: `ปี ${y}`, value: y })))
const statusOptions = [
  { label: 'เปิดใช้งาน', value: 1 },
  { label: 'ปิด',         value: 0 },
]

function remaining(item) {
  return Number(item.quota_bags) - Number(item.allocations_sum_bags ?? 0)
}

async function fetchData() {
  loading.value = true
  try {
    const params = { page: currentPage, per_page: 20 }
    if (filters.value.district)         params.district  = filters.value.district
    if (filters.value.year)             params.year      = filters.value.year
    if (filters.value.round)            params.round     = filters.value.round
    if (filters.value.is_active !== null && filters.value.is_active !== undefined) params.is_active = filters.value.is_active
    const { data } = await api.get('/mushroom-quotas', { params })
    items.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: 'โหลดข้อมูลไม่สำเร็จ', life: 3000 })
  } finally {
    loading.value = false
  }
}

async function fetchYears() {
  try {
    const { data } = await api.get('/reports/years')
    years.value = data
  } catch {}
}

async function fetchDistricts() {
  try {
    const { data } = await api.get('/locations/districts')
    districtOptions.value = data
  } catch {}
}

const totalQuotaBags = computed(() => items.value.reduce((acc, x) => acc + Number(x.quota_bags || 0), 0))
const totalAllocated = computed(() => items.value.reduce((acc, x) => acc + Number(x.allocations_sum_bags || 0), 0))
const totalRemaining = computed(() => totalQuotaBags.value - totalAllocated.value)

function onFilterChange() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    currentPage = 1
    fetchData()
  }, 300)
}

function onPage(page) {
  currentPage = page
  fetchData()
}

function openCreate() {
  editId.value = null
  dialogOpen.value = true
}

function openEdit(item) {
  editId.value = item.id
  dialogOpen.value = true
}

function onSaved() {
  toast.add({ severity: 'success', summary: 'สำเร็จ', detail: editId.value ? 'แก้ไขข้อมูลแล้ว' : 'เพิ่มข้อมูลแล้ว', life: 2500 })
  fetchData()
}

function confirmDelete(item) {
  confirm.require({
    message: `ต้องการลบโควต้า ${item.district} ปี ${item.year} รอบ ${item.round} ใช่หรือไม่?`,
    header: 'ยืนยันการลบ',
    icon: 'fi fi-rr-triangle-warning',
    rejectLabel: 'ยกเลิก',
    acceptLabel: 'ลบ',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/mushroom-quotas/${item.id}`)
        toast.add({ severity: 'success', summary: 'ลบสำเร็จ', life: 2000 })
        fetchData()
      } catch (e) {
        toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: e.response?.data?.message || 'ลบไม่สำเร็จ', life: 3000 })
      }
    },
  })
}

onMounted(() => {
  fetchData()
  fetchYears()
  fetchDistricts()
})
</script>
