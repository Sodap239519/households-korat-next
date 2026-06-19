<template>
  <div>
    <!-- Hero (โทนนุ่ม ลาเวนเดอร์ → พีช) -->
    <section class="hero-banner relative overflow-hidden bg-gradient-to-br from-violet-100 via-white to-orange-100">
      <!-- decorative floating blobs (พาสเทล) -->
      <div class="blob w-72 h-72 bg-violet-300/40" style="top:-70px; right:-30px;"></div>
      <div class="blob w-80 h-80 bg-orange-200/50" style="bottom:-100px; left:-40px; animation-delay:-5s;"></div>
      <div class="blob w-40 h-40 bg-fuchsia-200/40" style="top:38%; right:28%; animation-delay:-9s;"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20 text-center sm:text-left">
        <div class="max-w-2xl mx-auto sm:mx-0">
          <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-100 text-violet-700 text-sm mb-4 anim-fade-up" style="animation-delay:.05s">
            <i class="fi fi-rr-leaf"></i> สินค้าชุมชนจากกลุ่มแก้จน
          </span>
          <h1 class="text-4xl sm:text-5xl font-bold leading-tight anim-fade-up brand-text-gradient" style="animation-delay:.12s">
            ตลาดชุมชนโคราช
          </h1>
          <p class="mt-4 text-slate-600 leading-relaxed text-lg anim-fade-up" style="animation-delay:.2s">
            เลือกซื้อผลผลิตและสินค้าแปรรูปคุณภาพดีจากกลุ่มชุมชนทั่วจังหวัดนครราชสีมา
            สนับสนุนเศรษฐกิจฐานรากโดยตรงถึงผู้ผลิต
          </p>
          <div class="mt-7 flex flex-wrap gap-3 justify-center sm:justify-start anim-fade-up" style="animation-delay:.28s">
            <RouterLink to="/shop/products" class="btn-sheen btn-orange px-7 py-3 rounded-full font-semibold transition">
              เลือกซื้อสินค้า <i class="fi fi-rr-arrow-right ml-1"></i>
            </RouterLink>
            <a href="#featured" class="px-7 py-3 rounded-full border border-violet-300 text-violet-700 font-medium hover:bg-violet-50 transition">
              สินค้าแนะนำ
            </a>
          </div>
        </div>
      </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-10 space-y-10">
      <!-- Categories -->
      <section v-if="categories.length">
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
          <i class="fi fi-rr-apps text-violet-600"></i> หมวดหมู่สินค้า
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <RouterLink
            v-for="(cat, i) in categories"
            :key="cat.id"
            v-reveal="i"
            :to="{ path: '/shop/products', query: { category: cat.slug } }"
            class="box-card hover-lift p-4 text-center hover:border-violet-400"
          >
            <div class="w-12 h-12 mx-auto rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center text-xl mb-2">
              <i class="fi fi-rr-box-open"></i>
            </div>
            <p class="font-semibold text-slate-700 text-sm">{{ cat.name }}</p>
            <p class="text-xs text-slate-400">{{ cat.products_count }} รายการ</p>
          </RouterLink>
        </div>
      </section>

      <!-- Featured products -->
      <section id="featured" class="scroll-mt-20">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <i class="fi fi-rr-star text-amber-500"></i> สินค้าแนะนำ
          </h2>
          <RouterLink to="/shop/products" class="text-sm text-violet-600 hover:underline">ดูทั้งหมด</RouterLink>
        </div>

        <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <div v-for="n in 8" :key="n" class="box-card aspect-[3/4] skeleton"></div>
        </div>
        <div v-else-if="featured.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <ProductCard v-for="(p, i) in featured" :key="p.id" v-reveal="i" :product="p" />
        </div>
        <div v-else class="box-card p-10 text-center text-slate-400">
          <i class="fi fi-rr-box-open text-3xl"></i>
          <p class="mt-2">ยังไม่มีสินค้า</p>
        </div>
      </section>

      <!-- Seller groups -->
      <section v-if="groups.length">
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
          <i class="fi fi-rr-users-alt text-fuchsia-600"></i> กลุ่มผู้ขาย
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <RouterLink
            v-for="(g, i) in groups"
            :key="g.id"
            v-reveal="i"
            :to="{ path: '/shop/products', query: { group: g.slug } }"
            class="box-card hover-lift p-4 flex items-center gap-3 hover:border-violet-400"
          >
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center text-xl shrink-0">
              <i class="fi fi-rr-shop"></i>
            </div>
            <div class="min-w-0">
              <p class="font-semibold text-slate-700 truncate">{{ g.name }}</p>
              <p class="text-xs text-slate-400 truncate">{{ (g.districts || []).join(', ') }}</p>
              <p class="text-xs text-violet-500">{{ g.products_count }} สินค้า</p>
            </div>
          </RouterLink>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/index.js'
import ProductCard from './components/ProductCard.vue'

const loading = ref(true)
const featured = ref([])
const categories = ref([])
const groups = ref([])

async function load() {
  loading.value = true
  try {
    const [prodRes, catRes, grpRes] = await Promise.all([
      api.get('/shop/products', { params: { featured: 1, per_page: 4, sort: 'newest' } }),
      api.get('/shop/categories'),
      api.get('/shop/groups'),
    ])
    featured.value = prodRes.data.data || []
    // ถ้าไม่มีสินค้าแนะนำ ดึงสินค้าล่าสุดมาแสดงแทน
    if (!featured.value.length) {
      const fallback = await api.get('/shop/products', { params: { per_page: 4 } })
      featured.value = fallback.data.data || []
    }
    categories.value = (catRes.data || []).filter(c => c.products_count > 0)
    groups.value = grpRes.data || []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
