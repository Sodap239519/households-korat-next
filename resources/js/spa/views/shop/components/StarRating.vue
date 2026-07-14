<template>
  <span class="inline-flex items-center gap-1" :title="`${Number(rating).toFixed(1)} จาก 5`">
    <span class="inline-flex text-amber-400" :class="sizeClass">
      <i v-for="n in 5" :key="n" :class="n <= Math.round(rating) ? 'fi fi-sr-star' : 'fi fi-rr-star text-slate-300'"></i>
    </span>
    <span v-if="showCount" class="text-xs text-slate-400">({{ count }})</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  rating: { type: [Number, String], default: 0 },
  count: { type: [Number, String], default: 0 },
  showCount: { type: Boolean, default: true },
  size: { type: String, default: 'sm' }, // sm | md | lg
})

const sizeClass = computed(() => ({
  sm: 'text-xs',
  md: 'text-sm',
  lg: 'text-lg',
}[props.size] || 'text-xs'))
</script>
