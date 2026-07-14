<template>
  <Teleport to="body">
    <Transition name="splash-fade">
      <div v-if="visible" @click="skip"
        class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden select-none"
        style="background:radial-gradient(120% 120% at 50% 0%,#5b21b6 0%,#3b0f73 55%,#2e1065 100%)">

        <!-- วงกลมประดับพื้นหลัง -->
        <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-white/5 blur-2xl"></div>
        <div class="absolute -bottom-28 -right-20 w-80 h-80 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

        <!-- โลโก้ -->
        <div class="splash-pop relative">
          <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-[26px] overflow-hidden shadow-2xl shadow-black/40 ring-4 ring-white/15">
            <img :src="'/icons/icon-192.png'" alt="ตลาดชุมชนโคราช" class="w-full h-full object-cover" />
          </div>
        </div>

        <!-- ชื่อ + สโลแกน -->
        <h1 class="splash-rise mt-6 text-white text-2xl sm:text-3xl font-extrabold tracking-tight">ตลาดชุมชนโคราช</h1>
        <p class="splash-rise-2 mt-1.5 text-violet-200/80 text-sm sm:text-base px-8 text-center leading-snug">
          สินค้าชุมชนจากกลุ่มแก้จน จังหวัดนครราชสีมา
        </p>

        <!-- โหลดดิ้ง dots -->
        <div class="splash-rise-2 flex items-center gap-1.5 mt-8">
          <span class="splash-dot"></span><span class="splash-dot"></span><span class="splash-dot"></span>
        </div>

        <!-- progress bar -->
        <div class="absolute bottom-24 w-40 h-1 rounded-full bg-white/15 overflow-hidden">
          <div class="h-full rounded-full bg-white/80" :style="{ width: progress + '%' }"></div>
        </div>

        <!-- ปุ่มข้าม -->
        <button @click.stop="skip"
          class="absolute bottom-10 text-violet-200/60 text-xs font-medium px-4 py-1.5 rounded-full border border-white/15 hover:bg-white/10 transition">
          ข้าม
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const DURATION = 4500          // ~4.5 วินาที (แตะที่ไหนก็ข้ามได้)
const ONCE_KEY = 'shop_splash_shown'   // โชว์ครั้งเดียวต่อ session (ไม่รบกวนตอนกดไปมา)

const visible  = ref(false)
const progress = ref(0)
let startTs = 0
let raf = null
let doneTimer = null

function tick(ts) {
  if (!startTs) startTs = ts
  progress.value = Math.min(100, ((ts - startTs) / DURATION) * 100)
  if (progress.value < 100 && visible.value) raf = requestAnimationFrame(tick)
}

function skip() {
  visible.value = false
  cancelAnimationFrame(raf)
  clearTimeout(doneTimer)
}

onMounted(() => {
  // เคยโชว์ใน session นี้แล้ว → ข้าม
  if (sessionStorage.getItem(ONCE_KEY)) return
  // ถ้ามาแบบ deep-link ลึกๆ (เช่น /shop/products/xxx) ไม่ต้องคั่นด้วย splash
  const p = window.location.pathname
  const deep = p !== '/shop' && p !== '/shop/' && p.split('/').filter(Boolean).length > 1
  if (deep) { sessionStorage.setItem(ONCE_KEY, '1'); return }

  sessionStorage.setItem(ONCE_KEY, '1')
  visible.value = true
  raf = requestAnimationFrame(tick)
  doneTimer = setTimeout(skip, DURATION)
})

onBeforeUnmount(() => { cancelAnimationFrame(raf); clearTimeout(doneTimer) })
</script>

<style scoped>
.splash-fade-leave-active { transition: opacity .5s ease, transform .5s ease; }
.splash-fade-leave-to { opacity: 0; transform: scale(1.04); }

.splash-pop { animation: splashPop .6s cubic-bezier(.16,1,.3,1) both; }
@keyframes splashPop { from { opacity: 0; transform: scale(.7) translateY(8px); } to { opacity: 1; transform: none; } }

.splash-rise { animation: rise .5s ease .25s both; }
.splash-rise-2 { animation: rise .5s ease .45s both; }
@keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

.splash-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,.85); animation: dot 1s infinite ease-in-out; }
.splash-dot:nth-child(2) { animation-delay: .16s; }
.splash-dot:nth-child(3) { animation-delay: .32s; }
@keyframes dot { 0%,80%,100% { opacity: .3; transform: scale(.8); } 40% { opacity: 1; transform: scale(1.15); } }
</style>
