<template>
  <!-- Backdrop -->
  <Teleport to="body">
    <Transition name="sheet">
      <div v-if="modelValue" class="fixed inset-0 z-[80] flex items-end justify-center">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('update:modelValue', false)"></div>

        <!-- Sheet -->
        <div class="relative w-full max-w-lg bg-white rounded-t-3xl shadow-2xl z-10 max-h-[90vh] overflow-y-auto">
          <!-- Handle bar -->
          <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1 rounded-full bg-slate-200"></div>
          </div>

          <!-- Close -->
          <button @click="$emit('update:modelValue', false)" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition">
            <i class="fi fi-rr-cross-small text-base"></i>
          </button>

          <!-- Product summary -->
          <div class="px-4 pb-4 flex gap-4 border-b border-slate-100">
            <div class="w-24 h-24 rounded-2xl overflow-hidden bg-slate-100 shrink-0">
              <img v-if="imageUrl" :src="imageUrl" :alt="product.name" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                <i class="fi fi-rr-picture text-3xl"></i>
              </div>
            </div>
            <div class="flex-1 pt-1">
              <p class="text-xl font-bold text-fuchsia-700">฿{{ fmt(effectivePrice) }}</p>
              <p v-if="onSale" class="text-sm text-slate-400 line-through">฿{{ fmt(product.price) }}</p>
              <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ product.name }}</p>
              <p class="text-xs text-slate-400 mt-0.5">คลัง: {{ product.stock_qty || 0 }} {{ product.unit }}</p>
            </div>
          </div>

          <div class="px-4 py-4 space-y-5">
            <!-- Note (optional) -->
            <div>
              <label class="text-sm font-semibold text-slate-700 mb-2 block">หมายเหตุ <span class="text-slate-400 font-normal">(ไม่บังคับ)</span></label>
              <input v-model="note" class="buy-inp w-full" placeholder="ระบุรายละเอียดเพิ่มเติม เช่น ขนาด สี..." />
            </div>

            <!-- Quantity -->
            <div>
              <label class="text-sm font-semibold text-slate-700 mb-2 block">จำนวน</label>
              <div class="flex items-center gap-3">
                <button @click="dec" class="w-10 h-10 rounded-full border-2 border-slate-200 flex items-center justify-center text-slate-600 hover:border-violet-400 hover:text-violet-600 transition font-bold text-lg" :disabled="qty <= 1">
                  <i class="fi fi-rr-minus-small"></i>
                </button>
                <input v-model.number="qty" type="number" min="1" :max="maxQty"
                  class="w-16 h-10 text-center font-bold text-lg border-2 border-slate-200 rounded-xl focus:outline-none focus:border-violet-400" />
                <button @click="inc" class="w-10 h-10 rounded-full border-2 border-slate-200 flex items-center justify-center text-slate-600 hover:border-violet-400 hover:text-violet-600 transition font-bold text-lg" :disabled="qty >= maxQty">
                  <i class="fi fi-rr-plus-small"></i>
                </button>
                <span class="text-sm text-slate-400">/ {{ maxQty }} {{ product.unit }}</span>
              </div>
            </div>

            <!-- Total -->
            <div class="bg-violet-50 rounded-2xl px-4 py-3 flex items-center justify-between">
              <span class="text-sm text-slate-600">ราคารวม</span>
              <span class="text-xl font-bold text-fuchsia-700">฿{{ fmt(effectivePrice * qty) }}</span>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="px-4 pb-6 flex gap-3">
            <button @click="addCart" class="flex-1 h-12 rounded-2xl bg-orange-500 hover:bg-orange-600 text-white font-bold text-base transition shadow-lg shadow-orange-500/30 flex items-center justify-center gap-2">
              <i class="fi fi-rr-shopping-cart-add"></i> เพิ่มลงตะกร้า
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCart } from '../../../composables/useCart.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  product:    { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue'])

const cart  = useCart()
const toast = useToast()
const qty   = ref(1)
const note  = ref('')

const imageUrl = computed(() => props.product?.primary_image_url || props.product?.images?.[0]?.url || null)
const effectivePrice = computed(() => Number(props.product?.effective_price ?? props.product?.sale_price ?? props.product?.price ?? 0))
const onSale = computed(() => props.product?.sale_price != null && Number(props.product.sale_price) < Number(props.product.price))
const maxQty = computed(() => Math.max(1, props.product?.stock_qty || 99))

watch(() => props.modelValue, v => { if (v) { qty.value = 1; note.value = '' } })

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function dec() { if (qty.value > 1) qty.value-- }
function inc() { if (qty.value < maxQty.value) qty.value++ }

function addCart() {
  cart.add(props.product, qty.value)
  toast.add({ severity: 'success', summary: 'เพิ่มลงตะกร้าแล้ว', detail: `${props.product.name} ×${qty.value}`, life: 2000 })
  emit('update:modelValue', false)
}
</script>

<style scoped>
.buy-inp {
  height: 2.6rem;
  padding: 0 0.75rem;
  border-radius: 0.85rem;
  border: 1.5px solid rgb(226 232 240);
  font-size: 0.875rem;
  transition: border-color .15s;
}
.buy-inp:focus { outline: none; border-color: rgb(167 139 250); }

.sheet-enter-active, .sheet-leave-active { transition: all .3s cubic-bezier(.32,0,.67,0); }
.sheet-enter-from .absolute, .sheet-leave-to .absolute { transform: translateY(100%); }
.sheet-enter-from, .sheet-leave-to { opacity: 0; }
</style>
