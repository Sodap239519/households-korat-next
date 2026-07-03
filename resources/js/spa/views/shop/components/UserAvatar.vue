<template>
  <div
    :class="[sizeClass, 'rounded-full overflow-hidden shrink-0 flex items-center justify-center']"
    :style="showFallback ? gradientStyle : ''"
  >
    <img v-if="src && !imgError" :src="src" :alt="name"
      class="w-full h-full object-cover"
      @error="imgError = true" />
    <span v-else class="text-white font-bold leading-none" :class="textSizeClass">
      {{ initial }}
    </span>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  avatarPath: { type: String, default: null },
  name:       { type: String, default: '' },
  size:       { type: String, default: 'md' }, // xs | sm | md | lg
})

const SIZES = {
  xs: { box: 'w-6 h-6',   text: 'text-[10px]' },
  sm: { box: 'w-8 h-8',   text: 'text-xs' },
  md: { box: 'w-10 h-10', text: 'text-sm' },
  lg: { box: 'w-16 h-16', text: 'text-2xl' },
}

const sizeClass     = computed(() => SIZES[props.size]?.box  || SIZES.md.box)
const textSizeClass = computed(() => SIZES[props.size]?.text || SIZES.md.text)

const imgError = ref(false)

const src = computed(() =>
  props.avatarPath ? `/storage/${props.avatarPath}` : null
)

// reset error เมื่อ avatar path เปลี่ยน
watch(() => props.avatarPath, () => { imgError.value = false })

const showFallback = computed(() => !src.value || imgError.value)

const initial = computed(() =>
  (props.name || '?').charAt(0).toUpperCase()
)

// deterministic gradient from name initial
const GRADIENTS = [
  'from-violet-400 to-fuchsia-500',
  'from-blue-400 to-cyan-500',
  'from-emerald-400 to-teal-500',
  'from-amber-400 to-orange-500',
  'from-rose-400 to-pink-500',
]
const gradientStyle = computed(() => {
  const idx = (props.name?.charCodeAt(0) || 0) % GRADIENTS.length
  // inline gradient since we can't use dynamic Tailwind classes
  const gradients = [
    'linear-gradient(135deg,#a78bfa,#e879f9)',
    'linear-gradient(135deg,#60a5fa,#22d3ee)',
    'linear-gradient(135deg,#34d399,#14b8a6)',
    'linear-gradient(135deg,#fbbf24,#f97316)',
    'linear-gradient(135deg,#fb7185,#ec4899)',
  ]
  return `background:${gradients[idx]}`
})
</script>
