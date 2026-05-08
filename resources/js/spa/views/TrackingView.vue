<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-search text-violet-600"></i>
          การติดตามครัวเรือน
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">คลิกที่การ์ดเพื่อดูรายละเอียดโควต้าเห็ด · ผลผลิต · รายได้</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="box-card p-4">
      <div class="flex items-center gap-2 mb-3 text-sm text-violet-700 font-semibold">
        <i class="fi fi-rr-filter"></i> ตัวกรอง
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <IconField class="md:col-span-2">
          <InputIcon class="fi fi-rr-search text-slate-400" />
          <InputText v-model="search" @input="onFilter" placeholder="รหัสบ้าน / ชื่อ / บัตรประชาชน..." class="w-full" />
        </IconField>
        <Select
          v-model="filters.district"
          :options="districtOptions"
          placeholder="ทุกอำเภอ"
          showClear
          filter
          @change="onFilter"
          class="w-full"
        />
        <Select
          v-model="filters.priority"
          :options="priorityOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="ทุก Priority"
          showClear
          @change="onFilter"
          class="w-full"
        />
      </div>

      <!-- Mushroom-status quick filters -->
      <div class="flex items-center gap-2 mt-3 flex-wrap">
        <span class="text-xs text-slate-500 mr-1">สถานะโควต้า:</span>
        <button
          v-for="btn in quickFilters"
          :key="btn.key"
          type="button"
          @click="toggleFilter(btn.key)"
          :class="['px-3 py-1.5 rounded-full text-xs font-medium transition flex items-center gap-1.5 border-2',
            filters[btn.key]
              ? `${btn.activeBg} ${btn.activeText} ${btn.activeBorder}`
              : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300']"
        >
          <i :class="filters[btn.key] ? 'fi fi-rr-check' : btn.icon"></i>
          {{ btn.label }}
        </button>
        <button
          v-if="anyFilterActive"
          @click="clearAllFilters"
          class="ml-auto text-xs text-rose-500 hover:text-rose-700 hover:underline flex items-center gap-1"
        >
          <i class="fi fi-rr-cross-small"></i> ล้างตัวกรอง
        </button>
      </div>
    </div>

    <!-- Counters -->
    <div class="grid grid-cols-3 gap-3">
      <div class="box-card p-3 text-center">
        <p class="text-xs text-slate-500">ที่พบ</p>
        <p class="text-xl font-bold text-violet-700">{{ Number(meta.total || 0).toLocaleString() }}</p>
      </div>
      <div class="box-card p-3 text-center">
        <p class="text-xs text-slate-500">ผ่านเกณฑ์</p>
        <p class="text-xl font-bold text-emerald-700">{{ items.filter(i => i.passed).length }}</p>
      </div>
      <div class="box-card p-3 text-center">
        <p class="text-xs text-slate-500">ไม่ผ่าน</p>
        <p class="text-xl font-bold text-rose-700">{{ items.filter(i => !i.passed).length }}</p>
      </div>
    </div>

    <!-- Result -->
    <div v-if="loading" class="text-center py-12 text-violet-400">
      <i class="fi fi-rr-loading text-3xl animate-spin"></i>
    </div>

    <div v-else>
      <div v-if="items.length === 0" class="box-card p-12 text-center text-slate-400">
        <i class="fi fi-rr-info text-3xl"></i>
        <p class="mt-2">ไม่พบครัวเรือน</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
        <div
          v-for="h in items"
          :key="h.id"
          class="box-card p-4 cursor-pointer hover:scale-[1.01] transition group"
          @click="openDetail(h)"
        >
          <!-- Header line: code + priority + status -->
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="font-mono text-sm text-violet-700 font-semibold">{{ h.household_code }}</p>
                <StatusBadge v-if="h.priority" :status="h.priority" :label="h.priority" />
              </div>
              <p class="font-medium text-slate-800 mt-1 truncate">
                {{ h.prefix }} {{ h.first_name }} {{ h.last_name }}
              </p>
              <p class="text-xs text-slate-500 mt-0.5 truncate">
                <i class="fi fi-rr-marker"></i>
                {{ h.district || '-' }} · {{ h.sub_district || '-' }}
              </p>
            </div>
            <StatusBadge :status="h.passed ? 'success' : 'failed'" :label="h.passed ? 'ผ่าน' : 'ไม่ผ่าน'" />
          </div>

          <!-- Mushroom summary -->
          <div :class="['mt-3 rounded-lg border-2 p-3',
            Number(h.total_revenue) > 0 ? 'border-emerald-200 bg-emerald-50/40' :
            Number(h.total_bags_received) > 0 ? 'border-violet-200 bg-violet-50/40' :
            'border-slate-200 bg-slate-50/40']">
            <p class="text-[11px] font-semibold text-violet-700 uppercase tracking-wide mb-2">
              <i class="fi fi-rr-mushroom"></i> ข้อมูลโควต้าเห็ด
            </p>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div>
                <p class="text-slate-400">ก้อนเห็ดที่ได้รับ</p>
                <p class="font-bold text-violet-700">{{ fmt(h.total_bags_received) }} <span class="text-[10px] text-slate-500">ก้อน</span></p>
              </div>
              <div>
                <p class="text-slate-400">ผลผลิต</p>
                <p :class="['font-bold', Number(h.total_harvest_kg) > 0 ? 'text-emerald-700' : 'text-slate-400']">
                  {{ fmt(h.total_harvest_kg, 2) }} <span class="text-[10px] text-slate-500">กก.</span>
                </p>
              </div>
              <div>
                <p class="text-slate-400">ขายได้</p>
                <p :class="['font-bold', Number(h.total_sold_kg) > 0 ? 'text-orange-700' : 'text-slate-400']">
                  {{ fmt(h.total_sold_kg, 2) }} <span class="text-[10px] text-slate-500">กก.</span>
                </p>
              </div>
              <div>
                <p class="text-slate-400">รายได้</p>
                <p :class="['font-bold', Number(h.total_revenue) > 0 ? 'text-amber-700' : 'text-slate-400']">
                  {{ fmt(h.total_revenue, 2) }} <span class="text-[10px] text-slate-500">บ.</span>
                </p>
              </div>
            </div>
            <!-- Status tags — one per matched condition (mirrors the filter pills above) -->
            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
              <span v-if="Number(h.total_bags_received) > 0"
                    class="text-[10px] px-2 py-0.5 rounded-full bg-violet-100 text-violet-800 border border-violet-200 inline-flex items-center gap-1">
                <i class="fi fi-rr-check"></i> ได้รับโควต้าแล้ว
                <span class="text-[9px] text-violet-600">· {{ h.allocation_count }} ครั้ง</span>
              </span>
              <span v-if="Number(h.total_harvest_kg) > 0"
                    class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                <i class="fi fi-rr-check"></i> ได้รับผลผลิตแล้ว
                <span class="text-[9px] text-emerald-600">· {{ h.followup_count }} รอบ</span>
              </span>
              <span v-if="Number(h.total_revenue) > 0"
                    class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                <i class="fi fi-rr-check"></i> มีรายได้แล้ว
              </span>
              <span v-if="Number(h.total_bags_received) === 0"
                    class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200">
                ยังไม่ได้รับโควต้า
              </span>
            </div>
          </div>

          <!-- Bottom: economic snapshot -->
          <div class="grid grid-cols-3 gap-2 mt-3 text-[11px]">
            <div>
              <p class="text-slate-400">รายได้ครัวเรือน</p>
              <p class="font-medium text-emerald-700 truncate">{{ fmtMoney(h.income_month) }}</p>
            </div>
            <div>
              <p class="text-slate-400">รายจ่าย</p>
              <p class="font-medium text-amber-700 truncate">{{ fmtMoney(h.expense_month) }}</p>
            </div>
            <div>
              <p class="text-slate-400">หนี้สิน</p>
              <p class="font-medium text-rose-700 truncate">{{ fmtMoney(h.debt_amount) }}</p>
            </div>
          </div>

          <!-- Click hint -->
          <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-violet-600 group-hover:text-violet-800 flex items-center gap-1">
            <i class="fi fi-rr-eye"></i> คลิกเพื่อดูรายละเอียด
          </div>
        </div>
      </div>

      <Pagination v-if="items.length" :meta="meta" @change="onPage" class="mt-3" />
    </div>

    <!-- Detail dialog -->
    <HouseholdTrackingDialog v-model="detailOpen" :householdId="detailId" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api/index.js'

