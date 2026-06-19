<template>
  <div class="p-3 sm:p-6 space-y-5">
    <div>
      <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-chart-histogram text-violet-600"></i> แดชบอร์ดตลาด
      </h2>
      <p class="text-sm text-slate-500 mt-0.5">ภาพรวมยอดขาย / คำสั่งซื้อ / สินค้าขายดี</p>
    </div>

    <!-- KPI cards -->
    <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div v-for="n in 6" :key="n" class="box-card h-24 skeleton"></div>
    </div>
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div class="box-card p-4">
        <p class="text-xs text-slate-500">ยอดขายรวม</p>
        <p class="text-xl font-bold text-violet-700 mt-1">฿{{ fmtShort(summary.sales_total) }}</p>
        <p class="text-xs text-slate-400 mt-0.5">ออเดอร์ที่ยืนยันแล้ว</p>
      </div>
      <div class="box-card p-4">
        <p class="text-xs text-slate-500">คำสั่งซื้อทั้งหมด</p>
        <p class="text-xl font-bold text-slate-800 mt-1">{{ summary.orders_total }}</p>
        <p class="text-xs text-slate-400 mt-0.5">รายการ</p>
      </div>
      <div class="box-card p-4">
        <p class="text-xs text-slate-500">รอยืนยันชำระ</p>
        <p class="text-xl font-bold text-amber-600 mt-1">{{ summary.awaiting }}</p>
        <RouterLink to="/app/market/payments" class="text-xs text-violet-600 hover:underline">ไปยืนยัน →</RouterLink>
      </div>
      <div class="box-card p-4">
        <p class="text-xs text-slate-500">รอจัดส่ง</p>
        <p class="text-xl font-bold text-blue-600 mt-1">{{ summary.to_ship }}</p>
        <RouterLink to="/app/market/orders" class="text-xs text-violet-600 hover:underline">ดูคำสั่งซื้อ →</RouterLink>
      </div>
      <div class="box-card p-4">
        <p class="text-xs text-slate-500">สินค้าทั้งหมด</p>
        <p class="text-xl font-bold text-slate-800 mt-1">{{ summary.products_count }}</p>
        <p class="text-xs text-slate-400 mt-0.5">รายการ</p>
      </div>
      <div class="box-card p-4">
        <p class="text-xs text-slate-500">สต็อกใกล้หมด</p>
        <p class="text-xl font-bold mt-1" :class="summary.low_stock > 0 ? 'text-rose-600' : 'text-emerald-600'">{{ summary.low_stock }}</p>
        <p class="text-xs text-slate-400 mt-0.5">น้อยกว่า 10 ชิ้น</p>
      </div>
    </div>

    <!-- ยอดขาย 14 วัน (bar chart CSS) -->
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
        <i class="fi fi-rr-chart-line-up text-violet-600"></i> ยอดขาย 14 วันล่าสุด
      </p>
      <div v-if="salesByDay.length" class="w-full">
        <div class="flex items-end gap-0.5 h-28">
          <div v-for="d in salesByDay" :key="d.date" class="flex-1 flex flex-col items-center group">
            <div class="w-full rounded-t bg-violet-300 group-hover:bg-violet-600 transition-colors relative cursor-default"
              :style="{ height: barH(d.total) + 'px' }">
              <div v-if="d.total>0" class="absolute -top-6 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[9px] px-1.5 py-0.5 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition z-10 pointer-events-none">
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
      <p v-else class="text-sm text-slate-400 text-center py-4">ยังไม่มีข้อมูลยอดขาย</p>
    </div>

    <!-- สินค้าขายดี -->
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
        <i class="fi fi-rr-trophy text-amber-500"></i> สินค้าขายดี Top 5
      </p>
      <div v-if="topProducts.length" class="space-y-2.5">
        <div v-for="(p, i) in topProducts" :key="i" class="flex items-center gap-3">
          <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
            :class="[['bg-amber-400 text-white','bg-slate-300 text-slate-700','bg-orange-300 text-white'][i]||'bg-slate-100 text-slate-500']">
            {{ i + 1 }}
          </span>
          <div class="flex-1 min-w-0">
            <p class="text-sm text-slate-700 truncate">{{ p.product_name }}</p>
            <div class="flex items-center gap-2 mt-0.5">
              <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full bg-violet-400" :style="{ width: topBarPct(p.qty)+'%' }"></div>
              </div>
              <span class="text-xs text-slate-400 shrink-0">{{ p.qty }} ชิ้น</span>
            </div>
          </div>
          <span class="text-sm font-semibold text-fuchsia-700 shrink-0">฿{{ fmtShort(p.revenue) }}</span>
        </div>
      </div>
      <p v-else class="text-sm text-slate-400 text-center py-4">ยังไม่มีข้อมูล</p>
    </div>

    <!-- Quick links -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <RouterLink v-for="q in quickLinks" :key="q.to" :to="q.to" class="box-card hover-lift p-4 flex flex-col items-center gap-2 text-center">
        <i :class="q.icon + ' text-2xl text-violet-600'"></i>
        <span class="text-xs text-slate-600">{{ q.label }}</span>
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
const maxQty = computed(() => Math.max(...topProducts.value.map(p => Number(p.qty)), 1))

function barH(v) { return Math.max(3, Math.round((v / maxSale.value) * 100)) }
function topBarPct(v) { return Math.round((Number(v) / maxQty.value) * 100) }
function fmtShort(v) {
  const n = Number(v)
  if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M'
  if (n >= 1000) return (n / 1000).toFixed(1) + 'K'
  return n.toLocaleString('th-TH', { maximumFractionDigits: 0 })
}

const quickLinks = [
  { to: '/app/market/orders',   icon: 'fi fi-rr-shopping-bag', label: 'คำสั่งซื้อ' },
  { to: '/app/market/payments', icon: 'fi fi-rr-receipt',      label: 'ยืนยันชำระ' },
  { to: '/app/market/reviews',  icon: 'fi fi-rr-star',         label: 'รีวิว' },
  { to: '/app/market/returns',  icon: 'fi fi-rr-undo',         label: 'คืน/เคลม' },
]

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/market/dashboard')
    summary.value = data.summary || summary.value
    salesByDay.value = data.sales_by_day || []
    topProducts.value = data.top_products || []
  } catch { /* ignore */ }
  finally { loading.value = false }
}
onMounted(load)
</script>
