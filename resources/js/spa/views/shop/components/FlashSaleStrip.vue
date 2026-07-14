<template>
  <!-- ซ่อน section ถ้าไม่มี event ที่กำลัง active -->
  <section v-if="items.length || loading" class="overflow-hidden" style="background:linear-gradient(135deg,#dc2626 0%,#ea580c 60%,#f97316 100%)">
    <!-- Header -->
    <div class="flex items-center px-4 pt-3 pb-2 gap-2">
      <span class="flex items-center gap-1.5 flex-1 min-w-0">
        <i class="fi fi-rr-bolt text-yellow-300 text-base" style="line-height:1"></i>
        <span class="font-extrabold text-white text-sm tracking-wider shrink-0">FLASH SALE</span>
        <span v-if="eventTitle" class="text-white/70 text-[11px] font-medium truncate hidden sm:block ml-1">{{ eventTitle }}</span>
      </span>

      <!-- Countdown จาก ends_at จริง -->
      <div class="flex items-center gap-0.5 shrink-0">
        <span class="bg-black/40 text-white text-[11px] font-mono font-bold px-1.5 py-1 rounded-md leading-none">{{ pad(h) }}</span>
        <span class="text-white/70 text-xs font-bold mx-0.5">:</span>
        <span class="bg-black/40 text-white text-[11px] font-mono font-bold px-1.5 py-1 rounded-md leading-none">{{ pad(m) }}</span>
        <span class="text-white/70 text-xs font-bold mx-0.5">:</span>
        <span class="bg-black/40 text-white text-[11px] font-mono font-bold px-1.5 py-1 rounded-md leading-none">{{ pad(s) }}</span>
      </div>
    </div>

    <!-- Product Cards (horizontal scroll) -->
    <div class="flex gap-2.5 overflow-x-auto px-4 pb-3 scrollbar-hide">

      <!-- Skeleton -->
      <template v-if="loading">
        <div v-for="n in 4" :key="n" class="shrink-0 w-[118px] bg-white/20 rounded-2xl overflow-hidden animate-pulse">
          <div class="aspect-square bg-white/20"></div>
          <div class="px-2.5 pt-1.5 pb-2 space-y-1.5">
            <div class="h-2 bg-white/20 rounded"></div>
            <div class="h-2 w-2/3 bg-white/20 rounded"></div>
            <div class="h-3 bg-white/20 rounded mt-2"></div>
          </div>
        </div>
      </template>

      <!-- Flash Sale Items -->
      <RouterLink
        v-else
        v-for="item in items"
        :key="item.id"
        :to="`/shop/products/${item.product.slug}`"
        class="shrink-0 w-[118px] bg-white rounded-2xl overflow-hidden shadow-lg active:scale-95 transition-transform"
      >
        <!-- Product image -->
        <div class="relative aspect-square bg-slate-100 overflow-hidden flex items-center justify-center">
          <img
            v-if="primaryImage(item.product)"
            :src="primaryImage(item.product)"
            :alt="item.product.name"
            class="w-full h-full object-cover"
          />
          <i v-else class="fi fi-rr-picture text-slate-300 text-3xl"></i>
          <!-- Discount badge ใช้ sale_price จาก event item จริง -->
          <span class="absolute top-1.5 left-1.5 bg-rose-600 text-white text-[10px] font-extrabold px-1.5 py-0.5 rounded-full leading-none shadow">
            -{{ discountPct(item) }}%
          </span>
        </div>

        <!-- Info -->
        <div class="px-2.5 pt-1.5 pb-2">
          <p class="text-[11px] text-slate-700 font-medium line-clamp-2 leading-tight mb-1" style="min-height:2.4em">{{ item.product.name }}</p>
          <!-- ราคา flash sale จาก event item -->
          <p class="text-sm font-extrabold text-fuchsia-700 leading-none">฿{{ fmt(item.sale_price) }}</p>
          <p class="text-[10px] text-slate-400 line-through leading-none mt-0.5">฿{{ fmt(item.product.price) }}</p>

          <!-- Sold progress bar -->
          <div class="mt-2 h-1.5 rounded-full overflow-hidden bg-rose-100">
            <div class="h-full rounded-full transition-all duration-500"
              :style="`width:${soldPct(item.product)}%;background:linear-gradient(to right,#f97316,#ef4444)`"></div>
          </div>
          <p class="text-[9px] mt-0.5 font-medium"
            :style="soldPct(item.product) >= 70 ? 'color:#dc2626' : 'color:#9ca3af'">
            {{ soldPct(item.product) >= 90 ? '🔥 ใกล้หมดแล้ว!' : `ขายแล้ว ${item.product.total_sold ?? 0} ชิ้น` }}
          </p>
        </div>
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import api from '../../../api/index.js'

const items      = ref([])
const loading    = ref(true)
const eventTitle = ref('')

// Countdown
const h = ref(0)
const m = ref(0)
const s = ref(0)
let timer     = null
let endsAtMs  = 0

async function fetchCurrent() {
  try {
    const { data } = await api.get('/shop/flash-sale/current')
    if (!data || !data.items?.length) {
      items.value = []
      return
    }
    items.value = data.items
    eventTitle.value = data.event?.title ?? ''
    endsAtMs = data.event?.ends_at ? new Date(data.event.ends_at).getTime() : 0
    if (endsAtMs) startCountdown()
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

function startCountdown() {
  tick()
  timer = setInterval(tick, 1000)
}

function tick() {
  const diff = Math.max(0, endsAtMs - Date.now())
  if (diff === 0) {
    // Event หมดเวลา — ซ่อน section
    clearInterval(timer)
    items.value = []
    return
  }
  const total = Math.floor(diff / 1000)
  h.value = Math.floor(total / 3600)
  m.value = Math.floor((total % 3600) / 60)
  s.value = total % 60
}

function pad(n) { return String(n).padStart(2, '0') }

function primaryImage(p) {
  const img = p.images?.find(i => i.is_primary) ?? p.images?.[0]
  return img ? `/storage/${img.path}` : null
}

function fmt(v) {
  return Number(v || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

function discountPct(item) {
  const sale = Number(item.sale_price)
  const orig = Number(item.product?.price)
  if (!sale || !orig || orig <= 0) return 0
  return Math.round((1 - sale / orig) * 100)
}

function soldPct(p) {
  // ใช้ view_count เป็น proxy เนื่องจากไม่มีคอลัมน์ total_sold
  return Math.max(Math.min(Math.floor((p.view_count || 0) / 1.5), 85), 12)
}

onMounted(fetchCurrent)
onBeforeUnmount(() => clearInterval(timer))
</script>
