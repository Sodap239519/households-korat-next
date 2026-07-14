<template>
  <div class="bg-violet-950">

    <!-- ===== Hero Banner Carousel (single-direction, always slides left) ===== -->
    <section class="relative overflow-hidden select-none transition-all duration-500" :style="`aspect-ratio:${heroAspectRatio}`">
      <Transition name="hero-slide">
        <div :key="current"
          class="absolute inset-0 flex items-center overflow-hidden"
          :class="slides[current].image ? 'bg-slate-900' : slides[current].gradient">

          <!-- Hero image (full cover) -->
          <img v-if="slides[current].image"
            :src="slides[current].image"
            class="absolute inset-0 w-full h-full object-cover" />

          <!-- BG blobs (only when no image) -->
          <div v-if="!slides[current].image" class="absolute inset-0" :style="slides[current].bgBlob"></div>


          <!-- Content -->
          <div class="relative w-full max-w-5xl mx-auto px-5 sm:px-8 flex flex-col sm:flex-row items-center gap-4">
            <div class="flex-1 text-white text-center sm:text-left">
              <p v-if="slides[current].tag" class="text-xs sm:text-sm font-medium mb-1.5 opacity-80 flex items-center gap-1.5 justify-center sm:justify-start">
                <i class="fi fi-rr-mushroom"></i> {{ slides[current].tag }}
              </p>
              <h1 class="text-2xl sm:text-4xl font-bold leading-tight drop-shadow" v-html="slides[current].title"></h1>
              <p v-if="slides[current].subtitle" class="mt-1.5 text-white/75 text-xs sm:text-sm max-w-xs sm:max-w-md leading-relaxed">{{ slides[current].subtitle }}</p>
              <div class="mt-4 flex flex-wrap gap-2 justify-center sm:justify-start">
                <RouterLink v-if="slides[current].cta" :to="slides[current].cta.to"
                  class="inline-flex items-center gap-1.5 text-white font-semibold px-5 py-2 rounded-full shadow-lg transition text-sm"
                  :class="slides[current].cta.color">
                  <i :class="slides[current].cta.icon"></i> {{ slides[current].cta.label }}
                </RouterLink>
                <a v-if="slides[current].secondary" :href="slides[current].secondary.href"
                  class="inline-flex items-center gap-1.5 bg-white/15 hover:bg-white/25 text-white font-medium px-4 py-2 rounded-full border border-white/20 transition text-sm">
                  <i :class="slides[current].secondary.icon"></i> {{ slides[current].secondary.label }}
                </a>
              </div>
            </div>
            <!-- Emoji grid (desktop — ซ่อนเมื่อมีรูป) -->
            <div v-if="!slides[current].image && slides[current].emojis.length" class="hidden sm:flex flex-wrap gap-3 w-48 justify-center opacity-90 shrink-0">
              <div v-for="(em, j) in slides[current].emojis" :key="j"
                class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-lg"
                style="background:rgba(255,255,255,0.15)">{{ em }}</div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Arrows -->
      <button @click="prev"
        class="absolute left-2 top-1/2 -translate-y-1/2 text-white/80 hover:text-white transition text-2xl drop-shadow-lg z-10">
        <i class="fi fi-rr-angle-left"></i>
      </button>
      <button @click="next"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-white/80 hover:text-white transition text-2xl drop-shadow-lg z-10">
        <i class="fi fi-rr-angle-right"></i>
      </button>

      <!-- Dots -->
      <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
        <button v-for="(_, i) in slides" :key="i" @click="goTo(i)"
          class="rounded-full transition-all duration-300"
          :class="i === current ? 'w-5 h-2 bg-white' : 'w-2 h-2 bg-white/50 hover:bg-white/80'">
        </button>
      </div>
    </section>

    <!-- ===== White Content Card ===== -->
    <div class="relative z-10 bg-white">

    <!-- ===== Quick Access Categories + Seller Groups ===== -->
    <section class="border-b border-slate-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex gap-0.5 overflow-x-auto py-3 scrollbar-hide">
          <!-- ทั้งหมด -->
          <RouterLink to="/shop/products"
            class="flex flex-col items-center gap-1 px-2.5 shrink-0 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-100 to-violet-200 flex items-center justify-center text-violet-600 text-lg group-hover:scale-105 transition">
              <i class="fi fi-rr-apps"></i>
            </div>
            <span class="text-[10px] text-slate-600 font-medium w-12 h-7 flex items-start justify-center text-center leading-tight pt-0.5">ทั้งหมด</span>
          </RouterLink>
          <!-- หมวดหมู่ -->
          <RouterLink v-for="(cat, i) in categories.slice(0, 10)" :key="cat.id"
            :to="{ path: '/shop/products', query: { category: cat.slug } }"
            class="flex flex-col items-center gap-1 px-2.5 shrink-0 group">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg group-hover:scale-105 transition"
              :style="catColors[i % catColors.length]">
              <i :class="catIcons[i % catIcons.length]"></i>
            </div>
            <span class="text-[10px] text-slate-600 font-medium w-12 h-7 flex items-start justify-center text-center leading-tight pt-0.5 line-clamp-2">{{ cat.name }}</span>
          </RouterLink>
          <!-- divider -->
          <div class="self-stretch w-px bg-slate-100 mx-1.5 my-1.5 shrink-0"></div>
          <!-- โซนตลาด → แผนที่ -->
          <RouterLink to="/shop/map"
            class="flex flex-col items-center gap-1 px-2.5 shrink-0 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center text-emerald-600 text-lg group-hover:scale-105 transition">
              <i class="fi fi-rr-map-marker"></i>
            </div>
            <span class="text-[10px] text-slate-600 font-medium w-12 h-7 flex items-start justify-center text-center leading-tight pt-0.5">โซนตลาด</span>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ===== Flash Sale Strip ===== -->
    <FlashSaleStrip />

    <!-- ===== สินค้าทั้งหมด (infinite scroll) ===== -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-5">
      <!-- Loading initial skeleton -->
      <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        <div v-for="n in 12" :key="n" class="box-card aspect-[3/4] skeleton"></div>
      </div>

      <!-- Products grid -->
      <div v-else>
        <div v-if="allProducts.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
          <ProductCard v-for="(p, i) in allProducts" :key="p.id" v-reveal="i" :product="p" />
        </div>
        <div v-else class="box-card p-16 text-center text-slate-400">
          <i class="fi fi-rr-box-open text-4xl mb-3 block"></i>
          <p>ยังไม่มีสินค้า</p>
        </div>

        <!-- Sentinel / load-more indicator -->
        <div ref="sentinel" class="flex items-center justify-center py-3">
          <template v-if="loadingMore">
            <i class="fi fi-rr-spinner animate-spin text-violet-400 text-xl"></i>
          </template>
          <template v-else-if="allPage >= allLastPage && allProducts.length">
            <span class="text-xs text-slate-300">— แสดงสินค้าทั้งหมด {{ allProducts.length }} รายการ —</span>
          </template>
        </div>
      </div>

    </div><!-- /max-w-7xl (products) -->

    </div><!-- /white-card -->
  </div><!-- /bg-violet-950 -->
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import api from '../../api/index.js'
import ProductCard from './components/ProductCard.vue'
import FlashSaleStrip from './components/FlashSaleStrip.vue'

