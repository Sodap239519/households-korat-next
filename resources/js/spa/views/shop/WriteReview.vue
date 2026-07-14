<template>
  <div class="max-w-2xl mx-auto px-4 py-5 pb-28 lg:pb-10 space-y-4">

    <!-- Header -->
    <div class="flex items-center gap-3">
      <button @click="goHome" class="p-2 rounded-full hover:bg-slate-100 text-slate-500 -ml-1 transition">
        <i class="fi fi-rr-arrow-small-left text-xl"></i>
      </button>
      <div>
        <h1 class="font-bold text-slate-800 text-base">รีวิวสินค้า</h1>
        <p class="text-xs text-slate-400 font-mono">{{ orderNo }}</p>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 2" :key="n" class="box-card h-44 skeleton rounded-2xl"></div>
    </div>

    <!-- Error -->
    <div v-else-if="loadError" class="box-card p-10 text-center space-y-3 text-slate-400">
      <i class="fi fi-rr-triangle-warning text-4xl text-rose-300 block"></i>
      <p class="text-sm">ไม่พบออเดอร์ หรือออเดอร์ยังไม่ถึงสถานะที่รีวิวได้</p>
      <button @click="goHome" class="text-sm text-violet-600 hover:underline">กลับหน้าแรก</button>
    </div>

    <!-- All done -->
    <div v-else-if="allDone" class="box-card p-12 text-center space-y-4">
      <span class="w-20 h-20 rounded-full bg-amber-50 flex items-center justify-center mx-auto">
        <i class="fi fi-sr-star text-amber-400 text-4xl"></i>
      </span>
      <p class="font-bold text-slate-800 text-xl">ขอบคุณสำหรับรีวิว!</p>
      <p class="text-slate-500 text-sm leading-relaxed">
        รีวิวของคุณช่วยให้ผู้ซื้อคนอื่น<br>ตัดสินใจได้ดีขึ้น ขอบคุณมากค่ะ 💜
      </p>
      <button @click="goHome"
        class="mt-2 px-8 py-3 rounded-full bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700 transition">
        กลับหน้าแรก
      </button>
    </div>

    <!-- Review forms -->
    <template v-else>
      <p class="text-xs text-slate-400">รีวิวสินค้าที่คุณได้รับ — ข้ามรายการที่ไม่ต้องการรีวิวได้เลย</p>

      <div v-for="item in reviewableItems" :key="item.id"
        class="box-card p-4 space-y-4 transition"
        :class="done[item.id] ? 'opacity-60 pointer-events-none' : ''">

        <!-- Done overlay badge -->
        <div v-if="done[item.id]" class="flex items-center gap-2 text-emerald-600 text-sm font-semibold">
          <i class="fi fi-sr-check-circle"></i> รีวิวแล้ว
        </div>

        <!-- Product info -->
        <div class="flex gap-3">
          <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 flex items-center justify-center text-xl font-bold text-white"
            :style="{ background: avatarColor(item.product_name) }">
            {{ item.product_name?.[0] || '?' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-700 text-sm leading-snug line-clamp-2">{{ item.product_name }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ item.qty }} ชิ้น · ฿{{ fmt(item.line_total) }}</p>
          </div>
        </div>

        <!-- Stars -->
        <div>
          <p class="text-xs text-slate-500 mb-2 font-medium">คะแนน <span class="text-rose-400">*</span></p>
          <div class="flex gap-3">
            <button v-for="s in 5" :key="s" type="button"
              @click="setRating(item.id, s)"
              class="text-3xl transition active:scale-125"
              :class="(reviews[item.id]?.rating || 0) >= s ? 'text-amber-400 drop-shadow-sm' : 'text-slate-200 hover:text-amber-200'">
              <i :class="(reviews[item.id]?.rating || 0) >= s ? 'fi fi-sr-star' : 'fi fi-rr-star'"></i>
            </button>
          </div>
          <p class="text-xs text-slate-400 mt-1.5">
            {{ ratingLabel(reviews[item.id]?.rating) }}
          </p>
        </div>

        <!-- Comment -->
        <div>
          <p class="text-xs text-slate-500 mb-1.5 font-medium">ความคิดเห็น (ไม่บังคับ)</p>
          <textarea v-model="reviews[item.id].comment" rows="3"
            placeholder="บอกเล่าประสบการณ์ของคุณ — คุณภาพสินค้า การบรรจุ ความตรงปก..."
            class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-violet-400 resize-none leading-relaxed">
          </textarea>
        </div>

        <!-- Images -->
        <div>
          <p class="text-xs text-slate-500 mb-2 font-medium">
            ภาพประกอบรีวิว (ไม่บังคับ, สูงสุด 5 ภาพ)
          </p>
          <div class="flex gap-2 flex-wrap">
            <!-- Previews -->
            <div v-for="(img, idx) in reviews[item.id].images" :key="idx"
              class="relative w-16 h-16 rounded-xl overflow-hidden bg-slate-100 shrink-0 shadow-sm">
              <img :src="img.preview" class="w-full h-full object-cover" />
              <button type="button"
                class="absolute top-0.5 right-0.5 w-5 h-5 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black/80 transition"
                @click="removeImage(item.id, idx)">
                <i class="fi fi-rr-cross-small text-[10px] leading-none"></i>
              </button>
            </div>
            <!-- Add button -->
            <button v-if="reviews[item.id].images.length < 5"
              type="button"
              class="w-16 h-16 rounded-xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-0.5 text-slate-400 hover:border-violet-400 hover:text-violet-500 transition"
              @click="triggerFileInput(item.id)">
              <i class="fi fi-rr-picture text-lg"></i>
              <span class="text-[9px] font-medium">เพิ่มรูป</span>
            </button>
          </div>
          <!-- Hidden file input per item -->
          <input
            :ref="el => { if (el) fileInputs[item.id] = el }"
            type="file" accept="image/*" multiple class="hidden"
            @change="onFilesSelected(item.id, $event)" />
        </div>

        <!-- Submit / Skip row -->
        <div class="flex gap-2">
          <button
            :disabled="!reviews[item.id]?.rating || submitting[item.id]"
            class="flex-1 h-11 rounded-2xl bg-violet-600 text-white text-sm font-semibold transition disabled:opacity-40 flex items-center justify-center gap-2 hover:bg-violet-700"
            @click="submitReview(item)">
            <i :class="submitting[item.id] ? 'fi fi-rr-spinner animate-spin' : 'fi fi-sr-star'"></i>
            {{ submitting[item.id] ? 'กำลังบันทึก...' : 'ส่งรีวิว' }}
          </button>
          <button
            class="px-4 h-11 rounded-2xl border border-slate-200 text-slate-500 text-sm hover:bg-slate-50 transition"
            @click="done[item.id] = true">
            ข้าม
          </button>
        </div>
      </div>

      <!-- No reviewable items -->
      <div v-if="!reviewableItems.length" class="box-card p-10 text-center text-slate-400 space-y-2">
        <i class="fi fi-rr-star text-3xl block"></i>
        <p class="text-sm">ไม่มีสินค้าที่รีวิวได้ในออเดอร์นี้</p>
      </div>

      <!-- Bottom skip all -->
      <div class="text-center pt-2">
        <button @click="goHome" class="text-sm text-slate-400 hover:text-slate-600 transition py-2">
          ข้ามทั้งหมด — กลับหน้าแรก
        </button>
      </div>
    </template>

    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import Toast from 'primevue/toast'

const route  = useRoute()
const router = useRouter()
const toast  = useToast()

const orderNo    = route.params.orderNo
const loading    = ref(true)
const loadError  = ref(false)
const order      = ref(null)
const reviews    = reactive({})
const submitting = reactive({})
const done       = reactive({})
const fileInputs = ref({})

const reviewableItems = computed(() =>
  (order.value?.items || []).filter(it => it.product?.slug)
)

const allDone = computed(() =>
  !loading.value && !loadError.value &&
  reviewableItems.value.length > 0 &&
  reviewableItems.value.every(it => done[it.id])
)

const RATING_LABELS = ['', 'แย่มาก', 'ไม่ดี', 'พอใช้', 'ดี', 'ดีมาก']
function ratingLabel(r) { return r ? RATING_LABELS[r] : 'แตะดาวเพื่อให้คะแนน' }

function fmt(v) {
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

const AVATAR_COLORS = ['#7c3aed','#db2777','#0891b2','#059669','#d97706','#dc2626']
function avatarColor(name) {
  const code = (name || '').charCodeAt(0) || 0
  return AVATAR_COLORS[code % AVATAR_COLORS.length]
}

function setRating(itemId, star) {
  if (!reviews[itemId]) reviews[itemId] = { rating: 0, comment: '', images: [] }
  reviews[itemId].rating = reviews[itemId].rating === star ? 0 : star
}

function triggerFileInput(itemId) {
  fileInputs.value[itemId]?.click()
}

function onFilesSelected(itemId, event) {
  const files = Array.from(event.target.files || [])
  const current = reviews[itemId].images
  const slots = 5 - current.length
  for (const file of files.slice(0, slots)) {
    current.push({ file, preview: URL.createObjectURL(file) })
  }
  event.target.value = ''
}

function removeImage(itemId, idx) {
  const [removed] = reviews[itemId].images.splice(idx, 1)
  URL.revokeObjectURL(removed.preview)
}

function goHome() {
  router.push('/shop')
}

async function submitReview(item) {
  const r = reviews[item.id]
  if (!r?.rating) return
  submitting[item.id] = true
  try {
    const fd = new FormData()
    fd.append('rating', r.rating)
    if (r.comment) fd.append('comment', r.comment)
    for (const img of r.images) {
      fd.append('images[]', img.file)
    }
    await api.post(`/shop/products/${item.product.slug}/reviews`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    for (const img of r.images) URL.revokeObjectURL(img.preview)
    done[item.id] = true
    toast.add({ severity: 'success', summary: 'ขอบคุณ!', detail: `รีวิว "${item.product_name}" แล้ว`, life: 2000 })
  } catch (e) {
    const msg = e.response?.data?.message || ''
    if (msg.includes('รีวิวสินค้านี้')) {
      done[item.id] = true
      toast.add({ severity: 'info', summary: 'รีวิวแล้ว', detail: msg, life: 2500 })
    } else {
      toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: msg, life: 3000 })
    }
  } finally {
    submitting[item.id] = false
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/shop/my/orders/${orderNo}`)
    order.value = data
    const reviewedProductIds = new Set((data.reviews || []).map(r => r.product_id))
    for (const item of data.items || []) {
      reviews[item.id]    = { rating: 0, comment: '', images: [] }
      done[item.id]       = reviewedProductIds.has(item.product_id)
      submitting[item.id] = false
    }
  } catch {
    loadError.value = true
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  for (const r of Object.values(reviews)) {
    for (const img of r.images || []) URL.revokeObjectURL(img.preview)
  }
})
</script>
