<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-search text-violet-600"></i>
          การติดตามครัวเรือน
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">ค้นหาและติดตามสถานะ ครัวเรือนเปราะบางในระบบ</p>
      </div>
    </div>

    <!-- Quick search + filters -->
    <div class="box-card p-4">
      <div class="flex items-center gap-2 mb-3 text-sm text-violet-700 font-semibold">
        <i class="fi fi-rr-filter"></i> ตัวกรอง
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <IconField class="md:col-span-2">
          <InputIcon class="fi fi-rr-search text-slate-400" />
          <InputText v-model="search" @input="onFilter" placeholder="รหัสบ้าน / ชื่อ / บัตรประชาชน..." class="w-full" />
        </IconField>
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

      <div v-else class="space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="h in items"
            :key="h.id"
            class="box-card p-4 cursor-pointer hover:scale-[1.01] transition"
            @click="goDetail(h)"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
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
            <div class="grid grid-cols-3 gap-2 mt-3 text-xs">
              <div>
                <p class="text-slate-400">รายได้</p>
                <p class="font-semibold text-emerald-700 truncate">{{ fmtMoney(h.income_month) }}</p>
              </div>
              <div>
                <p class="text-slate-400">รายจ่าย</p>
                <p class="font-semibold text-amber-700 truncate">{{ fmtMoney(h.expense_month) }}</p>
              </div>
              <div>
                <p class="text-slate-400">หนี้สิน</p>
                <p class="font-semibold text-rose-700 truncate">{{ fmtMoney(h.debt_amount) }}</p>
              </div>
            </div>
          </div>
        </div>

        <Pagination :meta="meta" @change="onPage" class="mt-3" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/index.js'

import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import StatusBadge from '../components/StatusBadge.vue'
import Pagination from './components/Pagination.vue'

const router = useRouter()
const search = ref('')
const filters = ref({ priority: null })
const items = ref([])
const meta = ref({})
const loading = ref(false)
let currentPage = 1
let filterTimer = null

const priorityOptions = [
  { label: 'A (สูงสุด)', value: 'A' },
  { label: 'B (สูง)',    value: 'B' },
  { label: 'C (ปานกลาง)', value: 'C' },
  { label: 'D (ต่ำ)',     value: 'D' },
]

function fmtMoney(v) {
  return v == null ? '-' : Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0 }) + ' บ.'
}

async function fetchData() {
  loading.value = true
  try {
    const params = { page: currentPage, per_page: 24 }
    if (search.value) params.search = search.value
    if (filters.value.priority) params.priority = filters.value.priority
    const { data } = await api.get('/households', { params })
    items.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally {
    loading.value = false
  }
}

function onFilter() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    currentPage = 1
    fetchData()
  }, 300)
}

function onPage(p) { currentPage = p; fetchData() }

function goDetail(h) {
  router.push(`/app/households?focus=${h.id}`)
}

onMounted(fetchData)
</script>
