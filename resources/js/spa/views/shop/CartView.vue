<template>
  <!-- เพิ่ม pb บน mobile เพื่อกัน content ซ้อน fixed bar -->
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 pb-[12rem] lg:pb-6">
    <Breadcrumb :items="[{ label: 'ตะกร้าสินค้า' }]" class="mb-4" />

    <div v-if="!cart.items.value.length" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-shopping-cart text-5xl"></i>
      <p class="mt-3 text-lg">ตะกร้าว่างเปล่า</p>
      <RouterLink to="/shop/products" class="inline-block mt-5 px-6 py-2.5 rounded-full bg-violet-600 text-white font-semibold hover:bg-violet-700">เลือกซื้อสินค้า</RouterLink>
    </div>

    <div v-else class="grid lg:grid-cols-3 gap-5">
      <!-- Cart items -->
      <div class="lg:col-span-2 space-y-4">

        <!-- stock warning banner (ถ้ามีสินค้าหมด) -->
        <div v-if="hasOutOfStock" class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 flex items-start gap-3">
          <i class="fi fi-rr-exclamation-triangle text-rose-500 text-lg mt-0.5 shrink-0"></i>
          <div class="flex-1 min-w-0">
            <p class="text-rose-700 font-semibold text-sm">มีสินค้าบางรายการหมดหรือสต็อกไม่พอ</p>
            <p class="text-rose-500 text-xs mt-0.5">กรุณาลบหรือปรับจำนวนสินค้าที่ไฮไลท์สีแดงก่อนสั่งซื้อ</p>
          </div>
          <RouterLink to="/shop/products" class="shrink-0 text-xs text-violet-600 hover:underline font-medium">ดูสินค้าอื่น</RouterLink>
        </div>

        <div v-for="g in cart.groups.value" :key="g.group_id" class="box-card p-4">
          <p class="text-sm font-semibold text-violet-700 mb-3 flex items-center gap-2 border-b border-slate-100 pb-2">
            <i class="fi fi-rr-shop"></i> {{ g.group_name || 'กลุ่มผู้ขาย' }}
          </p>
          <div v-for="item in g.items" :key="item.product_id"
            class="flex gap-3 py-3 border-b border-slate-50 last:border-0 transition-colors"
            :class="stockStatus(item) === 'out' ? 'bg-rose-50/60 -mx-4 px-4 rounded-xl' : ''">
            <!-- รูปสินค้า -->
            <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden shrink-0 flex items-center justify-center relative">
              <img v-if="item.image" :src="item.image" class="w-full h-full object-cover" :class="stockStatus(item) === 'out' ? 'opacity-40' : ''" />
              <i v-else class="fi fi-rr-picture text-slate-300 text-xl"></i>
              <!-- overlay badge สินค้าหมด -->
              <div v-if="stockStatus(item) === 'out'"
                class="absolute inset-0 flex items-center justify-center bg-rose-500/70 rounded-xl">
                <span class="text-white text-[10px] font-bold text-center leading-tight px-1">สินค้า<br>หมด</span>
              </div>
            </div>
            <!-- ข้อมูล + controls -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start gap-1.5 mb-0.5">
                <p class="font-medium text-sm leading-snug" :class="stockStatus(item) === 'out' ? 'text-rose-600' : 'text-slate-700'">
                  {{ item.name }}
                </p>
                <span v-if="item.original_price > item.price"
                  class="shrink-0 text-[10px] font-bold px-1 py-0.5 rounded-full bg-rose-500 text-white leading-none mt-0.5">
                  -{{ Math.round((1 - item.price / item.original_price) * 100) }}%
                </span>
              </div>
              <!-- stock warning -->
              <p v-if="stockStatus(item) === 'out'" class="text-xs text-rose-500 font-medium mb-1 flex items-center gap-1">
                <i class="fi fi-rr-cross-circle text-[11px]"></i> สินค้าหมดแล้ว
              </p>
              <p v-else-if="stockStatus(item) === 'over'" class="text-xs text-amber-600 font-medium mb-1 flex items-center gap-1">
                <i class="fi fi-rr-exclamation text-[11px]"></i> เหลือเพียง {{ getAvailable(item) }} {{ item.unit }}
              </p>
              <p class="text-fuchsia-700 font-semibold text-sm flex items-baseline gap-1.5 flex-wrap">
                ฿{{ fmt(item.price) }}<span class="text-xs text-slate-400 font-normal"> / {{ item.unit }}</span>
                <span v-if="item.original_price > item.price" class="text-xs text-slate-400 line-through font-normal">฿{{ fmt(item.original_price) }}</span>
              </p>
              <p v-if="getAvailable(item) !== null && stockStatus(item) === 'ok'"
                class="text-[10px] text-slate-400 leading-none mt-0.5">
                คงเหลือ {{ getAvailable(item) }} {{ item.unit }}
              </p>
              <div class="flex items-center gap-2 mt-2">
                <div class="flex items-center border border-slate-200 rounded-full overflow-hidden bg-white"
                  :class="stockStatus(item) === 'out' ? 'opacity-50 pointer-events-none' : ''">
                  <button class="w-8 h-8 flex items-center justify-center hover:bg-slate-50 text-slate-500 active:bg-slate-100"
                    @click="cart.updateQty(item.product_id, item.qty - 1)">
                    <i class="fi fi-rr-minus-small text-base"></i>
                  </button>
                  <span class="w-7 text-center text-sm font-medium">{{ item.qty }}</span>
                  <button class="w-8 h-8 flex items-center justify-center hover:bg-slate-50 text-slate-500 active:bg-slate-100"
                    @click="incQty(item)">
                    <i class="fi fi-rr-plus-small text-base"></i>
                  </button>
                </div>
                <span class="flex-1 text-right text-sm" :class="stockStatus(item) === 'out' ? 'text-rose-400 line-through' : 'text-slate-700'">
                  <span class="font-bold block">฿{{ fmt(item.price * item.qty) }}</span>
                  <span v-if="item.original_price > item.price" class="text-xs text-slate-400 line-through font-normal block">฿{{ fmt(item.original_price * item.qty) }}</span>
                </span>
                <button class="text-rose-400 hover:text-rose-600 p-1.5 rounded-full hover:bg-rose-50 transition self-end"
                  @click="cart.remove(item.product_id)">
                  <i class="fi fi-rr-trash text-sm"></i>
                </button>
              </div>
            </div>
          </div>
          <p class="text-right text-sm text-slate-500 mt-2 pt-2 border-t border-slate-100">
            รวมกลุ่มนี้: <span class="font-semibold text-slate-700">฿{{ fmt(g.subtotal) }}</span>
          </p>
        </div>
        <p class="text-xs text-slate-400"><i class="fi fi-rr-info"></i> สินค้าจากต่างกลุ่มจะถูกแยกเป็นคนละคำสั่งซื้อ และชำระเงินแยกกัน</p>
      </div>

      <!-- Desktop summary sidebar (lg only) -->
      <div class="hidden lg:block lg:col-span-1">
        <div class="rounded-2xl p-5 sticky top-20 bg-slate-900 text-white shadow-lg">
          <h3 class="font-bold text-white mb-4 flex items-center gap-2">
            <i class="fi fi-rr-receipt text-violet-400 text-sm"></i> สรุปคำสั่งซื้อ
          </h3>
          <div class="space-y-2 mb-4">
            <div class="flex justify-between text-sm">
              <span class="text-white/60">จำนวนสินค้า</span>
              <span class="text-white font-medium">{{ cart.totalQty.value }} ชิ้น</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-white/60">จำนวนกลุ่มผู้ขาย</span>
              <span class="text-white font-medium">{{ cart.groups.value.length }} กลุ่ม</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-white/60">ราคาเต็ม</span>
              <span class="text-white/80 font-medium">฿{{ fmt(cart.fullPrice.value) }}</span>
            </div>
            <div v-if="cart.discount.value > 0" class="flex justify-between text-sm">
              <span class="text-emerald-400">ส่วนลด</span>
              <span class="text-emerald-400 font-semibold">-฿{{ fmt(cart.discount.value) }}</span>
            </div>
          </div>
          <div v-if="hasOutOfStock" class="mb-3 rounded-xl bg-rose-900/50 border border-rose-700/50 px-3 py-2 text-xs text-rose-300 flex items-center gap-2">
            <i class="fi fi-rr-exclamation-triangle"></i> มีสินค้าหมดในตะกร้า
          </div>
          <div class="flex justify-between items-center border-t border-white/10 pt-4 mb-4">
            <span class="text-white/70 text-sm">ยอดสุทธิ</span>
            <span class="text-2xl font-bold text-amber-400">฿{{ fmt(cart.subtotal.value) }}</span>
          </div>
          <button
            class="btn-sheen w-full h-12 rounded-xl text-white font-semibold transition shadow-lg"
            :class="hasOutOfStock ? 'bg-slate-600 cursor-not-allowed opacity-60' : 'bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-orange-500/30'"
            :disabled="hasOutOfStock"
            @click="goCheckout">
            ดำเนินการสั่งซื้อ <i class="fi fi-rr-arrow-right ml-1"></i>
          </button>
          <RouterLink to="/shop/products" class="block text-center text-xs text-white/40 hover:text-white/70 mt-3 transition">เลือกซื้อสินค้าต่อ</RouterLink>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile fixed bottom summary bar (above bottom nav h-16) -->
  <Teleport to="body">
    <Transition name="bar-up">
      <div v-if="cart.items.value.length"
        class="lg:hidden fixed inset-x-0 bottom-16 z-40 px-3 pb-2">
        <div class="bg-gradient-to-br from-violet-50 via-fuchsia-50 to-purple-100 border border-violet-200 rounded-2xl shadow-md shadow-violet-200/30 overflow-hidden">
          <!-- discount detail panel -->
          <Transition name="detail-slide">
            <div v-if="showDiscountDetail && discountItems.length"
              class="mx-3 mt-3 rounded-xl bg-white/80 border border-emerald-200/60 overflow-hidden">
              <div class="px-3 py-2">
                <p class="text-[10px] font-semibold text-emerald-700 mb-2 flex items-center gap-1">
                  <i class="fi fi-rr-tag text-[10px]"></i> รายการที่มีส่วนลด
                </p>
                <div v-for="item in discountItems" :key="item.product_id"
                  class="flex items-center gap-2 py-1.5 border-b border-emerald-50 last:border-0">
                  <div class="w-7 h-7 rounded-lg overflow-hidden bg-slate-100 shrink-0 flex items-center justify-center">
                    <img v-if="item.image" :src="item.image" class="w-full h-full object-cover" />
                    <i v-else class="fi fi-rr-picture text-slate-300 text-xs"></i>
                  </div>
                  <p class="flex-1 min-w-0 text-[11px] text-slate-700 truncate">{{ item.name }}</p>
                  <span class="text-[9px] font-bold bg-rose-500 text-white rounded-full px-1.5 py-0.5 shrink-0">
                    -{{ Math.round((1 - item.price / item.original_price) * 100) }}%
                  </span>
                  <span class="text-xs font-semibold text-emerald-600 shrink-0 min-w-[3rem] text-right">
                    -฿{{ fmt((item.original_price - item.price) * item.qty) }}
                  </span>
                </div>
              </div>
            </div>
          </Transition>

          <!-- info row -->
          <div class="flex items-center gap-3 px-4 pt-3 pb-2">
            <div class="flex-1 flex items-center gap-3 text-sm">
              <span class="text-violet-500 text-xs font-medium">{{ cart.totalQty.value }} ชิ้น</span>
              <span class="text-violet-300 text-xs">·</span>
              <span v-if="hasOutOfStock" class="text-rose-500 text-xs font-semibold flex items-center gap-1">
                <i class="fi fi-rr-exclamation-triangle text-[11px]"></i> มีสินค้าหมด
              </span>
              <span v-else class="text-violet-500 text-xs font-medium">{{ cart.groups.value.length }} กลุ่มผู้ขาย</span>
            </div>
            <div class="text-right">
              <p v-if="cart.discount.value > 0" class="text-[10px] text-slate-400 line-through leading-none mb-0.5">฿{{ fmt(cart.fullPrice.value) }}</p>
              <p v-else class="text-[10px] text-violet-400 leading-none mb-0.5">ยอดรวม</p>
              <p class="text-lg font-bold text-violet-800 leading-none">฿{{ fmt(cart.subtotal.value) }}</p>
              <button v-if="cart.discount.value > 0"
                @click="showDiscountDetail = !showDiscountDetail"
                class="text-[10px] text-emerald-600 font-semibold leading-none mt-0.5 flex items-center gap-0.5 ml-auto hover:text-emerald-700 transition">
                ประหยัด ฿{{ fmt(cart.discount.value) }}
                <i class="fi text-[9px] transition-transform duration-200"
                  :class="showDiscountDetail ? 'fi-rr-angle-up' : 'fi-rr-angle-down'"></i>
              </button>
            </div>
          </div>
          <!-- button -->
          <div class="px-3 pb-3">
            <button @click="goCheckout"
              :disabled="hasOutOfStock"
              class="w-full h-11 rounded-xl text-white font-semibold text-sm transition shadow-lg flex items-center justify-center gap-2"
              :class="hasOutOfStock
                ? 'bg-slate-400 cursor-not-allowed'
                : 'bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-orange-500/25'">
              {{ hasOutOfStock ? 'มีสินค้าหมด — แก้ไขก่อน' : 'ดำเนินการสั่งซื้อ' }}
              <i v-if="!hasOutOfStock" class="fi fi-rr-arrow-right text-xs"></i>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCart } from '../../composables/useCart.js'
