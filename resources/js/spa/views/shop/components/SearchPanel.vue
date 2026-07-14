<template>
  <div>
    <!-- Popular tags -->
    <div class="mb-3">
      <p class="text-xs text-slate-400 font-medium mb-2">ค้นหาที่นิยม</p>
      <div class="flex flex-wrap gap-1.5">
        <button
          v-for="tag in popular"
          :key="tag"
          @mousedown.prevent="$emit('go', tag)"
          class="px-3 py-1 rounded-full text-xs bg-violet-50 text-violet-700 hover:bg-violet-100 transition font-medium"
        >
          <i class="fi fi-rr-fire text-orange-400 text-[10px] mr-0.5"></i>{{ tag }}
        </button>
      </div>
    </div>

    <!-- Search history -->
    <template v-if="history.length">
      <div class="flex items-center justify-between mb-2">
        <p class="text-xs text-slate-400 font-medium">ค้นหาล่าสุด</p>
        <button @mousedown.prevent="$emit('clear')" class="text-xs text-slate-400 hover:text-rose-500 transition flex items-center gap-1">
          <i class="fi fi-rr-trash text-[10px]"></i> ล้างประวัติ
        </button>
      </div>
      <ul class="space-y-0.5">
        <li v-for="(term, i) in history" :key="i" class="flex items-center gap-1 group">
          <button
            @mousedown.prevent="$emit('go', term)"
            class="flex-1 flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 text-sm text-slate-600 text-left transition"
          >
            <i class="fi fi-rr-clock text-slate-300 text-xs shrink-0"></i>
            <span class="truncate">{{ term }}</span>
          </button>
          <button
            @mousedown.prevent="$emit('remove', i)"
            class="opacity-0 group-hover:opacity-100 p-1.5 text-slate-300 hover:text-slate-500 transition rounded-full hover:bg-slate-100"
          >
            <i class="fi fi-rr-cross-small text-xs"></i>
          </button>
        </li>
      </ul>
    </template>

    <div v-else-if="!history.length" class="text-xs text-slate-300 text-center py-1">
      ยังไม่มีประวัติค้นหา
    </div>
  </div>
</template>

<script setup>
defineProps({
  popular: { type: Array, default: () => [] },
  history: { type: Array, default: () => [] },
})
defineEmits(['go', 'remove', 'clear'])
</script>
