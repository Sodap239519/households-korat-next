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
              <p class="text-xl font-bold text-fuchsia-700">{{ priceLabel }}</p>
              <p v-if="!hasOptions && onSale" class="text-sm text-slate-400 line-through">฿{{ fmt(product.price) }}</p>
              <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ product.name }}</p>
              <p v-if="outOfStock" class="text-xs font-semibold text-rose-600 mt-0.5">สินค้าหมด</p>
              <p v-else-if="hasOptions && !selectedOption" class="text-xs text-violet-500 mt-0.5">เลือกตัวเลือกด้านล่าง</p>
              <p v-else class="text-xs text-slate-400 mt-0.5">คลัง: {{ stockQty }} {{ unitLabel }}</p>
            </div>
          </div>

          <div class="px-4 py-4 space-y-5">
            <!-- ตัวเลือกสินค้า -->
            <div v-if="hasOptions">
              <label class="text-sm font-semibold text-slate-700 mb-2 block">ตัวเลือก</label>
              <div class="flex flex-wrap gap-2">
                <button v-for="opt in options" :key="opt.id"
                  @click="selectOption(opt)"
                  :disabled="Number(opt.stock_qty) <= 0"
                  class="px-3.5 py-2 rounded-xl border-2 text-sm font-medium transition text-left"
                  :class="Number(opt.stock_qty) <= 0
                    ? 'border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed line-through'
                    : (selectedOption?.id === opt.id
                        ? 'border-violet-500 bg-violet-50 text-violet-700 shadow-sm'
                        : 'border-slate-200 text-slate-600 hover:border-violet-300')">
                  <span class="block">{{ opt.name }}</span>
                  <span class="block text-xs" :class="Number(opt.stock_qty) <= 0 ? '' : 'text-fuchsia-600 font-semibold'">
                    ฿{{ fmt(opt.price) }}
                    <span v-if="Number(opt.stock_qty) <= 0" class="text-rose-400 no-underline">· หมด</span>
                  </span>
                </button>
              </div>
            </div>

            <!-- Note (optional) -->
            <div>
              <label class="text-sm font-semibold text-slate-700 mb-2 block">หมายเหตุ <span class="text-slate-400 font-normal">(ไม่บังคับ)</span></label>
              <input v-model="note" class="buy-inp w-full" placeholder="ระบุรายละเอียดเพิ่มเติม เช่น ขนาด สี..." />
            </div>

            <!-- Quantity -->
            <div>
              <label class="text-sm font-semibold text-slate-700 mb-2 block">จำนวน</label>
              <div class="flex items-center gap-3" :class="(outOfStock || needSelectOption) ? 'opacity-50 pointer-events-none' : ''">
                <button @click="dec" class="w-10 h-10 rounded-full border-2 border-slate-200 flex items-center justify-center text-slate-600 hover:border-violet-400 hover:text-violet-600 transition font-bold text-lg" :disabled="qty <= 1 || outOfStock || needSelectOption">
                  <i class="fi fi-rr-minus-small"></i>
                </button>
                <input v-model.number="qty" type="number" min="1" :max="maxQty" :disabled="outOfStock || needSelectOption"
                  class="w-16 h-10 text-center font-bold text-lg border-2 border-slate-200 rounded-xl focus:outline-none focus:border-violet-400" />
                <button @click="inc" class="w-10 h-10 rounded-full border-2 border-slate-200 flex items-center justify-center text-slate-600 hover:border-violet-400 hover:text-violet-600 transition font-bold text-lg" :disabled="qty >= maxQty || outOfStock || needSelectOption">
                  <i class="fi fi-rr-plus-small"></i>
                </button>
                <span class="text-sm text-slate-400">/ {{ maxQty }} {{ unitLabel }}</span>
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
            <button v-if="outOfStock" disabled
              class="flex-1 h-12 rounded-2xl bg-slate-200 text-slate-400 font-bold text-base cursor-not-allowed flex items-center justify-center gap-2">
              <i class="fi fi-rr-cross-circle"></i> สินค้าหมด
            </button>
            <button v-else-if="needSelectOption" disabled
              class="flex-1 h-12 rounded-2xl bg-slate-200 text-slate-400 font-bold text-base cursor-not-allowed flex items-center justify-center gap-2">
              <i class="fi fi-rr-list-check"></i> เลือกตัวเลือกก่อน
            </button>
            <button v-else @click="addCart" class="flex-1 h-12 rounded-2xl bg-orange-500 hover:bg-orange-600 text-white font-bold text-base transition shadow-lg shadow-orange-500/30 flex items-center justify-center gap-2">
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
const selectedOption = ref(null)

