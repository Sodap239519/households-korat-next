<template>
  <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
    <h1 class="text-xl font-bold text-slate-800 mb-3 px-1">การซื้อของฉัน</h1>

    <!-- Status tabs -->
    <div ref="tabBar" class="flex gap-1 overflow-x-auto pb-2 -mx-1 px-1 sticky top-14 z-10 scrollbar-none">
      <button
        v-for="t in tabs"
        :key="t.key"
        :ref="el => { if (el) tabEls[t.key] = el }"
        class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap"
        :class="tab === t.key ? 'btn-orange' : 'box-card text-slate-600'"
        @click="setTab(t.key)"
      >{{ t.label }}</button>
    </div>

    <div v-if="loading" class="space-y-3 mt-3">
      <div v-for="n in 3" :key="n" class="box-card h-40 skeleton"></div>
    </div>

    <div v-else-if="!orders.length" class="box-card p-12 text-center text-slate-400 mt-3">
      <i class="fi fi-rr-box-open text-4xl"></i>
      <p class="mt-3">ยังไม่มีรายการ</p>
      <RouterLink to="/shop/products" class="text-violet-600 hover:underline mt-2 inline-block">เลือกซื้อสินค้า</RouterLink>
    </div>

    <div v-else class="space-y-3 mt-3">
      <div v-for="o in orders" :key="o.id" class="box-card overflow-hidden">
        <!-- shop header -->
        <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
          <RouterLink
            v-if="o.seller_group?.slug"
            :to="`/shop/sellers/${o.seller_group.slug}`"
            class="flex items-start gap-1.5 hover:text-violet-600 transition group"
            @click.stop
          >
            <i class="fi fi-rr-shop text-violet-600 mt-0.5 text-sm shrink-0"></i>
            <div>
              <span class="text-sm font-semibold text-slate-700 group-hover:text-violet-600">{{ o.seller_group?.name }}</span>
              <span v-if="orderSellerNames(o).length" class="block text-xs text-slate-400">{{ orderSellerNames(o).join(', ') }}</span>
            </div>
          </RouterLink>
          <span v-else class="flex items-start gap-1.5 text-slate-700">
            <i class="fi fi-rr-shop text-violet-600 mt-0.5 text-sm shrink-0"></i>
            <div>
              <span class="text-sm font-semibold">{{ o.seller_group?.name }}</span>
              <span v-if="orderSellerNames(o).length" class="block text-xs text-slate-400">{{ orderSellerNames(o).join(', ') }}</span>
            </div>
          </span>
          <OrderStatusChip :status="o.status" />
        </div>

        <!-- items -->
        <RouterLink :to="`/shop/account/orders/${o.order_no}`" class="block px-4 py-3 space-y-3">
          <div v-for="it in o.items" :key="it.id" class="flex gap-3">
            <div class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden shrink-0 flex items-center justify-center">
              <img v-if="it.product?.primary_image_url" :src="it.product.primary_image_url" class="w-full h-full object-cover" />
              <i v-else class="fi fi-rr-picture text-slate-300"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-slate-700 line-clamp-2">{{ it.product_name }}</p>
              <p class="text-xs text-slate-400">x{{ it.qty }}</p>
            </div>
            <div class="text-right text-sm text-slate-600">฿{{ fmt(it.unit_price) }}</div>
          </div>
        </RouterLink>

        <!-- footer -->
        <div class="px-4 py-3 border-t border-slate-100">
          <p class="text-right text-sm text-slate-500 mb-2">
            สินค้ารวม {{ o.items_count }} รายการ:
            <span class="text-lg font-bold text-fuchsia-700">฿{{ fmt(o.total) }}</span>
          </p>
          <div v-if="o.shipment?.tracking_no" class="text-xs text-indigo-600 mb-2">
            <i class="fi fi-rr-truck-side"></i> {{ o.shipment.carrier }} · {{ o.shipment.tracking_no }}
          </div>
          <div class="flex justify-end gap-2 flex-wrap">
            <button
              v-if="['pending_payment'].includes(o.status)"
              class="btn-orange px-4 py-2 rounded-full text-sm font-semibold"
              @click="goDetail(o)"
            >แจ้งชำระเงิน</button>
            <button
              v-if="['shipped','delivered'].includes(o.status)"
              class="btn-orange px-4 py-2 rounded-full text-sm font-semibold"
              @click="receive(o)"
            >ฉันได้รับสินค้าแล้ว</button>
            <button
              v-if="['completed', 'cancelled', 'refunded'].includes(o.status)"
              :disabled="reordering === o.id"
              class="px-4 py-2 rounded-full text-sm font-medium border border-violet-300 text-violet-700 hover:bg-violet-50 disabled:opacity-50 flex items-center gap-1.5"
              @click.stop="reorder(o)"
            >
              <i :class="reordering === o.id ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-refresh'"></i>
              สั่งซื้ออีกครั้ง
            </button>
            <RouterLink
              v-if="o.status === 'completed' && (o.reviews_count || 0) < (o.items_count || 1)"
              :to="`/shop/account/orders/${o.order_no}/review`"
              class="px-4 py-2 rounded-full text-sm font-medium border border-amber-300 text-amber-600 hover:bg-amber-50 flex items-center gap-1.5"
              @click.stop
            >
              <i class="fi fi-rr-star"></i> รีวิว
            </RouterLink>
            <RouterLink
              v-else-if="o.status === 'completed' && o.items_count > 0"
              :to="o.items?.[0]?.product?.slug
                ? `/shop/products/${o.items[0].product.slug}?tab=reviews`
                : `/shop/account/orders/${o.order_no}/review`"
              class="px-4 py-2 rounded-full text-sm font-medium border border-emerald-300 text-emerald-600 hover:bg-emerald-50 flex items-center gap-1.5"
              @click.stop
            >
              <i class="fi fi-sr-star"></i> ดูรีวิว
            </RouterLink>
            <button class="px-4 py-2 rounded-full text-sm font-medium border border-violet-300 text-violet-700 hover:bg-violet-50" @click="goDetail(o)">
              {{ ['shipped','delivered'].includes(o.status) ? 'ติดตามคำสั่งซื้อ' : 'ดูรายละเอียด' }}
            </button>
          </div>
        </div>
      </div>
      <Pagination :meta="meta" @change="goPage" />
    </div>

    <ConfirmDialog /><Toast />
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useCart } from '../../composables/useCart.js'
import { useBuyNow } from '../../composables/useBuyNow.js'
import api from '../../api/index.js'
import OrderStatusChip from './components/OrderStatusChip.vue'
import Pagination from '../components/Pagination.vue'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const router  = useRouter()
const route   = useRoute()
const confirm = useConfirm()
const toast   = useToast()
const cart    = useCart()
const buyNow  = useBuyNow()

