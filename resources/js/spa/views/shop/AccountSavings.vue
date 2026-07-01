<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-4">
      <RouterLink to="/shop/account" class="p-2 -ml-2 rounded-full hover:bg-slate-100 text-slate-500 transition">
        <i class="fi fi-rr-arrow-small-left text-lg"></i>
      </RouterLink>
      <div>
        <h1 class="text-lg font-bold text-slate-800">ประหยัดจากส่วนลด</h1>
        <p class="text-xs text-slate-400">คำสั่งซื้อที่ได้รับส่วนลด (สำเร็จแล้ว)</p>
      </div>
    </div>

    <!-- Summary banner -->
    <div class="rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white p-5 mb-4 shadow-lg shadow-emerald-200">
      <p class="text-sm text-white/80 mb-1">ประหยัดไปทั้งหมด</p>
      <p class="text-3xl font-bold">฿{{ fmt(totalSaved) }}</p>
      <p class="text-xs text-white/70 mt-1">จาก {{ meta.total ?? 0 }} คำสั่งซื้อที่มีส่วนลด</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 3" :key="n" class="box-card h-28 skeleton rounded-2xl"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="!orders.length" class="box-card p-12 text-center text-slate-400 rounded-2xl">
      <i class="fi fi-rr-piggy-bank text-4xl"></i>
      <p class="mt-3 font-medium">ยังไม่มีคำสั่งซื้อที่ได้รับส่วนลด</p>
    </div>

    <!-- Order list -->
    <div v-else class="space-y-3">
      <RouterLink
        v-for="o in orders"
        :key="o.id"
        :to="`/shop/account/orders/${o.order_no}`"
        class="box-card rounded-2xl overflow-hidden block hover:shadow-md transition-shadow">

        <!-- Shop + date row -->
        <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-50">
          <div class="flex items-center gap-1.5 text-sm font-semibold text-violet-700">
            <i class="fi fi-rr-shop text-xs"></i> {{ o.seller_group?.name }}
          </div>
          <span class="text-xs text-slate-400">{{ fmtDate(o.created_at) }}</span>
        </div>

        <!-- Items snapshot -->
        <div class="px-4 py-3">
          <p class="text-xs text-slate-500 line-clamp-2">
            {{ o.items.map(i => i.product_name).join(', ') }}
          </p>
        </div>

        <!-- Savings footer -->
        <div class="flex items-center justify-between px-4 py-2.5 bg-emerald-50 border-t border-emerald-100">
          <div class="flex items-center gap-1.5 text-emerald-700 text-sm font-semibold">
            <i class="fi fi-rr-piggy-bank text-xs"></i>
            ประหยัดได้
          </div>
          <div class="text-right">
            <span class="text-emerald-700 font-bold text-sm">-฿{{ fmt(o.discount) }}</span>
            <span class="text-xs text-slate-400 ml-2">จาก ฿{{ fmt(Number(o.total) + Number(o.discount)) }}</span>
          </div>
        </div>
      </RouterLink>

      <Pagination :meta="meta" @change="goPage" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api/index.js'
import Pagination from '../components/Pagination.vue'

const orders  = ref([])
const meta    = ref({})
const loading = ref(true)
const page    = ref(1)

const totalSaved = computed(() =>
  orders.value.reduce((s, o) => s + Number(o.discount), 0)
)

function fmt(v) {
  return Number(v || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function fmtDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' })
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/shop/my/orders/savings', { params: { page: page.value } })
    orders.value = data.data || []
    meta.value   = data
  } finally { loading.value = false }
}

function goPage(p) { page.value = p; load() }

onMounted(load)
</script>
