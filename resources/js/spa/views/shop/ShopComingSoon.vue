<template>
  <div class="max-w-2xl mx-auto px-4 py-20 text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white text-3xl shadow-lg shadow-violet-500/30 mb-5">
      <i class="fi fi-rr-time-fast"></i>
    </div>
    <h2 class="text-2xl font-bold text-slate-800">{{ title }}</h2>
    <p class="text-slate-500 mt-3">ส่วนนี้กำลังพัฒนา จะเปิดให้ใช้งานเร็วๆ นี้</p>
    <RouterLink to="/shop/products" class="inline-block mt-6 px-5 py-2.5 rounded-full bg-violet-600 text-white font-semibold hover:bg-violet-700">
      เลือกซื้อสินค้าต่อ
    </RouterLink>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
const route = useRoute()
const title = computed(() => route.meta?.title || 'กำลังพัฒนา')
</script>
