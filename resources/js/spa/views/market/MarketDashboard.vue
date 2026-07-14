<template>
  <div class="p-3 sm:p-5 space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <div>
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-chart-histogram text-violet-600"></i> แดชบอร์ดตลาด
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">ยอดขาย / ออเดอร์ / สินค้า</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <!-- group filter สำหรับ admin/superadmin -->
        <div v-if="groups.length > 1" class="relative">
          <select v-model="selectedGroupId" @change="load"
            class="text-xs border border-slate-200 rounded-xl px-3 py-2 pr-7 bg-white text-slate-700 focus:outline-none focus:border-violet-400 appearance-none cursor-pointer">
            <option value="">ทุกกลุ่ม</option>
            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
          </select>
          <i class="fi fi-rr-angle-small-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
        </div>
        <!-- preset days filter -->
        <div class="relative">
          <select v-model="selectedDays" @change="onPresetChange"
            class="text-xs border border-slate-200 rounded-xl px-3 py-2 pr-7 bg-white text-slate-700 focus:outline-none focus:border-violet-400 appearance-none cursor-pointer"
            :class="dateRange ? 'opacity-40' : ''">
            <option :value="7">7 วัน</option>
            <option :value="14">14 วัน</option>
            <option :value="30">30 วัน</option>
            <option :value="90">90 วัน</option>
          </select>
          <i class="fi fi-rr-angle-small-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
        </div>
        <!-- calendar date range picker -->
        <div class="relative flex items-center">
          <DatePicker
            v-model="dateRange"
            selectionMode="range"
            dateFormat="dd/mm/yy"
            :maxDate="new Date()"
            showIcon
            iconDisplay="button"
            placeholder="ปฏิทิน"
            :numberOfMonths="1"
            inputClass="text-xs border border-slate-200 rounded-xl px-3 py-2 bg-white text-slate-700 focus:outline-none focus:border-violet-400 w-36 h-9"
            :pt="{
              inputIconButton: { class: 'text-violet-500 hover:text-violet-700' },
              dropdown: { class: 'text-xs' },
            }"
            @update:modelValue="onDateRangeChange"
          />
          <button v-if="dateRange" @click="clearDateRange"
            class="absolute -right-1 -top-1 w-4 h-4 rounded-full bg-rose-500 text-white flex items-center justify-center text-[9px] hover:bg-rose-600 transition z-10">
            <i class="fi fi-rr-cross-small leading-none"></i>
          </button>
        </div>
        <button @click="load" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-violet-100 text-slate-500 hover:text-violet-600 flex items-center justify-center transition">
          <i class="fi fi-rr-refresh text-sm"></i>
        </button>
      </div>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <template v-if="loading">
        <div v-for="n in 6" :key="n" class="box-card h-24 skeleton"></div>
      </template>
      <template v-else>
        <!-- ยอดขาย -->
        <RouterLink to="/app/market/orders?status_group=completed"
          class="box-card p-4 hover-lift cursor-pointer group">
          <div class="flex items-start justify-between">
            <p class="text-xs text-slate-500">ยอดขายรวม</p>
            <div class="w-8 h-8 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center">
              <i class="fi fi-rr-coins text-sm"></i>
            </div>
          </div>
          <p class="text-2xl font-bold text-violet-700 mt-2">฿{{ fmtShort(summary.sales_total) }}</p>
          <p class="text-[11px] text-slate-400 mt-0.5">ออเดอร์ที่ยืนยันแล้ว</p>
        </RouterLink>
        <!-- ออเดอร์ -->
        <RouterLink to="/app/market/orders"
          class="box-card p-4 hover-lift cursor-pointer">
          <div class="flex items-start justify-between">
            <p class="text-xs text-slate-500">คำสั่งซื้อ</p>
            <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
              <i class="fi fi-rr-shopping-bag text-sm"></i>
            </div>
          </div>
          <p class="text-2xl font-bold text-slate-800 mt-2">{{ summary.orders_total }}</p>
          <p class="text-[11px] text-slate-400 mt-0.5">รายการทั้งหมด</p>
        </RouterLink>
        <!-- รอยืนยันชำระ -->
        <RouterLink to="/app/market/payments"
          class="box-card p-4 hover-lift cursor-pointer">
          <div class="flex items-start justify-between">
            <p class="text-xs text-slate-500">รอยืนยันชำระ</p>
            <div class="w-8 h-8 rounded-xl flex items-center justify-center"
              :class="summary.awaiting > 0 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-400'">
              <i class="fi fi-rr-receipt text-sm"></i>
            </div>
          </div>
          <p class="text-2xl font-bold mt-2" :class="summary.awaiting > 0 ? 'text-amber-600' : 'text-slate-700'">{{ summary.awaiting }}</p>
          <p class="text-[11px] text-violet-600 mt-0.5 hover:underline">ไปยืนยัน →</p>
        </RouterLink>
        <!-- รอจัดส่ง -->
        <RouterLink to="/app/market/shipments"
          class="box-card p-4 hover-lift cursor-pointer">
          <div class="flex items-start justify-between">
            <p class="text-xs text-slate-500">รอจัดส่ง</p>
            <div class="w-8 h-8 rounded-xl flex items-center justify-center"
              :class="summary.to_ship > 0 ? 'bg-sky-100 text-sky-600' : 'bg-slate-100 text-slate-400'">
              <i class="fi fi-rr-truck-side text-sm"></i>
            </div>
          </div>
          <p class="text-2xl font-bold mt-2" :class="summary.to_ship > 0 ? 'text-sky-600' : 'text-slate-700'">{{ summary.to_ship }}</p>
          <p class="text-[11px] text-violet-600 mt-0.5 hover:underline">ดูคำสั่งซื้อ →</p>
        </RouterLink>
        <!-- สินค้า -->
        <RouterLink to="/app/market/products"
          class="box-card p-4 hover-lift cursor-pointer">
          <div class="flex items-start justify-between">
            <p class="text-xs text-slate-500">สินค้า</p>
            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
              <i class="fi fi-rr-box-open text-sm"></i>
            </div>
          </div>
          <p class="text-2xl font-bold text-slate-800 mt-2">{{ summary.products_count }}</p>
          <p class="text-[11px] text-slate-400 mt-0.5">รายการสินค้า</p>
        </RouterLink>
        <!-- สต็อกใกล้หมด -->
        <RouterLink to="/app/market/products?status=low_stock"
          class="box-card p-4 hover-lift cursor-pointer">
          <div class="flex items-start justify-between">
            <p class="text-xs text-slate-500">สต็อกใกล้หมด</p>
            <div class="w-8 h-8 rounded-xl flex items-center justify-center"
              :class="summary.low_stock > 0 ? 'bg-rose-100 text-rose-500' : 'bg-emerald-100 text-emerald-600'">
              <i class="fi fi-rr-exclamation text-sm"></i>
            </div>
          </div>
          <p class="text-2xl font-bold mt-2" :class="summary.low_stock > 0 ? 'text-rose-600' : 'text-emerald-600'">{{ summary.low_stock }}</p>
          <p class="text-[11px] text-slate-400 mt-0.5">น้อยกว่า 10 ชิ้น</p>
        </RouterLink>
      </template>
    </div>

    <!-- ยอดขาย N วัน -->
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
        <i class="fi fi-rr-chart-line-up text-violet-600"></i> {{ chartTitle }}
      </p>
      <div v-if="salesByDay.length">
        <!-- chart area: bars + SVG line overlay -->
        <div class="relative" style="height: 128px;">
          <!-- bars + number labels -->
          <div class="absolute bottom-0 left-0 right-0 flex items-end gap-px" style="height: 108px;">
            <div v-for="(d, i) in salesByDay" :key="d.date"
              class="flex-1 relative flex flex-col justify-end group cursor-default">
              <!-- bar -->
              <div class="w-full rounded-t-sm transition-colors"
                :class="d.total > 0 ? 'bg-violet-300 group-hover:bg-violet-500' : 'bg-slate-100'"
                :style="{ height: barH(d.total) + 'px' }">
              </div>
              <!-- number label always visible above bar -->
              <div v-if="d.total > 0"
                class="absolute left-0 right-0 text-center font-semibold text-violet-600 pointer-events-none whitespace-nowrap overflow-hidden"
                :style="{ bottom: barH(d.total) + 3 + 'px', fontSize: labelFontSize + 'px', lineHeight: '1' }">
                {{ fmtShort(d.total) }}
              </div>
            </div>
          </div>
          <!-- SVG connecting line -->
          <svg class="absolute bottom-0 left-0 w-full pointer-events-none" height="108" viewBox="0 0 100 108" preserveAspectRatio="none">
            <polyline
              :points="polylinePoints"
              fill="none"
              stroke="#7c3aed"
              stroke-width="0.6"
              stroke-linejoin="round"
              stroke-linecap="round"
              opacity="0.55"
            />
          </svg>
        </div>
        <!-- x-axis labels -->
        <div class="flex gap-px mt-1.5">
          <div v-for="(d, i) in salesByDay" :key="d.date"
            class="flex-1 text-center text-[8px] text-slate-400 truncate">
            {{ showDateLabel(i) ? d.date : '' }}
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-slate-400 text-center py-6">ยังไม่มีข้อมูลยอดขาย</p>
    </div>

    <!-- สินค้าขายดี -->
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
        <i class="fi fi-rr-trophy text-amber-500"></i> สินค้าขายดี Top 5
      </p>
      <div v-if="topProducts.length" class="space-y-3">
        <div v-for="(p, i) in topProducts" :key="i" class="flex items-center gap-3">
          <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 shadow-sm"
            :class="[
              'bg-amber-400 text-white',
              'bg-slate-300 text-slate-700',
              'bg-orange-300 text-white',
              'bg-slate-100 text-slate-500',
              'bg-slate-100 text-slate-500',
            ][i]">{{ i + 1 }}</span>
          <div class="flex-1 min-w-0">
            <p class="text-sm text-slate-700 truncate">{{ p.product_name }}</p>
            <div class="flex items-center gap-2 mt-1">
              <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full bg-violet-400 transition-all" :style="{ width: topBarPct(p.qty)+'%' }"></div>
              </div>
              <span class="text-xs text-slate-400 shrink-0">{{ p.qty }} ชิ้น</span>
            </div>
          </div>
          <span class="text-sm font-bold text-fuchsia-700 shrink-0">฿{{ fmtShort(p.revenue) }}</span>
        </div>
      </div>
      <p v-else class="text-sm text-slate-400 text-center py-6">ยังไม่มีข้อมูล</p>
    </div>

    <!-- Quick links -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <RouterLink v-for="q in quickLinks" :key="q.to" :to="q.to"
        class="box-card hover-lift p-4 flex flex-col items-center gap-2.5 text-center group">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition"
          :class="[q.bg, 'group-hover:scale-110']">
          <i :class="q.icon + ' text-xl ' + q.color"></i>
        </div>
        <span class="text-xs font-medium text-slate-600">{{ q.label }}</span>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import DatePicker from 'primevue/datepicker'