import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import StatusBadge from '../components/StatusBadge.vue'
import Pagination from './components/Pagination.vue'
import HouseholdTrackingDialog from './households/HouseholdTrackingDialog.vue'

const search = ref('')
const filters = ref({
  district:    null,
  priority:    null,
  has_quota:   false,
  has_harvest: false,
  has_revenue: false,
})
const districtOptions = ref([])

const quickFilters = [
  {
    key: 'has_quota',
    label: 'ได้รับโควต้าแล้ว',
    icon: 'fi fi-rr-shopping-bag',
    activeBg: 'bg-violet-100', activeText: 'text-violet-800', activeBorder: 'border-violet-400',
  },
  {
    key: 'has_harvest',
    label: 'ได้รับผลผลิตแล้ว',
    icon: 'fi fi-rr-mushroom',
    activeBg: 'bg-emerald-100', activeText: 'text-emerald-800', activeBorder: 'border-emerald-400',
  },
  {
    key: 'has_revenue',
    label: 'มีรายได้แล้ว',
    icon: 'fi fi-rr-money-bill-wave',
    activeBg: 'bg-amber-100', activeText: 'text-amber-800', activeBorder: 'border-amber-400',
  },
]
const items = ref([])
const meta = ref({})
const loading = ref(false)
const detailOpen = ref(false)
const detailId = ref(null)
let currentPage = 1
let filterTimer = null