import { useAuth } from '../../composables/useAuth.js'
import api from '../../api/index.js'
import Breadcrumb from './components/Breadcrumb.vue'

const router = useRouter()
const cart   = useCart()
const { user } = useAuth()

// stockMap: slug → available qty (-1 = ยังไม่ได้โหลด)
const stockMap = ref({})
const showDiscountDetail = ref(false)

const discountItems = computed(() =>
  cart.items.value.filter(i => i.original_price > i.price)
)
let stockTimer = null

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }

// ดึงจำนวน stock จาก stockMap (ซึ่งอาจเป็น object หรือ number)
function getAvailable(item) {
  const d = stockMap.value[item.slug]
  if (d === undefined) return null
  return typeof d === 'object' ? (d.stock ?? null) : d
}

// 'ok' | 'over' (qty > stock แต่ยังมี) | 'out' (stock = 0)
function stockStatus(item) {
  const d = stockMap.value[item.slug]
  if (!d) return 'ok'
  const available = typeof d === 'object' ? d.stock : d
  if (available === undefined || available === -1) return 'ok'
  if (available <= 0) return 'out'
  if (item.qty > available) return 'over'
  return 'ok'
}

const hasOutOfStock = computed(() =>
  cart.items.value.some(item => stockStatus(item) !== 'ok')
)

