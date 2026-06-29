<template>
  <div class="relative" ref="root">
    <!-- Trigger button -->
    <button
      @click="toggle"
      :title="label"
      class="transition active:scale-95"
      :class="{
        'flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold': variant === 'pill',
        'w-10 h-10 rounded-full flex items-center justify-center': variant === 'icon',
        'w-9 h-9 rounded-xl flex items-center justify-center': variant === 'icon-sm',
        'bg-white/20 hover:bg-white/30 text-white border border-white/30': theme === 'light',
        'bg-violet-50 hover:bg-violet-100 text-violet-600 border border-violet-100': theme === 'violet',
        'bg-slate-100 hover:bg-slate-200 text-slate-500 border border-slate-200': theme === 'default',
      }"
    >
      <i class="fi fi-rr-share text-sm leading-none"></i>
      <span v-if="variant === 'pill'">{{ label }}</span>
    </button>

    <!-- Bottom Sheet (Teleport ออกนอก card เพื่อไม่โดน clip) -->
    <Teleport to="body">
      <Transition name="overlay-fade">
        <div v-if="open" class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm" @click="open = false"></div>
      </Transition>
      <Transition name="sheet-up">
        <div v-if="open" class="fixed bottom-0 inset-x-0 z-[61] bg-white rounded-t-3xl shadow-2xl overflow-hidden"
          style="padding-bottom: env(safe-area-inset-bottom, 0px)">
          <!-- Handle bar -->
          <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1 rounded-full bg-slate-200"></div>
          </div>
          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
            <h3 class="font-bold text-slate-800">แชร์</h3>
            <button @click="open = false"
              class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition">
              <i class="fi fi-rr-cross-small text-base"></i>
            </button>
          </div>
          <!-- Options -->
          <div class="pb-4">
            <!-- Web Share API -->
            <button v-if="canNativeShare" @click="nativeShare"
              class="w-full flex items-center gap-4 px-5 py-3.5 text-slate-700 hover:bg-violet-50 active:bg-violet-100 transition text-left">
              <div class="w-11 h-11 rounded-2xl bg-violet-100 flex items-center justify-center shrink-0">
                <i class="fi fi-rr-share text-violet-600 text-lg"></i>
              </div>
              <span class="font-medium">แชร์ไปยัง...</span>
            </button>

            <!-- คัดลอกลิงก์ -->
            <button @click="copyLink"
              class="w-full flex items-center gap-4 px-5 py-3.5 text-slate-700 hover:bg-slate-50 active:bg-slate-100 transition text-left">
              <div class="w-11 h-11 rounded-2xl bg-slate-100 flex items-center justify-center shrink-0">
                <i class="fi fi-rr-link text-slate-600 text-lg"></i>
              </div>
              <span class="font-medium">{{ copied ? 'คัดลอกแล้ว ✓' : 'คัดลอกลิงก์' }}</span>
            </button>

            <!-- ส่งเข้าแชทร้านค้า -->
            <button v-if="chatGroupSlug && loggedIn" @click="sendToChat"
              class="w-full flex items-center gap-4 px-5 py-3.5 text-slate-700 hover:bg-violet-50 active:bg-violet-100 transition text-left">
              <div class="w-11 h-11 rounded-2xl bg-violet-100 flex items-center justify-center shrink-0">
                <i class="fi fi-rr-comment-alt text-violet-600 text-lg"></i>
              </div>
              <span class="font-medium">ส่งเข้าแชทร้านค้า</span>
            </button>

            <!-- แชร์ LINE -->
            <a :href="lineShareUrl" target="_blank" rel="noopener noreferrer" @click="open = false"
              class="w-full flex items-center gap-4 px-5 py-3.5 text-slate-700 hover:bg-green-50 active:bg-green-100 transition">
              <div class="w-11 h-11 rounded-2xl bg-green-100 flex items-center justify-center shrink-0">
                <span class="font-extrabold text-green-600 text-lg" style="font-family:sans-serif">L</span>
              </div>
              <span class="font-medium">แชร์ LINE</span>
            </a>

            <!-- แชร์ Facebook -->
            <a :href="fbShareUrl" target="_blank" rel="noopener noreferrer" @click="open = false"
              class="w-full flex items-center gap-4 px-5 py-3.5 text-slate-700 hover:bg-blue-50 active:bg-blue-100 transition">
              <div class="w-11 h-11 rounded-2xl bg-blue-100 flex items-center justify-center shrink-0">
                <i class="fi fi-brands-facebook text-blue-600 text-lg"></i>
              </div>
              <span class="font-medium">แชร์ Facebook</span>
            </a>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../../api/index.js'
import { useAuth } from '../../../composables/useAuth.js'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
  title:        { type: String, required: true },
  text:         { type: String, default: '' },
  url:          { type: String, required: true },
  label:        { type: String, default: 'แชร์' },
  variant:      { type: String, default: 'icon' },
  theme:        { type: String, default: 'default' },
  align:        { type: String, default: 'right' },
  chatGroupSlug:{ type: String, default: '' },
  productSlug:  { type: String, default: '' },
})

const open   = ref(false)
const copied = ref(false)
const router = useRouter()
const toast  = useToast()
const { user } = useAuth()

const loggedIn       = computed(() => !!user.value)
const canNativeShare = computed(() => typeof navigator !== 'undefined' && !!navigator.share)

const absoluteUrl = computed(() => {
  if (props.url.startsWith('http')) return props.url
  return `${window.location.origin}${props.url}`
})

const lineShareUrl = computed(() =>
  `https://line.me/R/msg/text/?${encodeURIComponent(props.title + '\n' + absoluteUrl.value)}`
)
const fbShareUrl = computed(() =>
  `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(absoluteUrl.value)}`
)

function toggle() { open.value = !open.value }

async function nativeShare() {
  open.value = false
  try {
    await navigator.share({ title: props.title, text: props.text, url: absoluteUrl.value })
  } catch { /* ยกเลิกโดยผู้ใช้ */ }
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(absoluteUrl.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
    toast.add({ severity: 'success', summary: 'คัดลอกลิงก์แล้ว', life: 2000 })
  } catch {
    toast.add({ severity: 'warn', summary: 'ไม่สามารถคัดลอกได้', life: 2000 })
  }
  setTimeout(() => { open.value = false }, 1200)
}

async function sendToChat() {
  open.value = false
  if (!user.value) { router.push('/shop/login'); return }
  try {
    const { data } = await api.post(`/shop/chat/start/${props.chatGroupSlug}`)
    const query = { id: data.id }
    if (props.productSlug) query.productSlug = props.productSlug
    router.push({ path: '/shop/chat', query })
    toast.add({ severity: 'info', summary: 'เปิดแชทร้านค้า', life: 1800 })
  } catch {
    toast.add({ severity: 'warn', summary: 'ไม่สามารถเปิดแชทได้', life: 2000 })
  }
}
</script>

<style scoped>
.overlay-fade-enter-active, .overlay-fade-leave-active { transition: opacity 0.2s ease; }
.overlay-fade-enter-from, .overlay-fade-leave-to { opacity: 0; }
.sheet-up-enter-active, .sheet-up-leave-active { transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1); }
.sheet-up-enter-from, .sheet-up-leave-to { transform: translateY(100%); }
</style>
