<template>
  <div class="p-6 space-y-5">
    <!-- Hero -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 text-white p-6 shadow-lg shadow-violet-500/30">
      <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-12 -left-8 w-56 h-56 rounded-full bg-fuchsia-300/20 blur-3xl pointer-events-none"></div>
      <div class="relative flex items-center justify-between flex-wrap gap-4">
        <div>
          <p class="text-violet-100 text-sm">ยินดีต้อนรับ 👋</p>
          <h2 class="text-2xl font-bold mt-1">Households Korat — Dashboard</h2>
          <p class="text-violet-100 text-sm mt-1">ภาพรวมครัวเรือน · การเพาะเห็ด · การติดตาม · การตลาด</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="px-4 py-2 rounded-lg bg-white/15 backdrop-blur text-sm">
            <i class="fi fi-rr-calendar mr-1.5"></i>
            ปี พ.ศ. {{ thaiYear }}
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="box-card p-2">
      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="0">
            <span class="flex items-center gap-2">
              <i class="fi fi-rr-house-blank"></i> ภาพรวมครัวเรือน
            </span>
          </Tab>
          <Tab value="1">
            <span class="flex items-center gap-2">
              <i class="fi fi-rr-leaf"></i> การเพาะเห็ด
            </span>
          </Tab>
          <Tab value="2">
            <span class="flex items-center gap-2">
              <i class="fi fi-rr-search"></i> การติดตาม
            </span>
          </Tab>
          <Tab value="3">
            <span class="flex items-center gap-2">
              <i class="fi fi-rr-shop"></i> การตลาด
            </span>
          </Tab>
        </TabList>

        <TabPanels>
          <!-- TAB 1: ภาพรวมครัวเรือน -->
          <TabPanel value="0">
            <div v-if="loadingHh" class="text-center py-12 text-slate-400">
              <i class="fi fi-rr-loading text-3xl animate-spin"></i>
            </div>
            <div v-else class="space-y-5 pt-2">
              <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <StatCard label="อำเภอทั้งหมด"  :value="hh.summary?.total_districts"     icon="fi fi-rr-marker"      tone="violet" small />
                <StatCard label="ตำบลทั้งหมด"  :value="hh.summary?.total_subdistricts" icon="fi fi-rr-map-marker"  tone="indigo" small />
                <StatCard label="ครัวเรือน"    :value="hh.summary?.total_households"   icon="fi fi-rr-house-blank" tone="amber"  small />
                <StatCard label="ผ่านเกณฑ์"    :value="hh.summary?.passed"             icon="fi fi-rr-check"       tone="emerald" small />
                <StatCard label="ไม่ผ่าน"     :value="hh.summary?.failed"             icon="fi fi-rr-cross"       tone="rose"   small />
                <StatCard label="คะแนนเฉลี่ย" :value="hh.summary?.avg_total_score?.toFixed(2)" icon="fi fi-rr-chart-line-up" tone="cyan" small />
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <i class="fi fi-rr-chart-histogram text-violet-600"></i> Priority Distribution
                  </h3>
                  <Chart v-if="priorityChart" type="bar" :data="priorityChart" :options="priorityOpts" class="h-64" />
                  <p v-else class="text-center text-slate-400 py-12 text-sm">ไม่มีข้อมูล Priority</p>
                </div>
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <i class="fi fi-rr-chart-line-up text-fuchsia-600"></i>
                    รายได้ / รายจ่าย / หนี้สิน — รายอำเภอ
                  </h3>
                  <Chart v-if="incomeChart" type="line" :data="incomeChart" :options="incomeOpts" class="h-64" />
                  <p v-else class="text-center text-slate-400 py-12 text-sm">ไม่มีข้อมูล</p>
                </div>
              </div>

              <!-- Priority by District (stacked) -->
              <div class="box-card p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                  <i class="fi fi-rr-chart-histogram text-fuchsia-600"></i>
                  Priority รายอำเภอ
                </h3>
                <Chart v-if="priorityByDistrictChart" type="bar" :data="priorityByDistrictChart" :options="stackedOpts" class="h-72" />
                <p v-else class="text-center text-slate-400 py-12 text-sm">ไม่มีข้อมูล</p>
              </div>
            </div>
          </TabPanel>

          <!-- TAB 2: การเพาะเห็ด -->
          <TabPanel value="1">
            <div v-if="loadingMs" class="text-center py-12 text-slate-400">
              <i class="fi fi-rr-loading text-3xl animate-spin"></i>
            </div>
            <div v-else class="space-y-5 pt-2">
              <!-- Hero metrics: 4 highlighted gradient cards -->
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <HeroCard
                  label="โควต้าทั้งหมด"
                  :value="fmt(ms.total_bags_quota)"
                  unit="ถุง"
                  icon="fi fi-rr-shopping-bag"
                  gradient="from-violet-500 via-purple-600 to-fuchsia-600"
                />
                <HeroCard
                  label="จัดสรรแล้ว"
                  :value="fmt(ms.total_bags_allocated)"
                  unit="ถุง"
                  icon="fi fi-rr-seedling"
                  gradient="from-fuchsia-500 via-pink-500 to-rose-500"
                  :hint="allocPct"
                />
                <HeroCard
                  label="ผลผลิตรวม"
                  :value="fmt(ms.total_harvest_kg, 2)"
                  unit="กก."
                  icon="fi fi-rr-leaf"
                  gradient="from-emerald-500 via-teal-500 to-cyan-500"
                />
                <HeroCard
                  label="รายได้รวม"
                  :value="fmt(ms.total_revenue, 2)"
                  unit="บาท"
                  icon="fi fi-rr-money-bill-wave"
                  gradient="from-amber-500 via-orange-500 to-rose-500"
                />
              </div>

              <!-- Sub stats with subtle look -->
              <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <StatCard label="โควต้าอำเภอ" :value="ms.total_quotas"      icon="fi fi-rr-clipboard-list" tone="violet"  small />
                <StatCard label="ครัวเรือน"  :value="ms.total_households"  icon="fi fi-rr-house-blank"    tone="indigo"  small />
                <StatCard label="จัดสรร"      :value="ms.total_allocations" icon="fi fi-rr-seedling"       tone="fuchsia" small />
                <StatCard label="ติดตามผล"   :value="ms.total_followups"   icon="fi fi-rr-list-check"     tone="purple"  small />
                <StatCard label="ขายได้ (กก.)" :value="fmt(ms.total_sold_kg, 2)" icon="fi fi-rr-shop" tone="orange" small />
                <StatCard label="คงเหลือ (ถุง)" :value="fmt((ms.total_bags_quota || 0) - (ms.total_bags_allocated || 0))" icon="fi fi-rr-box-alt" tone="cyan" small />
              </div>

              <!-- Charts -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <i class="fi fi-rr-chart-histogram text-violet-600"></i>
                    โควต้า vs จัดสรร (รายอำเภอ)
                  </h3>
                  <Chart v-if="quotaChart" type="bar" :data="quotaChart" :options="basicOpts" class="h-72" />
                  <p v-else class="text-center text-slate-400 py-12 text-sm">ยังไม่มีข้อมูล</p>
                </div>
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <i class="fi fi-rr-chart-histogram text-fuchsia-600"></i>
                    สัดส่วนรายได้ (รายอำเภอ)
                  </h3>
                  <Chart v-if="districtBarChart" type="bar" :data="districtBarChart" :options="basicOpts" class="h-72" />
                  <p v-else class="text-center text-slate-400 py-12 text-sm">ยังไม่มีข้อมูล</p>
                </div>
              </div>

              <!-- Top districts ranking table -->
              <div v-if="districtBarChart" class="box-card p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                  <i class="fi fi-rr-trophy text-amber-500"></i>
                  อันดับอำเภอตามรายได้
                </h3>
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-slate-500 border-b border-violet-100">
                      <th class="px-3 py-2 font-medium">#</th>
                      <th class="px-3 py-2 font-medium">อำเภอ</th>
                      <th class="px-3 py-2 font-medium text-right">ผลผลิต (กก.)</th>
                      <th class="px-3 py-2 font-medium text-right">ขาย (กก.)</th>
                      <th class="px-3 py-2 font-medium text-right">รายได้ (บาท)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(d, idx) in byDistrictData.slice(0, 10)" :key="d.district" class="border-b border-violet-50 hover:bg-violet-50/30">
                      <td class="px-3 py-2.5">
                        <span :class="['inline-flex items-center justify-center w-7 h-7 rounded-full font-bold text-xs',
                          idx === 0 ? 'bg-amber-100 text-amber-700' :
                          idx === 1 ? 'bg-slate-100 text-slate-700' :
                          idx === 2 ? 'bg-orange-100 text-orange-700' : 'bg-violet-50 text-violet-600']">
                          {{ idx + 1 }}
                        </span>
                      </td>
                      <td class="px-3 py-2.5 font-medium text-slate-700">{{ d.district }}</td>
                      <td class="px-3 py-2.5 text-right">{{ fmt(d.total_harvest_kg, 2) }}</td>
                      <td class="px-3 py-2.5 text-right">{{ fmt(d.total_sold_kg, 2) }}</td>
                      <td class="px-3 py-2.5 text-right font-semibold text-emerald-700">{{ fmt(d.total_revenue, 2) }}</td>
                    </tr>
                  </tbody>
                  <tfoot class="bg-violet-50/50">
                    <tr class="font-semibold">
                      <td class="px-3 py-2"></td>
                      <td class="px-3 py-2 text-violet-700">รวมทั้งหมด</td>
                      <td class="px-3 py-2 text-right text-slate-700">{{ fmt(totalHarvestSum, 2) }}</td>
                      <td class="px-3 py-2 text-right text-slate-700">{{ fmt(totalSoldSum, 2) }}</td>
                      <td class="px-3 py-2 text-right text-emerald-700">{{ fmt(totalRevenueSum, 2) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </TabPanel>

          <!-- TAB 3: การติดตาม -->
          <TabPanel value="2">
            <div class="text-center py-16">
              <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-violet-100 to-fuchsia-100 text-violet-600 text-3xl mb-4">
                <i class="fi fi-rr-search"></i>
              </div>
              <h3 class="text-lg font-bold text-slate-700">สถิติการติดตามครัวเรือน</h3>
              <p class="text-sm text-slate-500 mt-1">กราฟ/ตารางสถานะการติดตามจะมาในรอบถัดไป</p>
              <Button label="ไปหน้าค้นหา" icon="fi fi-rr-arrow-small-right" iconPos="right" class="mt-4" @click="$router.push('/app/tracking')" />
            </div>
          </TabPanel>

          <!-- TAB 4: การตลาด -->
          <TabPanel value="3">
            <div class="text-center py-16">
              <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-violet-100 to-fuchsia-100 text-fuchsia-600 text-3xl mb-4">
                <i class="fi fi-rr-shop"></i>
              </div>
              <h3 class="text-lg font-bold text-slate-700">ภาพรวมการตลาด</h3>
              <p class="text-sm text-slate-500 mt-1">ลูกค้า · ราคาตลาด · แคมเปญ · ออเดอร์ — กำลังพัฒนา</p>
              <Button label="ดูโมดูลการตลาด" icon="fi fi-rr-arrow-small-right" iconPos="right" class="mt-4" @click="$router.push('/app/marketing')" />
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineComponent, h } from 'vue'
import api from '../api/index.js'
import Chart from 'primevue/chart'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Button from 'primevue/button'

const activeTab = ref('0')
const thaiYear = computed(() => new Date().getFullYear() + 543)

// ===== Tab 1: Households =====
const hh = ref({})
const loadingHh = ref(true)
const priorityChart = ref(null)
const incomeChart = ref(null)
const priorityByDistrictChart = ref(null)

async function loadHouseholdsOverview() {
  loadingHh.value = true
  try {
    const { data } = await api.get('/reports/households-overview')
    hh.value = data
    // Priority chart
    if (data.priorityCounts?.length) {
      const colors = { A: '#1e293b', B: '#38bdf8', C: '#fcd34d', D: '#f9a8d4' }
      priorityChart.value = {
        labels: data.priorityCounts.map(p => `Priority ${p.priority}`),
        datasets: [{
          label: 'จำนวนครัวเรือน',
          data: data.priorityCounts.map(p => p.count),
          backgroundColor: data.priorityCounts.map(p => colors[p.priority] || '#a78bfa'),
          borderRadius: 8,
        }],
      }
    }
    // Income/Expense/Debt by district
    if (data.byDistrict?.length) {
      incomeChart.value = {
        labels: data.byDistrict.map(d => d.district),
        datasets: [
          { label: 'รายได้ (รวม)', data: data.byDistrict.map(d => Number(d.total_income)),  borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.35, fill: true },
          { label: 'รายจ่าย (รวม)', data: data.byDistrict.map(d => Number(d.total_expense)), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', tension: 0.35, fill: true },
          { label: 'หนี้สิน (รวม)', data: data.byDistrict.map(d => Number(d.total_debt)),    borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', tension: 0.35, fill: true },
        ],
      }
    }
    // Priority by District (stacked)
    if (data.priorityByDistrict?.length) {
      const districtSet = [...new Set(data.priorityByDistrict.map(r => r.district))]
      const colors = { A: '#1e293b', B: '#38bdf8', C: '#fcd34d', D: '#f9a8d4' }
      const priorities = ['A', 'B', 'C', 'D']
      priorityByDistrictChart.value = {
        labels: districtSet,
        datasets: priorities.map(p => ({
          label: `Priority ${p}`,
          backgroundColor: colors[p],
          stack: 'priority',
          data: districtSet.map(d => {
            const row = data.priorityByDistrict.find(x => x.district === d && x.priority === p)
            return row ? row.count : 0
          }),
        })),
      }
    }
  } catch (e) {
    console.error(e)
  } finally {
    loadingHh.value = false
  }
}

const priorityOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { x: { grid: { display: false } }, y: { beginAtZero: true } },
}
const incomeOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
  interaction: { mode: 'index', intersect: false },
  scales: { y: { beginAtZero: true, ticks: { callback: v => Number(v).toLocaleString() } } },
}
const stackedOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
  scales: {
    x: { stacked: true, grid: { display: false }, ticks: { autoSkip: false, maxRotation: 45, minRotation: 30 } },
    y: { stacked: true, beginAtZero: true },
  },
}

