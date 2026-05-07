<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <!-- Hero -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 text-white p-4 sm:p-6 shadow-lg shadow-violet-500/30">
      <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-12 -left-8 w-56 h-56 rounded-full bg-fuchsia-300/20 blur-3xl pointer-events-none"></div>
      <div class="relative flex items-start sm:items-center justify-between flex-wrap gap-3 sm:gap-4">
        <div class="min-w-0">
          <p class="text-violet-100 text-xs sm:text-sm">ยินดีต้อนรับ 👋</p>
          <h2 class="text-lg sm:text-2xl font-bold mt-1">Households Korat — Dashboard</h2>
          <p class="text-violet-100 text-xs sm:text-sm mt-1">ภาพรวมครัวเรือน · การเพาะเห็ด · การติดตาม · การตลาด</p>
        </div>
        <div class="flex items-center gap-2 bg-white/15 backdrop-blur rounded-lg pl-3 pr-1 py-1 whitespace-nowrap">
          <i class="fi fi-rr-calendar text-white"></i>
          <Select
            v-model="selectedYear"
            :options="yearOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="ทุกปี"
            showClear
            :pt="{
              root:        { class: 'bg-transparent border-0 shadow-none text-white' },
              label:       { class: 'text-white text-xs sm:text-sm py-1 pr-1 pl-1' },
              dropdown:    { class: 'text-white' },
              clearIcon:   { class: 'text-white/80' },
              trigger:     { class: 'text-white' },
            }"
          />
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-violet-400">
      <i class="fi fi-rr-loading text-3xl animate-spin"></i>
    </div>

    <div v-else class="box-card p-2">
      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="0"><span class="flex items-center gap-2"><i class="fi fi-rr-house-blank"></i> ภาพรวมครัวเรือน</span></Tab>
          <Tab value="1"><span class="flex items-center gap-2"><i class="fi fi-rr-mushroom"></i> การเพาะเห็ด</span></Tab>
          <Tab value="2"><span class="flex items-center gap-2"><i class="fi fi-rr-search"></i> การติดตาม</span></Tab>
          <Tab value="3"><span class="flex items-center gap-2"><i class="fi fi-rr-shop"></i> การตลาด</span></Tab>
        </TabList>

        <TabPanels>
          <!-- TAB 1: ภาพรวมครัวเรือน -->
          <TabPanel value="0">
            <div class="space-y-5 pt-2">
              <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <StatCard label="อำเภอทั้งหมด"  :value="o.summary?.total_districts"     icon="fi fi-rr-marker"      tone="violet"  small />
                <StatCard label="ตำบลทั้งหมด"  :value="o.summary?.total_subdistricts" icon="fi fi-rr-map-marker"  tone="indigo"  small />
                <StatCard label="ครัวเรือน"    :value="o.summary?.total_households"   icon="fi fi-rr-house-blank" tone="amber"   small />
                <StatCard label="ผ่านเกณฑ์"    :value="o.summary?.passed"             icon="fi fi-rr-check"       tone="emerald" small />
                <StatCard label="ไม่ผ่าน"     :value="o.summary?.failed"             icon="fi fi-rr-cross"       tone="rose"    small />
                <StatCard label="คะแนนเฉลี่ย" :value="o.summary?.avg_total_score?.toFixed(2)" icon="fi fi-rr-chart-line-up" tone="cyan" small />
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                    <i class="fi fi-rr-chart-histogram text-violet-600"></i> Priority Distribution
                  </h3>
                  <AppChart type="bar"
                    :labels="priorityChart?.labels || []"
                    :series="priorityChart?.series || []"
                    :colors="priorityChart?.colors || []"
                    :legend="false"
                    :height="260" />
                </div>
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                    <i class="fi fi-rr-chart-line-up text-fuchsia-600"></i> รายได้/รายจ่าย/หนี้สิน รายอำเภอ
                  </h3>
                  <AppChart type="area"
                    :labels="incomeChart?.labels || []"
                    :series="incomeChart?.series || []"
                    :colors="['#3b82f6','#f59e0b','#ef4444']"
                    :height="260" />
                </div>
              </div>

              <div class="box-card p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                  <i class="fi fi-rr-chart-histogram text-fuchsia-600"></i> Priority รายอำเภอ
                </h3>
                <AppChart type="stacked-bar"
                  :labels="priorityByDistrictChart?.labels || []"
                  :series="priorityByDistrictChart?.series || []"
                  :colors="['#1e293b','#38bdf8','#fcd34d','#f9a8d4']"
                  :height="320" />
              </div>
            </div>
          </TabPanel>

          <!-- TAB 2: การเพาะเห็ด -->
          <TabPanel value="1">
            <div class="space-y-5 pt-2">
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <HeroCard label="โควต้าทั้งหมด" :value="fmt(m.summary?.total_bags_quota)"     unit="ถุง" icon="fi fi-rr-shopping-bag"   gradient="from-violet-500 via-purple-600 to-fuchsia-600" />
                <HeroCard label="จัดสรรแล้ว"   :value="fmt(m.summary?.total_bags_allocated)" unit="ถุง" icon="fi fi-rr-seedling"      gradient="from-fuchsia-500 via-pink-500 to-rose-500" :hint="allocPct" />
                <HeroCard label="ผลผลิตรวม"   :value="fmt(m.summary?.total_harvest_kg, 2)"  unit="กก." icon="fi fi-rr-mushroom"      gradient="from-emerald-500 via-teal-500 to-cyan-500" />
                <HeroCard label="รายได้รวม"   :value="fmt(m.summary?.total_revenue, 2)"     unit="บาท" icon="fi fi-rr-money-bill-wave" gradient="from-amber-500 via-orange-500 to-rose-500" />
              </div>

              <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <StatCard label="โควต้าอำเภอ" :value="m.summary?.total_quotas"      icon="fi fi-rr-clipboard-list" tone="violet"  small />
                <StatCard label="จัดสรร"      :value="m.summary?.total_allocations" icon="fi fi-rr-seedling"       tone="fuchsia" small />
                <StatCard label="ติดตามผล"    :value="m.summary?.total_followups"   icon="fi fi-rr-list-check"     tone="purple"  small />
                <StatCard label="ขายได้ (กก.)" :value="fmt(m.summary?.total_sold_kg, 2)" icon="fi fi-rr-shop" tone="orange" small />
                <StatCard label="คงเหลือ (ถุง)" :value="fmt((m.summary?.total_bags_quota || 0) - (m.summary?.total_bags_allocated || 0))" icon="fi fi-rr-box-alt" tone="cyan" small />
                <StatCard label="ใช้โควต้า %" :value="allocPct" icon="fi fi-rr-pie-chart" tone="indigo" small />
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                    <i class="fi fi-rr-chart-histogram text-violet-600"></i> โควต้า vs จัดสรร (รายอำเภอ)
                  </h3>
                  <AppChart type="bar"
                    :labels="quotaChart?.labels || []"
                    :series="quotaChart?.series || []"
                    :colors="['#a78bfa','#d946ef']"
                    :height="320" />
                </div>
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                    <i class="fi fi-rr-chart-histogram text-fuchsia-600"></i> สัดส่วนรายได้ (รายอำเภอ)
                  </h3>
                  <AppChart type="bar"
                    :labels="districtChart?.labels || []"
                    :series="districtChart?.series || []"
                    :legend="false"
                    :height="320" />
                </div>
              </div>

              <div v-if="m.byDistrict?.length" class="box-card p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                  <i class="fi fi-rr-trophy text-amber-500"></i> อันดับอำเภอตามรายได้
                </h3>
                <div class="overflow-x-auto">
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
                      <tr v-for="(d, idx) in m.byDistrict.slice(0, 10)" :key="d.district" class="border-b border-violet-50 hover:bg-violet-50/30">
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
            </div>
          </TabPanel>

          <!-- TAB 3: การติดตาม -->
          <TabPanel value="2">
            <div class="space-y-5 pt-2">
              <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <StatCard label="ครั้งติดตามรวม" :value="t.totals?.total_followups"  icon="fi fi-rr-list-check" tone="violet" />
                <StatCard label="เดือนที่มีข้อมูล" :value="t.totals?.months_with_data" icon="fi fi-rr-calendar"   tone="indigo" />
                <StatCard label="ช่องทางขาย"     :value="t.byChannel?.length || 0"   icon="fi fi-rr-truck-side" tone="fuchsia" />
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                    <i class="fi fi-rr-truck-side text-violet-600"></i> การขายตามช่องทาง
                  </h3>
                  <AppChart type="bar"
                    :labels="channelChart?.labels || []"
                    :series="channelChart?.series || []"
                    :colors="['#a78bfa','#d946ef']"
                    :height="280" />
                </div>
                <div class="box-card p-5">
                  <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                    <i class="fi fi-rr-chart-line-up text-fuchsia-600"></i> ผลผลิต/รายได้ รายเดือน
                  </h3>
                  <AppChart type="area"
                    :labels="monthlyChart?.labels || []"
                    :series="monthlyChart?.series || []"
                    :colors="['#10b981','#f59e0b','#ec4899']"
                    :height="280" />
                </div>
              </div>

              <div class="box-card p-4 flex items-center justify-between flex-wrap gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700"><i class="fi fi-rr-search mr-2"></i> ค้นหาครัวเรือนเฉพาะ</p>
                  <p class="text-xs text-slate-500">ใช้รหัสบ้าน/ชื่อ/บัตรประชาชน เพื่อดูประวัติเฉพาะราย</p>
                </div>
                <Button label="เปิดหน้าค้นหา" icon="fi fi-rr-arrow-small-right" iconPos="right" outlined @click="$router.push('/app/tracking')" />
              </div>
            </div>
          </TabPanel>

          <!-- TAB 4: การตลาด -->
          <TabPanel value="3">
            <div class="space-y-5 pt-2">
              <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <StatCard label="วิสาหกิจที่ขาย"     :value="mk.totals?.total_enterprise_count"      icon="fi fi-rr-shop"           tone="fuchsia" />
                <StatCard label="รายได้รวม"          :value="fmt(mk.totals?.total_revenue, 2)"      icon="fi fi-rr-money-bill-wave" tone="amber" />
                <StatCard label="รายได้จากวิสาหกิจ" :value="fmt(mk.totals?.enterprise_revenue, 2)" icon="fi fi-rr-handshake"      tone="violet" />
              </div>

              <div class="box-card p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                  <i class="fi fi-rr-trophy text-amber-500"></i> Top วิสาหกิจ ตามรายได้
                </h3>
                <AppChart type="bar"
                  :labels="enterpriseChart?.labels || []"
                  :series="enterpriseChart?.series || []"
                  :legend="false"
                  :height="320" />
              </div>

              <div v-if="mk.byEnterprise?.length" class="box-card p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                  <i class="fi fi-rr-list text-violet-500"></i> รายชื่อวิสาหกิจ
                </h3>
                <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="text-left text-slate-500 border-b border-violet-100">
                        <th class="px-3 py-2 font-medium">#</th>
                        <th class="px-3 py-2 font-medium">วิสาหกิจ</th>
                        <th class="px-3 py-2 font-medium text-right">ขาย (กก.)</th>
                        <th class="px-3 py-2 font-medium text-right">รายได้ (บาท)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(e, idx) in mk.byEnterprise.slice(0, 15)" :key="e.enterprise_name" class="border-b border-violet-50 hover:bg-violet-50/30">
                        <td class="px-3 py-2.5 text-slate-500">{{ idx + 1 }}</td>
                        <td class="px-3 py-2.5 font-medium text-slate-700">{{ e.enterprise_name }}</td>
                        <td class="px-3 py-2.5 text-right">{{ fmt(e.total_sold_kg, 2) }}</td>
                        <td class="px-3 py-2.5 text-right font-semibold text-emerald-700">{{ fmt(e.total_revenue, 2) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, defineComponent, h } from 'vue'
import api from '../api/index.js'
import AppChart from '../components/AppChart.vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'

const activeTab = ref('0')
const loading = ref(true)
const data = ref({})
const selectedYear = ref(null)
const yearOptions = ref([])

const o  = computed(() => data.value.overview  || {})
const m  = computed(() => data.value.mushroom  || {})
const t  = computed(() => data.value.tracking  || {})
const mk = computed(() => data.value.marketing || {})

function fmt(v, dec = 0) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}

