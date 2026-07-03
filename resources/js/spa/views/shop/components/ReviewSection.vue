<template>
  <div class="space-y-4">
    <!-- สรุปคะแนน -->
    <div class="flex items-center gap-4">
      <div class="text-center">
        <p class="text-4xl font-bold text-slate-800">{{ avgRating.toFixed(1) }}</p>
        <StarRating :rating="avgRating" size="sm" class="mt-1" />
        <p class="text-xs text-slate-400 mt-0.5">{{ totalReviews }} รีวิว</p>
      </div>
      <div class="flex-1 space-y-1">
        <div v-for="s in [5,4,3,2,1]" :key="s" class="flex items-center gap-2 text-xs">
          <span class="w-3 text-slate-500">{{ s }}</span>
          <i class="fi fi-rr-star text-amber-400 text-[10px]"></i>
          <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full rounded-full bg-amber-400 transition-all" :style="{ width: barPct(s) + '%' }"></div>
          </div>
          <span class="w-5 text-right text-slate-400">{{ ratingDist[s] || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- ฟอร์มรีวิว (ถ้า eligible) -->
    <div v-if="canReview && !submitted" class="box-card p-4 border border-violet-200">
      <p class="text-sm font-semibold text-violet-700 mb-3"><i class="fi fi-rr-pencil mr-1"></i> เขียนรีวิวของคุณ</p>

      <!-- ดาว -->
      <div class="flex items-center gap-1 mb-3">
        <button v-for="s in 5" :key="s" type="button" @click="form.rating = s"
          class="text-2xl transition-transform hover:scale-110 active:scale-95"
          :class="s <= form.rating ? 'text-amber-400' : 'text-slate-200'">
          <i class="fi fi-sr-star"></i>
        </button>
      </div>

      <input v-model="form.title" placeholder="หัวข้อรีวิว (ไม่บังคับ)" class="inp mb-2" />
      <textarea v-model="form.comment" rows="3" placeholder="แชร์ความรู้สึกของคุณ..." class="inp mb-2"></textarea>

      <!-- เพิ่มรูปภาพ -->
      <div class="mb-3">
        <input ref="imgInput" type="file" accept="image/*" multiple class="hidden" @change="onImagesSelect" />
        <button type="button" @click="imgInput?.click()"
          :disabled="pendingImages.length >= 5"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-slate-200 text-xs text-slate-500 hover:border-violet-400 hover:text-violet-600 transition disabled:opacity-40 disabled:cursor-not-allowed">
          <i class="fi fi-rr-picture"></i> เพิ่มรูปภาพ
          <span class="text-slate-400">({{ pendingImages.length }}/5)</span>
        </button>
        <!-- Preview รูปที่เลือก -->
        <div v-if="pendingImages.length" class="mt-2 flex gap-2 flex-wrap">
          <div v-for="(url, i) in pendingImageUrls" :key="i" class="relative">
            <img :src="url" class="w-16 h-16 rounded-xl object-cover border border-violet-200 cursor-pointer"
              @click="previewLightbox = url" />
            <button type="button" @click="removeImage(i)"
              class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-slate-700 text-white flex items-center justify-center leading-none hover:bg-rose-600 transition">
              <i class="fi fi-rr-cross-small" style="font-size:9px"></i>
            </button>
          </div>
        </div>
      </div>

      <p v-if="err" class="text-rose-500 text-xs mb-2">{{ err }}</p>
      <button type="button" class="btn-orange px-5 py-2 rounded-full text-sm font-semibold" :disabled="busy" @click="submit">
        <i class="fi fi-rr-paper-plane mr-1"></i>{{ busy ? 'กำลังส่ง...' : 'ส่งรีวิว' }}
      </button>
    </div>
    <div v-else-if="submitted" class="box-card p-4 text-center text-emerald-600">
      <i class="fi fi-rr-check-circle text-2xl"></i>
      <p class="mt-1 text-sm font-medium">ขอบคุณสำหรับรีวิวของคุณ!</p>
    </div>

    <!-- รายการรีวิว -->
    <div v-if="reviews.length" class="space-y-4">
      <div v-for="r in reviews" :key="r.id" class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
        <div class="flex items-start justify-between gap-2">
          <div class="flex items-center gap-2.5">
            <UserAvatar :avatar-path="r.user?.avatar_path" :name="r.user?.name || ''" size="md" />
            <div>
              <p class="text-sm font-semibold text-slate-700">{{ r.user?.name || 'ไม่ระบุชื่อ' }}</p>
              <StarRating :rating="r.rating" size="xs" :show-count="false" class="mt-0.5" />
            </div>
          </div>
          <div class="flex flex-col items-end gap-1.5 shrink-0">
            <button class="text-[11px] text-slate-400 hover:text-violet-500 flex items-center gap-1 transition">
              <i class="fi fi-rr-thumbs-up"></i> มีประโยชน์
            </button>
            <span class="text-[11px] text-slate-300">{{ fmtDate(r.created_at) }}</span>
          </div>
        </div>

        <p v-if="r.title" class="text-sm font-semibold text-slate-700 mt-2.5">{{ r.title }}</p>
        <p v-if="r.comment" class="text-sm text-slate-600 mt-1.5 leading-relaxed">{{ r.comment }}</p>

        <!-- รูปภาพในรีวิว -->
        <div v-if="reviewImages(r).length" class="mt-2.5 flex gap-1.5 flex-wrap">
          <div v-for="(url, idx) in reviewImages(r)" :key="idx" class="relative">
            <img :src="url"
              class="w-20 h-20 rounded-xl object-cover border border-slate-100 cursor-pointer hover:opacity-90 hover:scale-105 transition"
              @click="previewLightbox = url" />
            <div v-if="idx === 5 && reviewImages(r).length > 6"
              class="absolute inset-0 rounded-xl bg-black/50 flex items-center justify-center text-white text-xs font-bold cursor-pointer"
              @click="previewLightbox = url">
              +{{ reviewImages(r).length - 6 }}
            </div>
          </div>
        </div>

        <!-- ผู้ขายตอบกลับ (แสดงถ้ามี) -->
        <div v-if="r.reply" class="mt-3 bg-violet-50 rounded-xl p-3 border-l-4 border-violet-400">
          <p class="text-xs text-violet-600 font-semibold mb-1 flex items-center gap-1">
            <i class="fi fi-rr-shop"></i> ตอบโดยผู้ขาย
          </p>
          <p class="text-xs text-slate-600 leading-relaxed">{{ r.reply }}</p>
        </div>

        <!-- ผู้ขายตอบกลับ (ฟอร์ม — เฉพาะ staff ที่ยังไม่ตอบ) -->
        <div v-if="isMarketStaff && !r.reply" class="mt-2">
          <template v-if="replyingId === r.id">
            <textarea v-model="replyText" rows="2" placeholder="พิมพ์คำตอบกลับรีวิวนี้..." class="inp w-full text-xs mt-2"></textarea>
            <div class="flex gap-2 mt-1.5">
              <button type="button" @click="submitReply(r.id)" :disabled="replyBusy"
                class="px-3 py-1 rounded-full bg-violet-600 text-white text-xs font-semibold hover:bg-violet-700 transition disabled:opacity-50">
                {{ replyBusy ? 'กำลังส่ง...' : 'ส่งคำตอบ' }}
              </button>
              <button type="button" @click="replyingId = null; replyText = ''"
                class="px-3 py-1 rounded-full border border-slate-200 text-xs text-slate-500 hover:bg-slate-50 transition">
                ยกเลิก
              </button>
            </div>
          </template>
          <button v-else type="button" @click="replyingId = r.id; replyText = ''"
            class="mt-2 text-xs text-violet-500 hover:text-violet-700 flex items-center gap-1 transition">
            <i class="fi fi-rr-comment-alt"></i> ตอบกลับรีวิว
          </button>
        </div>
      </div>
    </div>
    <p v-else-if="!loading" class="text-sm text-slate-400 text-center py-8">ยังไม่มีรีวิว</p>

    <button v-if="hasMore" class="w-full py-2 text-sm text-violet-600 hover:underline" @click="loadMore">
      {{ loading ? 'กำลังโหลด...' : 'ดูรีวิวเพิ่มเติม' }}
    </button>
  </div>

  <!-- Lightbox รูปภาพ -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="previewLightbox"
        class="fixed inset-0 z-[400] bg-black/90 flex items-center justify-center p-4"
        @click="previewLightbox = null">
        <img :src="previewLightbox" class="max-w-full max-h-full rounded-2xl shadow-2xl object-contain" @click.stop />
        <button @click="previewLightbox = null"
          class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white/20 text-white hover:bg-white/40 flex items-center justify-center transition">
          <i class="fi fi-rr-cross text-sm"></i>
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../../api/index.js'
import { useAuth } from '../../../composables/useAuth.js'
import StarRating from './StarRating.vue'
import UserAvatar from './UserAvatar.vue'

const { user } = useAuth()

const isMarketStaff = computed(() => user.value?.role === 'staff' && !!user.value?.seller_group_id)

const props = defineProps({
  slug:           { type: String, required: true },
  initialReviews: { type: Array,  default: () => [] },
  ratingAvg:      { type: [Number, String], default: 0 },
  ratingCount:    { type: [Number, String], default: 0 },
})

const reviews         = ref([...props.initialReviews])
const canReview       = ref(false)
const submitted       = ref(false)
const loading         = ref(false)
const busy            = ref(false)
const err             = ref('')
const page            = ref(1)
const hasMore         = ref(false)
const form            = ref({ rating: 5, title: '', comment: '' })
const imgInput        = ref(null)
const pendingImages   = ref([])
const pendingImageUrls = ref([])
const previewLightbox = ref(null)
const replyingId      = ref(null)
const replyText       = ref('')
const replyBusy       = ref(false)

const avgRating    = computed(() => Number(props.ratingAvg) || 0)
const totalReviews = computed(() => Number(props.ratingCount) || reviews.value.length)

const ratingDist = computed(() => {
  const d = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
  reviews.value.forEach(r => { if (r.rating >= 1 && r.rating <= 5) d[r.rating]++ })
  return d
})
function barPct(s) {
  const total = Object.values(ratingDist.value).reduce((a, b) => a + b, 0)
  return total ? Math.round((ratingDist.value[s] / total) * 100) : 0
}
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('th-TH') : '' }

function reviewImages(r) {
  if (r.image_urls?.length) return r.image_urls.slice(0, 6)
  if (r.images?.length) return r.images.slice(0, 6).map(p => `/storage/${p}`)
  return []
}

function onImagesSelect(e) {
  const files = Array.from(e.target.files || [])
  const remaining = 5 - pendingImages.value.length
  files.slice(0, remaining).forEach(file => {
    pendingImages.value.push(file)
    pendingImageUrls.value.push(URL.createObjectURL(file))
  })
  if (imgInput.value) imgInput.value.value = ''
}

function removeImage(i) {
  URL.revokeObjectURL(pendingImageUrls.value[i])
  pendingImages.value.splice(i, 1)
  pendingImageUrls.value.splice(i, 1)
}

async function submitReply(reviewId) {
  if (!replyText.value.trim()) return
  replyBusy.value = true
  try {
    await api.post(`/market/reviews/${reviewId}/reply`, { reply: replyText.value.trim() })
    const r = reviews.value.find(r => r.id === reviewId)
    if (r) r.reply = replyText.value.trim()
    replyingId.value = null
    replyText.value = ''
  } catch { /* ignore */ } finally {
    replyBusy.value = false
  }
}

async function checkEligibility() {
  if (!user.value) return
  try {
    const { data } = await api.get(`/shop/products/${props.slug}/eligibility`)
    canReview.value = data.can_review
  } catch { /* ซ่อนฟอร์มถ้า error */ }
}

async function submit() {
  if (!form.value.rating) { err.value = 'กรุณาให้คะแนน'; return }
  busy.value = true; err.value = ''
  try {
    const fd = new FormData()
    fd.append('rating', form.value.rating)
    if (form.value.title)   fd.append('title',   form.value.title)
    if (form.value.comment) fd.append('comment', form.value.comment)
    pendingImages.value.forEach(file => fd.append('images[]', file))

    await api.post(`/shop/products/${props.slug}/reviews`, fd)
    submitted.value = true
    pendingImages.value.forEach((_, i) => URL.revokeObjectURL(pendingImageUrls.value[i]))
    pendingImages.value = []
    pendingImageUrls.value = []

    const { data } = await api.get(`/shop/products/${props.slug}/reviews`, { params: { per_page: 10, page: 1 } })
    reviews.value = data.data || []
    hasMore.value = !!data.next_page_url
  } catch (e) {
    err.value = e.response?.data?.message || 'ส่งรีวิวไม่สำเร็จ'
  } finally { busy.value = false }
}

async function loadMore() {
  loading.value = true
  try {
    page.value++
    const { data } = await api.get(`/shop/products/${props.slug}/reviews`, { params: { per_page: 10, page: page.value } })
    reviews.value.push(...(data.data || []))
    hasMore.value = !!data.next_page_url
  } finally { loading.value = false }
}

onMounted(async () => {
  hasMore.value = props.ratingCount > props.initialReviews.length
  await checkEligibility()
})
</script>

<style scoped>
.inp { width: 100%; border: 1px solid rgb(226 232 240); border-radius: .6rem; padding: .45rem .65rem; font-size: .875rem; }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
textarea.inp { resize: vertical; }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
