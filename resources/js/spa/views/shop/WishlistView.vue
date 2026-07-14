<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 pb-24 lg:pb-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500">
        <i class="fi fi-sr-heart text-lg"></i>
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-800">รายการโปรด</h1>
        <p class="text-sm text-slate-400">{{ totalLabel }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <div v-for="i in 8" :key="i" class="box-card aspect-[3/4] skeleton"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="!items.length" class="box-card py-20 flex flex-col items-center gap-3 text-slate-400">
      <i class="fi fi-rr-heart text-5xl text-rose-200"></i>
      <p class="font-medium">ยังไม่มีสินค้าในรายการโปรด</p>
      <RouterLink to="/shop/products"
        class="mt-2 px-5 py-2.5 rounded-full bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700 transition">
        เลือกดูสินค้า
      </RouterLink>
    </div>

    <!-- Grid -->
    <div v-else>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <ProductCard v-for="item in items" :key="item.id" :product="item.product" />
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="flex justify-center gap-2 mt-8">
        <button v-for="p in lastPage" :key="p" @click="loadPage(p)"
          class="w-9 h-9 rounded-full text-sm font-medium transition"
          :class="p === currentPage ? 'bg-violet-600 text-white' : 'bg-white text-slate-600 hover:bg-violet-50 border border-slate-200'">
          {{ p }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api/index.js'
import ProductCard from './components/ProductCard.vue'

const items       = ref([])
const loading     = ref(true)
const currentPage = ref(1)
const lastPage    = ref(1)
const total       = ref(0)

const totalLabel = computed(() => total.value ? `${total.value} รายการ` : 'ยังไม่มีสินค้าที่กดใจ')

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/shop/my/wishlist', { params: { page } })
    items.value       = data.data
    currentPage.value = data.current_page
    lastPage.value    = data.last_page
    total.value       = data.total
  } finally { loading.value = false }
}

function loadPage(p) { load(p); window.scrollTo({ top: 0, behavior: 'smooth' }) }

onMounted(() => load())
</script>