// ===== Tab 2: Mushroom =====
const ms = ref({})
const loadingMs = ref(true)
const quotaChart = ref(null)
const districtBarChart = ref(null)
const byDistrictData = ref([])

const totalHarvestSum = computed(() => byDistrictData.value.reduce((a, x) => a + Number(x.total_harvest_kg || 0), 0))
const totalSoldSum    = computed(() => byDistrictData.value.reduce((a, x) => a + Number(x.total_sold_kg    || 0), 0))
const totalRevenueSum = computed(() => byDistrictData.value.reduce((a, x) => a + Number(x.total_revenue    || 0), 0))

const allocPct = computed(() => {
  const q = Number(ms.value.total_bags_quota) || 0
  const a = Number(ms.value.total_bags_allocated) || 0
  if (q === 0) return ''
  return Math.round((a / q) * 100) + '% ของโควต้า'
})

async function loadMushroom() {
  loadingMs.value = true
  try {
    const [r1, r2, r3] = await Promise.all([
      api.get('/reports/dashboard'),
      api.get('/reports/quota-vs-allocated'),
      api.get('/reports/by-district'),
    ])
    ms.value = r1.data
    if (r2.data?.length) {
      quotaChart.value = {
        labels: r2.data.map(r => r.district),
        datasets: [
          { label: 'โควต้า (ถุง)',     data: r2.data.map(r => Number(r.quota_bags || 0)),     backgroundColor: '#a78bfa', borderRadius: 6 },
          { label: 'จัดสรรแล้ว (ถุง)', data: r2.data.map(r => Number(r.allocated_bags || 0)), backgroundColor: '#d946ef', borderRadius: 6 },
        ],
      }
    }
    if (r3.data?.length) {
      byDistrictData.value = r3.data
      const palette = ['#7c3aed','#a855f7','#d946ef','#ec4899','#8b5cf6','#6366f1','#3b82f6','#06b6d4','#10b981','#f59e0b']
      districtBarChart.value = {
        labels: r3.data.map(r => r.district),
        datasets: [{
          label: 'รายได้ (บาท)',
          data: r3.data.map(r => Number(r.total_revenue || 0)),
          backgroundColor: r3.data.map((_, i) => palette[i % palette.length]),
          borderRadius: 6,
        }],
      }
    }
  } finally {
    loadingMs.value = false
  }
}

const basicOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
  scales: {
    x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 45, minRotation: 30 } },
    y: { beginAtZero: true, ticks: { callback: v => Number(v).toLocaleString() } },
  },
}

function fmt(v, dec = 0) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}

// ===== StatCard =====
const TONES = {
  violet:  { bg: 'from-violet-500 to-violet-600',   ring: 'shadow-violet-500/25' },
  indigo:  { bg: 'from-indigo-500 to-indigo-600',   ring: 'shadow-indigo-500/25' },
  fuchsia: { bg: 'from-fuchsia-500 to-fuchsia-600', ring: 'shadow-fuchsia-500/25' },
  purple:  { bg: 'from-purple-500 to-purple-600',   ring: 'shadow-purple-500/25' },
  emerald: { bg: 'from-emerald-500 to-emerald-600', ring: 'shadow-emerald-500/25' },
  lime:    { bg: 'from-lime-500 to-lime-600',       ring: 'shadow-lime-500/25' },
  orange:  { bg: 'from-orange-500 to-orange-600',   ring: 'shadow-orange-500/25' },
  amber:   { bg: 'from-amber-500 to-amber-600',     ring: 'shadow-amber-500/25' },
  rose:    { bg: 'from-rose-500 to-rose-600',       ring: 'shadow-rose-500/25' },
  cyan:    { bg: 'from-cyan-500 to-cyan-600',       ring: 'shadow-cyan-500/25' },
}