const allocPct = computed(() => {
  const q = Number(m.value.summary?.total_bags_quota)     || 0
  const a = Number(m.value.summary?.total_bags_allocated) || 0
  if (q === 0) return ''
  return Math.round((a / q) * 100) + '%'
})

const totalHarvestSum = computed(() => (m.value.byDistrict || []).reduce((a, x) => a + Number(x.total_harvest_kg || 0), 0))
const totalSoldSum    = computed(() => (m.value.byDistrict || []).reduce((a, x) => a + Number(x.total_sold_kg    || 0), 0))
const totalRevenueSum = computed(() => (m.value.byDistrict || []).reduce((a, x) => a + Number(x.total_revenue    || 0), 0))

// ---- Charts (AppChart-friendly shape) ----
const priorityChart = computed(() => {
  if (!o.value.priorityCounts?.length) return null
  const colors = { A: '#1e293b', B: '#38bdf8', C: '#fcd34d', D: '#f9a8d4' }
  return {
    labels: o.value.priorityCounts.map(p => `Priority ${p.priority}`),
    series: [{ name: 'จำนวน', data: o.value.priorityCounts.map(p => p.count) }],
    colors: o.value.priorityCounts.map(p => colors[p.priority] || '#a78bfa'),
  }
})
const incomeChart = computed(() => {
  if (!o.value.byDistrict?.length) return null
  return {
    labels: o.value.byDistrict.map(d => d.district),
    series: [
      { name: 'รายได้',  data: o.value.byDistrict.map(d => Number(d.total_income)) },
      { name: 'รายจ่าย', data: o.value.byDistrict.map(d => Number(d.total_expense)) },
      { name: 'หนี้สิน', data: o.value.byDistrict.map(d => Number(d.total_debt)) },
    ],
  }
})
const priorityByDistrictChart = computed(() => {
  if (!o.value.priorityByDistrict?.length) return null
  const districts = [...new Set(o.value.priorityByDistrict.map(r => r.district))]
  const priorities = ['A', 'B', 'C', 'D']
  return {
    labels: districts,
    series: priorities.map(p => ({
      name: `Priority ${p}`,
      data: districts.map(d => {
        const row = o.value.priorityByDistrict.find(x => x.district === d && x.priority === p)
        return row ? row.count : 0
      }),
    })),
  }
})

