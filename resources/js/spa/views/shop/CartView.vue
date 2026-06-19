<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
    <Breadcrumb :items="[{ label: 'ตะกร้าสินค้า' }]" class="mb-4" />

    <div v-if="!cart.items.value.length" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-shopping-cart text-5xl"></i>
      <p class="mt-3 text-lg">ตะกร้าว่างเปล่า</p>
      <RouterLink to="/shop/products" class="inline-block mt-5 px-6 py-2.5 rounded-full bg-violet-600 text-white font-semibold hover:bg-violet-700">เลือกซื้อสินค้า</RouterLink>
    </div>

    <div v-else class="grid lg:grid-cols-3 gap-5">
      <div class="lg:col-span-2 space-y-4">
        <div v-for="g in cart.groups.value" :key="g.group_id" class="box-card p-4">
          <p class="text-sm font-semibold text-violet-700 mb-3 flex items-center gap-2 border-b border-slate-100 pb-2">
            <i class="fi fi-rr-shop"></i> {{ g.group_name || 'กลุ่มผู้ขาย' }}
          </p>
          <div v-for="item in g.items" :key="item.product_id" class="flex gap-3 py-3 border-b border-slate-50 last:border-0">
            <!-- รูปสินค้า -->
            <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden shrink-0 flex items-center justify-center">
              <img v-if="item.image" :src="item.image" class="w-full h-full object-cover" />
              <i v-else class="fi fi-rr-picture text-slate-300 text-xl"></i>
            </div>

            <!-- ข้อมูล + controls -->
            <div class="flex-1 min-w-0">
              <!-- แถว 1: ชื่อสินค้า -->
              <p class="font-medium text-slate-700 text-sm leading-snug mb-1">{{ item.name }}</p>
              <!-- แถว 2: ราคา/หน่วย -->
              <p class="text-fuchsia-700 font-semibold text-sm">
                ฿{{ fmt(item.price) }}
                <span class="text-xs text-slate-400 font-normal">/ {{ item.unit }}</span>
              </p>
              <!-- แถว 3: stepper + ยอดรวม + ลบ -->
              <div class="flex items-center gap-2 mt-2">
                <div class="flex items-center border border-slate-200 rounded-full overflow-hidden bg-white">
                  <button class="w-8 h-8 flex items-center justify-center hover:bg-slate-50 text-slate-500 active:bg-slate-100"
                    @click="cart.updateQty(item.product_id, item.qty - 1)">
                    <i class="fi fi-rr-minus-small text-base"></i>
                  </button>
                  <span class="w-7 text-center text-sm font-medium">{{ item.qty }}</span>
                  <button class="w-8 h-8 flex items-center justify-center hover:bg-slate-50 text-slate-500 active:bg-slate-100"
                    @click="cart.updateQty(item.product_id, item.qty + 1)">
                    <i class="fi fi-rr-plus-small text-base"></i>
                  </button>
                </div>
                <span class="flex-1 text-right font-bold text-slate-700 text-sm">฿{{ fmt(item.price * item.qty) }}</span>
                <button class="text-rose-400 hover:text-rose-600 p-1.5 rounded-full hover:bg-rose-50 transition"
                  @click="cart.remove(item.product_id)">
                  <i class="fi fi-rr-trash text-sm"></i>
                </button>
              </div>
            </div>
          </div>
          <p class="text-right text-sm text-slate-500 mt-2 pt-2 border-t border-slate-100">รวมกลุ่มนี้: <span class="font-semibold text-slate-700">฿{{ fmt(g.subtotal) }}</span></p>
        </div>
        <p class="text-xs text-slate-400"><i class="fi fi-rr-info"></i> สินค้าจากต่างกลุ่มจะถูกแยกเป็นคนละคำสั่งซื้อ และชำระเงินแยกกัน</p>
      </div>

      <!-- Summary -->
      <div class="lg:col-span-1">
        <div class="box-card p-5 sticky top-20">
          <h3 class="font-bold text-slate-800 mb-3">สรุปคำสั่งซื้อ</h3>
          <div class="flex justify-between text-sm text-slate-600 mb-1"><span>จำนวนสินค้า</span><span>{{ cart.count.value }} ชิ้น</span></div>
          <div class="flex justify-between text-sm text-slate-600 mb-1"><span>จำนวนกลุ่มผู้ขาย</span><span>{{ cart.groups.value.length }} กลุ่ม</span></div>
          <div class="flex justify-between items-end border-t border-slate-100 mt-3 pt-3">
            <span class="text-slate-600">ยอดรวม</span>
            <span class="text-2xl font-bold text-fuchsia-700">฿{{ fmt(cart.subtotal.value) }}</span>
          </div>
          <button class="btn-sheen w-full mt-4 h-12 rounded-full btn-orange font-semibold transition" @click="goCheckout">
            ดำเนินการสั่งซื้อ <i class="fi fi-rr-arrow-right ml-1"></i>
          </button>
          <RouterLink to="/shop/products" class="block text-center text-sm text-violet-600 hover:underline mt-3">เลือกซื้อสินค้าต่อ</RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useCart } from '../../composables/useCart.js'
import { useAuth } from '../../composables/useAuth.js'
import Breadcrumb from './components/Breadcrumb.vue'

const router = useRouter()
const cart = useCart()
const { user } = useAuth()

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }

function goCheckout() {
  if (!user.value) {
    router.push({ path: '/shop/login', query: { redirect: '/shop/checkout' } })
  } else {
    router.push('/shop/checkout')
  }
}
</script>