const StatCard = defineComponent({
  props: ['label', 'value', 'icon', 'tone', 'small'],
  setup(props) {
    return () => {
      const tone = TONES[props.tone] || TONES.violet
      return h('div', {
        class: `box-card group relative overflow-hidden ${props.small ? 'p-4' : 'p-5'}`,
      }, [
        h('div', { class: `absolute -top-8 -right-8 w-24 h-24 rounded-full bg-gradient-to-br ${tone.bg} opacity-10 group-hover:opacity-20 transition` }),
        h('div', { class: 'relative flex items-start justify-between gap-2' }, [
          h('div', { class: 'min-w-0' }, [
            h('p', { class: 'text-[11px] text-slate-500 font-medium' }, props.label),
            h('p', { class: `mt-1 ${props.small ? 'text-lg' : 'text-2xl'} font-bold text-slate-800 truncate` }, props.value ?? '-'),
          ]),
          h('div', {
            class: `flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br ${tone.bg} text-white flex items-center justify-center shadow-md ${tone.ring}`,
          }, [h('i', { class: `${props.icon} text-base` })]),
        ]),
      ])
    }
  },
})

const HeroCard = defineComponent({
  props: ['label', 'value', 'unit', 'icon', 'gradient', 'hint'],
  setup(p) {
    return () => h('div', {
      class: `relative overflow-hidden rounded-2xl bg-gradient-to-br ${p.gradient} text-white p-5 shadow-lg`,
    }, [
      h('div', { class: 'absolute -top-8 -right-8 w-32 h-32 rounded-full bg-white/15 blur-2xl pointer-events-none' }),
      h('div', { class: 'relative' }, [
        h('div', { class: 'flex items-center justify-between' }, [
          h('p', { class: 'text-white/90 text-xs font-medium' }, p.label),
          h('div', { class: 'w-9 h-9 rounded-lg bg-white/20 backdrop-blur flex items-center justify-center' }, [
            h('i', { class: `${p.icon} text-base` }),
          ]),
        ]),
        h('p', { class: 'text-3xl font-bold mt-3 tracking-tight' }, p.value ?? '-'),
        h('p', { class: 'text-xs text-white/80 mt-0.5' }, p.unit),
        p.hint ? h('p', { class: 'text-[11px] text-white/70 mt-1 inline-block px-2 py-0.5 rounded bg-white/15 backdrop-blur' }, p.hint) : null,
      ]),
    ])
  },
})

onMounted(() => {
  loadHouseholdsOverview()
  loadMushroom()
})
</script>