// เพิ่มจำนวน — จำกัดไม่ให้เกิน stock
function incQty(item) {
  const d = stockMap.value[item.slug]
  const available = d && typeof d === 'object' ? d.stock : d
  if (available !== undefined && available >= 0 && item.qty >= available) return
  cart.updateQty(item.product_id, item.qty + 1)
}

async function refreshStock() {
  const slugs = cart.items.value.map(i => i.slug).filter(Boolean)
  if (!slugs.length) return
  try {
    const params = new URLSearchParams()
    slugs.forEach(s => params.append('slugs[]', s))
    const { data } = await api.get(`/shop/products/stock?${params.toString()}`)
    stockMap.value = { ...stockMap.value, ...data }
    cart.syncPrices(data)
  } catch { /* ignore */ }
}

function goCheckout() {
  if (!user.value) {
    router.push({ path: '/shop/login', query: { redirect: '/shop/checkout' } })
  } else {
    router.push('/shop/checkout')
  }
}

onMounted(() => {
  refreshStock()
  stockTimer = setInterval(() => {
    if (document.visibilityState === 'visible') refreshStock()
  }, 60_000)
})

onUnmounted(() => {
  if (stockTimer) { clearInterval(stockTimer); stockTimer = null }
})
</script>

<style scoped>
.bar-up-enter-active { transition: transform 0.25s cubic-bezier(.22,.68,0,1.2), opacity 0.2s ease; }
.bar-up-leave-active { transition: transform 0.2s ease, opacity 0.15s ease; }
.bar-up-enter-from  { transform: translateY(16px); opacity: 0; }
.bar-up-leave-to    { transform: translateY(16px); opacity: 0; }

.detail-slide-enter-active { transition: max-height 0.25s ease, opacity 0.2s ease; max-height: 300px; }
.detail-slide-leave-active { transition: max-height 0.2s ease, opacity 0.15s ease; }
.detail-slide-enter-from   { max-height: 0; opacity: 0; overflow: hidden; }
.detail-slide-leave-to     { max-height: 0; opacity: 0; overflow: hidden; }
</style>