import api from '../../api/index.js'

const loading         = ref(true)
const summary         = ref({ sales_total: 0, orders_total: 0, awaiting: 0, to_ship: 0, products_count: 0, low_stock: 0 })
const salesByDay      = ref([])
const topProducts     = ref([])
const groups          = ref([])
const selectedGroupId = ref('')
const selectedDays    = ref(14)
const dateRange       = ref(null) // [Date, Date] เมื่อเลือกจาก date picker

const maxSale = computed(() => Math.max(...salesByDay.value.map(d => d.total), 1))
const maxQty  = computed(() => Math.max(...topProducts.value.map(p => Number(p.qty)), 1))

// chart: bar height (max 100px ใน container 108px)
function barH(v) { return Math.max(3, Math.round((v / maxSale.value) * 100)) }
function topBarPct(v) { return Math.round((Number(v) / maxQty.value) * 100) }
function fmtShort(v) {
  const n = Number(v)
  if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M'
  if (n >= 1000) return (n / 1000).toFixed(1) + 'K'
  return n.toLocaleString('th-TH', { maximumFractionDigits: 0 })
}

// แสดง label ทุก N แท่งตามจำนวนวัน
function showDateLabel(i) {
  const step = selectedDays.value <= 14 ? 2 : selectedDays.value <= 30 ? 4 : 9
  return i % step === 0
}

