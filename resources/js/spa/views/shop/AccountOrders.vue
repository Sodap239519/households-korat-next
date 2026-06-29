<template>
  <div class="max-w-3xl mx-auto px-3 sm:px-6 py-4 sm:py-6">
    <h1 class="text-xl font-bold text-slate-800 mb-3 px-1">การซื้อของฉัน</h1>

    <!-- Status tabs -->
    <div class="flex gap-1 overflow-x-auto pb-2 -mx-1 px-1 sticky top-16 z-10 bg-transparent">
      <button
        v-for="t in tabs"
        :key="t.key"
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
            class="flex items-center gap-1.5 text-sm font-semibold text-slate-700 hover:text-violet-600 transition"
            @click.stop
          >
            <i class="fi fi-rr-shop text-violet-600"></i> {{ o.seller_group?.name }}
          </RouterLink>
          <span v-else class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
            <i class="fi fi-rr-shop text-violet-600"></i> {{ o.seller_group?.name }}
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
          <div class="flex justify-end gap-2">
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import OrderStatusChip from './components/OrderStatusChip.vue'
import Pagination from '../components/Pagination.vue'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const router = useRouter()
const confirm = useConfirm()
const toast = useToast()

const orders = ref([])
const meta = ref({})
const loading = ref(true)
const page = ref(1)
const tab = ref('all')

const tabs = [
  { key: 'all',        label: 'ทั้งหมด' },
  { key: 'to_pay',     label: 'ที่ต้องชำระ' },
  { key: 'to_ship',    label: 'ที่ต้องจัดส่ง' },
  { key: 'to_receive', label: 'ที่ต้องได้รับ' },
  { key: 'completed',  label: 'สำเร็จ' },
  { key: 'return',     label: 'คืน/เคลม' },
]

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function goDetail(o) { router.push(`/shop/account/orders/${o.order_no}`) }

async function load() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (tab.value !== 'all') params.status_group = tab.value
    const { data } = await api.get('/shop/my/orders', { params })
    orders.value = data.data || []
    meta.value = data
  } finally { loading.value = false }
}
function setTab(k) { tab.value = k; page.value = 1; load() }
function goPage(p) { page.value = p; load() }

function receive(o) {
  confirm.require({
    message: 'ยืนยันว่าคุณได้รับสินค้าครบถ้วนแล้ว?',
    header: 'ยืนยันรับสินค้า', icon: 'fi fi-rr-box-check',
    acceptLabel: 'ได้รับแล้ว', rejectLabel: 'ยกเลิก',
    accept: async () => {
      try {
        await api.post(`/shop/orders/${o.order_no}/receive`)
        toast.add({ severity: 'success', summary: 'ขอบคุณค่ะ', detail: 'ยืนยันรับสินค้าแล้ว', life: 2500 })
        load()
      } catch (e) {
        toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 })
      }
    },
  })
}

onMounted(load)
</script>
