<template>
  <div class="p-3 sm:p-5 space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-chart-histogram text-violet-600"></i> แดชบอร์ดตลาด
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">ยอดขาย / ออเดอร์ / สินค้า</p>
      </div>
      <button @click="load" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-violet-100 text-slate-500 hover:text-violet-600 flex items-center justify-center transition">
        <i class="fi fi-rr-refresh text-sm"></i>
      </button>
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

    <!-- ยอดขาย 14 วัน -->
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
        <i class="fi fi-rr-chart-line-up text-violet-600"></i> ยอดขาย 14 วันล่าสุด
      </p>
      <div v-if="salesByDay.length">
        <div class="flex items-end gap-0.5 h-28">
          <div v-for="d in salesByDay" :key="d.date" class="flex-1 flex flex-col items-center group">
            <div class="w-full rounded-t-sm transition-colors relative cursor-default"
              :class="d.total > 0 ? 'bg-violet-300 group-hover:bg-violet-600' : 'bg-slate-100'"
              :style="{ height: barH(d.total) + 'px' }">
              <div v-if="d.total > 0"
                class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[9px] px-1.5 py-0.5 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition z-10 pointer-events-none shadow">
                ฿{{ fmtShort(d.total) }}
              </div>
            </div>
          </div>
        </div>
        <div class="flex gap-0.5 mt-1.5">
          <div v-for="(d, i) in salesByDay" :key="d.date" class="flex-1 text-center text-[8px] text-slate-400 truncate">
            {{ i % 2 === 0 ? d.date : '' }}
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
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../../api/index.js'

const loading = ref(true)
const summary = ref({ sales_total: 0, orders_total: 0, awaiting: 0, to_ship: 0, products_count: 0, low_stock: 0 })
const salesByDay = ref([])
const topProducts = ref([])

const maxSale = computed(() => Math.max(...salesByDay.value.map(d => d.total), 1))
const maxQty  = computed(() => Math.max(...topProducts.value.map(p => Number(p.qty)), 1))

function barH(v) { return Math.max(4, Math.round((v / maxSale.value) * 100)) }
function topBarPct(v) { return Math.round((Number(v) / maxQty.value) * 100) }
function fmtShort(v) {
  const n = Number(v)
  if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M'
  if (n >= 1000) return (n / 1000).toFixed(1) + 'K'
  return n.toLocaleString('th-TH', { maximumFractionDigits: 0 })
}

const quickLinks = [
  { to: '/app/market/orders',    icon: 'fi fi-rr-shopping-bag', label: 'คำสั่งซื้อ',    bg: 'bg-violet-100', color: 'text-violet-600' },
  { to: '/app/market/payments',  icon: 'fi fi-rr-receipt',      label: 'ยืนยันชำระ',   bg: 'bg-amber-100',  color: 'text-amber-600'  },
  { to: '/app/market/reviews',   icon: 'fi fi-rr-star',         label: 'รีวิว',        bg: 'bg-yellow-100', color: 'text-yellow-600' },
  { to: '/app/market/returns',   icon: 'fi fi-rr-undo',         label: 'คืน/เคลม',    bg: 'bg-rose-100',   color: 'text-rose-500'   },
]

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/market/dashboard')
    summary.value    = data.summary    || summary.value
    salesByDay.value = data.sales_by_day || []
    topProducts.value = data.top_products || []
  } catch { /* ignore */ }
  finally { loading.value = false }
}
onMounted(load)
</script>
