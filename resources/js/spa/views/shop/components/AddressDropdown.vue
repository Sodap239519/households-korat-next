<template>
  <div class="relative">
    <div class="relative">
      <!-- Input — หน้าตาเหมือน inp ปกติทุกอย่าง -->
      <input
        ref="inputRef"
        v-model="query"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
        @keydown.down.prevent="moveDown"
        @keydown.up.prevent="moveUp"
        @keydown.enter.prevent="selectHighlighted"
        @keydown.escape="close"
        :placeholder="disabled ? '— เลือก' + (placeholder.replace('เลือก','').trim() ? ' ' + placeholder.replace('เลือก','').trim() : '') + ' ก่อน —' : placeholder"
        :disabled="disabled"
        class="inp w-full pr-8"
        :class="disabled ? 'opacity-50 cursor-not-allowed bg-slate-50' : ''"
        autocomplete="off"
      />

      <!-- Right icon: spinner / clear / chevron -->
      <span v-if="loading" class="absolute right-3 top-1/2 -translate-y-1/2 text-violet-400 text-xs pointer-events-none">
        <i class="fi fi-rr-spinner animate-spin"></i>
      </span>
      <button v-else-if="modelValue && !disabled" type="button"
        @mousedown.prevent="onClear"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-rose-400 transition text-sm">
        <i class="fi fi-rr-cross-small"></i>
      </button>
      <span v-else class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none">
        <i class="fi fi-rr-angle-down"></i>
      </span>
    </div>

    <!-- Dropdown list -->
    <div v-if="open && options.length"
      class="absolute z-50 left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden max-h-52 overflow-y-auto"
      @mousedown.prevent>
      <button
        v-for="(opt, i) in options" :key="i"
        type="button"
        @click="select(opt)"
        class="w-full text-left px-4 py-2.5 text-sm hover:bg-violet-50 transition border-b border-slate-50 last:border-0"
        :class="highlighted === i ? 'bg-violet-50 text-violet-700 font-medium' : 'text-slate-700'">
        {{ opt[displayKey] }}
      </button>
    </div>

    <!-- ไม่พบผล -->
    <div v-else-if="open && searched && !loading && !options.length"
      class="absolute z-50 left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow px-4 py-3 text-sm text-slate-400"
      @mousedown.prevent>
      ไม่พบข้อมูล
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  options:    { type: Array,  default: () => [] },
  loading:    { type: Boolean, default: false },
  disabled:   { type: Boolean, default: false },
  placeholder:{ type: String, default: 'เลือก' },
  displayKey: { type: String, default: 'name' },
})

const emit = defineEmits(['update:modelValue', 'search', 'select', 'clear'])

const inputRef   = ref(null)
const query      = ref(props.modelValue || '')
const open       = ref(false)
const searched   = ref(false)
const highlighted = ref(-1)
let timer = null

// เมื่อ parent เปลี่ยน value (เช่น clear cascade) → sync กลับ
watch(() => props.modelValue, v => {
  if (v !== query.value) query.value = v || ''
})

// เมื่อ options มาใหม่ → เปิด dropdown
watch(() => props.options, v => {
  searched.value = true
  if (v.length) open.value = true
})

function onInput() {
  emit('update:modelValue', query.value)
  highlighted.value = -1
  searched.value = false
  clearTimeout(timer)
  if (!query.value.trim()) { open.value = false; return }
  timer = setTimeout(() => emit('search', query.value), 280)
}

function onFocus() {
  if (props.disabled) return
  // เปิด dropdown ให้แสดงทันทีถ้ามีข้อมูลอยู่แล้ว หรือ search ว่าง
  if (props.options.length) { open.value = true; return }
  if (!query.value.trim()) emit('search', '')
}

function onBlur() {
  setTimeout(() => {
    open.value = false
    // ถ้าพิมพ์แต่ไม่ได้เลือก → คืนค่าเดิม
    if (query.value !== props.modelValue) query.value = props.modelValue || ''
  }, 150)
}

function select(opt) {
  query.value = opt[props.displayKey]
  open.value  = false
  highlighted.value = -1
  emit('update:modelValue', query.value)
  emit('select', opt)
}

function onClear() {
  query.value = ''
  open.value  = false
  searched.value = false
  emit('update:modelValue', '')
  emit('clear')
}

function close() { open.value = false }

function moveDown() {
  if (!props.options.length) return
  highlighted.value = (highlighted.value + 1) % props.options.length
}
function moveUp() {
  if (!props.options.length) return
  highlighted.value = highlighted.value <= 0 ? props.options.length - 1 : highlighted.value - 1
}
function selectHighlighted() {
  if (highlighted.value >= 0 && props.options[highlighted.value]) select(props.options[highlighted.value])
}
</script>

<style scoped>
.inp {
  height: 2.75rem;
  padding: 0 0.75rem;
  border-radius: 0.75rem;
  border: 1px solid rgb(226 232 240);
  background: white;
  width: 100%;
  font-size: 0.875rem;
  color: rgb(30 41 59);
  transition: border-color 0.15s;
}
.inp:focus { outline: none; border-color: rgb(167 139 250); }
.inp:disabled { background: rgb(248 250 252); cursor: not-allowed; }
</style>
