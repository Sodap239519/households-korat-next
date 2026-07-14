<template>
  <div class="max-w-2xl mx-auto px-4 py-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-time-past text-violet-500"></i> ประวัติการเข้าชมสินค้า
      </h2>
      <button v-if="history.length" @click="onClear"
        class="text-xs text-rose-500 hover:text-rose-700 flex items-center gap-1.5 transition">
        <i class="fi fi-rr-trash text-[11px]"></i> ล้างประวัติ
      </button>
    </div>

    <div v-if="!history.length" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-time-past text-4xl"></i>
      <p class="mt-3 text-sm">ยังไม่มีประวัติการเข้าชม</p>
      <RouterLink to="/shop/products" class="text-violet-600 hover:underline mt-2 inline-block text-sm">เลือกดูสินค้า</RouterLink>
    </div>

    <div v-else class="box-card overflow-hidden divide-y divide-slate-100">
      <RouterLink v-for="item in history" :key="item.id"
        :to="`/shop/products/${item.slug}`"
        class="flex items-center gap-3 p-3 hover:bg-violet-50/40 transition">
        <!-- รูปสินค้า -->
        <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-100 shrink-0 flex items-center justify-center relative">
          <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
          <i v-else class="fi fi-rr-picture text-slate-300 text-xl"></i>
          <!-- badge ส่วนลด -->
          <span v-if="item.original_price > item.price"
            class="absolute top-0.5 left-0.5 text-[9px] font-bold bg-rose-500 text-white rounded px-1 py-0.5 leading-none">
            -{{ Math.round((1 - item.price / item.original_price) * 100) }}%
          </span>
        </div>
        <!-- ข้อมูล -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-slate-800 line-clamp-2 leading-snug">{{ item.name }}</p>
          <div class="flex items-center gap-2 mt-1 flex-wrap">
            <span class="text-fuchsia-700 font-semibold text-sm">฿{{ fmt(item.price) }}</span>
            <span v-if="item.original_price > item.price"
              class="text-xs text-slate-400 line-through font-normal">฿{{ fmt(item.original_price) }}</span>
            <span v-if="item.seller_name" class="text-[11px] text-slate-400">{{ item.seller_name }}</span>
          </div>
          <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
            <i class="fi fi-rr-clock text-[10px]"></i> {{ timeAgo(item.viewed_at) }}
          </p>
        </div>
        <i class="fi fi-rr-angle-small-right text-slate-300 shrink-0"></i>
      </RouterLink>
    </div>
  </div>

  <ConfirmDialog />
</template>

<script setup>
import { useProductHistory } from '../../composables/useProductHistory.js'
import { useConfirm } from 'primevue/useconfirm'
import ConfirmDialog from 'primevue/confirmdialog'

const { history, clearHistory } = useProductHistory()
const confirm = useConfirm()

function fmt(v) {
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

function timeAgo(ts) {
  const diff = Math.floor((Date.now() - ts) / 1000)
  if (diff < 60)    return 'เมื่อกี้'
  if (diff < 3600)  return `${Math.floor(diff / 60)} นาทีที่แล้ว`
  if (diff < 86400) return `${Math.floor(diff / 3600)} ชั่วโมงที่แล้ว`
  return `${Math.floor(diff / 86400)} วันที่แล้ว`
}

function onClear() {
  confirm.require({
    message: 'ล้างประวัติการเข้าชมทั้งหมด?',
    header: 'ยืนยัน',
    icon: 'fi fi-rr-exclamation',
    acceptLabel: 'ล้าง',
    rejectLabel: 'ยกเลิก',
    acceptClass: 'p-button-danger',
    accept: clearHistory,
  })
}
</script>
