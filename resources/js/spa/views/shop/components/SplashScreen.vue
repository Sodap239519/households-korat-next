<template>
  <Teleport to="body">
    <Transition name="splash-fade">
      <div v-if="visible" @click="skip"
        class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden select-none"
        style="background:radial-gradient(130% 100% at 50% 35%,#5b21b6 0%,#3b0f73 55%,#2e1065 100%)">

        <!-- โลโก้ + วงแหวนหมุน -->
        <div class="relative flex items-center justify-center splash-pop">
          <div class="splash-ring absolute w-32 h-32 rounded-full"></div>
          <div class="w-24 h-24 rounded-[22px] overflow-hidden shadow-2xl shadow-black/40 ring-2 ring-white/15">
            <img :src="'/icons/icon-192.png'" alt="ตลาดชุมชนโคราช" class="w-full h-full object-cover" />
          </div>
        </div>

        <!-- ตัวนับเปอร์เซ็นต์ -->
        <div class="mt-10 tabular-nums text-white/85 text-lg font-light tracking-[0.15em] splash-rise">
          {{ pct }}%
        </div>

        <!-- ชื่อแบรนด์จางๆ ด้านล่าง -->
        <p class="absolute bottom-12 text-violet-200/50 text-xs tracking-wide splash-rise">ตลาดชุมชนโคราช</p>

        <!-- ปุ่มข้าม -->
        <button @click.stop="skip"
          class="absolute top-6 right-5 text-violet-200/50 text-xs font-medium px-3 py-1 rounded-full border border-white/10 hover:bg-white/10 transition">
          ข้าม
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const DURATION = 5000          // ~5 วินาที (แตะที่ไหนก็ข้าม)
const ONCE_KEY = 'shop_splash_shown'   // โชว์ครั้งเดียวต่อ session

const visible  = ref(false)
const progress = ref(0)                 // 0..100
const pct = computed(() => Math.min(100, Math.round(progress.value)))
let startTs = 0, raf = null, doneTimer = null

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
  if (sessionStorage.getItem(ONCE_KEY)) return
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
.splash-fade-leave-active { transition: opacity .55s ease, transform .55s ease; }
.splash-fade-leave-to { opacity: 0; transform: scale(1.05); }

.splash-pop { animation: splashPop .6s cubic-bezier(.16,1,.3,1) both; }
@keyframes splashPop { from { opacity: 0; transform: scale(.72); } to { opacity: 1; transform: none; } }

/* วงแหวนโหลดหมุนรอบโลโก้ */
.splash-ring {
  border: 2px solid rgba(255,255,255,.14);
  border-top-color: rgba(255,255,255,.85);
  animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.splash-rise { animation: rise .5s ease .3s both; }
@keyframes rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
</style>
