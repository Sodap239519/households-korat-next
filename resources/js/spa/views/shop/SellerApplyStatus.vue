<template>
  <div class="max-w-lg mx-auto px-4 sm:px-6 pb-24">

    <!-- ===== Header Banner ===== -->
    <div class="-mx-4 sm:-mx-6 -mt-0 mb-6 overflow-hidden"
      style="background:linear-gradient(135deg,#4c1d95 0%,#7c3aed 50%,#f97316 100%)">
      <div class="px-5 pt-5 pb-3 flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-white/20 border border-white/25 flex items-center justify-center text-white text-2xl shrink-0 shadow-lg backdrop-blur-sm">
          <i class="fi fi-rr-shop"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-white font-extrabold text-xl tracking-wide leading-none">ติดตามสถานะ</p>
          <p class="text-white/70 text-xs mt-1.5 leading-relaxed truncate">{{ info?.business_name || 'คำขอสมัครร้านค้า' }}</p>
        </div>
        <i class="fi fi-rr-badge-check text-white/15 text-6xl shrink-0 leading-none select-none hidden sm:block"></i>
      </div>

      <!-- Step indicator -->
      <div class="px-5 pb-4 flex items-center gap-2 text-xs">
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/20 text-white/60">
          <i class="fi fi-rr-check"></i> กรอกข้อมูล
        </div>
        <i class="fi fi-rr-angle-right text-white/40 text-[10px]"></i>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/20 text-white/60">
          <i class="fi fi-rr-check"></i> ส่งคำขอ
        </div>
        <i class="fi fi-rr-angle-right text-white/40 text-[10px]"></i>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition"
          :class="info?.status === 'approved'
            ? 'bg-emerald-400/40 text-white font-semibold'
            : info?.status === 'rejected'
              ? 'bg-rose-400/40 text-white font-semibold'
              : info?.status === 'revision_requested'
                ? 'bg-amber-400/50 text-white font-semibold'
                : 'bg-white/25 text-white font-semibold'">
          <i :class="info?.status === 'approved' ? 'fi fi-rr-badge-check'
                     : info?.status === 'rejected' ? 'fi fi-rr-cross-circle'
                     : info?.status === 'revision_requested' ? 'fi fi-rr-undo'
                     : 'fi fi-rr-time-half-past'"></i>
          {{ info?.status === 'approved' ? 'อนุมัติแล้ว!'
           : info?.status === 'rejected' ? 'ไม่ผ่าน'
           : info?.status === 'revision_requested' ? 'ต้องแก้ไข'
           : 'รอผล' }}
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-spinner animate-spin text-2xl"></i>
      <p class="mt-2 text-sm">กำลังโหลด...</p>
    </div>

    <!-- Not found -->
    <div v-else-if="notFound" class="box-card p-10 text-center">
      <i class="fi fi-rr-search text-4xl text-slate-300 mb-3"></i>
      <p class="font-semibold text-slate-700">ไม่พบคำขอนี้</p>
      <p class="text-sm text-slate-400 mt-1 mb-4">กรุณาตรวจสอบหมายเลขติดตามอีกครั้ง</p>
      <RouterLink to="/shop/seller-apply/my"
        class="inline-flex items-center gap-2 px-5 h-10 rounded-full bg-violet-600 text-white text-sm font-semibold">
        <i class="fi fi-rr-list"></i> ดูรายการสมัครของฉัน
      </RouterLink>
    </div>

    <template v-else-if="info">

      <!-- Status summary card -->
      <div class="box-card p-5 mb-4"
        :class="info.status === 'approved' ? 'border border-emerald-200 bg-emerald-50/30'
              : info.status === 'rejected' ? 'border border-rose-200 bg-rose-50/20' : ''">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl shrink-0"
            :class="statusStyle.bg">
            <i :class="[statusStyle.icon, statusStyle.text]"></i>
          </div>
          <div>
            <p class="text-xs text-slate-400 mb-0.5">สถานะล่าสุด</p>
            <p class="font-bold text-base" :class="statusStyle.text">{{ info.status_label }}</p>
          </div>
        </div>
        <div class="space-y-2 text-sm divide-y divide-slate-100">
          <div class="flex justify-between py-1.5">
            <span class="text-slate-400">ชื่อผู้สมัคร</span>
            <span class="font-medium text-slate-800">{{ info.applicant_name }}</span>
          </div>
          <div class="flex justify-between py-1.5">
            <span class="text-slate-400">ชื่อกิจการ</span>
            <span class="font-medium text-slate-800">{{ info.business_name }}</span>
          </div>
          <div class="flex justify-between py-1.5">
            <span class="text-slate-400">วันที่สมัคร</span>
            <span class="text-slate-600">{{ fmtDate(info.submitted_at) }}</span>
          </div>
        </div>

        <!-- CTA by status -->
        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col gap-2">
          <!-- Approved -->
          <template v-if="info.status === 'approved'">
            <p class="text-sm text-emerald-700 font-medium text-center mb-2">
              <i class="fi fi-rr-party-horn mr-1"></i> ยินดีด้วย! ร้านค้าของคุณได้รับการอนุมัติแล้ว
            </p>
            <!-- Login credentials info -->
            <div class="bg-violet-50 border border-violet-200 rounded-xl p-3 mb-2 space-y-2 text-sm">
              <p class="font-semibold text-violet-700 flex items-center gap-1.5">
                <i class="fi fi-rr-key"></i> ข้อมูลเข้าสู่ระบบผู้ขาย
              </p>
              <div class="flex items-center justify-between gap-2">
                <span class="text-slate-500 shrink-0">อีเมล</span>
                <span class="font-medium text-slate-800 font-mono text-xs break-all text-right">{{ info.created_user_email || info.applicant_email || '—' }}</span>
              </div>
              <!-- Temp password row -->
              <div class="pt-1 border-t border-violet-100">
                <p class="text-slate-500 text-xs mb-2">รหัสผ่านชั่วคราว</p>

                <!-- ยังไม่ได้กดเปิด -->
                <button v-if="info.password_available && !revealedPassword"
                  :disabled="revealLoading"
                  class="w-full flex items-center justify-center gap-2 h-10 rounded-xl bg-violet-100 hover:bg-violet-200 text-violet-700 text-sm font-semibold transition disabled:opacity-60"
                  @click="revealPassword">
                  <i :class="revealLoading ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-eye'" class="leading-none"></i>
                  {{ revealLoading ? 'กำลังโหลด...' : 'กดเพื่อดูรหัสผ่าน (นับ 30 นาทีหลังกด)' }}
                </button>

                <!-- เปิดแล้ว — แสดงพร้อม countdown -->
                <div v-else-if="revealedPassword" class="space-y-2">
                  <div class="flex items-center gap-2">
                    <span class="flex-1 font-mono text-base font-bold text-slate-800 tracking-widest select-all px-3 py-2 bg-white rounded-xl border border-violet-200"
                      :class="showPassword ? '' : 'blur-[4px] select-none'">
                      {{ revealedPassword }}
                    </span>
                    <button type="button" @click="showPassword = !showPassword"
                      class="w-10 h-10 rounded-xl bg-white border border-violet-200 flex items-center justify-center text-violet-500 hover:bg-violet-100 transition shrink-0">
                      <i :class="showPassword ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye'" class="leading-none"></i>
                    </button>
                  </div>
                  <!-- Countdown -->
                  <div class="flex items-center gap-1.5 text-xs"
                    :class="remainingSeconds < 300 ? 'text-rose-500' : 'text-amber-600'">
                    <i class="fi fi-rr-clock shrink-0"></i>
                    <span v-if="remainingSeconds > 0">
                      รหัสหมดอายุใน <strong>{{ countdownLabel }}</strong> — เข้าสู่ระบบและเปลี่ยนรหัสผ่านโดยเร็ว
                    </span>
                    <span v-else class="text-rose-500 font-semibold">รหัสผ่านหมดอายุแล้ว</span>
                  </div>
                </div>

                <!-- หมดอายุถาวร -->
                <p v-else class="text-xs text-slate-400 text-center py-1">
                  รหัสผ่านหมดอายุแล้ว — ติดต่อทีมงาน
                </p>
              </div>
            </div>
            <button @click="handleSellerLogin"
              class="flex items-center justify-center gap-2 h-11 rounded-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition w-full">
              <i class="fi fi-rr-sign-in-alt"></i>
              {{ isMarketStaff ? 'ไปหน้าจัดการร้านค้า' : 'เข้าสู่ระบบผู้ขาย' }}
            </button>
          </template>
          <!-- Rejected -->
          <template v-else-if="info.status === 'rejected'">
            <p v-if="info.admin_note || info.superadmin_note" class="text-sm text-rose-600 bg-rose-50 rounded-xl p-3 leading-relaxed">
              <i class="fi fi-rr-comment mr-1"></i> {{ info.superadmin_note || info.admin_note }}
            </p>
            <RouterLink to="/shop/seller-apply"
              class="flex items-center justify-center gap-2 h-11 rounded-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition">
              <i class="fi fi-rr-refresh"></i> สมัครใหม่
            </RouterLink>
          </template>
          <!-- Revision requested -->
          <template v-else-if="info.status === 'revision_requested'">
            <div class="bg-amber-50 rounded-xl p-3 mb-2">
              <p class="text-xs font-semibold text-amber-700 mb-1 flex items-center gap-1">
                <i class="fi fi-rr-undo"></i> ข้อมูลที่ต้องแก้ไข
              </p>
              <p class="text-sm text-amber-800 leading-relaxed">{{ info.revision_note }}</p>
            </div>
            <RouterLink :to="`/shop/seller-apply?edit=${info.token || tokenInput}`"
              class="flex items-center justify-center gap-2 h-11 rounded-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold transition">
              <i class="fi fi-rr-edit"></i> แก้ไขและส่งใหม่
            </RouterLink>
          </template>
          <!-- Pending/In review -->
          <template v-else>
            <p class="text-xs text-slate-400 text-center">
              <i class="fi fi-rr-clock mr-1"></i> ทีมงานจะตรวจสอบและติดต่อกลับภายใน 1–3 วันทำการ
            </p>
            <RouterLink to="/shop/seller-apply/my"
              class="flex items-center justify-center gap-2 h-10 rounded-full border border-violet-200 text-violet-600 text-sm font-medium hover:bg-violet-50 transition">
              <i class="fi fi-rr-list"></i> ดูรายการสมัครทั้งหมด
            </RouterLink>
          </template>
        </div>
      </div>

      <!-- Timeline -->
      <div class="box-card p-5 mb-4">
        <h3 class="font-semibold text-slate-700 mb-4 text-sm flex items-center gap-2">
          <i class="fi fi-rr-time-past text-violet-500"></i> ขั้นตอนการพิจารณา
        </h3>
        <div class="space-y-4">
          <TimelineStep
            :done="true"
            icon="fi-rr-paper-plane"
            label="ส่งคำขอสมัครแล้ว"
            :sub="fmtDate(info.submitted_at)" />
          <TimelineStep
            :done="['admin_approved','escalated','approved','rejected'].includes(info.status)"
            :current="info.status === 'pending'"
            icon="fi-rr-user-check"
            label="Admin พิจารณาขั้นแรก"
            :sub="info.status === 'escalated' ? 'เลยกำหนด 1 ชั่วโมง — ส่งตรงถึง superadmin' : undefined"
            :warn="info.status === 'escalated'" />
          <TimelineStep
            :done="['approved','rejected'].includes(info.status)"
            :current="['admin_approved','escalated'].includes(info.status)"
            icon="fi-rr-badge-check"
            label="Superadmin พิจารณาขั้นสุดท้าย" />
          <TimelineStep
            :done="info.status === 'approved'"
            :rejected="info.status === 'rejected'"
            :icon="info.status === 'approved' ? 'fi-rr-shop' : 'fi-rr-cross-circle'"
            :label="info.status === 'approved' ? 'อนุมัติ — เริ่มขายได้เลย!' : info.status === 'rejected' ? 'ไม่ผ่านการพิจารณา' : 'ผลการอนุมัติ'" />
        </div>
      </div>

      <!-- Notes (for non-rejected, shown inline in CTA for rejected) -->
      <div v-if="(info.superadmin_note || info.admin_note) && info.status !== 'rejected'" class="box-card p-5 mb-4">
        <h3 class="font-semibold text-slate-700 mb-3 text-sm flex items-center gap-2">
          <i class="fi fi-rr-comment text-violet-500"></i> หมายเหตุจากทีมงาน
        </h3>
        <p class="text-sm text-slate-600 leading-relaxed">{{ info.superadmin_note || info.admin_note }}</p>
      </div>

    </template>

    <!-- ประวัติในอุปกรณ์นี้ -->
    <div v-if="history.length" class="box-card p-5 mt-4">
      <p class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
        <i class="fi fi-rr-time-past text-violet-500"></i> ประวัติการสมัครในอุปกรณ์นี้
      </p>
      <div class="space-y-2">
        <button v-for="h in history" :key="h.token"
          @click="fetchStatus(h.token); tokenInput = h.token"
          class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-violet-50 transition text-left"
          :class="h.token === tokenInput ? 'ring-1 ring-violet-300 bg-violet-50' : ''">
          <div>
            <p class="text-sm font-medium text-slate-700">{{ h.business_name }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ fmtDate(h.submitted_at) }}</p>
          </div>
          <i class="fi fi-rr-angle-right text-slate-400 text-xs shrink-0"></i>
        </button>
      </div>
    </div>

    <!-- ค้นหาด้วย token (สำรอง) -->
    <div class="box-card p-5 mt-4">
      <p class="text-sm text-slate-500 mb-2 flex items-center gap-1.5">
        <i class="fi fi-rr-search text-slate-400 text-xs"></i> ค้นหาด้วยหมายเลขติดตาม
      </p>
      <div class="flex gap-2">
        <input v-model="tokenInput" class="inp flex-1" placeholder="วางหมายเลขติดตามที่นี่" @keyup.enter="search" />
        <button @click="search" class="px-4 h-11 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700 transition">
          ค้นหา
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, defineComponent, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/index.js'
import { useAuth } from '../../composables/useAuth.js'

