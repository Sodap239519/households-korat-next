<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    <div v-if="loading" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-spinner animate-spin text-3xl"></i>
      <p class="mt-2">กำลังโหลด...</p>
    </div>

    <div v-else-if="!product" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-box-open text-4xl"></i>
      <p class="mt-3">ไม่พบสินค้านี้</p>
      <RouterLink to="/shop/products" class="text-violet-600 hover:underline mt-2 inline-block">กลับไปหน้าสินค้า</RouterLink>
    </div>

    <template v-else>
      <Breadcrumb :items="[{ label: 'สินค้า', to: '/shop/products' }, { label: product.name }]" class="mb-4" />

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 anim-fade-up">
        <!-- Gallery -->
        <div class="space-y-3">
          <div class="box-card aspect-square bg-slate-100 overflow-hidden flex items-center justify-center">
            <Transition name="fade-slide" mode="out-in">
              <img v-if="activeImage" :key="activeImage" :src="activeImage" :alt="product.name" class="w-full h-full object-cover" />
              <i v-else class="fi fi-rr-picture text-6xl text-slate-300"></i>
            </Transition>
          </div>
          <div v-if="images.length > 1" class="flex gap-2 flex-wrap">
            <button
              v-for="(img, i) in images"
              :key="i"
              class="w-16 h-16 rounded-lg overflow-hidden border-2 transition"
              :class="activeImage === img ? 'border-violet-500' : 'border-transparent'"
              @click="activeImage = img"
            >
              <img :src="img" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>

        <!-- Info -->
        <div class="space-y-4">
          <div>
            <RouterLink v-if="groupName" :to="{ path: '/shop/products', query: { group: product.seller_group?.slug } }" class="text-sm text-violet-600 hover:underline">
              <i class="fi fi-rr-shop"></i> {{ groupName }}
            </RouterLink>
            <h1 class="text-2xl font-bold text-slate-800 mt-1">{{ product.name }}</h1>
            <div class="flex items-center gap-3 mt-2">
              <StarRating :rating="product.rating_avg || 0" :count="product.rating_count || 0" size="md" />
              <span class="text-slate-300">·</span>
              <span class="text-sm text-slate-400">ขายแล้ว/เข้าชม {{ product.view_count }} ครั้ง</span>
            </div>
          </div>

          <div class="box-card p-4 bg-violet-50/40">
            <div class="flex items-end gap-3 flex-wrap">
              <span class="text-3xl font-bold text-fuchsia-700">฿{{ formatPrice(effectivePrice) }}</span>
              <span v-if="onSale" class="text-lg text-slate-400 line-through mb-0.5">฿{{ formatPrice(product.price) }}</span>
              <span v-if="onSale" class="mb-1 px-2 py-0.5 rounded-full bg-rose-500 text-white text-xs font-bold">-{{ discountPct }}%</span>
              <span class="text-sm text-slate-500 mb-1">/ {{ product.unit }}</span>
            </div>
          </div>

          <p v-if="product.short_description" class="text-slate-600 leading-relaxed">{{ product.short_description }}</p>

          <dl class="grid grid-cols-2 gap-2 text-sm">
            <div class="flex gap-2"><dt class="text-slate-400">รหัสสินค้า:</dt><dd class="text-slate-700">{{ product.sku || '-' }}</dd></div>
            <div class="flex gap-2"><dt class="text-slate-400">หมวดหมู่:</dt><dd class="text-slate-700">{{ product.category?.name || '-' }}</dd></div>
            <div class="flex gap-2"><dt class="text-slate-400">แหล่งผลิต:</dt><dd class="text-slate-700">{{ product.district || '-' }}</dd></div>
            <div class="flex gap-2">
              <dt class="text-slate-400">คงเหลือ:</dt>
              <dd :class="product.stock_qty > 0 ? 'text-emerald-600' : 'text-rose-600'">
                {{ product.stock_qty > 0 ? `${product.stock_qty} ${product.unit}` : 'สินค้าหมด' }}
              </dd>
            </div>
          </dl>

          <!-- Qty + add to cart -->
          <div class="flex items-center gap-3 pt-2">
            <div class="flex items-center border border-slate-200 rounded-full overflow-hidden">
              <button class="w-10 h-10 hover:bg-slate-50 text-slate-500" @click="qty = Math.max(1, qty - 1)"><i class="fi fi-rr-minus-small"></i></button>
              <input v-model.number="qty" type="number" min="1" :max="product.stock_qty || 99" class="w-12 text-center border-0 focus:outline-none" />
              <button class="w-10 h-10 hover:bg-slate-50 text-slate-500" @click="qty = qty + 1"><i class="fi fi-rr-plus-small"></i></button>
            </div>
            <button
              class="btn-orange flex-1 h-11 rounded-full font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="product.stock_qty <= 0"
              @click="addToCart"
            >
              <i class="fi fi-rr-shopping-cart-add mr-1"></i> เพิ่มลงตะกร้า
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="mt-8 box-card overflow-hidden">
        <div class="flex border-b border-slate-100">
          <button v-for="t in tabs" :key="t.key" class="px-5 py-3 text-sm font-medium transition" :class="activeTab === t.key ? 'text-violet-700 border-b-2 border-violet-600' : 'text-slate-500 hover:text-slate-700'" @click="activeTab = t.key">
            {{ t.label }}
          </button>
        </div>
        <div class="p-5">
          <!-- Description -->
          <div v-show="activeTab === 'desc'" class="prose prose-sm max-w-none text-slate-600 whitespace-pre-line">
            {{ product.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}
          </div>

          <!-- Reviews -->
          <div v-show="activeTab === 'reviews'">
            <ReviewSection
              :slug="slug"
              :initial-reviews="reviews"
              :rating-avg="product.rating_avg || 0"
              :rating-count="product.rating_count || 0"
            />
          </div>

          <!-- Comments -->
          <div v-show="activeTab === 'comments'">
            <CommentSection :slug="slug" :initial-comments="comments" />
          </div>
        </div>
      </div>

      <!-- Related -->
      <section v-if="related.length" class="mt-8">
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2"><i class="fi fi-rr-apps-add text-violet-600"></i> สินค้าใกล้เคียง</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <ProductCard v-for="(p, i) in related" :key="p.id" v-reveal="i" :product="p" />
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useCart } from '../../composables/useCart.js'
import ProductCard from './components/ProductCard.vue'
import StarRating from './components/StarRating.vue'
import Breadcrumb from './components/Breadcrumb.vue'
import ReviewSection from './components/ReviewSection.vue'
import CommentSection from './components/CommentSection.vue'

