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

            <!-- Verify status badge -->
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2 py-0.5 rounded-full text-[11px] border font-medium" :class="verifyCls(row.verify_status)">
                {{ verifyLabel(row.verify_status) }}
              </span>
              <span v-if="row.bank_ref" class="text-[11px] text-slate-400 font-mono">ref: {{ row.bank_ref }}</span>
            </div>

            <!-- OCR scan result — checklist -->
            <div v-if="scanResults[row.id]" class="rounded-xl border text-xs overflow-hidden"
              :class="overallScanClass(scanResults[row.id], row)">
              <!-- Header -->
              <div class="px-3 py-1.5 flex items-center gap-2 border-b border-current/10 bg-black/[0.03]">
                <i class="fi fi-rr-scan shrink-0 text-[11px]"></i>
                <span class="font-semibold text-[11px]">ผลการอ่านสลิปจากระบบ</span>
              </div>

              <!-- Checklist rows -->
              <div class="divide-y divide-current/10">

                <!-- 1. ยอดเงิน -->
                <div class="flex items-start gap-2 px-3 py-2">
                  <i class="fi shrink-0 mt-0.5 text-[11px]"
                    :class="ocrAmountStatus(scanResults[row.id], row) === 'match'     ? 'fi-rr-check-circle text-emerald-600'
                          : ocrAmountStatus(scanResults[row.id], row) === 'not_found' ? 'fi-rr-exclamation text-amber-500'
                                                                                      : 'fi-rr-cross-circle text-red-600'"></i>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-700">
                      ยอดเงิน
                      <template v-if="ocrAmountStatus(scanResults[row.id], row) === 'match'">
                        <span class="text-emerald-600">ตรงกัน</span>
                        <span class="text-slate-400 font-normal ml-1">(฿{{ fmt(scanResults[row.id].amount) }})</span>
                      </template>
                      <template v-else-if="ocrAmountStatus(scanResults[row.id], row) === 'not_found'">
                        <span class="text-amber-600">อ่านไม่ได้จากสลิป</span>
                        <span class="text-slate-400 font-normal ml-1">(ตรวจสอบจากภาพสลิปโดยตรง)</span>
                      </template>
                      <template v-else>
                        <span class="text-red-600">ไม่ตรง</span>
                        <span class="text-slate-400 font-normal ml-1">สลิป ฿{{ fmt(scanResults[row.id].amount) }} ≠ ออเดอร์ ฿{{ fmt(row.order?.total) }}</span>
                      </template>
                    </p>
                  </div>
                </div>

                <!-- 2. ชื่อผู้รับ -->
                <div class="flex items-start gap-2 px-3 py-2">
                  <i class="fi shrink-0 mt-0.5 text-[11px]"
                    :class="nameMatch(scanResults[row.id].recipientName, row.order?.sellerGroup?.bank_account_name) === true  ? 'fi-rr-check-circle text-emerald-600'
                          : nameMatch(scanResults[row.id].recipientName, row.order?.sellerGroup?.bank_account_name) === false ? 'fi-rr-cross-circle text-red-600'
                                                                                                                              : 'fi-rr-user text-amber-500'"></i>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-700">
                      ชื่อผู้รับ
                      <span v-if="nameMatch(scanResults[row.id].recipientName, row.order?.sellerGroup?.bank_account_name) === true"
                        class="text-emerald-600">ตรงกัน</span>
                      <span v-else-if="nameMatch(scanResults[row.id].recipientName, row.order?.sellerGroup?.bank_account_name) === false"
                        class="text-red-600">ไม่ตรง</span>
                      <span v-else class="text-amber-600 font-normal">
                        {{ scanResults[row.id].recipientName ? '(ไม่มีชื่อบัญชีในระบบ)' : 'อ่านไม่ได้จากสลิป' }}
                      </span>
                    </p>
                    <p v-if="scanResults[row.id].recipientName" class="text-slate-500 mt-0.5">
                      สลิป: <span class="font-medium text-slate-700">{{ scanResults[row.id].recipientName }}</span>
                      <template v-if="row.order?.sellerGroup?.bank_account_name">
                        <span class="mx-1 text-slate-300">|</span>
                        ระบบ: <span class="font-medium text-slate-700">{{ row.order.sellerGroup.bank_account_name }}</span>
                      </template>
                    </p>
                  </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center gap-2 px-3 py-2 bg-black/[0.03] text-slate-500">
                  <i class="fi fi-rr-bank text-[10px] shrink-0"></i>
                  <span class="text-[11px]">โปรดตรวจสอบยอดเงินที่บัญชีธนาคารของท่านอีกครั้งก่อนยืนยัน</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- OCR scan button + main actions -->
        <div class="border-t border-slate-100">
          <!-- Scan button row -->
          <div v-if="row.slip_url" class="px-4 py-2 border-b border-slate-50">
            <button
              @click="scanSlipOCR(row)"
              :disabled="scanning === row.id"
              class="w-full h-8 rounded-lg border border-violet-200 bg-violet-50 hover:bg-violet-100 text-violet-700 text-xs font-medium flex items-center justify-center gap-2 transition disabled:opacity-60">
              <i :class="scanning === row.id ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-scan'"></i>
              <template v-if="scanning === row.id">
                อ่านสลิป...
                <span v-if="ocrProgress[row.id]" class="text-violet-400">({{ ocrProgress[row.id] }}%)</span>
              </template>
              <span v-else>อ่านข้อมูลจากสลิป (OCR)</span>
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
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import Pagination from '../components/Pagination.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'

