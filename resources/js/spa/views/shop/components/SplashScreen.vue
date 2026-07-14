<template>
  <Teleport to="body">
    <Transition name="splash-fade">
      <div v-if="visible" @click="skip"
        class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden select-none"
        style="background:radial-gradient(130% 100% at 50% 40%,#5b21b6 0%,#3b0f73 55%,#2e1065 100%)">

        <!-- โลโก้ + เอฟเฟกต์ -->
        <div class="relative flex items-center justify-center">
          <!-- แสงกระเพื่อม (ripple) 2 ชั้น -->
          <span class="ripple"></span>
          <span class="ripple ripple-2"></span>

          <!-- โลโก้ + แสงวิ่งผ่าน -->
          <div class="logo-wrap logo-pop w-24 h-24 sm:w-28 sm:h-28 rounded-[22px] overflow-hidden shadow-2xl shadow-black/40 ring-2 ring-white/15">
            <img :src="logoUrl" alt="ตลาดชุมชนโคราช" class="w-full h-full object-cover" />
            <span class="shine"></span>
          </div>
        </div>

        <!-- ชื่อแบรนด์ค่อยๆ ปรากฏ -->
        <h1 class="name-rise mt-6 text-white text-xl sm:text-2xl font-extrabold tracking-tight">ตลาดชุมชนโคราช</h1>
        <p class="name-rise-2 mt-1 text-violet-200/70 text-xs sm:text-sm">สินค้าชุมชน จ.นครราชสีมา</p>

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
import { ref, onMounted, onBeforeUnmount } from 'vue'
import logoUrl from '../../../assets/logo.png'

const DURATION = 2800          // สั้น กระชับ (แตะที่ไหนก็ข้าม)
const ONCE_KEY = 'shop_splash_shown'

const visible = ref(false)
let doneTimer = null

function skip() {
  visible.value = false
  clearTimeout(doneTimer)
}

onMounted(() => {
  if (sessionStorage.getItem(ONCE_KEY)) return
  const p = window.location.pathname
  const deep = p !== '/shop' && p !== '/shop/' && p.split('/').filter(Boolean).length > 1
  if (deep) { sessionStorage.setItem(ONCE_KEY, '1'); return }

  sessionStorage.setItem(ONCE_KEY, '1')
  visible.value = true
  doneTimer = setTimeout(skip, DURATION)
})
onBeforeUnmount(() => clearTimeout(doneTimer))
</script>

<style scoped>
/* เข้า-ออกทั้งหน้าจอ */
.splash-fade-leave-active { transition: opacity .55s ease, transform .55s ease; }
.splash-fade-leave-to { opacity: 0; transform: scale(1.06); }

/* โลโก้เด้งเข้ามา (สปริง) */
.logo-pop { animation: logoPop .85s cubic-bezier(.2,.8,.2,1.25) .1s both; }
@keyframes logoPop {
  0%   { opacity: 0; transform: scale(.5) translateY(8px); }
  60%  { opacity: 1; transform: scale(1.07); }
  100% { transform: scale(1); }
}

/* แสงวิ่งผ่านโลโก้ */
.logo-wrap { position: relative; }
.shine {
  position: absolute; inset: 0; pointer-events: none;
  background: linear-gradient(115deg, transparent 38%, rgba(255,255,255,.55) 50%, transparent 62%);
  transform: translateX(-130%);
  animation: shine 1.1s ease-in-out .75s 1 both;
}
@keyframes shine { to { transform: translateX(130%); } }

/* แสงกระเพื่อมออกจากโลโก้ */
.ripple {
  position: absolute; width: 100px; height: 100px; border-radius: 50%;
  background: radial-gradient(circle, rgba(255,255,255,.30), transparent 70%);
  animation: ripple 1.5s ease-out .2s 1 both;
}
.ripple-2 { animation-delay: .55s; }
@keyframes ripple {
  from { opacity: .75; transform: scale(.4); }
  to   { opacity: 0;   transform: scale(3.4); }
}

/* ชื่อแบรนด์ */
.name-rise   { animation: rise .55s ease .5s both; }
.name-rise-2 { animation: rise .55s ease .68s both; }
@keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
</style>
