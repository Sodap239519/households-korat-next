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

    <!-- Quick search -->
    <div class="box-card p-5">
      <div class="flex items-center gap-2 mb-3 text-sm text-violet-700 font-semibold">
        <i class="fi fi-rr-magnifying-glass"></i> ค้นหาด่วน
      </div>
      <div class="flex gap-2">
        <IconField class="flex-1">
          <InputIcon class="fi fi-rr-search text-slate-400" />
          <InputText v-model="search" @keyup.enter="doSearch" placeholder="ใส่รหัสบ้าน / ชื่อ / บัตรประชาชน..." class="w-full" size="large" />
        </IconField>
        <Button label="ค้นหา" icon="fi fi-rr-search" size="large" :loading="loading" @click="doSearch" />
      </div>
      <p class="text-xs text-slate-400 mt-2">เคล็ดลับ: ใส่บางส่วนของชื่อ/รหัสได้ — ระบบจะค้นหาแบบบางส่วน</p>
    </div>

    <!-- Result -->
    <div v-if="searched">
      <div v-if="results.length === 0 && !loading" class="box-card p-12 text-center text-slate-400">
        <i class="fi fi-rr-info text-3xl"></i>
        <p class="mt-2">ไม่พบครัวเรือนที่ตรงกับ "{{ lastQuery }}"</p>
      </div>

      <div v-else class="space-y-3">
        <p class="text-sm text-slate-600">พบ <span class="font-semibold text-violet-700">{{ results.length }}</span> รายการ</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="h in results" :key="h.id" class="box-card p-4 cursor-pointer hover:scale-[1.01] transition" @click="goDetail(h)">
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="font-mono text-sm text-violet-700 font-semibold">{{ h.household_code }}</p>
                  <StatusBadge v-if="h.priority" :status="h.priority" :label="h.priority" />
                </div>
                <p class="font-medium text-slate-800 mt-1">{{ h.prefix }} {{ h.first_name }} {{ h.last_name }}</p>
                <p class="text-xs text-slate-500 mt-0.5">
                  <i class="fi fi-rr-marker"></i>
                  {{ h.district }} · {{ h.sub_district }} · {{ h.village }}
                </p>
              </div>
              <StatusBadge :status="h.passed ? 'success' : 'failed'" :label="h.passed ? 'ผ่าน' : 'ไม่ผ่าน'" />
            </div>
            <div class="grid grid-cols-3 gap-2 mt-3 text-xs">
              <div>
                <p class="text-slate-400">รายได้/เดือน</p>
                <p class="font-semibold text-emerald-700">{{ fmtMoney(h.income_month) }}</p>
              </div>
              <div>
                <p class="text-slate-400">รายจ่าย/เดือน</p>
                <p class="font-semibold text-amber-700">{{ fmtMoney(h.expense_month) }}</p>
              </div>
              <div>
                <p class="text-slate-400">หนี้สิน</p>
                <p class="font-semibold text-rose-700">{{ fmtMoney(h.debt_amount) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-search text-3xl"></i>
      <p class="mt-2">เริ่มค้นหาเพื่อดูรายการ</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/index.js'

import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Button from 'primevue/button'
import StatusBadge from '../components/StatusBadge.vue'

const router = useRouter()
const search = ref('')
const lastQuery = ref('')
const results = ref([])
const loading = ref(false)
const searched = ref(false)

function fmtMoney(v) {
  return v == null ? '-' : Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0 }) + ' บ.'
}

async function doSearch() {
  if (!search.value.trim()) return
  loading.value = true
  lastQuery.value = search.value
  try {
    const { data } = await api.get('/households', { params: { search: search.value, per_page: 50 } })
    results.value = data.data
    searched.value = true
  } finally {
    loading.value = false
  }
}

function goDetail(h) {
  router.push(`/app/households?focus=${h.id}`)
}
</script>