const route        = useRoute()
const router       = useRouter()
const { user, logout, isMarketStaff } = useAuth()
const loading      = ref(false)
const notFound     = ref(false)
const info         = ref(null)
const tokenInput   = ref('')
const history      = ref([])
const showPassword      = ref(false)
const revealLoading     = ref(false)
const revealedPassword  = ref(null)
const remainingSeconds  = ref(0)
let   countdownInterval = null

const countdownLabel = computed(() => {
  const m = Math.floor(remainingSeconds.value / 60)
  const s = remainingSeconds.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

function startCountdown(expiresAt) {
  clearInterval(countdownInterval)
  const tick = () => {
    const diff = Math.max(0, Math.floor((new Date(expiresAt) - Date.now()) / 1000))
    remainingSeconds.value = diff
    if (diff === 0) clearInterval(countdownInterval)
  }
  tick()
  countdownInterval = setInterval(tick, 1000)
}

async function revealPassword() {
  if (revealLoading.value) return
  revealLoading.value = true
  try {
    const token = info.value?.token || tokenInput.value
    const { data } = await api.post(`/seller/apply/${token}/reveal-password`)
    revealedPassword.value = data.temp_password
    showPassword.value = true
    startCountdown(data.password_expires_at)
  } catch (e) {
    if (e.response?.status === 410) {
      info.value = { ...info.value, password_available: false }
    }
  } finally {
    revealLoading.value = false
  }
}

const APPLY_HISTORY_KEY = 'seller_apply_history'

function loadHistory() {
  try {
    history.value = JSON.parse(localStorage.getItem(APPLY_HISTORY_KEY) || '[]')
  } catch { history.value = [] }
}

const STATUS_STYLE = {
  pending:            { bg: 'bg-amber-100',  text: 'text-amber-600',   icon: 'fi fi-rr-time-half-past' },
  admin_approved:     { bg: 'bg-blue-100',   text: 'text-blue-600',    icon: 'fi fi-rr-user-check' },
  escalated:          { bg: 'bg-orange-100', text: 'text-orange-600',  icon: 'fi fi-rr-exclamation' },
  approved:           { bg: 'bg-emerald-100',text: 'text-emerald-600', icon: 'fi fi-rr-badge-check' },
  rejected:           { bg: 'bg-rose-100',   text: 'text-rose-600',    icon: 'fi fi-rr-cross-circle' },
  revision_requested: { bg: 'bg-amber-100',  text: 'text-amber-600',   icon: 'fi fi-rr-undo' },
}
const statusStyle = computed(() => STATUS_STYLE[info.value?.status] ?? STATUS_STYLE.pending)

function fmtDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' })
}

async function fetchStatus(token) {
  if (!token) return
  loading.value = true; notFound.value = false; info.value = null
  revealedPassword.value = null; clearInterval(countdownInterval)
  try {
    const { data } = await api.get(`/seller/apply/status/${token}`)
    info.value = data
    // ถ้าโหลดหน้ามาแล้ว 30 นาทียังไม่หมด → เริ่ม countdown ต่อทันที
    if (data.temp_password && data.password_expires_at) {
      revealedPassword.value = data.temp_password
      showPassword.value = false
      startCountdown(data.password_expires_at)
    }
  } catch (e) {
    notFound.value = true
  } finally {
    loading.value = false
  }
}

function search() {
  if (tokenInput.value.trim()) fetchStatus(tokenInput.value.trim())
}

// TimelineStep inline component
const TimelineStep = defineComponent({
  props: { done: Boolean, current: Boolean, warn: Boolean, rejected: Boolean, icon: String, label: String, sub: String },
  setup(props) {
    return () => h('div', { class: 'flex items-start gap-3' }, [
      h('div', {
        class: [
          'w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0 mt-0.5 transition',
          props.rejected ? 'bg-rose-500 text-white' :
          props.done    ? 'bg-emerald-500 text-white' :
          props.current ? (props.warn ? 'bg-orange-400 text-white' : 'bg-violet-500 text-white') :
          'bg-slate-100 text-slate-300'
        ]
      }, [h('i', { class: `fi ${props.rejected ? 'fi-rr-cross-small' : props.done ? 'fi-rr-check' : props.icon}` })]),
      h('div', {}, [
        h('p', {
          class: ['text-sm font-medium', props.rejected ? 'text-rose-600' : props.done ? 'text-slate-800' : props.current ? 'text-violet-700' : 'text-slate-400']
        }, props.label),
        props.sub ? h('p', { class: 'text-xs text-orange-500 mt-0.5' }, props.sub) : null,
      ])
    ])
  }
})

async function handleSellerLogin() {
  if (isMarketStaff.value) {
    router.push('/app/market')
    return
  }
  if (user.value) {
    try { await logout() } catch { /* ignore */ }
  }
  router.push('/shop/login')
}

onMounted(() => {
  loadHistory()
  const token = route.params.token
  if (token) { tokenInput.value = token; fetchStatus(token) }
  else if (history.value.length) { fetchStatus(history.value[0].token); tokenInput.value = history.value[0].token }
})

onUnmounted(() => {
  clearInterval(countdownInterval)
})
</script>

<style scoped>
.inp { height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); background: white; }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
</style>
