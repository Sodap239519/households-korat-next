<template>
  <Teleport to="body">
    <Transition name="pwa-pop">
      <div v-if="visible"
        class="fixed inset-x-3 z-[70] bottom-[4.75rem] lg:bottom-5 lg:left-auto lg:right-5 lg:inset-x-auto lg:max-w-md"
        role="dialog" aria-label="ติดตั้งแอปตลาดชุมชนโคราช">

        <div class="rounded-2xl overflow-hidden shadow-2xl shadow-violet-900/25" style="background:#ffffff">
          <!-- ===== banner แถวเดียว (เหมือนกันทั้ง Android / iOS) ===== -->
          <div class="px-4 py-2.5 flex items-center gap-2.5">
            <div class="w-11 h-11 rounded-xl bg-white shadow-sm border border-violet-100 shrink-0 overflow-hidden flex items-center justify-center">
              <img :src="'/icons/icon-192.png'" alt="ตลาดชุมชนโคราช" class="w-full h-full object-contain" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[13.5px] font-bold text-slate-800 leading-tight truncate">ติดตั้งตลาดชุมชนโคราช</p>
              <p class="text-[11.5px] text-slate-500 leading-tight truncate">เปิดเร็วขึ้น เหมือนใช้งานแอปจริง</p>
            </div>
            <!-- ไว้ก่อน (ซ้ายของปุ่มติดตั้ง) -->
            <button @click="dismiss" class="shrink-0 text-slate-400 hover:text-slate-600 text-xs font-medium px-1.5 py-2 transition">ไว้ก่อน</button>
            <!-- ติดตั้ง (เม็ดยา ขวาสุด) -->
            <button @click="install"
              class="shrink-0 h-9 pl-3 pr-4 rounded-full bg-violet-600 hover:bg-violet-700 active:scale-95 text-white text-sm font-semibold shadow-md shadow-violet-500/30 flex items-center gap-1.5 transition">
              <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
              ติดตั้ง
            </button>
          </div>

          <!-- ===== iOS: คลี่ขั้นตอนออกมาเมื่อกด "ติดตั้ง" ===== -->
          <Transition name="steps">
            <ol v-if="iosStepsOpen" class="border-t border-violet-100 bg-violet-50/40 px-3 py-2.5 space-y-1.5 text-[13px] text-slate-600">
              <li class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-violet-100 text-violet-700 text-[11px] font-bold flex items-center justify-center shrink-0">1</span>
                แตะปุ่ม <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-white border border-slate-200 mx-0.5 text-sky-600"><svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M8 6.5 12 3l4 3.5"/><path d="M6 11H5a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7a1 1 0 0 0-1-1h-1"/></svg></span> แชร์ ด้านล่างจอ
              </li>
              <li class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-violet-100 text-violet-700 text-[11px] font-bold flex items-center justify-center shrink-0">2</span>
                เลือก <span class="font-semibold text-slate-700">"เพิ่มลงในหน้าจอโฮม"</span>
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-white border border-slate-200 text-slate-600"><svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M12 9v6M9 12h6"/></svg></span>
              </li>
            </ol>
          </Transition>
        </div>

      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const DISMISS_KEY  = 'pwa_install_dismissed_at'
const SNOOZE_DAYS  = 1          // กด "ไว้ก่อน" แล้วเว้น 1 วัน (วันต่อวัน) ค่อยเด้งใหม่
const SHOW_DELAY   = 2500       // หน่วง 2.5 วิ ค่อยโผล่ (ไม่รบกวนตอนเข้าเว็บ)

const visible = ref(false)
const mode    = ref('')         // 'android' | 'ios'
const iosStepsOpen = ref(false)
let deferredPrompt = null
let showTimer = null

function isStandalone() {
  return window.matchMedia('(display-mode: standalone)').matches ||
         window.navigator.standalone === true
}
function isIOS() {
  const ua = window.navigator.userAgent
  const iDevice = /iphone|ipad|ipod/i.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) // iPadOS 13+
  const isSafari = /safari/i.test(ua) && !/crios|fxios|edgios/i.test(ua) // เฉพาะ Safari (Chrome iOS ติดตั้งไม่ได้)
  return iDevice && isSafari
}
function recentlyDismissed() {
  const at = Number(localStorage.getItem(DISMISS_KEY) || 0)
  return at && (Date.now() - at) < SNOOZE_DAYS * 86400000
}
function onChatPage() {
  return route.path.includes('/chat')
}

function scheduleShow(m) {
  if (isStandalone() || recentlyDismissed()) return
  mode.value = m
  clearTimeout(showTimer)
  showTimer = setTimeout(() => { if (!onChatPage()) visible.value = true }, SHOW_DELAY)
}

function onBeforeInstall(e) {
  e.preventDefault()          // กัน mini-infobar ของ Chrome เอง
  deferredPrompt = e
  scheduleShow('android')
}

async function install() {
  // Android/Chrome: มี prompt จริง → ติดตั้งเลย
  if (deferredPrompt) {
    visible.value = false
    deferredPrompt.prompt()
    try { await deferredPrompt.userChoice } catch (_) {}
    deferredPrompt = null
    localStorage.setItem(DISMISS_KEY, String(Date.now()))
    return
  }
  // iOS/อื่นๆ: ไม่มี prompt → คลี่ขั้นตอนเพิ่มลงโฮม
  iosStepsOpen.value = true
}

function dismiss() {
  visible.value = false
  localStorage.setItem(DISMISS_KEY, String(Date.now()))
}

function onInstalled() {
  visible.value = false
  localStorage.setItem(DISMISS_KEY, String(Date.now()))
}

onMounted(() => {
  window.addEventListener('beforeinstallprompt', onBeforeInstall)
  window.addEventListener('appinstalled', onInstalled)
  // iOS ไม่มี beforeinstallprompt → เช็คเองแล้วโชว์ banner (หน้าตาเหมือน Android)
  if (isIOS()) scheduleShow('ios')
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeinstallprompt', onBeforeInstall)
  window.removeEventListener('appinstalled', onInstalled)
  clearTimeout(showTimer)
})
</script>

<style scoped>
.pwa-pop-enter-active { transition: all .35s cubic-bezier(.16,1,.3,1); }
.pwa-pop-leave-active { transition: all .25s ease-in; }
.pwa-pop-enter-from, .pwa-pop-leave-to { opacity: 0; transform: translateY(16px) scale(.98); }

/* คลี่ขั้นตอน iOS */
.steps-enter-active, .steps-leave-active { transition: all .3s ease; overflow: hidden; }
.steps-enter-from, .steps-leave-to { opacity: 0; max-height: 0; padding-top: 0; padding-bottom: 0; }
.steps-enter-to, .steps-leave-from { opacity: 1; max-height: 120px; }
</style>