const loading     = ref(true)
const allProducts = ref([])
const allPage     = ref(0)
const allLastPage = ref(1)
const loadingMore = ref(false)
const sentinel    = ref(null)
const categories  = ref([])
const groups      = ref([])
const current     = ref(0)
const smUp        = ref(window.innerWidth >= 640)
const apiBanners  = ref([])
let observer      = null

const defaultSlides = [
  {
    tag: 'สินค้าชุมชน โครงการแก้จนโคราช',
    title: 'ตลาดชุมชน<br class="sm:hidden"/>โคราช',
    subtitle: 'สนับสนุนสินค้าผลิตภัณฑ์ชุมชน จากเกษตรกรและกลุ่มแก้จนในนครราชสีมา',
    gradient: 'bg-gradient-to-r from-violet-700 via-violet-600 to-fuchsia-600',
    bgBlob: 'background-image:radial-gradient(circle at 20% 80%, #f97316 0%, transparent 50%),radial-gradient(circle at 80% 20%, #a78bfa 0%, transparent 50%);opacity:.25',
    cta: { label: 'ช้อปเลย', to: '/shop/products', icon: 'fi fi-rr-shopping-cart', color: 'bg-orange-500 hover:bg-orange-600' },
    secondary: { label: 'กลุ่มผู้ขาย', href: '#sellers', icon: 'fi fi-rr-users-alt' },
    emojis: ['🍄', '🌿', '🍯', '🧴', '🌾', '🧺'],
  },
  {
    tag: 'สินค้าแนะนำประจำฤดูกาล',
    title: 'เห็ดสดคุณภาพ<br class="sm:hidden"/>จากชุมชน',
    subtitle: 'เห็ดนางฟ้า เห็ดนางรม เห็ดหอม สดใหม่ทุกวันจากฟาร์มชุมชนโคราช',
    gradient: 'bg-gradient-to-r from-emerald-700 via-emerald-600 to-teal-500',
    bgBlob: 'background-image:radial-gradient(circle at 30% 70%, #6ee7b7 0%, transparent 50%),radial-gradient(circle at 70% 30%, #34d399 0%, transparent 60%);opacity:.25',
    cta: { label: 'เลือกซื้อเห็ด', to: '/shop/products', icon: 'fi fi-rr-mushroom', color: 'bg-orange-500 hover:bg-orange-600' },
    emojis: ['🍄', '🍄', '🌿', '🥗', '🍄', '🌱'],
  },
  {
    tag: 'ผลิตภัณฑ์ชุมชนแปรรูป',
    title: 'ของดีจากชุมชน<br class="sm:hidden"/>หลากหลาย',
    subtitle: 'น้ำพริก ข้าวซ้อมมือ สมุนไพร สินค้าแปรรูปจากธรรมชาติ 100%',
    gradient: 'bg-gradient-to-r from-amber-600 via-orange-500 to-rose-500',
    bgBlob: 'background-image:radial-gradient(circle at 20% 60%, #fbbf24 0%, transparent 50%),radial-gradient(circle at 80% 40%, #fb923c 0%, transparent 60%);opacity:.3',
    cta: { label: 'ดูสินค้า', to: '/shop/products', icon: 'fi fi-rr-box-open', color: 'bg-white/20 hover:bg-white/30 border border-white/30' },
    emojis: ['🌶️', '🧄', '🌾', '🍯', '🧴', '🌿'],
  },
]

