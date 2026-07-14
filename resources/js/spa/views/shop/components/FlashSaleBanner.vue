<template>
  <div
    v-if="activeEvent"
    class="overflow-hidden px-4 sm:px-6 py-3 flex items-center gap-3"
    style="background:linear-gradient(135deg,#dc2626 0%,#ea580c 60%,#f97316 100%)"
  >
    <i class="fi fi-rr-bolt text-yellow-300 text-xl shrink-0" style="line-height:1"></i>
    <div class="flex-1 min-w-0">
      <p class="font-extrabold text-white text-base tracking-wider leading-none">FLASH SALE</p>
      <p class="text-white/70 text-xs mt-0.5 truncate">{{ activeEvent.title }}</p>
    </div>
    <!-- Countdown -->
    <div class="flex items-center gap-0.5 shrink-0">
      <span class="bg-black/40 text-white text-sm font-mono font-bold px-2 py-1.5 rounded-lg leading-none">{{ pad(h) }}</span>
      <span class="text-white/70 text-base font-bold mx-0.5">:</span>
      <span class="bg-black/40 text-white text-sm font-mono font-bold px-2 py-1.5 rounded-lg leading-none">{{ pad(m) }}</span>
      <span class="text-white/70 text-base font-bold mx-0.5">:</span>
      <span class="bg-black/40 text-white text-sm font-mono font-bold px-2 py-1.5 rounded-lg leading-none">{{ pad(s) }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import api from '../../../api/index.js'

const activeEvent = ref(null)
const h = ref(0)
const m = ref(0)
const s = ref(0)
let timer = null

function pad(n) { return String(n).padStart(2, '0') }

function tick(endTs) {
  const diff = Math.max(0, endTs - Date.now())
  if (diff === 0) {
    clearInterval(timer)
    activeEvent.value = null
    return
  }
  const totalSec = Math.floor(diff / 1000)
  h.value = Math.floor(totalSec / 3600)
  m.value = Math.floor((totalSec % 3600) / 60)
  s.value = totalSec % 60
}

onMounted(async () => {
  try {
    const { data } = await api.get('/shop/flash-sale-events')
    // หา event ที่กำลัง active อยู่ (starts_at <= now <= ends_at)
    const now = Date.now()
    const current = (data || []).find(ev => {
      const start = new Date(ev.starts_at).getTime()
      const end   = new Date(ev.ends_at).getTime()
      return start <= now && end >= now
    })
    if (!current) return
    activeEvent.value = current
    const endTs = new Date(current.ends_at).getTime()
    tick(endTs)
    timer = setInterval(() => tick(endTs), 1000)
  } catch {
    // ไม่ต้องแสดงอะไรถ้า API ล้มเหลว
  }
})

onBeforeUnmount(() => clearInterval(timer))
</script>
