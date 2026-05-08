<template>
  <fieldset :class="['rounded-xl border-2 bg-white/70 backdrop-blur-sm p-5 transition', borderClass]">
    <legend :class="['px-3 text-sm font-semibold flex items-center gap-1.5 rounded-md', tagClass]">
      <i v-if="icon" :class="icon"></i>
      <span>{{ title }}</span>
    </legend>
    <slot />
  </fieldset>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  icon:  { type: String, default: '' },
  tone:  { type: String, default: 'violet' },
})

const TONES = {
  violet:  { border: 'border-violet-300/70', tag: 'bg-violet-100 text-violet-700' },
  fuchsia: { border: 'border-fuchsia-300/70', tag: 'bg-fuchsia-100 text-fuchsia-700' },
  emerald: { border: 'border-emerald-300/70', tag: 'bg-emerald-100 text-emerald-700' },
  amber:   { border: 'border-amber-300/70', tag: 'bg-amber-100 text-amber-700' },
  sky:     { border: 'border-sky-300/70', tag: 'bg-sky-100 text-sky-700' },
  rose:    { border: 'border-rose-300/70', tag: 'bg-rose-100 text-rose-700' },
}

const borderClass = computed(() => (TONES[props.tone] || TONES.violet).border)
const tagClass    = computed(() => (TONES[props.tone] || TONES.violet).tag)
</script>