function bannerLinkPath(b) {
  if (!b.link_value) return '/shop/products'
  if (b.link_type === 'product')  return `/shop/products/${b.link_value}`
  if (b.link_type === 'category') return `/shop/products?category=${b.link_value}`
  if (b.link_type === 'group')    return `/shop/sellers/${b.link_value}`
  return b.link_value
}

const slides = computed(() => {
  if (!apiBanners.value.length) return defaultSlides
  return apiBanners.value.map(b => ({
    tag:          b.tag      || '',
    title:        b.title    || '',
    subtitle:     b.subtitle || '',
    gradient:     `bg-gradient-to-r ${b.gradient}`,
    image:        b.image_path ? `/storage/${b.image_path}` : null,
    noColor:      b.gradient === 'from-white to-white',
    bgBlob:       '',
    aspect_ratio: b.aspect_ratio || '16/9',
    cta:          b.cta_label ? { label: b.cta_label, to: bannerLinkPath(b), icon: b.cta_icon || 'fi fi-rr-shopping-cart', color: b.cta_color || 'bg-orange-500 hover:bg-orange-600' } : null,
    emojis:       b.emojis || [],
  }))
})

const heroAspectRatio = computed(() => {
  const r = slides.value[current.value]?.aspect_ratio
  if (!r) return '16/9'
  if (!smUp.value) {
    // mobile: ทำให้สูงขึ้นนิดหน่อย (denominator × 0.75 = สูงขึ้น 25%)
    const [w, h] = r.split('/').map(Number)
    if (w && h) return `${w}/${Math.round(h * 0.75)}`
  }
  return r
})

