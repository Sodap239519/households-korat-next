<template>
  <div class="group box-card hover-lift overflow-hidden flex flex-col">
    <RouterLink :to="`/shop/products/${product.slug}`" class="block relative aspect-square bg-slate-100 overflow-hidden">
      <img
        v-if="imageUrl"
        :src="imageUrl"
        :alt="product.name"
        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
        loading="lazy"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
        <i class="fi fi-rr-picture text-4xl"></i>
      </div>
      <span v-if="onSale" class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-rose-500 text-white text-[11px] font-bold shadow">-{{ discountPct }}%</span>
      <span v-if="product.is_featured" class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-amber-400 text-amber-900 text-[11px] font-bold shadow">แนะนำ</span>
    </RouterLink>

    <div class="p-3 flex flex-col flex-1">
      <p v-if="groupName" class="text-[11px] text-violet-500 truncate mb-0.5">
        <i class="fi fi-rr-shop"></i> {{ groupName }}
      </p>
      <RouterLink :to="`/shop/products/${product.slug}`" class="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 hover:text-violet-700 min-h-[2.5rem]">
        {{ product.name }}
      </RouterLink>

      <div class="mt-1.5">
        <StarRating :rating="product.rating_avg || 0" :count="product.rating_count || 0" size="sm" />
      </div>

      <div class="mt-auto pt-2 flex items-end justify-between gap-2">
        <div>
          <span class="text-lg font-bold text-fuchsia-700">฿{{ formatPrice(effectivePrice) }}</span>
          <span v-if="onSale" class="block text-xs text-slate-400 line-through">฿{{ formatPrice(product.price) }}</span>
          <span class="block text-[11px] text-slate-400">ต่อ {{ product.unit }}</span>
        </div>
        <button
          class="shrink-0 w-9 h-9 rounded-full bg-violet-600 hover:bg-violet-700 text-white flex items-center justify-center shadow-md shadow-violet-500/30 transition-transform duration-200 hover:scale-110 active:scale-95"
          title="เพิ่มลงตะกร้า"
          @click="addToCart"
        >
          <i class="fi fi-rr-shopping-cart-add"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCart } from '../../../composables/useCart.js'
import StarRating from './StarRating.vue'

const props = defineProps({
  product: { type: Object, required: true },
})

const cart = useCart()
const toast = useToast()

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

function formatPrice(v) {
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function addToCart() {
  cart.add(props.product, 1)
  toast.add({ severity: 'success', summary: 'เพิ่มลงตะกร้าแล้ว', detail: props.product.name, life: 2000 })
}
</script>