const imageUrl = computed(() => props.product?.primary_image_url || props.product?.images?.[0]?.url || null)
const options   = computed(() => props.product?.options || [])
const hasOptions = computed(() => options.value.length > 0)
// หน่วยนับ: ถ้ามีตัวเลือก นับเป็น "ชิ้น" (แพ็ค) เพราะน้ำหนักอยู่ในชื่อตัวเลือกแล้ว
const unitLabel = computed(() => hasOptions.value ? 'ชิ้น' : (props.product?.unit || 'ชิ้น'))

const onSale = computed(() => props.product?.sale_price != null && Number(props.product.sale_price) < Number(props.product.price))

// ราคาที่ใช้จริง: ราคาตัวเลือกที่เลือก (ถ้ามีตัวเลือก) ไม่งั้นราคาสินค้า
const effectivePrice = computed(() => {
  if (hasOptions.value) return Number(selectedOption.value?.price ?? 0)
  return Number(props.product?.effective_price ?? props.product?.sale_price ?? props.product?.price ?? 0)
})

// ป้ายราคา: ถ้ามีตัวเลือกและยังไม่เลือก → แสดงช่วงราคา
const priceLabel = computed(() => {
  if (hasOptions.value && !selectedOption.value) {
    const prices = options.value.map(o => Number(o.price))
    const min = Math.min(...prices), max = Math.max(...prices)
    return min === max ? `฿${fmt(min)}` : `฿${fmt(min)} - ฿${fmt(max)}`
  }
  return `฿${fmt(effectivePrice.value)}`
})

const stockQty = computed(() => {
  if (hasOptions.value) return Number(selectedOption.value?.stock_qty ?? 0)
  return Number(props.product?.stock_qty ?? 0)
})
// สินค้าหมด = ไม่มีตัวเลือกไหนเหลือเลย (หรือสินค้าธรรมดาสต๊อก 0)
const outOfStock = computed(() => {
  if (hasOptions.value) return options.value.every(o => Number(o.stock_qty) <= 0)
  return stockQty.value <= 0
})
const needSelectOption = computed(() => hasOptions.value && !selectedOption.value && !outOfStock.value)
const maxQty = computed(() => Math.max(1, stockQty.value))

watch(() => props.modelValue, v => { if (v) { qty.value = 1; note.value = ''; selectedOption.value = null } })
watch(qty, v => {
  if (Number.isNaN(v) || v < 1) qty.value = 1
  else if (v > maxQty.value) qty.value = maxQty.value
})

function fmt(v) { return Number(v || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function dec() { if (qty.value > 1) qty.value-- }
function inc() { if (qty.value < maxQty.value) qty.value++ }

function selectOption(opt) {
  if (Number(opt.stock_qty) <= 0) return
  selectedOption.value = opt
  qty.value = 1
}

function addCart() {
  if (outOfStock.value) return
  if (hasOptions.value && !selectedOption.value) return
  cart.add(props.product, qty.value, selectedOption.value || null)
  const label = selectedOption.value ? `${props.product.name} (${selectedOption.value.name})` : props.product.name
  toast.add({ severity: 'success', summary: 'เพิ่มลงตะกร้าแล้ว', detail: `${label} ×${qty.value}`, life: 2000 })
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