const catColors = [
  'background:#EDE9FE;color:#7C3AED;',
  'background:#DDD6FE;color:#6D28D9;',
  'background:#F3E8FF;color:#9333EA;',
  'background:#E9D5FF;color:#7E22CE;',
  'background:#EDE9FE;color:#5B21B6;',
  'background:#DDD6FE;color:#7C3AED;',
  'background:#F3E8FF;color:#6D28D9;',
  'background:#E9D5FF;color:#9333EA;',
]

const catIcons = [
  'fi fi-rr-mushroom', 'fi fi-rr-box-open', 'fi fi-rr-utensils',
  'fi fi-rr-flower', 'fi fi-rr-shopping-bag', 'fi fi-rr-cookie',
  'fi fi-rr-mushroom', 'fi fi-rr-honey',
]

let slideTimer = null

function next() { current.value = (current.value + 1) % slides.value.length; resetTimer() }
function prev() { current.value = (current.value - 1 + slides.value.length) % slides.value.length; resetTimer() }
function goTo(i) { current.value = i; resetTimer() }

function resetTimer() {
  clearInterval(slideTimer)
  slideTimer = setInterval(() => { current.value = (current.value + 1) % slides.value.length }, 4000)
}

function onResize() { smUp.value = window.innerWidth >= 640 }

async function loadMoreProducts() {
  if (loadingMore.value || allPage.value >= allLastPage.value) return
  loadingMore.value = true
  try {
    const nextPage = allPage.value + 1
    const { data } = await api.get('/shop/products', { params: { per_page: 12, sort: 'newest', page: nextPage } })
    allProducts.value.push(...(data.data || []))
    allPage.value     = data.current_page
    allLastPage.value = data.last_page
    if (allPage.value >= allLastPage.value && observer) {
      observer.disconnect()
      observer = null
    }
  } catch { /* ignore */ } finally {
    loadingMore.value = false
  }
}

async function load() {
  loading.value = true
  try {
    const [prodRes, catRes, grpRes, bannerRes] = await Promise.all([
      api.get('/shop/products', { params: { per_page: 12, sort: 'newest', page: 1 } }),
      api.get('/shop/categories'),
      api.get('/shop/groups'),
      api.get('/shop/banners'),
    ])
    allProducts.value = prodRes.data.data || []
    allPage.value     = prodRes.data.current_page || 1
    allLastPage.value = prodRes.data.last_page || 1
    categories.value  = (catRes.data || []).filter(c => c.products_count > 0)
    groups.value      = grpRes.data || []
    apiBanners.value  = bannerRes.data || []
    current.value     = 0
  } catch {
    // API error — show empty states, hero still renders
  } finally {
    loading.value = false
  }
}

function setupObserver() {
  if (!sentinel.value) return
  observer = new IntersectionObserver(
    entries => { if (entries[0].isIntersecting) loadMoreProducts() },
    { rootMargin: '200px' },
  )
  observer.observe(sentinel.value)
}

onMounted(async () => {
  await load()
  await nextTick()
  setupObserver()
  resetTimer()
  window.addEventListener('resize', onResize)
})

onBeforeUnmount(() => {
  clearInterval(slideTimer)
  window.removeEventListener('resize', onResize)
  if (observer) { observer.disconnect(); observer = null }
})
</script>

<style scoped>
/* Hero carousel — always slides left (enter from right, exit to left) */
.hero-slide-enter-active {
  transition: transform .5s cubic-bezier(.22,.61,.36,1);
  position: absolute; inset: 0;
}
.hero-slide-leave-active {
  transition: transform .45s ease;
  position: absolute; inset: 0;
}
.hero-slide-enter-from { transform: translateX(100%); }
.hero-slide-leave-to   { transform: translateX(-100%); }
</style>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
