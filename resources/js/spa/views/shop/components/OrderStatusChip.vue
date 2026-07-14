<template>
  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border" :class="cfg.cls">
    <i :class="cfg.icon"></i> {{ cfg.label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ status: { type: String, default: '' } })

const MAP = {
  pending_payment:  { label: 'รอชำระเงิน',   icon: 'fi fi-rr-clock',         cls: 'bg-amber-50 text-amber-700 border-amber-300' },
  awaiting_confirm: { label: 'รอยืนยันสลิป',  icon: 'fi fi-rr-receipt',       cls: 'bg-sky-50 text-sky-700 border-sky-300' },
  confirmed:        { label: 'ยืนยันแล้ว',     icon: 'fi fi-rr-check',         cls: 'bg-violet-50 text-violet-700 border-violet-300' },
  processing:       { label: 'กำลังเตรียม',    icon: 'fi fi-rr-box',           cls: 'bg-violet-50 text-violet-700 border-violet-300' },
  shipped:          { label: 'จัดส่งแล้ว',     icon: 'fi fi-rr-truck-side',    cls: 'bg-indigo-50 text-indigo-700 border-indigo-300' },
  delivered:        { label: 'ได้รับสินค้า',   icon: 'fi fi-rr-box-check',     cls: 'bg-emerald-50 text-emerald-700 border-emerald-300' },
  completed:        { label: 'สำเร็จ',         icon: 'fi fi-rr-badge-check',   cls: 'bg-emerald-50 text-emerald-700 border-emerald-300' },
  cancelled:        { label: 'ยกเลิก',         icon: 'fi fi-rr-cross-circle',  cls: 'bg-slate-100 text-slate-500 border-slate-300' },
  refund_requested: { label: 'ขอคืน/เคลม',     icon: 'fi fi-rr-undo',          cls: 'bg-rose-50 text-rose-700 border-rose-300' },
  refunded:         { label: 'คืนเงินแล้ว',    icon: 'fi fi-rr-rotate-left',   cls: 'bg-rose-50 text-rose-700 border-rose-300' },
}

const cfg = computed(() => MAP[props.status] || { label: props.status, icon: 'fi fi-rr-info', cls: 'bg-slate-100 text-slate-500 border-slate-300' })
</script>