const priorityOptions = [
  { label: 'A (สูงสุด)', value: 'A' },
  { label: 'B (สูง)',    value: 'B' },
  { label: 'C (ปานกลาง)', value: 'C' },
  { label: 'D (ต่ำ)',     value: 'D' },
]

function fmt(v, dec = 0) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}
function fmtMoney(v) {
  return v == null ? '-' : Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0 }) + ' บ.'
}

async function fetchData() {
  loading.value = true
  try {
    const params = { page: currentPage, per_page: 24 }
    if (search.value)              params.search   = search.value
    if (filters.value.district)    params.district = filters.value.district
    if (filters.value.priority)    params.priority = filters.value.priority
    if (filters.value.has_quota)   params.has_quota   = 1
    if (filters.value.has_harvest) params.has_harvest = 1
    if (filters.value.has_revenue) params.has_revenue = 1
    const { data } = await api.get('/households', { params })
    items.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally {
    loading.value = false
  }
}

async function fetchDistricts() {
  try {
    const { data } = await api.get('/locations/districts')
    districtOptions.value = data
  } catch {}
}

function onFilter() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    currentPage = 1
    fetchData()
  }, 300)
}

function toggleFilter(key) {
  filters.value[key] = !filters.value[key]
  currentPage = 1
  fetchData()
}

const anyFilterActive = computed(() =>
  filters.value.has_quota || filters.value.has_harvest || filters.value.has_revenue
  || filters.value.priority || filters.value.district
)

function clearAllFilters() {
  filters.value.has_quota   = false
  filters.value.has_harvest = false
  filters.value.has_revenue = false
  filters.value.priority    = null
  filters.value.district    = null
  currentPage = 1
  fetchData()
}

function onPage(p) { currentPage = p; fetchData() }

function openDetail(h) {
  detailId.value = h.id
  detailOpen.value = true
}

onMounted(() => {
  fetchData()
  fetchDistricts()
})
</script>