const quotaChart = computed(() => {
  if (!m.value.quotaVsAllocated?.length) return null
  return {
    labels: m.value.quotaVsAllocated.map(r => r.district),
    series: [
      { name: 'โควต้า (ถุง)',     data: m.value.quotaVsAllocated.map(r => Number(r.quota_bags || 0)) },
      { name: 'จัดสรรแล้ว (ถุง)', data: m.value.quotaVsAllocated.map(r => Number(r.allocated_bags || 0)) },
    ],
  }
})
const districtChart = computed(() => {
  if (!m.value.byDistrict?.length) return null
  const palette = ['#7c3aed','#a855f7','#d946ef','#ec4899','#8b5cf6','#6366f1','#3b82f6','#06b6d4','#10b981','#f59e0b']
  return {
    labels: m.value.byDistrict.map(r => r.district),
    series: [{ name: 'รายได้ (บาท)', data: m.value.byDistrict.map(r => Number(r.total_revenue || 0)) }],
    colors: m.value.byDistrict.map((_, i) => palette[i % palette.length]),
  }
})

const channelLabels = { direct: 'ขายตรง', online: 'ออนไลน์', enterprise: 'วิสาหกิจ', market: 'ตลาด' }
const channelChart = computed(() => {
  if (!t.value.byChannel?.length) return null
  return {
    labels: t.value.byChannel.map(c => channelLabels[c.sale_channel] || c.sale_channel),
    series: [
      { name: 'จำนวนครั้ง',     data: t.value.byChannel.map(c => c.count) },
      { name: 'รายได้ (พันบ.)', data: t.value.byChannel.map(c => Number(c.total_revenue || 0) / 1000) },
    ],
  }
})
const monthlyChart = computed(() => {
  if (!t.value.monthly?.length) return null
  return {
    labels: t.value.monthly.map(r => r.month),
    series: [
      { name: 'ผลผลิต (กก.)',   data: t.value.monthly.map(r => Number(r.total_harvest_kg)) },
      { name: 'ขาย (กก.)',       data: t.value.monthly.map(r => Number(r.total_sold_kg)) },
      { name: 'รายได้ (พันบ.)', data: t.value.monthly.map(r => Number(r.total_revenue) / 1000) },
    ],
  }
})