const toast = useToast()
const rows         = ref([])
const meta         = ref({})
const loading      = ref(false)
const page         = ref(1)
const fullscreenUrl = ref(null)
const rejectOpen   = ref(false)
const rejectReason = ref('')
const rejectTarget = ref(null)
const busy         = ref(false)
const scanning     = ref(null)
const ocrProgress  = reactive({})   // { [row.id]: 0-100 }
const scanResults  = reactive({})   // { [row.id]: { amount, recipientName } }

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function amountMatch(row) { return Number(row.amount) === Number(row.order?.total) }
function verifyLabel(s) { return { passed: 'ผ่านอัตโนมัติ', failed: 'ไม่ผ่าน', skipped: 'ตรวจเอง', unchecked: 'ยังไม่ตรวจ' }[s] || s }

/* ===== OCR result helpers ===== */

// สถานะยอดเงินจาก OCR
function ocrAmountStatus(scan, row) {
  if (scan.amount == null) return 'not_found'
  return Math.abs(Number(scan.amount) - Number(row.order?.total)) < 0.01 ? 'match' : 'mismatch'
}

// สถานะโดยรวม → สีกรอบ
function overallScanClass(scan, row) {
  const amtStatus = ocrAmountStatus(scan, row)
  const nameResult = nameMatch(scan.recipientName, row.order?.sellerGroup?.bank_account_name)
  if (amtStatus === 'mismatch' || nameResult === false) {
    return 'bg-red-50 border-2 border-red-400 text-red-900'
  }
  if (amtStatus === 'not_found' || nameResult === null) {
    return 'bg-amber-50 border border-amber-300 text-amber-900'
  }
  return 'bg-emerald-50 border border-emerald-300 text-emerald-900'
}

function verifyCls(s) { return {
  passed: 'bg-emerald-50 text-emerald-700 border-emerald-300',
  failed: 'bg-rose-50 text-rose-700 border-rose-300',
}[s] || 'bg-slate-100 text-slate-500 border-slate-300' }

function openFullscreen(url) { fullscreenUrl.value = url }

/* ===== ชื่อ fuzzy match ===== */
function normalizeName(s) {
  if (!s) return ''
  return s.toLowerCase()
    .replace(/^(นาย|นาง|นางสาว|mr\.?|mrs\.?|ms\.?)\s*/i, '')
    .replace(/\s+/g, ' ')
    .trim()
}
function nameMatch(ocrName, registeredName) {
  if (!ocrName || !registeredName) return null
  const a = normalizeName(ocrName)
  const b = normalizeName(registeredName)
  return a === b || a.includes(b) || b.includes(a)
}

/* ===== ดึงยอดเงินจากข้อความ OCR ===== */
function extractAmount(text) {
  const patterns = [
    /฿\s*([\d,]+\.?\d{0,2})/,
    /([\d,]+\.\d{2})\s*(?:บาท|THB|Baht)/i,
    /(?:จำนวน(?:เงิน)?|ยอด(?:โอน)?|Amount)\s*:?\s*([\d,]+\.?\d*)/i,
  ]
  for (const re of patterns) {
    const m = text.match(re)
    if (m) {
      const n = parseFloat(m[1].replace(/,/g, ''))
      if (!isNaN(n) && n > 0) return n
    }
  }
  // fallback: หาเลขทศนิยม 2 หลักที่มีมากกว่า 0
  const decimals = [...text.matchAll(/([\d,]+\.\d{2})/g)]
    .map(m => parseFloat(m[1].replace(/,/g, '')))
    .filter(n => n > 0)
  if (decimals.length === 1) return decimals[0]
  return null
}

/* ===== ดึงชื่อผู้รับจากข้อความ OCR ===== */
function extractRecipientName(text) {
  const patterns = [
    /ชื่อผู้รับ\s*:?\s*([^\n]+)/,
    /ชื่อบัญชีผู้รับ\s*:?\s*([^\n]+)/,
    /โอน(?:เงิน)?(?:ให้|สู่)\s*:?\s*([^\n]+)/,
    /ผู้รับ\s*:?\s*([^\n]+)/,
    /ถึง\s*:?\s*([^\n]+)/,
    /To\s*:?\s*([^\n]+)/i,
  ]
  for (const re of patterns) {
    const m = text.match(re)
    if (m) {
      const name = m[1].trim().replace(/[^฀-๿a-zA-Z\s.]/g, '').trim()
      if (name.length >= 3) return name
    }
  }
  return null
}

/* ===== OCR ด้วย Tesseract.js ===== */
async function scanSlipOCR(row) {
  if (scanning.value === row.id) return
  scanning.value = row.id
  ocrProgress[row.id] = 0
  try {
    const { createWorker } = await import('tesseract.js')
    const worker = await createWorker(['tha', 'eng'], 1, {
      logger: m => {
        if (m.status === 'recognizing text') {
          ocrProgress[row.id] = Math.round(m.progress * 100)
        }
      },
    })
    const { data } = await worker.recognize(row.slip_url)
    await worker.terminate()

    const text = data.text || ''
    const amount        = extractAmount(text)
    const recipientName = extractRecipientName(text)

    scanResults[row.id] = { amount, recipientName }
  } catch {
    toast.add({ severity: 'warn', summary: 'อ่านสลิปไม่ได้', detail: 'ภาพอาจไม่ชัด ลองดูจาก fullscreen แทน', life: 4000 })
  } finally {
    scanning.value = null
    delete ocrProgress[row.id]
  }
}

/* ===== API ===== */
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
