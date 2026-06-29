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
      <div class="flex items-center justify-between mb-4">
        <Breadcrumb :items="[{ label: 'สินค้า', to: '/shop/products' }, { label: product.name }]" />
        <ShareButton v-if="product"
          :title="product.name"
          :text="`${product.short_description || product.name} — ฿${formatPrice(effectivePrice)}`"
          :url="`/shop/products/${product.slug}`"
          :chat-group-slug="product.seller_group?.slug || ''"
          :product-slug="product.slug"
          variant="icon-sm"
          theme="default"
          align="right"
        />
      </div>

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
            <h1 class="text-2xl font-bold text-slate-800">{{ product.name }}</h1>
            <div class="flex items-center gap-3 mt-2">
              <StarRating :rating="product.rating_avg || 0" :count="product.rating_count || 0" size="md" />
              <span class="text-slate-300">·</span>
              <span class="text-sm text-slate-400">ขายแล้ว {{ product.total_sold ?? 0 }} ชิ้น</span>
              <span class="text-slate-200">·</span>
              <span class="text-sm text-slate-400 flex items-center gap-1">
                <i class="fi fi-rr-heart text-rose-400 text-xs"></i>{{ product.wishlist_count || 0 }}
                <span class="text-slate-200 mx-0.5">·</span>
                <i class="fi fi-rr-eye text-xs"></i>{{ product.view_count }}
              </span>
            </div>
          </div>

          <!-- ═══ Action Group Card ═══ -->
          <div class="rounded-2xl border border-violet-100 shadow-sm">

            <!-- Row 1: ราคา (ซ้าย) + จำนวน (ขวา) -->
            <div class="bg-gradient-to-r from-violet-50 to-fuchsia-50/60 px-4 py-3 flex items-center gap-3 rounded-t-2xl">
              <!-- ราคา -->
              <div class="flex-1 min-w-0">
                <div class="flex items-baseline gap-2 flex-wrap">
                  <span class="text-2xl font-extrabold text-fuchsia-700 tracking-tight">
                    ฿{{ formatPrice(effectivePrice) }}
                  </span>
                  <span v-if="onSale" class="text-sm text-slate-400 line-through">฿{{ formatPrice(product.price) }}</span>
                  <span v-if="onSale" class="px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[11px] font-bold leading-none">
                    -{{ discountPct }}%
                  </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                  <span>/ {{ product.unit }}</span>
                  <span class="text-slate-200">·</span>
                  <span :class="product.stock_qty > 0 ? 'text-emerald-600' : 'text-rose-500'">
                    {{ product.stock_qty > 0 ? `คงเหลือ ${product.stock_qty} ${product.unit}` : 'สินค้าหมด' }}
                  </span>
                </p>
              </div>

              <!-- จำนวน -->
              <div class="shrink-0 flex items-center rounded-xl overflow-hidden border border-slate-200 bg-white shadow-sm">
                <button class="w-9 h-9 flex items-center justify-center text-slate-500 hover:bg-violet-50 hover:text-violet-600 transition text-base"
                  @click="qty = Math.max(1, qty - 1)">
                  <i class="fi fi-rr-minus-small"></i>
                </button>
                <span class="w-8 text-center text-sm font-semibold text-slate-700 select-none">{{ qty }}</span>
                <button class="w-9 h-9 flex items-center justify-center text-slate-500 hover:bg-violet-50 hover:text-violet-600 transition text-base"
                  @click="qty = Math.min(product.stock_qty || 99, qty + 1)">
                  <i class="fi fi-rr-plus-small"></i>
                </button>
              </div>
            </div>

            <!-- Divider -->
            <div class="h-px bg-violet-100/80"></div>

            <!-- Row 2: ปุ่ม Action (ซ้าย→ขวา: แชท · โปรด · ตะกร้า · ซื้อเลย) -->
            <div class="bg-white px-3 py-2.5 flex items-center gap-2 rounded-b-2xl">
              <!-- แชทกับผู้ขาย (icon compact) -->
              <button v-if="product?.seller_group?.slug && user"
                @click="openProductChat"
                title="แชทกับผู้ขาย"
                class="shrink-0 w-11 h-11 rounded-xl border-2 border-slate-200 flex items-center justify-center transition text-slate-500 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-600 text-base"
              >
                <i class="fi fi-rr-comment-alt"></i>
              </button>

              <!-- รายการโปรด -->
              <button
                @click="onToggleWishlist"
                :title="liked ? 'นำออกจากรายการโปรด' : 'เพิ่มในรายการโปรด'"
                class="shrink-0 w-11 h-11 rounded-xl border-2 flex items-center justify-center transition text-lg"
                :class="liked ? 'border-rose-300 bg-rose-50 text-rose-500' : 'border-slate-200 hover:border-rose-300 text-slate-300 hover:text-rose-400'"
              >
                <i :class="liked ? 'fi fi-sr-heart' : 'fi fi-rr-heart'"></i>
              </button>

              <!-- เพิ่มลงตะกร้า -->
              <button
                class="flex-1 min-w-0 h-11 rounded-xl border-2 border-violet-500 text-violet-700 font-semibold text-sm transition hover:bg-violet-50 flex items-center justify-center gap-1 disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="product.stock_qty <= 0"
                @click="addToCart"
              >
                <i class="fi fi-rr-shopping-cart-add text-sm"></i>
                <span>ตะกร้า</span>
              </button>

              <!-- ซื้อเลย (primary · ขวาสุด = นิ้วโป้งมือขวา) -->
              <button
                class="flex-1 min-w-0 h-11 rounded-xl font-bold text-sm transition flex items-center justify-center gap-1 shadow-md shadow-orange-400/25 disabled:opacity-40 disabled:cursor-not-allowed"
                :class="product.stock_qty > 0 ? 'btn-orange' : 'bg-slate-200 text-slate-400'"
                :disabled="product.stock_qty <= 0"
                @click="buyNow"
              >
                <i class="fi fi-rr-bolt text-sm"></i> ซื้อเลย
              </button>
            </div>
          </div>
          <!-- ════════════════════════ -->

          <p v-if="product.short_description" class="text-slate-600 leading-relaxed text-sm">{{ product.short_description }}</p>

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

      <!-- Seller Profile Card -->
      <section v-if="product.seller_group" class="mt-6 box-card p-4">
        <div class="flex items-center gap-3">
          <!-- Logo / Avatar -->
          <div class="w-16 h-16 rounded-full overflow-hidden bg-gradient-to-br from-violet-400 to-fuchsia-500 flex items-center justify-center shrink-0 shadow-md shadow-violet-200">
            <img v-if="product.seller_group.logo_path"
              :src="`/storage/${product.seller_group.logo_path}`"
              :alt="product.seller_group.name"
              class="w-full h-full object-cover" />
            <span v-else class="text-white font-bold text-2xl">
              {{ product.seller_group.name.charAt(0) }}
            </span>
          </div>
          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="font-bold text-slate-800 truncate">{{ product.seller_group.name }}</p>
            <p v-if="product.seller_group.districts?.length" class="text-xs text-slate-400 mt-0.5">
              <i class="fi fi-rr-marker text-[10px]"></i>
              {{ product.seller_group.districts.slice(0, 2).join(' · ') }}
            </p>
          </div>
          <!-- ดูร้านค้า -->
          <RouterLink
            :to="`/shop/sellers/${product.seller_group.slug}`"
            class="shrink-0 px-4 py-1.5 rounded-full border-2 border-violet-500 text-violet-700 text-xs font-semibold hover:bg-violet-50 transition"
          >ดูร้านค้า</RouterLink>
        </div>
        <!-- Stats -->
        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-3 gap-3 text-center">
          <div>
            <p class="text-lg font-bold text-slate-800">{{ (product.rating_avg || 0).toFixed(1) }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">ให้คะแนน</p>
          </div>
          <div class="border-x border-slate-100">
            <p class="text-lg font-bold text-slate-800">{{ fromStore.length > 0 ? fromStore.length + '+' : '-' }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">รายการสินค้า</p>
          </div>
          <div>
            <p class="text-lg font-bold text-slate-800">{{ (product.seller_group.districts || []).length }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">เขตพื้นที่</p>
          </div>
        </div>
      </section>

      <!-- สินค้าขายดีประจำร้าน (horizontal scroll) -->
      <section v-if="fromStore.length" class="mt-6">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-bold text-slate-800 flex items-center gap-2">
            <i class="fi fi-rr-shop text-violet-600"></i>
            สินค้าขายดีประจำร้าน
          </h2>
          <RouterLink
            v-if="product.seller_group?.slug"
            :to="`/shop/sellers/${product.seller_group.slug}`"
            class="text-sm text-violet-600 hover:underline flex items-center gap-1 shrink-0"
          >ดูทั้งหมด <i class="fi fi-rr-angle-right text-xs"></i></RouterLink>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-2 -mx-1 px-1 snap-x snap-mandatory scrollbar-hide">
          <div v-for="p in fromStore" :key="p.id" class="shrink-0 w-40 sm:w-48 snap-start">
            <ProductCard :product="p" />
          </div>
        </div>
      </section>

      <!-- สินค้าที่คล้ายกัน (grid) -->
      <section v-if="related.length" class="mt-8">
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
          <i class="fi fi-rr-apps-add text-violet-600"></i> สินค้าที่คล้ายกัน
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <ProductCard v-for="(p, i) in related" :key="p.id" v-reveal="i" :product="p" />
        </div>
      </section>

      <!-- สินค้าที่คุณอาจชอบ (ยอดนิยมทั้งร้าน) -->
      <section v-if="alsoLike.length" class="mt-8">
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
          <i class="fi fi-rr-heart text-rose-400"></i> สินค้าที่คุณอาจชอบ
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <ProductCard v-for="(p, i) in alsoLike" :key="p.id" v-reveal="i" :product="p" />
        </div>
      </section>

      <!-- Floating: scroll-to-top จาง ๆ (เลื่อนลงแล้ว) -->
      <Teleport to="body">
        <Transition name="fade-up">
          <button v-if="scrolledDown"
            @click="scrollTop"
            class="fixed bottom-20 lg:bottom-6 right-4 z-30 w-10 h-10 rounded-full bg-white/60 hover:bg-white/90 backdrop-blur-sm border border-slate-200/60 text-slate-500 flex items-center justify-center shadow-sm transition">
            <i class="fi fi-rr-angle-up text-sm"></i>
          </button>
        </Transition>
      </Teleport>

      <!-- ดูสินค้าเพิ่มเติม CTA -->
      <div class="mt-8 box-card p-6 text-center bg-gradient-to-r from-violet-50 to-fuchsia-50 border-violet-100">
        <p class="text-slate-600 mb-3">สำรวจสินค้าชุมชนคุณภาพดีจากทั่วจังหวัดนครราชสีมา</p>
        <div class="flex justify-center gap-3 flex-wrap">
          <RouterLink to="/shop/products" class="btn-orange btn-sheen px-6 h-10 rounded-full font-semibold text-sm flex items-center gap-2">
            <i class="fi fi-rr-grid"></i> สินค้าทั้งหมด
          </RouterLink>
          <RouterLink to="/shop" class="px-6 h-10 rounded-full border border-violet-200 text-violet-700 hover:bg-violet-50 font-semibold text-sm flex items-center gap-2 transition">
            <i class="fi fi-rr-home"></i> หน้าหลัก
          </RouterLink>
        </div>
      </div>
    </template>
  </div>

  <!-- ===== Action Sheets (ซื้อเลย / เพิ่มลงตะกร้า) ===== -->
  <Teleport to="body">
    <Transition name="overlay-fade">
      <div v-if="sheetOpen" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm" @click="sheetOpen = false"></div>
    </Transition>
    <Transition name="sheet-up">
      <div v-if="sheetOpen && product" class="fixed bottom-0 inset-x-0 z-50 bg-white rounded-t-3xl shadow-2xl overflow-hidden"
        style="padding-bottom: env(safe-area-inset-bottom, 0px)">
        <!-- Handle bar -->
        <div class="flex justify-center pt-3 pb-2">
          <div class="w-10 h-1 rounded-full bg-slate-200"></div>
        </div>

        <!-- ── Buy Now Summary ── -->
        <template v-if="sheetMode === 'buy-now'">
          <div class="flex items-center justify-between px-5 pb-3 border-b border-slate-100">
            <h3 class="font-bold text-slate-800 text-lg">สรุปคำสั่งซื้อ</h3>
            <button @click="sheetOpen = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
              <i class="fi fi-rr-cross-small"></i>
            </button>
          </div>
          <div class="px-5 py-4 space-y-3">
            <!-- Product row -->
            <div class="flex gap-3">
              <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 shrink-0">
                <img v-if="activeImage" :src="activeImage" class="w-full h-full object-cover" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-800 line-clamp-2 text-sm leading-snug">{{ product.name }}</p>
                <p class="text-fuchsia-700 font-bold mt-1">฿{{ formatPrice(effectivePrice) }} <span class="text-slate-400 font-normal text-xs">/ {{ product.unit }}</span></p>
              </div>
            </div>
            <!-- Qty & Total -->
            <div class="bg-violet-50 rounded-2xl p-4 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">จำนวน</span>
                <span class="font-semibold text-slate-700">{{ qty }} {{ product.unit }}</span>
              </div>
              <div class="flex justify-between items-end border-t border-violet-100 pt-2">
                <span class="text-slate-600 font-medium">ยอดรวม</span>
                <span class="text-2xl font-extrabold text-fuchsia-700">฿{{ formatPrice(effectivePrice * qty) }}</span>
              </div>
            </div>
            <!-- Actions -->
            <button @click="executeBuyNow"
              class="w-full h-12 rounded-2xl btn-orange btn-sheen font-bold text-base flex items-center justify-center gap-2">
              <i class="fi fi-rr-bolt"></i> ยืนยันซื้อเลย
            </button>
            <button @click="sheetOpen = false"
              class="w-full h-10 rounded-2xl text-slate-400 text-sm font-medium hover:bg-slate-50 transition">
              ยกเลิก
            </button>
          </div>
        </template>

        <!-- ── Add to Cart Confirm ── -->
        <template v-if="sheetMode === 'cart'">
          <div class="flex items-center justify-between px-5 pb-3 border-b border-slate-100">
            <h3 class="font-bold text-slate-800 text-lg">เพิ่มลงตะกร้า</h3>
            <button @click="sheetOpen = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
              <i class="fi fi-rr-cross-small"></i>
            </button>
          </div>
          <div class="px-5 py-4 space-y-3">
            <div class="flex gap-3">
              <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 shrink-0">
                <img v-if="activeImage" :src="activeImage" class="w-full h-full object-cover" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-800 line-clamp-2 text-sm leading-snug">{{ product.name }}</p>
                <p class="text-fuchsia-700 font-bold mt-1">฿{{ formatPrice(effectivePrice) }} <span class="text-slate-400 font-normal text-xs">/ {{ product.unit }}</span></p>
              </div>
            </div>
            <div class="bg-violet-50 rounded-2xl p-4 flex items-center justify-between">
              <span class="text-violet-700 font-medium text-sm">เพิ่ม <strong>{{ qty }}</strong> {{ product.unit }} ลงตะกร้า</span>
              <span class="font-bold text-violet-800 text-lg">฿{{ formatPrice(effectivePrice * qty) }}</span>
            </div>
            <button @click="executeAddToCart"
              class="w-full h-12 rounded-2xl border-2 border-violet-500 text-violet-700 font-bold text-base flex items-center justify-center gap-2 hover:bg-violet-50 transition">
              <i class="fi fi-rr-shopping-cart-add"></i> ยืนยันเพิ่มลงตะกร้า
            </button>
            <button @click="sheetOpen = false"
              class="w-full h-10 rounded-2xl text-slate-400 text-sm font-medium hover:bg-slate-50 transition">
              ยกเลิก
            </button>
          </div>
        </template>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.overlay-fade-enter-active, .overlay-fade-leave-active { transition: opacity 0.2s ease; }
.overlay-fade-enter-from, .overlay-fade-leave-to { opacity: 0; }
.sheet-up-enter-active, .sheet-up-leave-active { transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1); }
.sheet-up-enter-from, .sheet-up-leave-to { transform: translateY(100%); }
</style>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useCart } from '../../composables/useCart.js'
import { useWishlist } from '../../composables/useWishlist.js'
import { useAuth } from '../../composables/useAuth.js'
import ProductCard from './components/ProductCard.vue'
import StarRating from './components/StarRating.vue'
import Breadcrumb from './components/Breadcrumb.vue'
import ReviewSection from './components/ReviewSection.vue'
import CommentSection from './components/CommentSection.vue'
import ShareButton from './components/ShareButton.vue'
import { useBuyNow } from '../../composables/useBuyNow.js'

const route   = useRoute()
const router  = useRouter()
const toast   = useToast()
const cart    = useCart()
const buyNow_ = useBuyNow()
const wishlist = useWishlist()
const { user } = useAuth()

const liked = computed(() => product.value ? wishlist.isLiked(product.value.id) : false)

async function onToggleWishlist() {
  if (!user.value) {
    router.push({ path: '/shop/login', query: { redirect: route.fullPath } })
    return
  }
  const added = await wishlist.toggle(product.value.id, user.value)
  toast.add({
    severity: added ? 'success' : 'info',
    summary: added ? 'เพิ่มในรายการโปรดแล้ว' : 'นำออกจากรายการโปรดแล้ว',
    life: 1800,
  })
}

const scrolledDown = ref(false)
function onScroll() { scrolledDown.value = window.scrollY > 280 }
function scrollTop() { window.scrollTo({ top: 0, behavior: 'smooth' }) }

const sheetOpen = ref(false)
const sheetMode = ref('')

const loading = ref(true)
const product = ref(null)
const reviews = ref([])
const comments = ref([])
const related = ref([])
const fromStore = ref([])
const alsoLike  = ref([])
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
  if (qty.value > 1) {
    sheetMode.value = 'cart'
    sheetOpen.value = true
  } else {
    executeAddToCart()
  }
}

