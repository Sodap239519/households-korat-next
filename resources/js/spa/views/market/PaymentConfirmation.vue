<template>
  <div class="p-3 sm:p-5 space-y-4">
    <div>
      <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-receipt text-violet-600"></i> ยืนยันการชำระเงิน
      </h2>
      <p class="text-xs text-slate-400 mt-0.5">ตรวจสลิปแล้วยืนยัน หรือ ปฏิเสธ</p>
    </div>

    <!-- Card list -->
    <div class="space-y-3">
      <template v-if="loading">
        <div v-for="n in 3" :key="n" class="box-card p-4 skeleton h-44"></div>
      </template>
      <div v-else-if="!rows.length" class="box-card py-16 text-center text-slate-400">
        <i class="fi fi-rr-check-circle text-5xl text-emerald-400"></i>
        <p class="mt-2 text-sm font-medium">ไม่มีสลิปที่รอยืนยัน</p>
        <p class="text-xs mt-1">ดีมาก! ทุกรายการถูกจัดการแล้ว</p>
      </div>
      <div v-else v-for="row in rows" :key="row.id" class="box-card overflow-hidden">
        <!-- Top: slip + info -->
        <div class="flex gap-3 p-4">
          <!-- Slip thumbnail → fullscreen -->
          <div class="shrink-0">
            <button v-if="row.slip_url"
              @click="openFullscreen(row.slip_url)"
              class="relative w-24 h-24 rounded-2xl overflow-hidden border-2 border-violet-200 shadow-md hover:border-violet-400 active:scale-95 transition block group">
              <img :src="row.slip_url" class="w-full h-full object-cover" />
              <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center">
                <i class="fi fi-rr-search text-white opacity-0 group-hover:opacity-100 transition text-xl drop-shadow"></i>
              </div>
            </button>
            <div v-else class="w-24 h-24 rounded-2xl bg-slate-100 flex flex-col items-center justify-center text-slate-300 border border-slate-200">
              <i class="fi fi-rr-picture text-2xl"></i>
              <span class="text-[10px] mt-1">ไม่มีสลิป</span>
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0 space-y-2">
            <div>
              <p class="font-bold text-slate-800">{{ row.order?.order_no }}</p>
              <p class="text-xs text-slate-500 truncate mt-0.5">{{ row.order?.user?.name || row.order?.shipping_name }}</p>
            </div>

            <!-- Amount comparison -->
            <div class="flex items-center gap-2 flex-wrap">
              <div class="px-2.5 py-1.5 rounded-xl bg-violet-50 border border-violet-100 text-center">
                <p class="text-[10px] text-violet-400 leading-none">สลิปโอน</p>
                <p class="font-bold text-violet-700 text-sm mt-0.5">฿{{ fmt(row.amount) }}</p>
              </div>
              <div class="flex flex-col items-center">
                <i class="text-base" :class="amountMatch(row) ? 'fi fi-rr-check text-emerald-500' : 'fi fi-rr-cross text-rose-500'"></i>
              </div>
              <div class="px-2.5 py-1.5 rounded-xl border text-center"
                :class="amountMatch(row) ? 'bg-emerald-50 border-emerald-100' : 'bg-rose-50 border-rose-100'">
                <p class="text-[10px] leading-none" :class="amountMatch(row) ? 'text-emerald-400' : 'text-rose-400'">ยอดออเดอร์</p>
                <p class="font-bold text-sm mt-0.5" :class="amountMatch(row) ? 'text-emerald-700' : 'text-rose-600'">฿{{ fmt(row.order?.total) }}</p>
              </div>
            </div>

            <!-- Verify status + QR result -->
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2 py-0.5 rounded-full text-[11px] border font-medium" :class="verifyCls(row.verify_status)">
                {{ verifyLabel(row.verify_status) }}
              </span>
              <span v-if="row.bank_ref" class="text-[11px] text-slate-400 font-mono">ref: {{ row.bank_ref }}</span>
            </div>

            <!-- QR scan result for this row -->
            <div v-if="scanResults[row.id]" class="rounded-lg px-2.5 py-2 text-xs flex items-start gap-2"
              :class="scanResults[row.id].found ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200'">
              <i class="fi shrink-0 mt-0.5"
                :class="scanResults[row.id].found ? 'fi-rr-check-circle text-emerald-600' : 'fi-rr-exclamation text-amber-600'"></i>
              <div>
                <p v-if="scanResults[row.id].found" class="font-medium text-emerald-700">
                  QR ยืนยัน: ฿{{ fmt(scanResults[row.id].amount) }}
                  <span v-if="scanResults[row.id].amount == row.order?.total" class="ml-1 text-emerald-600">✓ ตรงกัน</span>
                  <span v-else class="ml-1 text-rose-600">⚠ ไม่ตรง</span>
                </p>
                <p v-else class="text-amber-700">ไม่พบ QR ในสลิป</p>
                <p v-if="scanResults[row.id].ref" class="text-slate-500 font-mono mt-0.5">ref: {{ scanResults[row.id].ref }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- QR scan button + main actions -->
        <div class="border-t border-slate-100">
          <!-- Scan button row -->
          <div v-if="row.slip_url" class="px-4 py-2 border-b border-slate-50">
            <button
              @click="scanSlipQR(row)"
              :disabled="scanning === row.id"
              class="w-full h-8 rounded-lg border border-violet-200 bg-violet-50 hover:bg-violet-100 text-violet-700 text-xs font-medium flex items-center justify-center gap-2 transition disabled:opacity-50">
              <i :class="scanning === row.id ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-qr'"></i>
              {{ scanning === row.id ? 'กำลังสแกน...' : 'สแกน QR จากสลิป' }}
            </button>
          </div>
          <!-- Reject / Confirm -->
          <div class="grid grid-cols-2 divide-x divide-slate-100">
            <button @click="openReject(row)"
              class="flex items-center justify-center gap-2 py-3.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 active:bg-rose-100 transition">
              <i class="fi fi-rr-cross-circle text-base"></i> ปฏิเสธ
            </button>
            <button @click="confirmPayment(row)"
              class="flex items-center justify-center gap-2 py-3.5 text-sm font-semibold text-emerald-600 hover:bg-emerald-50 active:bg-emerald-100 transition">
              <i class="fi fi-rr-check-circle text-base"></i> ยืนยัน
            </button>
          </div>
        </div>
      </div>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <!-- Reject dialog -->
    <Dialog v-model:visible="rejectOpen" modal header="ปฏิเสธการชำระเงิน" :style="{ width: '95vw', maxWidth: '26rem' }">
      <div class="space-y-2">
        <label class="form-label">เหตุผล *</label>
        <Textarea v-model="rejectReason" rows="3" class="w-full" placeholder="กรุณาระบุเหตุผลที่ปฏิเสธ..." />
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="rejectOpen = false" />
        <Button label="ยืนยันปฏิเสธ" severity="danger" :loading="busy" @click="doReject" />
      </template>
    </Dialog>

    <!-- Fullscreen slip viewer -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="fullscreenUrl"
          class="fixed inset-0 z-[9999] bg-black/95 flex items-center justify-center"
          style="touch-action: pinch-zoom;"
          @click.self="fullscreenUrl = null">
          <img :src="fullscreenUrl"
            class="max-w-full max-h-full object-contain select-none"
            style="touch-action: pinch-zoom;" />
          <div class="absolute top-4 right-4 flex gap-2">
            <!-- Download button -->
            <a :href="fullscreenUrl" target="_blank" download
              class="w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition backdrop-blur-sm">
              <i class="fi fi-rr-download"></i>
            </a>
            <button @click="fullscreenUrl = null"
              class="w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition backdrop-blur-sm">
              <i class="fi fi-rr-cross"></i>
            </button>
          </div>
          <p class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/40 text-xs pointer-events-none">
            Pinch เพื่อ zoom · กดนอกภาพเพื่อปิด
          </p>
        </div>
      </Transition>
    </Teleport>

    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import Pagination from '../components/Pagination.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'

const toast = useToast()
const rows        = ref([])
const meta        = ref({})
const loading     = ref(false)
const page        = ref(1)
const fullscreenUrl = ref(null)
const rejectOpen  = ref(false)
const rejectReason = ref('')
const rejectTarget = ref(null)
const busy        = ref(false)
const scanning    = ref(null)       // row.id currently scanning
const scanResults = reactive({})    // { [row.id]: { found, amount, ref } }

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function amountMatch(row) { return Number(row.amount) === Number(row.order?.total) }
function verifyLabel(s) { return { passed: 'ผ่านอัตโนมัติ', failed: 'ไม่ผ่าน', skipped: 'ตรวจเอง', unchecked: 'ยังไม่ตรวจ' }[s] || s }
function verifyCls(s) { return {
  passed: 'bg-emerald-50 text-emerald-700 border-emerald-300',
  failed: 'bg-rose-50 text-rose-700 border-rose-300',
}[s] || 'bg-slate-100 text-slate-500 border-slate-300' }

function openFullscreen(url) { fullscreenUrl.value = url }

/* ===== QR scan from seller side ===== */
async function scanSlipQR(row) {
  if (scanning.value === row.id) return
  scanning.value = row.id
  try {
    const jsQR = (await import('jsqr')).default

    const img = new Image()
    img.crossOrigin = 'anonymous'
    img.src = row.slip_url + (row.slip_url.includes('?') ? '&' : '?') + 't=' + Date.now()
    await new Promise((res, rej) => { img.onload = res; img.onerror = rej })

    const canvas = document.createElement('canvas')
    canvas.width  = img.naturalWidth
    canvas.height = img.naturalHeight
    const ctx = canvas.getContext('2d')
    ctx.drawImage(img, 0, 0)
    const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height)

    const code = jsQR(imgData.data, imgData.width, imgData.height, { inversionAttempts: 'dontInvert' })

    if (code?.data) {
      const { amount, ref } = parseEMV(code.data)
      scanResults[row.id] = { found: true, amount, ref }
    } else {
      scanResults[row.id] = { found: false }
    }
  } catch {
    scanResults[row.id] = { found: false }
    toast.add({ severity: 'warn', summary: 'สแกนไม่ได้', detail: 'ภาพอาจโหลดไม่ได้ ลองดูแบบ fullscreen แทน', life: 3000 })
  } finally { scanning.value = null }
}

