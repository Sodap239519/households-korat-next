<template>
  <div class="min-h-screen bg-gradient-to-br from-violet-50 via-fuchsia-50 to-purple-100 relative overflow-hidden">
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-violet-300/30 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-[28rem] h-[28rem] rounded-full bg-fuchsia-300/30 blur-3xl pointer-events-none"></div>

    <!-- Top bar -->
    <header class="relative h-16 flex items-center justify-between px-6 bg-white/70 backdrop-blur-xl border-b border-violet-200/50 z-10">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-600 text-white flex items-center justify-center shadow">
          <i class="fi fi-rr-leaf"></i>
        </div>
        <div>
          <h1 class="text-base font-bold text-slate-800">Households Korat</h1>
          <p class="text-[11px] text-slate-500">ระบบจัดการครัวเรือนเปราะบาง · นครราชสีมา</p>
        </div>
      </div>
      <Button label="เข้าสู่ระบบ" icon="fi fi-rr-sign-in-alt" iconPos="right" @click="$router.push('/app/login')" />
    </header>

    <main class="relative p-6 max-w-7xl mx-auto space-y-6">
      <!-- Hero -->
      <div class="rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 text-white p-8 shadow-xl shadow-violet-500/30">
        <p class="text-violet-100 text-sm">ภาพรวมข้อมูลสาธารณะ</p>
        <h2 class="text-3xl font-bold mt-2">ติดตามครัวเรือนเปราะบาง · โควต้าเห็ด · จังหวัดนครราชสีมา</h2>
        <p class="text-violet-100 text-sm mt-2">ข้อมูลภาพรวมแบบ aggregate ไม่แสดงข้อมูลรายบุคคล</p>
      </div>

      <div v-if="loading" class="text-center py-16 text-violet-400">
        <i class="fi fi-rr-loading text-4xl animate-spin"></i>
      </div>

      <div v-else class="space-y-5">
        <!-- Stat cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
          <StatCard label="อำเภอ"        :value="data.summary?.total_districts"    icon="fi fi-rr-marker"      tone="violet" />
          <StatCard label="ตำบล"        :value="data.summary?.total_subdistricts" icon="fi fi-rr-map-marker"  tone="indigo" />
          <StatCard label="ครัวเรือน"   :value="data.summary?.total_households"   icon="fi fi-rr-house-blank" tone="amber" />
          <StatCard label="ผ่านเกณฑ์"  :value="data.summary?.passed"             icon="fi fi-rr-check"       tone="emerald" />
          <StatCard label="ไม่ผ่าน"    :value="data.summary?.failed"             icon="fi fi-rr-cross"       tone="rose" />
          <StatCard label="โควต้า"      :value="data.summary?.total_quotas"       icon="fi fi-rr-clipboard-list" tone="purple" />
          <StatCard label="โควต้า (ถุง)" :value="fmt(data.summary?.total_bags_quota)" icon="fi fi-rr-shopping-bag" tone="fuchsia" />
          <StatCard label="รายได้รวม"  :value="fmt(data.summary?.total_revenue, 0)" icon="fi fi-rr-money-bill-wave" tone="cyan" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="box-card p-5">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
              <i class="fi fi-rr-chart-histogram text-violet-600"></i>
              ครัวเรือนรายอำเภอ (Top 20)
            </h3>
            <Chart v-if="hhDistrictChart" type="bar" :data="hhDistrictChart" :options="barOpts" class="h-80" />
          </div>
          <div class="box-card p-5">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
              <i class="fi fi-rr-chart-pie-alt text-fuchsia-600"></i>
              Priority Distribution
            </h3>
            <Chart v-if="priorityChart" type="bar" :data="priorityChart" :options="basicOpts" class="h-80" />
          </div>
        </div>

        <div class="box-card p-5">
          <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
            <i class="fi fi-rr-money-bill-wave text-emerald-600"></i>
            รายได้จากการเพาะเห็ด (รายอำเภอ Top 20)
          </h3>
          <Chart v-if="revChart" type="bar" :data="revChart" :options="basicOpts" class="h-80" />
        </div>
      </div>

      <p class="text-center text-xs text-slate-400 pt-8">
        © {{ new Date().getFullYear() }} Households Korat · ระบบสำหรับเจ้าหน้าที่
        <button @click="$router.push('/app/login')" class="text-violet-600 hover:underline ml-2">เข้าสู่ระบบ</button>
      </p>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineComponent, h } from 'vue'