function executeAddToCart() {
  sheetOpen.value = false
  cart.add(product.value, qty.value)
  toast.add({ severity: 'success', summary: 'เพิ่มลงตะกร้าแล้ว', detail: `${product.value.name} × ${qty.value}`, life: 2000 })
}

function buyNow() {
  sheetMode.value = 'buy-now'
  sheetOpen.value = true
}

async function executeBuyNow() {
  if (!product.value) return
  sheetOpen.value = false
  buyNow_.set({
    product_id: product.value.id,
    slug:       product.value.slug,
    name:       product.value.name,
    price:      Number(effectivePrice.value),
    unit:       product.value.unit,
    image:      product.value.primary_image_url || null,
    group_id:   product.value.seller_group_id ?? product.value.seller_group?.id ?? null,
    group_name: product.value.seller_group?.name ?? '',
    stock_qty:  product.value.stock_qty ?? null,
    qty:        qty.value,
  })
  await nextTick()
  router.push('/shop/checkout?buynow=1')
}

async function openProductChat() {
  if (!user.value) {
    router.push({ path: '/shop/login', query: { redirect: route.fullPath } })
    return
  }
  try {
    const { data } = await api.post(`/shop/chat/start/${product.value.seller_group.slug}`)
    router.push({ path: '/shop/chat', query: { id: data.id, productSlug: product.value.slug } })
  } catch { /* ignore */ }
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
    fromStore.value = data.from_store || []
    alsoLike.value  = data.also_like  || []
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
onMounted(() => {
  load(route.params.slug)
  window.addEventListener('scroll', onScroll, { passive: true })
})
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>
