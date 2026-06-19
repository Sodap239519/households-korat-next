<template>
  <nav class="flex items-center flex-wrap gap-1.5 text-sm text-slate-500">
    <RouterLink to="/shop" class="hover:text-violet-700"><i class="fi fi-rr-home"></i></RouterLink>
    <template v-for="(item, i) in items" :key="i">
      <i class="fi fi-rr-angle-small-right text-slate-300"></i>
      <RouterLink v-if="item.to && i < items.length - 1" :to="item.to" class="hover:text-violet-700">{{ item.label }}</RouterLink>
      <span v-else class="text-slate-700 font-medium">{{ item.label }}</span>
    </template>
  </nav>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] }, // [{ label, to? }]
})
</script>
