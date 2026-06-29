<template>
  <div class="space-y-4">
    <!-- สรุปคะแนน -->
    <div class="flex items-center gap-4">
      <div class="text-center">
        <p class="text-4xl font-bold text-slate-800">{{ avgRating.toFixed(1) }}</p>
        <StarRating :value="avgRating" size="sm" class="mt-1" />
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
      <div class="flex items-center gap-1 mb-3">
        <button v-for="s in 5" :key="s" @click="form.rating = s"
          class="text-2xl transition-transform hover:scale-110 active:scale-95"
          :class="s <= form.rating ? 'text-amber-400' : 'text-slate-200'">
          <i class="fi fi-sr-star"></i>
        </button>
      </div>
      <input v-model="form.title" placeholder="หัวข้อรีวิว (ไม่บังคับ)" class="inp mb-2" />
      <textarea v-model="form.comment" rows="3" placeholder="แชร์ความรู้สึกของคุณ..." class="inp mb-2"></textarea>
      <p v-if="err" class="text-rose-500 text-xs mb-2">{{ err }}</p>
      <button class="btn-orange px-5 py-2 rounded-full text-sm font-semibold" :disabled="busy" @click="submit">
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
        <!-- Header: avatar + name + helpful + date -->
        <div class="flex items-start justify-between gap-2">
          <div class="flex items-center gap-2.5">
            <!-- Avatar circle -->
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-violet-400 to-fuchsia-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
              {{ (r.user?.name || '?').charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-700">{{ r.user?.name || 'ไม่ระบุชื่อ' }}</p>
              <StarRating :value="r.rating" size="xs" class="mt-0.5" />
            </div>
          </div>
          <div class="flex flex-col items-end gap-1.5 shrink-0">
            <button class="text-[11px] text-slate-400 hover:text-violet-500 flex items-center gap-1 transition">
              <i class="fi fi-rr-thumbs-up"></i> มีประโยชน์
            </button>
            <span class="text-[11px] text-slate-300">{{ fmtDate(r.created_at) }}</span>
          </div>
        </div>

        <!-- Review title -->
        <p v-if="r.title" class="text-sm font-semibold text-slate-700 mt-2.5">{{ r.title }}</p>

        <!-- Review body -->
        <p v-if="r.comment" class="text-sm text-slate-600 mt-1.5 leading-relaxed">{{ r.comment }}</p>

        <!-- Review images -->
        <div v-if="r.images?.length" class="mt-2.5 grid grid-cols-3 gap-1.5">
          <img v-for="(img, idx) in r.images.slice(0, 6)" :key="idx"
            :src="`/storage/${img}`"
            class="aspect-square rounded-xl object-cover border border-slate-100 cursor-pointer hover:opacity-90 transition"
          />
          <div v-if="r.images.length > 6"
            class="aspect-square rounded-xl bg-slate-100 flex items-center justify-center text-xs text-slate-500 font-medium">
            +{{ r.images.length - 6 }}
          </div>
        </div>

        <!-- Seller reply -->
        <div v-if="r.reply" class="mt-3 bg-violet-50 rounded-xl p-3 border-l-4 border-violet-400">
          <p class="text-xs text-violet-600 font-semibold mb-1 flex items-center gap-1">
            <i class="fi fi-rr-shop"></i> ตอบโดยผู้ขาย
          </p>
          <p class="text-xs text-slate-600 leading-relaxed">{{ r.reply }}</p>
        </div>
      </div>
    </div>
    <p v-else-if="!loading" class="text-sm text-slate-400 text-center py-8">ยังไม่มีรีวิว</p>

    <button v-if="hasMore" class="w-full py-2 text-sm text-violet-600 hover:underline" @click="loadMore">
      {{ loading ? 'กำลังโหลด...' : 'ดูรีวิวเพิ่มเติม' }}
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../../api/index.js'
import { useAuth } from '../../../composables/useAuth.js'
import StarRating from './StarRating.vue'

const { user } = useAuth()

const props = defineProps({
  slug: { type: String, required: true },
  initialReviews: { type: Array, default: () => [] },
  ratingAvg: { type: [Number, String], default: 0 },
  ratingCount: { type: [Number, String], default: 0 },
})

const reviews = ref([...props.initialReviews])
const canReview = ref(false)
const submitted = ref(false)
const loading = ref(false)
const busy = ref(false)
const err = ref('')
const page = ref(1)
const hasMore = ref(false)
const form = ref({ rating: 5, title: '', comment: '' })

const avgRating = computed(() => Number(props.ratingAvg) || 0)
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

async function checkEligibility() {
  if (!user.value) return
  try {
    const { data } = await api.get(`/shop/products/${props.slug}/eligibility`)
    canReview.value = data.can_review
  } catch { /* error → ซ่อนฟอร์ม */ }
}

async function submit() {
  if (!form.value.rating) { err.value = 'กรุณาให้คะแนน'; return }
  busy.value = true; err.value = ''
  try {
    await api.post(`/shop/products/${props.slug}/reviews`, form.value)
    submitted.value = true
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
</style>
