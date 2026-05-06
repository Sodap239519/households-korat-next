<template>
  <div class="p-6 space-y-6">
    <!-- Hero / Welcome -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 text-white p-6 shadow-lg shadow-violet-500/30">
      <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-12 -left-8 w-56 h-56 rounded-full bg-fuchsia-300/20 blur-3xl pointer-events-none"></div>
      <div class="relative flex items-center justify-between flex-wrap gap-4">
        <div>
          <p class="text-violet-100 text-sm">ยินดีต้อนรับ 👋</p>
          <h2 class="text-2xl font-bold mt-1">ภาพรวมระบบโควต้าเห็ด</h2>
          <p class="text-violet-100 text-sm mt-1">ติดตามผลผลิต รายได้ และการจัดสรรของครัวเรือนในจังหวัด</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="px-4 py-2 rounded-lg bg-white/15 backdrop-blur text-sm">
            <i class="fi fi-rr-calendar mr-1.5"></i>
            ปี พ.ศ. {{ thaiYear }}
          </div>
        </div>
      </div>
    </div>

    <!-- Stat cards -->
    <div v-if="loading" class="text-slate-400 text-center py-12">
      <i class="fi fi-rr-loading text-3xl animate-spin"></i>
      <p class="mt-2">กำลังโหลดข้อมูล...</p>
    </div>

    <div v-else>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard label="ครัวเรือน"   :value="stats.total_households"   icon="fi fi-rr-house-blank" tone="violet" />
        <StatCard label="โควต้า"      :value="stats.total_quotas"       icon="fi fi-rr-clipboard-list" tone="indigo" />
        <StatCard label="จัดสรร"      :value="stats.total_allocations"  icon="fi fi-rr-seedling" tone="fuchsia" />
        <StatCard label="ติดตามผล"   :value="stats.total_followups"    icon="fi fi-rr-list-check" tone="purple" />
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mt-4">
        <StatCard label="โควต้ารวม (ถุง)"   :value="fmt(stats.total_bags_quota)"     icon="fi fi-rr-shopping-bag"  tone="indigo" small />
        <StatCard label="จัดสรรแล้ว (ถุง)"  :value="fmt(stats.total_bags_allocated)" icon="fi fi-rr-check-circle"  tone="emerald" small />
        <StatCard label="ผลผลิต (กก.)"      :value="fmt(stats.total_harvest_kg, 2)"  icon="fi fi-rr-leaf"          tone="lime" small />
        <StatCard label="ขายได้ (กก.)"      :value="fmt(stats.total_sold_kg, 2)"     icon="fi fi-rr-shop"          tone="orange" small />
        <StatCard label="รายได้รวม (บาท)"   :value="fmt(stats.total_revenue, 2)"     icon="fi fi-rr-money-bill-wave" tone="amber" small />
      </div>

      <!-- Charts row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
        <!-- Bar chart: Quota vs Allocated by district -->
        <div class="box-card p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
              <i class="fi fi-rr-chart-histogram text-violet-600"></i>
              โควต้า vs จัดสรร (รายอำเภอ)
            </h3>
            <span class="text-xs text-slate-400">ปี {{ thaiYear }}</span>
          </div>
          <div v-if="quotaVsAllocated.length === 0" class="text-center text-slate-400 text-sm py-12">
            <i class="fi fi-rr-info text-2xl"></i>
            <p class="mt-2">ยังไม่มีข้อมูลการจัดสรร</p>
          </div>
          <Chart v-else type="bar" :data="quotaChartData" :options="quotaChartOptions" class="h-72" />
        </div>

        <!-- Doughnut: Revenue by district -->
        <div class="box-card p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
              <i class="fi fi-rr-chart-pie-alt text-fuchsia-600"></i>
              สัดส่วนรายได้ (รายอำเภอ)
            </h3>
          </div>
          <div v-if="byDistrict.length === 0" class="text-center text-slate-400 text-sm py-12">
            <i class="fi fi-rr-info text-2xl"></i>
            <p class="mt-2">ยังไม่มีข้อมูลรายได้</p>
          </div>
          <Chart v-else type="doughnut" :data="districtChartData" :options="districtChartOptions" class="h-72" />
        </div>
      </div>

      <!-- Top districts table -->
      <div v-if="byDistrict.length" class="box-card p-5 mt-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
            <i class="fi fi-rr-trophy text-amber-500"></i>
            อำเภอที่มีรายได้สูงสุด
          </h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-slate-500 border-b border-violet-100">
                <th class="px-3 py-2 font-medium">อันดับ</th>
                <th class="px-3 py-2 font-medium">อำเภอ</th>
                <th class="px-3 py-2 font-medium text-right">ผลผลิต (กก.)</th>
                <th class="px-3 py-2 font-medium text-right">ขาย (กก.)</th>
                <th class="px-3 py-2 font-medium text-right">รายได้ (บาท)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, idx) in byDistrict.slice(0, 5)" :key="d.district" class="border-b border-violet-50 hover:bg-violet-50/30">
                <td class="px-3 py-2.5">
                  <span :class="['inline-flex items-center justify-center w-7 h-7 rounded-full font-bold text-xs',
                    idx === 0 ? 'bg-amber-100 text-amber-700' :
                    idx === 1 ? 'bg-slate-100 text-slate-700' :
                    idx === 2 ? 'bg-orange-100 text-orange-700' : 'bg-violet-50 text-violet-600']">
                    {{ idx + 1 }}
                  </span>
                </td>
                <td class="px-3 py-2.5 font-medium text-slate-700">{{ d.district }}</td>
                <td class="px-3 py-2.5 text-right text-slate-600">{{ fmt(d.total_harvest_kg, 2) }}</td>
                <td class="px-3 py-2.5 text-right text-slate-600">{{ fmt(d.total_sold_kg, 2) }}</td>
                <td class="px-3 py-2.5 text-right font-semibold text-emerald-700">{{ fmt(d.total_revenue, 2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineComponent, h } from 'vue'
import api from '../api/index.js'
import Chart from 'primevue/chart'

const stats = ref({})
const quotaVsAllocated = ref([])
const byDistrict = ref([])
const loading = ref(true)

const thaiYear = computed(() => new Date().getFullYear() + 543)

function fmt(v, dec = 0) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

const TONE_STYLES = {
  violet:  { bg: 'from-violet-500 to-violet-600',   text: 'text-violet-600',   ring: 'shadow-violet-500/25',  light: 'bg-violet-50 border-violet-200' },
  indigo:  { bg: 'from-indigo-500 to-indigo-600',   text: 'text-indigo-600',   ring: 'shadow-indigo-500/25',  light: 'bg-indigo-50 border-indigo-200' },
  fuchsia: { bg: 'from-fuchsia-500 to-fuchsia-600', text: 'text-fuchsia-600',  ring: 'shadow-fuchsia-500/25', light: 'bg-fuchsia-50 border-fuchsia-200' },
  purple:  { bg: 'from-purple-500 to-purple-600',   text: 'text-purple-600',   ring: 'shadow-purple-500/25',  light: 'bg-purple-50 border-purple-200' },
  emerald: { bg: 'from-emerald-500 to-emerald-600', text: 'text-emerald-600',  ring: 'shadow-emerald-500/25', light: 'bg-emerald-50 border-emerald-200' },
  lime:    { bg: 'from-lime-500 to-lime-600',       text: 'text-lime-600',     ring: 'shadow-lime-500/25',    light: 'bg-lime-50 border-lime-200' },
  orange:  { bg: 'from-orange-500 to-orange-600',   text: 'text-orange-600',   ring: 'shadow-orange-500/25',  light: 'bg-orange-50 border-orange-200' },
  amber:   { bg: 'from-amber-500 to-amber-600',     text: 'text-amber-600',    ring: 'shadow-amber-500/25',   light: 'bg-amber-50 border-amber-200' },
}

const StatCard = defineComponent({
  props: ['label', 'value', 'icon', 'tone', 'small'],
  setup(props) {
    return () => {
      const tone = TONE_STYLES[props.tone] || TONE_STYLES.violet
      return h('div', {
        class: `box-card group relative overflow-hidden ${props.small ? 'p-4' : 'p-5'}`,
      }, [
        h('div', {
          class: `absolute -top-8 -right-8 w-24 h-24 rounded-full bg-gradient-to-br ${tone.bg} opacity-10 group-hover:opacity-20 transition`,
        }),
        h('div', { class: 'relative flex items-start justify-between' }, [
          h('div', {}, [
            h('p', { class: 'text-xs text-slate-500 font-medium' }, props.label),
            h('p', { class: `mt-1 ${props.small ? 'text-xl' : 'text-2xl'} font-bold text-slate-800` }, props.value ?? '-'),
          ]),
          h('div', {
            class: `flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br ${tone.bg} text-white flex items-center justify-center shadow-md ${tone.ring}`,
          }, [
            h('i', { class: `${props.icon} text-base` }),
          ]),
        ]),
      ])
    }
  },
})

// Chart data computed
const quotaChartData = computed(() => {
  const labels = quotaVsAllocated.value.map(r => r.district)
  return {
    labels,
    datasets: [
      {
        label: 'โควต้า (ถุง)',
        backgroundColor: '#a78bfa',
        borderRadius: 6,
        data: quotaVsAllocated.value.map(r => Number(r.quota_bags || 0)),
      },
      {
        label: 'จัดสรรแล้ว (ถุง)',
        backgroundColor: '#d946ef',
        borderRadius: 6,
        data: quotaVsAllocated.value.map(r => Number(r.allocated_bags || 0)),
      },
    ],
  }
})

const quotaChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom', labels: { font: { family: 'Prompt' } } },
  },
  scales: {
    x: { ticks: { font: { family: 'Prompt' } }, grid: { display: false } },
    y: { ticks: { font: { family: 'Prompt' } }, grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true },
  },
}

const districtChartData = computed(() => {
  const palette = ['#7c3aed','#a855f7','#d946ef','#ec4899','#8b5cf6','#6366f1','#3b82f6','#06b6d4','#10b981','#f59e0b']
  return {
    labels: byDistrict.value.map(r => r.district),
    datasets: [{
      data: byDistrict.value.map(r => Number(r.total_revenue || 0)),
      backgroundColor: byDistrict.value.map((_, i) => palette[i % palette.length]),
      borderWidth: 2,
      borderColor: '#fff',
    }],
  }
})

const districtChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom', labels: { font: { family: 'Prompt' }, boxWidth: 12 } },
  },
  cutout: '55%',
}

onMounted(async () => {
  try {
    const [r1, r2, r3] = await Promise.all([
      api.get('/reports/dashboard'),
      api.get('/reports/quota-vs-allocated'),
      api.get('/reports/by-district'),
    ])
    stats.value = r1.data
    quotaVsAllocated.value = r2.data
    byDistrict.value = r3.data
  } catch (e) {
    console.error('Dashboard load error:', e)
  } finally {
    loading.value = false
  }
})
</script>