const orders     = ref([])
const meta       = ref({})
const loading    = ref(true)
const page       = ref(1)
const tab        = ref('history')
const reordering = ref(null)
const tabEls     = {}

const tabs = [
  { key: 'history',    label: 'ประวัติการซื้อ' },
  { key: 'to_pay',     label: 'ที่ต้องชำระ' },
  { key: 'to_ship',    label: 'ที่ต้องจัดส่ง' },
  { key: 'to_receive', label: 'ที่ต้องได้รับ' },
  { key: 'completed',  label: 'สำเร็จแล้ว' },
]

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }

function orderSellerNames(order) {
  const names = new Set()
  for (const item of order.items ?? []) {
    const n = item.product?.seller_user?.shop_name || item.product?.seller_user?.name
    if (n) names.add(n)
  }
  return [...names]
}
function goDetail(o) { router.push(`/shop/account/orders/${o.order_no}`) }

function scrollTabIntoView(key) {
  nextTick(() => {
    tabEls[key]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' })
  })
}

async function autoReceive(orderList) {
  const sevenDaysMs = 7 * 24 * 60 * 60 * 1000
  const now = Date.now()
  const expired = orderList.filter(o =>
    ['shipped', 'delivered'].includes(o.status) &&
    o.shipped_at &&
    now - new Date(o.shipped_at).getTime() > sevenDaysMs
  )
  for (const o of expired) {
    try { await api.post(`/shop/orders/${o.order_no}/receive`) } catch { /* ignore */ }
  }
  return expired.length > 0
}

async function load() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (tab.value !== 'all') params.status_group = tab.value
    const { data } = await api.get('/shop/my/orders', { params })
    orders.value = data.data || []
    meta.value = data
    const didReceive = await autoReceive(orders.value)
    if (didReceive) {
      const { data: data2 } = await api.get('/shop/my/orders', { params })
      orders.value = data2.data || []
      meta.value = data2
    }
  } finally { loading.value = false }
}
function setTab(k) { tab.value = k; page.value = 1; load(); scrollTabIntoView(k) }
function goPage(p) { page.value = p; load() }

function receive(o) {
  confirm.require({
    message: 'ยืนยันว่าคุณได้รับสินค้าครบถ้วนแล้ว?',
    header: 'ยืนยันรับสินค้า', icon: 'fi fi-rr-box-check',
    acceptLabel: 'ได้รับแล้ว', rejectLabel: 'ยกเลิก',
    accept: async () => {
      try {
        await api.post(`/shop/orders/${o.order_no}/receive`)
        router.push(`/shop/account/orders/${o.order_no}/review`)
      } catch (e) {
        toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 })
      }
    },
  })
}

async function reorder(o) {
  reordering.value = o.id
  const fetched = []
  try {
    for (const item of o.items) {
      if (!item.product?.slug) continue
      try {
        const { data } = await api.get(`/shop/products/${item.product.slug}`)
        const p = data.product ?? data
        fetched.push({
          product_id:     p.id,
          slug:           p.slug,
          name:           p.name,
          price:          Number(p.effective_price ?? p.sale_price ?? p.price),
          original_price: Number(p.price),
          unit:           p.unit,
          image:          p.primary_image_url || null,
          group_id:       p.seller_group_id ?? p.seller_group?.id,
          group_name:     p.seller_group?.name ?? '',
          group_slug:     p.seller_group?.slug ?? '',
          stock_qty:      p.stock_qty ?? null,
          qty:            item.qty,
        })
      } catch { /* สินค้าอาจหมดหรือถูกลบ */ }
    }
    if (fetched.length) {
      buyNow.setAll(fetched)
      router.push('/shop/checkout?buynow=1')
    } else {
      toast.add({ severity: 'warn', summary: 'ไม่สามารถสั่งซื้อได้', detail: 'สินค้าอาจหมดหรือถูกลบแล้ว', life: 3000 })
    }
  } finally { reordering.value = null }
}

onMounted(() => {
  const s = route.query.status
  if (s && tabs.some(t => t.key === s)) tab.value = s
  load()
  scrollTabIntoView(tab.value)
})

watch(() => route.query.status, (s) => {
  if (s && tabs.some(t => t.key === s)) setTab(s)
})
</script>
