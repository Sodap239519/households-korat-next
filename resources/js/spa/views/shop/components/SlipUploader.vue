<template>
  <div class="space-y-2">
    <!-- Drop / pick zone -->
    <div
      @dragover.prevent
      @dragenter.prevent="dragging = true"
      @dragleave.prevent="dragging = false"
      @drop.prevent="onDrop"
      @click="fileInput?.click()"
      class="relative rounded-2xl border-2 border-dashed cursor-pointer transition-all select-none"
      :class="dragging
        ? 'border-violet-500 bg-violet-50'
        : preview
          ? 'border-violet-300 bg-violet-50/20'
          : 'border-slate-300 hover:border-violet-400 hover:bg-violet-50/20 bg-slate-50'">

      <!-- No file selected -->
      <div v-if="!preview" class="flex flex-col items-center justify-center py-10 gap-3 pointer-events-none">
        <div class="w-16 h-16 rounded-2xl bg-white shadow-sm flex items-center justify-center">
          <i class="fi fi-rr-camera text-3xl text-violet-500"></i>
        </div>
        <div class="text-center">
          <p class="font-semibold text-slate-700 text-sm">กดหรือลากไฟล์สลิปมาวาง</p>
          <p class="text-xs text-slate-400 mt-0.5">JPG / PNG — ไม่เกิน 5 MB</p>
        </div>
      </div>

      <!-- Preview -->
      <div v-else class="relative p-2">
        <img :src="preview" class="w-full max-h-72 object-contain rounded-xl" />
        <button @click.stop="clear"
          class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition backdrop-blur-sm">
          <i class="fi fi-rr-cross-small text-sm"></i>
        </button>
      </div>

      <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFile" />
    </div>

    <!-- QR scan status -->
    <Transition name="slide-down">
      <div v-if="scanDone || scanning" class="rounded-xl px-3.5 py-3 flex items-center gap-3 transition-all"
        :class="{
          'bg-slate-100 border border-slate-200': scanning,
          'bg-emerald-50 border border-emerald-200': scanDone && found,
          'bg-amber-50 border border-amber-200': scanDone && !found,
        }">
        <i v-if="scanning" class="fi fi-rr-spinner animate-spin text-violet-500 text-lg shrink-0"></i>
        <i v-else-if="found" class="fi fi-rr-check-circle text-emerald-600 text-xl shrink-0"></i>
        <i v-else class="fi fi-rr-exclamation text-amber-500 text-xl shrink-0"></i>

        <div class="flex-1 min-w-0">
          <p v-if="scanning" class="text-sm text-slate-600">กำลังสแกน QR code...</p>
          <template v-else-if="found">
            <p class="text-sm font-semibold text-emerald-700">พบข้อมูล QR ในสลิป</p>
            <p v-if="detectedAmount" class="text-xs text-emerald-600 mt-0.5">
              ยอดโอน: <span class="font-bold text-emerald-700">฿{{ fmt(detectedAmount) }}</span>
              <span v-if="orderTotal && detectedAmount == orderTotal" class="ml-1.5 bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full text-[10px] font-medium">ตรงกับออเดอร์ ✓</span>
              <span v-else-if="orderTotal" class="ml-1.5 bg-rose-100 text-rose-600 px-1.5 py-0.5 rounded-full text-[10px] font-medium">ไม่ตรง !</span>
            </p>
            <p v-if="detectedRef" class="text-[11px] text-emerald-500 font-mono mt-0.5">ref: {{ detectedRef }}</p>
          </template>
          <template v-else>
            <p class="text-sm font-medium text-amber-700">ไม่พบ QR ในสลิป</p>
            <p class="text-xs text-amber-600 mt-0.5">ระบบจะใช้ยอดจากออเดอร์โดยอัตโนมัติ</p>
          </template>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  orderTotal: { type: [Number, String], default: null },
})

const emit = defineEmits(['change'])

const fileInput = ref(null)
const preview   = ref(null)
const file      = ref(null)
const dragging  = ref(false)
const scanning  = ref(false)
const scanDone  = ref(false)
const found     = ref(false)
const detectedAmount = ref(null)
const detectedRef    = ref(null)

function fmt(v) {
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function clear() {
  file.value = null
  preview.value = null
  scanDone.value = false
  found.value = false
  detectedAmount.value = null
  detectedRef.value = null
  if (fileInput.value) fileInput.value.value = ''
  emit('change', { file: null, amount: null })
}

function onDrop(e) {
  dragging.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) processFile(f)
}
function onFile(e) {
  const f = e.target.files?.[0]
  if (f) processFile(f)
}

function processFile(f) {
  if (f.size > 5 * 1024 * 1024) {
    alert('ไฟล์ใหญ่เกิน 5 MB')
    return
  }
  file.value = f
  const reader = new FileReader()
  reader.onload = (ev) => {
    preview.value = ev.target.result
    emit('change', { file: f, amount: props.orderTotal })
    scanQR(ev.target.result)
  }
  reader.readAsDataURL(f)
}

async function scanQR(dataUrl) {
  scanning.value = true
  scanDone.value = false
  found.value = false
  detectedAmount.value = null
  detectedRef.value = null

  try {
    const jsQR = (await import('jsqr')).default

    const img = new Image()
    img.src = dataUrl
    await new Promise((res) => { img.onload = res })

    const canvas = document.createElement('canvas')
    canvas.width  = img.naturalWidth
    canvas.height = img.naturalHeight
    const ctx = canvas.getContext('2d')
    ctx.drawImage(img, 0, 0)
    const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height)

    const code = jsQR(imgData.data, imgData.width, imgData.height, {
      inversionAttempts: 'dontInvert',
    })

    if (code?.data) {
      found.value = true
      const { amount, ref } = parseEMV(code.data)
      detectedAmount.value = amount
      detectedRef.value    = ref
      emit('change', { file: file.value, amount: amount ?? props.orderTotal, qrRaw: code.data })
    } else {
      found.value = false
      emit('change', { file: file.value, amount: props.orderTotal })
    }
  } catch {
    found.value = false
    emit('change', { file: file.value, amount: props.orderTotal })
  } finally {
    scanning.value = false
    scanDone.value = true
  }
}

/* Parse EMV QR Code: tag 54 = Transaction Amount, tag 62 sub-tag 05 = Ref */
function parseEMV(raw) {
  let amount = null
  let ref    = null
  let i = 0
  while (i < raw.length - 3) {
    const tag = raw.slice(i, i + 2)
    const lenStr = raw.slice(i + 2, i + 4)
    const len = parseInt(lenStr, 10)
    if (isNaN(len) || i + 4 + len > raw.length) break
    const val = raw.slice(i + 4, i + 4 + len)
    if (tag === '54') {
      const n = parseFloat(val)
      if (!isNaN(n)) amount = n
    }
    if (tag === '62') {
      let j = 0
      while (j < val.length - 3) {
        const st  = val.slice(j, j + 2)
        const sl  = parseInt(val.slice(j + 2, j + 4), 10)
        if (isNaN(sl) || j + 4 + sl > val.length) break
        const sv  = val.slice(j + 4, j + 4 + sl)
        if (st === '05') ref = sv
        j += 4 + sl
      }
    }
    i += 4 + len
  }
  return { amount, ref }
}
</script>
