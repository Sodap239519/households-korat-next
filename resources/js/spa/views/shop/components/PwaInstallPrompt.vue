<template>
  <Teleport to="body">
    <Transition name="pwa-pop">
      <div v-if="visible"
        class="fixed inset-x-3 z-[70] bottom-[4.75rem] lg:bottom-5 lg:left-auto lg:right-5 lg:inset-x-auto lg:max-w-sm"
        role="dialog" aria-label="ติดตั้งแอปตลาดชุมชนโคราช">
        <div class="rounded-2xl bg-white shadow-2xl shadow-violet-900/25 border border-violet-100 overflow-hidden">
          <!-- แถบบนม่วง -->
          <div class="px-4 pt-3.5 pb-3 flex items-start gap-3" style="background:linear-gradient(120deg,#faf5ff,#f5f3ff)">
            <div class="w-12 h-12 rounded-xl bg-white shadow-sm border border-violet-100 shrink-0 overflow-hidden flex items-center justify-center">
              <img :src="'/icons/icon-192.png'" alt="ตลาดชุมชนโคราช" class="w-full h-full object-contain" />
            </div>
            <div class="flex-1 min-w-0 pt-0.5">
              <p class="text-[15px] font-bold text-slate-800 leading-tight">ติดตั้งแอปตลาดชุมชนโคราช</p>
              <p class="text-[12px] text-slate-500 mt-0.5 leading-snug">เพิ่มลงหน้าจอหลัก เปิดใช้งานได้รวดเร็วเหมือนแอป</p>
            </div>
            <button @click="dismiss" class="shrink-0 -mt-1 -mr-1 w-7 h-7 rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600 flex items-center justify-center transition" aria-label="ปิด">
              <i class="fi fi-rr-cross-small text-base"></i>
            </button>
          </div>

          <!-- Android/Chrome: ปุ่มติดตั้งจริง -->
          <div v-if="mode === 'android'" class="px-4 py-3 flex gap-2">
            <button @click="install"
              class="flex-1 h-10 rounded-xl bg-violet-600 hover:bg-violet-700 active:scale-[.98] text-white font-semibold text-sm transition shadow-md shadow-violet-500/30 flex items-center justify-center gap-2">
              <i class="fi fi-rr-download"></i> ติดตั้งเลย
            </button>
            <button @click="dismiss" class="h-10 px-4 rounded-xl text-slate-500 hover:bg-slate-100 font-medium text-sm transition">ไว้ก่อน</button>
          </div>

          <!-- iOS Safari: บอกวิธี (ไม่มีปุ่มติดตั้งอัตโนมัติ) -->
          <div v-else-if="mode === 'ios'" class="px-4 py-3">
            <ol class="space-y-2 text-[13px] text-slate-600">
              <li class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-violet-100 text-violet-700 text-[11px] font-bold flex items-center justify-center shrink-0">1</span>
                แตะปุ่ม <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-slate-100 border border-slate-200 mx-0.5"><i class="fi fi-rr-share text-[13px] text-sky-600"></i></span> แชร์ ด้านล่างจอ
              </li>
              <li class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-violet-100 text-violet-700 text-[11px] font-bold flex items-center justify-center shrink-0">2</span>
                เลือก <span class="font-semibold text-slate-700">"เพิ่มลงในหน้าจอโฮม"</span>
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-slate-100 border border-slate-200"><i class="fi fi-rr-square-plus text-[13px] text-slate-600"></i></span>
              </li>
            </ol>
          </div>
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
const SNOOZE_DAYS  = 7          // ปิดแล้วเว้น 7 วันค่อยเด้งใหม่
const SHOW_DELAY   = 2500       // หน่วง 2.5 วิ ค่อยโผล่ (ไม่รบกวนตอนเข้าเว็บ)

const visible = ref(false)
const mode    = ref('')         // 'android' | 'ios'
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
  if (!deferredPrompt) { visible.value = false; return }
  visible.value = false
  deferredPrompt.prompt()
  try { await deferredPrompt.userChoice } catch (_) {}
  deferredPrompt = null
  localStorage.setItem(DISMISS_KEY, String(Date.now())) // ตัดสินใจแล้ว ไม่ต้องเด้งซ้ำเร็วๆ
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
  // iOS ไม่มี beforeinstallprompt → เช็คเองแล้วโชว์วิธี
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
</style>
