<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-chart-pie text-violet-600"></i>
          รายงาน
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">สรุปผลการดำเนินงานในระบบ — สามารถส่งออกเป็น CSV ได้</p>
      </div>
      <Button label="ส่งออก CSV" icon="fi fi-rr-download" severity="secondary" outlined @click="exportCsv" />
    </div>

    <!-- Tabs -->
    <div class="box-card p-2">
      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="district"><span class="flex items-center gap-2"><i class="fi fi-rr-marker"></i> รายได้รายอำเภอ</span></Tab>
          <Tab value="quota"><span class="flex items-center gap-2"><i class="fi fi-rr-clipboard-list"></i> โควต้า vs จัดสรร</span></Tab>
          <Tab value="enterprise"><span class="flex items-center gap-2"><i class="fi fi-rr-shop"></i> วิสาหกิจ</span></Tab>
          <Tab value="household"><span class="flex items-center gap-2"><i class="fi fi-rr-house-blank"></i> รายได้ครัวเรือน</span></Tab>
        </TabList>

        <TabPanels>
          <!-- District -->
          <TabPanel value="district">
            <DataTable
              :value="districtData"
              :loading="loadingDistrict"
              stripedRows
              scrollable
              scrollHeight="60vh"
              sortField="total_revenue"
              :sortOrder="-1"
            >
              <template #footer>
                <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-2">
                  <span class="text-slate-600">รวม <span class="font-semibold text-violet-700">{{ districtData.length }}</span> อำเภอ</span>
                  <div class="flex gap-3 text-xs">
                    <span>ครัวเรือน: <span class="font-semibold">{{ sum(districtData, 'participating_households') }}</span></span>
                    <span>ผลผลิต: <span class="font-semibold">{{ fmt(sum(districtData, 'total_harvest_kg'), 2) }}</span> กก.</span>
                    <span>รายได้: <span class="font-semibold text-emerald-700">{{ fmt(sum(districtData, 'total_revenue'), 2) }}</span> บาท</span>
                  </div>
                </div>
              </template>

              <Column header="#" :style="{ width: '60px' }">
                <template #body="{ index }"><span class="text-xs text-slate-400">{{ index + 1 }}</span></template>
              </Column>
              <Column field="district" header="อำเภอ" sortable />
              <Column field="participating_households" header="ครัวเรือน" sortable :style="{ minWidth: '120px' }" />
              <Column field="total_allocated_bags" header="ถุงจัดสรร" sortable :style="{ minWidth: '130px' }">
                <template #body="{ data }">{{ fmt(data.total_allocated_bags) }}</template>
              </Column>
              <Column field="total_harvest_kg" header="ผลผลิต (กก.)" sortable :style="{ minWidth: '130px' }">
                <template #body="{ data }">{{ fmt(data.total_harvest_kg, 2) }}</template>
              </Column>
              <Column field="total_sold_kg" header="ขาย (กก.)" sortable :style="{ minWidth: '120px' }">
                <template #body="{ data }">{{ fmt(data.total_sold_kg, 2) }}</template>
              </Column>
              <Column field="total_revenue" header="รายได้ (บาท)" sortable :style="{ minWidth: '140px' }">
                <template #body="{ data }">
                  <span class="font-semibold text-emerald-700">{{ fmt(data.total_revenue, 2) }}</span>
                </template>
              </Column>
            </DataTable>
          </TabPanel>

          <!-- Quota -->
          <TabPanel value="quota">
            <DataTable :value="quotaData" :loading="loadingQuota" stripedRows scrollable scrollHeight="60vh">
              <template #footer>
                <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-2">
                  <span class="text-slate-600">รวม <span class="font-semibold text-violet-700">{{ quotaData.length }}</span> รายการ</span>
                  <div class="flex gap-3 text-xs">
                    <span>โควต้า: <span class="font-semibold">{{ fmt(sum(quotaData, 'quota_bags')) }}</span> ถุง</span>
                    <span>จัดสรร: <span class="font-semibold">{{ fmt(sum(quotaData, 'total_allocated')) }}</span> ถุง</span>
                    <span>คงเหลือ: <span class="font-semibold text-emerald-700">{{ fmt(sum(quotaData, 'remaining')) }}</span> ถุง</span>
                  </div>
                </div>
              </template>

              <Column header="#" :style="{ width: '60px' }">
                <template #body="{ index }"><span class="text-xs text-slate-400">{{ index + 1 }}</span></template>
              </Column>
              <Column field="district" header="อำเภอ" sortable />
              <Column header="ปี/รอบ" :style="{ minWidth: '120px' }">
                <template #body="{ data }">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-violet-50 text-violet-700 text-xs border border-violet-200">
                    {{ data.year }} · {{ data.round }}
                  </span>
                </template>
              </Column>
              <Column field="quota_bags" header="โควต้า" sortable>
                <template #body="{ data }">{{ fmt(data.quota_bags) }}</template>
              </Column>
              <Column field="total_allocated" header="จัดสรร" sortable>
                <template #body="{ data }">{{ fmt(data.total_allocated) }}</template>
              </Column>
              <Column field="remaining" header="คงเหลือ" sortable>
                <template #body="{ data }">
                  <span :class="['font-semibold', data.remaining < 0 ? 'text-rose-600' : data.remaining === 0 ? 'text-amber-600' : 'text-emerald-600']">
                    {{ fmt(data.remaining) }}
                  </span>
                </template>
              </Column>
              <Column field="pct_allocated" header="% จัดสรร" sortable :style="{ minWidth: '160px' }">
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <div class="flex-1 h-2 bg-violet-100 rounded-full overflow-hidden">
                      <div class="h-2 bg-gradient-to-r from-violet-500 to-fuchsia-500 rounded-full" :style="{ width: Math.min(data.pct_allocated, 100) + '%' }"></div>
                    </div>
                    <span class="text-xs font-semibold w-10 text-right">{{ data.pct_allocated }}%</span>
                  </div>
                </template>
              </Column>
            </DataTable>
          </TabPanel>

          <!-- Enterprise -->
          <TabPanel value="enterprise">
            <DataTable :value="enterpriseData" :loading="loadingEnterprise" stripedRows scrollable scrollHeight="60vh"
                       sortField="total_revenue" :sortOrder="-1">
              <template #empty>
                <div class="text-center py-12 text-slate-400">
                  <i class="fi fi-rr-info text-3xl"></i>
                  <p class="mt-2">ยังไม่มีข้อมูลวิสาหกิจ</p>
                </div>
              </template>
              <template #footer>
                <div class="flex items-center justify-between text-sm px-2 flex-wrap gap-2">
                  <span class="text-slate-600">รวม <span class="font-semibold text-violet-700">{{ enterpriseData.length }}</span> วิสาหกิจ</span>
                  <span class="text-xs">รายได้รวม: <span class="font-semibold text-emerald-700">{{ fmt(sum(enterpriseData, 'total_revenue'), 2) }}</span> บาท</span>
                </div>
              </template>

              <Column header="#" :style="{ width: '60px' }">
                <template #body="{ index }"><span class="text-xs text-slate-400">{{ index + 1 }}</span></template>
              </Column>
              <Column field="enterprise_name" header="วิสาหกิจ" sortable :style="{ minWidth: '200px' }" />
              <Column field="households_count" header="ครัวเรือน" sortable />
              <Column field="total_sold_kg" header="ขาย (กก.)" sortable>
                <template #body="{ data }">{{ fmt(data.total_sold_kg, 2) }}</template>
              </Column>
              <Column field="total_revenue" header="รายได้ (บาท)" sortable>
                <template #body="{ data }">
                  <span class="font-semibold text-emerald-700">{{ fmt(data.total_revenue, 2) }}</span>
                </template>
              </Column>
            </DataTable>
          </TabPanel>

          <!-- Household revenue -->
          <TabPanel value="household">
            <div class="mb-3 flex gap-2">
              <IconField class="flex-1">
                <InputIcon class="fi fi-rr-search text-slate-400" />
                <InputText v-model="hhSearch" @input="onHhSearch" placeholder="ค้นหาชื่อ / รหัสบ้าน..." class="w-full" />
              </IconField>
            </div>
            <DataTable :value="householdData" :loading="loadingHousehold" stripedRows scrollable scrollHeight="60vh">
              <template #empty>
                <div class="text-center py-12 text-slate-400">
                  <i class="fi fi-rr-info text-3xl"></i>
                  <p class="mt-2">ไม่พบข้อมูล</p>
                </div>
              </template>
              <template #footer>
                <div class="text-sm text-slate-600 px-2">
                  รวม <span class="font-semibold text-violet-700">{{ householdMeta.total || 0 }}</span> ครัวเรือน · รายได้รวมในหน้านี้
                  <span class="font-semibold text-emerald-700 ml-2">{{ fmt(sum(householdData, 'total_revenue'), 2) }}</span> บาท
                </div>
              </template>

              <Column header="#" :style="{ width: '60px' }">
                <template #body="{ index }">
                  <span class="text-xs text-slate-400">{{ ((householdMeta.current_page || 1) - 1) * 20 + index + 1 }}</span>
                </template>
              </Column>
              <Column field="household_code" header="รหัสบ้าน" sortable :style="{ minWidth: '130px' }">
                <template #body="{ data }">
                  <span class="font-mono text-violet-700 font-medium">{{ data.household_code }}</span>
                </template>
              </Column>
              <Column field="full_name" header="ชื่อ-นามสกุล" sortable :style="{ minWidth: '180px' }" />
              <Column field="district" header="อำเภอ" sortable />
              <Column field="allocation_count" header="จัดสรร" sortable>
                <template #body="{ data }">{{ fmt(data.allocation_count) }}</template>
              </Column>
              <Column field="total_sold_kg" header="ขาย (กก.)" sortable>
                <template #body="{ data }">{{ fmt(data.total_sold_kg, 2) }}</template>
              </Column>
              <Column field="total_revenue" header="รายได้ (บาท)" sortable>
                <template #body="{ data }">
                  <span class="font-semibold text-emerald-700">{{ fmt(data.total_revenue, 2) }}</span>
                </template>
              </Column>
            </DataTable>
            <Pagination :meta="householdMeta" @change="onHhPage" class="mt-3 px-2" />
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Toast from 'primevue/toast'
import Pagination from '../components/Pagination.vue'