import api from '../api/index.js'
import Chart from 'primevue/chart'
import Button from 'primevue/button'

const data = ref({})
const loading = ref(true)

function fmt(v, dec = 0) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}

const hhDistrictChart = computed(() => {
  if (!data.value.byDistrict?.length) return null
  return {
    labels: data.value.byDistrict.map(d => d.district),
    datasets: [{
      label: 'จำนวนครัวเรือน',
      data: data.value.byDistrict.map(d => d.households),
      backgroundColor: '#a78bfa',
      borderRadius: 6,
    }],
  }
})

const priorityChart = computed(() => {
  if (!data.value.priorityCounts?.length) return null
  const colors = { A: '#1e293b', B: '#38bdf8', C: '#fcd34d', D: '#f9a8d4' }
  return {
    labels: data.value.priorityCounts.map(p => `Priority ${p.priority}`),
    datasets: [{
      label: 'จำนวน',
      data: data.value.priorityCounts.map(p => p.count),
      backgroundColor: data.value.priorityCounts.map(p => colors[p.priority] || '#a78bfa'),
      borderRadius: 8,
    }],
  }
})

const revChart = computed(() => {
  if (!data.value.revenueByDistrict?.length) return null
  return {
    labels: data.value.revenueByDistrict.map(r => r.district),
    datasets: [{
      label: 'รายได้รวม (บาท)',
      data: data.value.revenueByDistrict.map(r => Number(r.total_revenue || 0)),
      backgroundColor: '#d946ef',
      borderRadius: 6,
    }],
  }
})

const basicOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { x: { grid: { display: false } }, y: { beginAtZero: true } },
}
const barOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 45, minRotation: 30 } }, y: { beginAtZero: true } },
}

const TONES = {
  violet:  'from-violet-500 to-violet-600',  indigo:  'from-indigo-500 to-indigo-600',
  fuchsia: 'from-fuchsia-500 to-fuchsia-600', purple:  'from-purple-500 to-purple-600',
  emerald: 'from-emerald-500 to-emerald-600', amber:   'from-amber-500 to-amber-600',
  rose:    'from-rose-500 to-rose-600',       cyan:    'from-cyan-500 to-cyan-600',
}

const StatCard = defineComponent({
  props: ['label', 'value', 'icon', 'tone'],
  setup(p) {
    return () => h('div', { class: 'box-card p-4 group relative overflow-hidden' }, [
      h('div', { class: `absolute -top-6 -right-6 w-16 h-16 rounded-full bg-gradient-to-br ${TONES[p.tone] || TONES.violet} opacity-10 group-hover:opacity-20 transition` }),
      h('div', { class: 'relative' }, [
        h('div', { class: `inline-flex w-9 h-9 rounded-lg bg-gradient-to-br ${TONES[p.tone] || TONES.violet} text-white items-center justify-center shadow-md mb-2` }, [
          h('i', { class: `${p.icon} text-sm` }),
        ]),
        h('p', { class: 'text-[11px] text-slate-500 font-medium' }, p.label),
        h('p', { class: 'text-lg font-bold text-slate-800' }, p.value ?? '-'),
      ]),
    ])
  },
})

onMounted(async () => {
  try {
    const { data: res } = await api.get('/public/dashboard')
    data.value = res
  } finally {
    loading.value = false
  }
})
</script>
