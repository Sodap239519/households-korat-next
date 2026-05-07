<template>
  <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border', styles]">
    <i v-if="iconClass" :class="iconClass"></i>
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, default: 'info' },
  label:  { type: String, default: '' },
  icon:   { type: String, default: '' },
})

const STATUS_STYLES = {
  // generic
  active:    'bg-emerald-50 text-emerald-700 border-emerald-300',
  inactive:  'bg-slate-100 text-slate-600 border-slate-300',
  pending:   'bg-amber-50 text-amber-700 border-amber-300',
  completed: 'bg-sky-50 text-sky-700 border-sky-300',
  failed:    'bg-rose-50 text-rose-700 border-rose-300',
  info:      'bg-violet-50 text-violet-700 border-violet-300',
  warning:   'bg-orange-50 text-orange-700 border-orange-300',
  success:   'bg-emerald-50 text-emerald-700 border-emerald-300',
  danger:    'bg-rose-50 text-rose-700 border-rose-300',

  // sale channels
  direct:     'bg-blue-50 text-blue-700 border-blue-300',
  online:     'bg-cyan-50 text-cyan-700 border-cyan-300',
  enterprise: 'bg-violet-50 text-violet-700 border-violet-300',
  market:     'bg-orange-50 text-orange-700 border-orange-300',

  // role
  superadmin: 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-300',
  staff:      'bg-blue-50 text-blue-700 border-blue-300',

  // priority badges (also accepted as plain "A","B","C","D" in caller)
  // (already defined below)

  // priority levels (Households Korat)
  A: 'bg-slate-800 text-white border-slate-800',
  B: 'bg-sky-400 text-white border-sky-400',
  C: 'bg-amber-200 text-amber-900 border-amber-300',
  D: 'bg-pink-300 text-pink-900 border-pink-400',
}

const STATUS_ICONS = {
  active:    'fi fi-rr-check-circle',
  inactive:  'fi fi-rr-cross-circle',
  pending:   'fi fi-rr-clock',
  completed: 'fi fi-rr-shield-check',
  failed:    'fi fi-rr-cross-circle',
  warning:   'fi fi-rr-triangle-warning',

  direct:     'fi fi-rr-handshake',
  online:     'fi fi-rr-globe',
  enterprise: 'fi fi-rr-building',
  market:     'fi fi-rr-shop',

  superadmin: 'fi fi-rr-crown',
  staff:      'fi fi-rr-user',
}

const styles    = computed(() => STATUS_STYLES[props.status] || STATUS_STYLES.info)
const iconClass = computed(() => props.icon || STATUS_ICONS[props.status] || '')
</script>