const toast = useToast()
const activeTab = ref('district')

const districtData    = ref([])
const quotaData       = ref([])
const enterpriseData  = ref([])
const householdData   = ref([])
const householdMeta   = ref({})
const loadingDistrict   = ref(false)
const loadingQuota      = ref(false)
const loadingEnterprise = ref(false)
const loadingHousehold  = ref(false)
const hhSearch = ref('')
let hhPage = 1, hhTimer = null

function fmt(v, dec = 0) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}
function sum(arr, key) {
  return (arr || []).reduce((acc, x) => acc + Number(x[key] || 0), 0)
}

async function loadDistrict() {
  loadingDistrict.value = true
  try {
    const { data } = await api.get('/reports/by-district')
    districtData.value = data
  } finally { loadingDistrict.value = false }
}
async function loadQuota() {
  loadingQuota.value = true
  try {
    const { data } = await api.get('/reports/quota-vs-allocated')
    quotaData.value = data
  } finally { loadingQuota.value = false }
}
async function loadEnterprise() {
  loadingEnterprise.value = true
  try {
    const { data } = await api.get('/reports/by-enterprise')
    enterpriseData.value = data
  } finally { loadingEnterprise.value = false }
}
async function loadHousehold() {
  loadingHousehold.value = true
  try {
    const params = { page: hhPage, per_page: 20 }
    if (hhSearch.value) params.search = hhSearch.value
    const { data } = await api.get('/reports/household-revenue', { params })
    householdData.value = data.data
    householdMeta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally { loadingHousehold.value = false }
}

function onHhSearch() {
  clearTimeout(hhTimer)
  hhTimer = setTimeout(() => { hhPage = 1; loadHousehold() }, 300)
}
function onHhPage(p) { hhPage = p; loadHousehold() }

watch(activeTab, (tab) => {
  if (tab === 'district'   && !districtData.value.length)   loadDistrict()
  if (tab === 'quota'      && !quotaData.value.length)      loadQuota()
  if (tab === 'enterprise' && !enterpriseData.value.length) loadEnterprise()
  if (tab === 'household'  && !householdData.value.length)  loadHousehold()
})

// CSV export — derives from currently active tab's data
function exportCsv() {
  const tab = activeTab.value
  let filename = ''
  let headers = []
  let rows = []

  if (tab === 'district') {
    filename = 'report_by_district'
    headers = ['อำเภอ', 'ครัวเรือน', 'ถุงจัดสรร', 'ผลผลิต(กก.)', 'ขาย(กก.)', 'รายได้(บาท)']
    rows = districtData.value.map(r => [r.district, r.participating_households, r.total_allocated_bags, r.total_harvest_kg, r.total_sold_kg, r.total_revenue])
  } else if (tab === 'quota') {
    filename = 'report_quota_vs_allocated'
    headers = ['อำเภอ', 'ปี', 'รอบ', 'โควต้า', 'จัดสรร', 'คงเหลือ', '%จัดสรร']
    rows = quotaData.value.map(r => [r.district, r.year, r.round, r.quota_bags, r.total_allocated, r.remaining, r.pct_allocated])
  } else if (tab === 'enterprise') {
    filename = 'report_by_enterprise'
    headers = ['วิสาหกิจ', 'ครัวเรือน', 'ขาย(กก.)', 'รายได้(บาท)']
    rows = enterpriseData.value.map(r => [r.enterprise_name, r.households_count, r.total_sold_kg, r.total_revenue])
  } else if (tab === 'household') {
    filename = 'report_household_revenue'
    headers = ['รหัสบ้าน', 'ชื่อ-นามสกุล', 'อำเภอ', 'จัดสรร', 'ขาย(กก.)', 'รายได้(บาท)']
    rows = householdData.value.map(r => [r.household_code, r.full_name, r.district, r.allocation_count, r.total_sold_kg, r.total_revenue])
  }

  if (!rows.length) {
    toast.add({ severity: 'warn', summary: 'ไม่มีข้อมูล', detail: 'แท็บนี้ยังไม่มีข้อมูลให้ส่งออก', life: 2500 })
    return
  }

  const csv = '﻿' + [headers, ...rows].map(line =>
    line.map(v => {
      const s = String(v ?? '')
      return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s
    }).join(',')
  ).join('\n')

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${filename}_${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
  toast.add({ severity: 'success', summary: 'ส่งออกสำเร็จ', life: 2000 })
}

onMounted(loadDistrict)
</script>