// font size ของ label เล็กลงเมื่อมีแท่งเยอะ
const labelFontSize = computed(() => {
  const n = salesByDay.value.length
  if (n <= 14) return 7
  if (n <= 30) return 6
  return 5
})

// จุดสำหรับ SVG polyline (viewBox 0 0 100 108)
const polylinePoints = computed(() => {
  const n = salesByDay.value.length
  if (!n) return ''
  return salesByDay.value
    .map((d, i) => {
      const x = (i + 0.5) / n * 100
      const y = 108 - barH(d.total)
      return `${x.toFixed(2)},${y}`
    })
    .join(' ')
})

const quickLinks = [
  { to: '/app/market/orders',    icon: 'fi fi-rr-shopping-bag', label: 'คำสั่งซื้อ',    bg: 'bg-violet-100', color: 'text-violet-600' },
  { to: '/app/market/payments',  icon: 'fi fi-rr-receipt',      label: 'ยืนยันชำระ',   bg: 'bg-amber-100',  color: 'text-amber-600'  },
  { to: '/app/market/reviews',   icon: 'fi fi-rr-star',         label: 'รีวิว',        bg: 'bg-yellow-100', color: 'text-yellow-600' },
  { to: '/app/market/returns',   icon: 'fi fi-rr-undo',         label: 'คืน/เคลม',    bg: 'bg-rose-100',   color: 'text-rose-500'   },
]