function parseEMV(raw) {
  let amount = null, ref = null, i = 0
  while (i < raw.length - 3) {
    const tag = raw.slice(i, i + 2)
    const len = parseInt(raw.slice(i + 2, i + 4), 10)
    if (isNaN(len) || i + 4 + len > raw.length) break
    const val = raw.slice(i + 4, i + 4 + len)
    if (tag === '54') { const n = parseFloat(val); if (!isNaN(n)) amount = n }
    if (tag === '62') {
      let j = 0
      while (j < val.length - 3) {
        const st = val.slice(j, j + 2), sl = parseInt(val.slice(j + 2, j + 4), 10)
        if (isNaN(sl) || j + 4 + sl > val.length) break
        const sv = val.slice(j + 4, j + 4 + sl)
        if (st === '05') ref = sv
        j += 4 + sl
      }
    }
    i += 4 + len
  }
  return { amount, ref }
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/market/payments', { params: { page: page.value } })
    rows.value = data.data || []
    meta.value = data
  } finally { loading.value = false }
}
function goPage(p) { page.value = p; load() }

async function confirmPayment(p) {
  try {
    await api.post(`/market/payments/${p.id}/confirm`)
    toast.add({ severity: 'success', summary: 'ยืนยันแล้ว', life: 2000 })
    load()
  } catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
}

function openReject(p) { rejectTarget.value = p; rejectReason.value = ''; rejectOpen.value = true }
async function doReject() {
  if (!rejectReason.value.trim()) return
  busy.value = true
  try {
    await api.post(`/market/payments/${rejectTarget.value.id}/reject`, { reason: rejectReason.value })
    toast.add({ severity: 'success', summary: 'ปฏิเสธแล้ว', life: 2000 })
    rejectOpen.value = false; load()
  } catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
  finally { busy.value = false }
}

onMounted(load)
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