const route = useRoute()
const toast = useToast()
const cart = useCart()

const loading = ref(true)
const product = ref(null)
const reviews = ref([])
const comments = ref([])
const related = ref([])
const activeImage = ref(null)
const qty = ref(1)
const activeTab = ref('desc')

const tabs = [
  { key: 'desc', label: 'รายละเอียด' },
  { key: 'reviews', label: 'รีวิว' },
  { key: 'comments', label: 'คอมเมนต์' },
]

const slug = computed(() => route.params.slug)
const images = computed(() => (product.value?.images || []).map(i => i.url).filter(Boolean))
const groupName = computed(() => product.value?.seller_group?.name || '')
const effectivePrice = computed(() => Number(product.value?.effective_price ?? product.value?.price ?? 0))
const onSale = computed(() => product.value?.sale_price != null && Number(product.value.sale_price) < Number(product.value.price))
const discountPct = computed(() => {
  const p = Number(product.value?.price || 0), s = Number(product.value?.sale_price || 0)
  return p > 0 && onSale.value ? Math.round((1 - s / p) * 100) : 0
})

function formatPrice(v) {
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function addToCart() {
  cart.add(product.value, qty.value)
  toast.add({ severity: 'success', summary: 'เพิ่มลงตะกร้าแล้ว', detail: `${product.value.name} × ${qty.value}`, life: 2000 })
}

async function load(slug) {
  loading.value = true
  product.value = null
  try {
    const { data } = await api.get(`/shop/products/${slug}`)
    product.value = data.product
    reviews.value = data.reviews || []
    comments.value = data.comments || []
    related.value = data.related || []
    activeImage.value = images.value[0] || null
    qty.value = 1
    activeTab.value = 'desc'
  } catch {
    product.value = null
  } finally {
    loading.value = false
  }
}

watch(() => route.params.slug, (slug) => slug && load(slug))
onMounted(() => load(route.params.slug))
</script>