// label ช่วงวันที่ที่เลือกอยู่ (สำหรับแสดงในชื่อ chart)
const dateRangeLabel = computed(() => {
  const r = dateRange.value
  if (!r?.[0] || !r?.[1]) return null
  const fmt = (d) => d.toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: '2-digit' })
  return `${fmt(r[0])} – ${fmt(r[1])}`
})

const chartTitle = computed(() =>
  dateRangeLabel.value
    ? `ยอดขาย ${dateRangeLabel.value}`
    : `ยอดขาย ${selectedDays.value} วันล่าสุด`
)

function onPresetChange() {
  dateRange.value = null // ล้าง custom range เมื่อเลือก preset
  load()
}

function onDateRangeChange(range) {
  if (range && range[0] && range[1]) {
    load() // โหลดเมื่อเลือกครบ 2 วัน
  }
}

function clearDateRange() {
  dateRange.value = null
  load()
}

async function load() {
  loading.value = true
  try {
    const params = {}
    if (selectedGroupId.value) params.group_id = selectedGroupId.value
    const r = dateRange.value
    if (r?.[0] && r?.[1]) {
      params.from_date = r[0].toISOString().split('T')[0]
      params.to_date   = r[1].toISOString().split('T')[0]
    } else {
      params.days = selectedDays.value
    }
    const { data } = await api.get('/market/dashboard', { params })
    summary.value     = data.summary      || summary.value
    salesByDay.value  = data.sales_by_day || []
    topProducts.value = data.top_products || []
  } catch { /* ignore */ }
  finally { loading.value = false }
}

async function loadGroups() {
  try {
    const { data } = await api.get('/market/seller-groups', { params: { per_page: 100 } })
    groups.value = data.data ?? data
  } catch { /* ถ้าไม่ใช่ admin ก็ไม่มี groups */ }
}

onMounted(() => { loadGroups(); load() })
</script>
