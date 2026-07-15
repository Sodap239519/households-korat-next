<template>
  <Teleport to="body">
    <Transition name="splash-fade">
      <div v-if="visible" @click="skip"
        class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden select-none"
        style="background:radial-gradient(130% 100% at 50% 40%,#5b21b6 0%,#3b0f73 55%,#2e1065 100%)">

        <!-- โลโก้ใหญ่ พื้นโปร่งใส (เส้นขาวบนม่วงโดยตรง) -->
        <div class="relative flex items-center justify-center">
          <span class="ripple"></span>
          <span class="ripple ripple-2"></span>
          <img :src="logoMark" alt="ตลาดชุมชนโคราช"
            class="logo-pop relative w-[74vw] max-w-[340px] drop-shadow-[0_6px_24px_rgba(0,0,0,.35)]" />
        </div>

        <!-- สโลแกน (ไม่ใส่ชื่อซ้ำ เพราะโลโก้มีคำว่า "ตลาดชุมชนโคราช" อยู่แล้ว) -->
        <p class="tagline-rise -mt-2 text-violet-200/70 text-xs sm:text-sm tracking-wide">
          สินค้าชุมชน จ.นครราชสีมา
        </p>

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
import logoMark from '../../../assets/logo-mark.png'

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
.logo-pop { animation: logoPop .9s cubic-bezier(.2,.8,.2,1.25) .1s both; }
@keyframes logoPop {
  0%   { opacity: 0; transform: scale(.6) translateY(10px); }
  60%  { opacity: 1; transform: scale(1.05); }
  100% { transform: scale(1); }
}

/* แสงกระเพื่อมออกจากโลโก้ */
.ripple {
  position: absolute; width: 60%; aspect-ratio: 1; border-radius: 50%;
  background: radial-gradient(circle, rgba(255,255,255,.22), transparent 70%);
  animation: ripple 1.6s ease-out .25s 1 both;
}
.ripple-2 { animation-delay: .6s; }
@keyframes ripple {
  from { opacity: .7; transform: scale(.5); }
  to   { opacity: 0;  transform: scale(2.6); }
}

/* สโลแกน */
.tagline-rise { animation: rise .55s ease .65s both; }
@keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
</style>
