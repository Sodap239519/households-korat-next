<template>
  <div v-if="meta?.last_page > 1" class="flex items-center gap-2 justify-end">
    <button
      v-for="page in pages"
      :key="page"
      @click="page !== '...' && $emit('change', page)"
      :disabled="page === '...'"
      class="px-3 py-1 text-sm rounded border transition"
      :class="page === meta.current_page
        ? 'bg-green-600 text-white border-green-600'
        : page === '...' ? 'border-transparent text-gray-400 cursor-default'
        : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
    >
      {{ page }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ meta: Object })
defineEmits(['change'])

const pages = computed(() => {
  if (!props.meta?.last_page) return []
  const total = props.meta.last_page
  const current = props.meta.current_page
  const result = []
  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || Math.abs(i - current) <= 2) {
      result.push(i)
    } else if (result[result.length - 1] !== '...') {
      result.push('...')
    }
  }
  return result
})
</script>
