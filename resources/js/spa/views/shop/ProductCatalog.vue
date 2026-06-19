<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-5">
    <!-- header: breadcrumb + filter button (right, mobile/tablet only) -->
    <div class="flex items-center justify-between gap-3">
      <Breadcrumb :items="[{ label: 'สินค้าทั้งหมด' }]" />
      <button
        class="lg:hidden inline-flex items-center gap-2 px-4 py-2 rounded-full box-card text-sm font-medium text-slate-700"
        @click="filterOpen = true"
      >
        <i class="fi fi-rr-settings-sliders text-violet-600"></i> ตัวกรอง
        <span v-if="activeFilterCount" class="min-w-[18px] h-[18px] px-1 rounded-full bg-orange-500 text-white text-[11px] flex items-center justify-center">{{ activeFilterCount }}</span>
      </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Sidebar filters (desktop) -->
      <aside class="hidden lg:block lg:w-64 shrink-0">
        <CatalogFilters :categories="categories" :groups="groups" :filters="filters" :on-set="setFilter" :on-apply="applyAndReload" />
      </aside>

      <!-- Filters drawer (mobile/tablet) -->
      <Drawer v-model:visible="filterOpen" position="right" header="ตัวกรอง" :style="{ width: '85vw', maxWidth: '22rem' }" class="lg:hidden">
        <CatalogFilters :categories="categories" :groups="groups" :filters="filters" :on-set="setFilterAndClose" :on-apply="applyAndClose" />
      </Drawer>

      <!-- Product grid -->
      <div class="flex-1 min-w-0 space-y-4">
        <!-- Toolbar -->
        <div class="box-card p-3 flex items-center justify-between flex-wrap gap-3">
          <p class="text-sm text-slate-500">
            พบ <span class="font-semibold text-slate-700">{{ meta.total || 0 }}</span> รายการ
            <span v-if="filters.q" class="text-violet-600">สำหรับ "{{ filters.q }}"</span>
          </p>
          <div class="flex items-center gap-2">
            <span class="text-sm text-slate-500">เรียงโดย</span>
            <select v-model="filters.sort" class="h-9 px-2 rounded-lg border border-slate-200 text-sm" @change="applyAndReload">
              <option value="newest">ใหม่ล่าสุด</option>
              <option value="price_asc">ราคา: ต่ำ→สูง</option>
              <option value="price_desc">ราคา: สูง→ต่ำ</option>
              <option value="popular">ยอดนิยม</option>
              <option value="rating">คะแนนรีวิว</option>
            </select>
          </div>
        </div>

        <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <div v-for="n in 9" :key="n" class="box-card aspect-[3/4] skeleton"></div>
        </div>
        <div v-else-if="products.length" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <ProductCard v-for="(p, i) in products" :key="p.id" v-reveal="i % 6" :product="p" />
        </div>
        <div v-else class="box-card p-12 text-center text-slate-400">
          <i class="fi fi-rr-search text-4xl"></i>
          <p class="mt-3">ไม่พบสินค้าที่ตรงกับเงื่อนไข</p>
        </div>

        <Pagination :meta="meta" @change="goPage" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/index.js'
import ProductCard from './components/ProductCard.vue'
import Breadcrumb from './components/Breadcrumb.vue'
import CatalogFilters from './components/CatalogFilters.vue'
import Pagination from '../components/Pagination.vue'
import Drawer from 'primevue/drawer'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const products = ref([])
const meta = ref({})
const categories = ref([])
const groups = ref([])
const filterOpen = ref(false)

const filters = reactive({
  q: route.query.q || '',
  category: route.query.category || null,
  group: route.query.group || null,
  min_price: route.query.min_price || null,
  max_price: route.query.max_price || null,
  sort: route.query.sort || 'newest',
  page: Number(route.query.page) || 1,
})

async function loadProducts() {
  loading.value = true
  try {
    const params = { per_page: 12, page: filters.page }
    for (const k of ['q', 'category', 'group', 'min_price', 'max_price', 'sort']) {
      if (filters[k]) params[k] = filters[k]
    }
    const { data } = await api.get('/shop/products', { params })
    products.value = data.data || []
    meta.value = data
    // sync URL (ไม่เพิ่ม history มาก)
    router.replace({ query: { ...params, per_page: undefined, page: filters.page > 1 ? filters.page : undefined } })
  } finally {
    loading.value = false
  }
}

async function loadFacets() {
  const [catRes, grpRes] = await Promise.all([
    api.get('/shop/categories'),
    api.get('/shop/groups'),
  ])
  categories.value = (catRes.data || []).filter(c => c.products_count > 0)
  groups.value = grpRes.data || []
}

function setFilter(key, value) {
  filters[key] = value
  filters.page = 1
  loadProducts()
}

function applyAndReload() {
  filters.page = 1
  loadProducts()
}

// versions ที่ปิด drawer หลังเลือก (มือถือ)
function setFilterAndClose(key, value) { setFilter(key, value); filterOpen.value = false }
function applyAndClose() { applyAndReload(); filterOpen.value = false }

const activeFilterCount = computed(() =>
  ['category', 'group', 'min_price', 'max_price'].filter(k => filters[k]).length
)

function goPage(p) {
  filters.page = p
  loadProducts()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ตอบสนองการค้นหาจาก header (เปลี่ยน query q)
watch(() => route.query.q, (q) => {
  if (q !== filters.q) {
    filters.q = q || ''
    filters.page = 1
    loadProducts()
  }
})

onMounted(() => {
  loadFacets()
  loadProducts()
})
</script>
