<template>
  <div class="group box-card hover-lift overflow-hidden flex flex-col h-full">
    <RouterLink :to="`/shop/products/${product.slug}`" class="block relative aspect-square bg-slate-100 overflow-hidden">
      <img v-if="imageUrl" :src="imageUrl" :alt="product.name"
        class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy" />
      <div v-else class="w-full h-full flex items-center justify-center text-slate-200">
        <i class="fi fi-rr-picture text-4xl"></i>
      </div>
      <span v-if="onSale" class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-rose-500 text-white text-[11px] font-bold shadow">-{{ discountPct }}%</span>
      <span v-if="product.is_featured"
        class="absolute px-2 py-0.5 rounded-full bg-amber-100/90 text-amber-600 text-[11px] font-semibold border border-amber-200"
        :class="onSale ? 'top-8 left-2' : 'top-2 left-2'">แนะนำ</span>

      <button
        class="heart-btn"
        :class="liked ? 'liked' : ''"
        :title="liked ? 'นำออกจากรายการโปรด' : 'เพิ่มในรายการโปรด'"
        @click.prevent="onToggleWishlist"
      >
        <i :class="liked ? 'fi fi-sr-heart' : 'fi fi-rr-heart'"></i>
      </button>
    </RouterLink>

    <div class="p-3 flex flex-col flex-1">
      <!-- ชื่อสินค้า -->
      <RouterLink :to="`/shop/products/${product.slug}`"
        class="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 hover:text-violet-700 min-h-[2.5rem]">
        {{ product.name }}
      </RouterLink>

      <!-- ราคา + stats row -->
      <div class="mt-1.5 flex items-center gap-2 flex-wrap">
        <span class="text-base font-bold text-fuchsia-700 leading-none">฿{{ fmt(effectivePrice) }}</span>
        <span v-if="onSale" class="text-[10px] text-slate-400 line-through leading-none">฿{{ fmt(product.price) }}</span>
        <span class="flex-1 min-w-0"></span>
        <!-- ขายแล้ว (ใช้ rating_count เป็น proxy) -->
        <span v-if="product.rating_count" class="text-[10px] text-slate-400 flex items-center gap-0.5">
          <i class="fi fi-rr-shopping-cart-check" style="font-size:9px;line-height:1"></i>
          {{ product.rating_count }}
        </span>
        <!-- ดู -->
        <span v-if="product.view_count" class="text-[10px] text-slate-400 flex items-center gap-0.5">
          <i class="fi fi-rr-eye" style="font-size:9px;line-height:1"></i>
          {{ fmtShort(product.view_count) }}
        </span>
      </div>

      <!-- Flash Sale progress bar (แสดงเฉพาะ flashSale mode) -->
      <template v-if="flashSale">
        <div class="flex items-center justify-between mt-2 mb-0.5">
          <span class="text-[10px] font-medium" :class="soldPct >= 70 ? 'text-rose-600' : 'text-slate-500'">
            {{ soldPct >= 90 ? '🔥 ใกล้หมดแล้ว!' : `ขายแล้ว ${product.total_sold ?? 0} ชิ้น` }}
          </span>
          <span class="text-[10px] text-slate-400">เหลือ {{ product.stock_qty ?? 0 }}</span>
        </div>
        <div class="h-1.5 rounded-full bg-rose-100 overflow-hidden">
          <div class="h-full rounded-full transition-all duration-500"
            :style="`width:${soldPct}%;background:linear-gradient(to right,#f97316,#ef4444)`"></div>
        </div>
      </template>

      <!-- กลุ่ม + ปุ่มตะกร้า -->
      <div class="mt-auto pt-2 flex items-center justify-between gap-2">
        <RouterLink v-if="groupName" :to="`/shop/sellers/${product.seller_group?.slug || product.sellerGroup?.slug}`"
          class="min-w-0 flex-1 text-[11px] text-violet-500 truncate hover:underline" @click.stop>
          <i class="fi fi-rr-shop text-[10px]"></i> {{ groupName }}
        </RouterLink>
        <div v-else class="flex-1"></div>
        <button
          class="shrink-0 w-9 h-9 rounded-full bg-violet-600 hover:bg-violet-700 active:scale-95 text-white flex items-center justify-center shadow-md shadow-violet-500/30 transition"
          title="เพิ่มลงตะกร้า"
          @click.prevent="showSheet = true"
        >
          <i class="fi fi-rr-shopping-cart-add text-sm"></i>
        </button>
      </div>
    </div>

    <ProductBuySheet v-model="showSheet" :product="product" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import ProductBuySheet from './ProductBuySheet.vue'
import { useWishlist } from '../../../composables/useWishlist.js'
import { useAuth } from '../../../composables/useAuth.js'

const props = defineProps({
  product:   { type: Object,  required: true },
  flashSale: { type: Boolean, default: false },
})

const router    = useRouter()
const showSheet = ref(false)
const { user }  = useAuth()
const wishlist  = useWishlist()

const liked = computed(() => wishlist.isLiked(props.product.id))

async function onToggleWishlist() {
  if (!user.value) {
    router.push({ path: '/shop/login', query: { redirect: `/shop/products/${props.product.slug}` } })
    return
  }
  await wishlist.toggle(props.product.id, user.value)
}

const imageUrl = computed(() =>
  props.product.primary_image_url || props.product.images?.[0]?.url || null
)
const groupName = computed(() =>
  props.product.seller_group?.name || props.product.sellerGroup?.name || ''
)
const effectivePrice = computed(() =>
  Number(props.product.effective_price ?? props.product.sale_price ?? props.product.price)
)
const onSale = computed(() =>
  props.product.sale_price != null && Number(props.product.sale_price) < Number(props.product.price)
)
const discountPct = computed(() => {
  const p = Number(props.product.price), s = Number(props.product.sale_price)
  return p > 0 ? Math.round((1 - s / p) * 100) : 0
})
const soldPct = computed(() => {
  const sold  = Number(props.product.total_sold || 0)
  const stock = Number(props.product.stock_qty  || 0)
  if (sold + stock === 0) return Math.max(Math.min(Math.floor((props.product.view_count || 0) / 1.5), 85), 12)
  return Math.min(Math.round(sold / (sold + stock) * 100), 95)
})

function fmt(v) {
  return Number(v || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}
function fmtShort(v) {
  const n = Number(v || 0)
  if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'k'
  return String(n)
}
</script>

<style scoped>
.heart-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(255,255,255,0.88);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: #94a3b8;
  transition: all .2s;
  box-shadow: 0 1px 6px rgba(0,0,0,.15);
  opacity: 0.8;
}
.heart-btn:hover { transform: scale(1.15); color: #f43f5e; opacity: 1; }
.heart-btn.liked  { color: #f43f5e; background: rgba(255,255,255,0.95); opacity: 1; }
.heart-btn:active { transform: scale(0.9); }
</style>