const enterpriseChart = computed(() => {
  if (!mk.value.byEnterprise?.length) return null
  return {
    labels: mk.value.byEnterprise.map(e => e.enterprise_name),
    series: [{ name: 'รายได้ (บาท)', data: mk.value.byEnterprise.map(e => Number(e.total_revenue || 0)) }],
  }
})

// ---- Stat / Hero cards ----
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
      return h('div', { class: `box-card group relative overflow-hidden ${props.small ? 'p-4' : 'p-5'}` }, [
        h('div', { class: `absolute -top-8 -right-8 w-24 h-24 rounded-full bg-gradient-to-br ${tone.bg} opacity-10 group-hover:opacity-20 transition` }),
        h('div', { class: 'relative flex items-start justify-between gap-2' }, [
          h('div', { class: 'min-w-0' }, [
            h('p', { class: 'text-[11px] text-slate-500 font-medium' }, props.label),
            h('p', { class: `mt-1 ${props.small ? 'text-lg' : 'text-2xl'} font-bold text-slate-800 truncate` }, props.value ?? '-'),
          ]),
          h('div', { class: `flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br ${tone.bg} text-white flex items-center justify-center shadow-md ${tone.ring}` }, [
            h('i', { class: `${props.icon} text-base` }),
          ]),
        ]),
      ])
    }
  },
})

const HeroCard = defineComponent({
  props: ['label', 'value', 'unit', 'icon', 'gradient', 'hint'],
  setup(p) {
    return () => h('div', { class: `relative overflow-hidden rounded-2xl bg-gradient-to-br ${p.gradient} text-white p-5 shadow-lg` }, [
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

// ---- Data fetching ----
async function fetchData() {
  loading.value = true
  try {
    const params = {}
    if (selectedYear.value) params.year = selectedYear.value
    const { data: res } = await api.get('/public/dashboard', { params })
    data.value = res
  } finally {
    loading.value = false
  }
}

async function fetchYears() {
  try {
    const { data: res } = await api.get('/public/years')
    yearOptions.value = res.map(y => ({ label: `ปี พ.ศ. ${y}`, value: y }))
  } catch {}
}

watch(selectedYear, fetchData)

onMounted(async () => {
  await fetchYears()
  await fetchData()
})
</script>
