<template>
  <div class="space-y-4">
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-violet-700 mb-2 flex items-center gap-2"><i class="fi fi-rr-apps"></i> หมวดหมู่</p>
      <ul class="space-y-1 text-sm">
        <li>
          <button class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-violet-50" :class="!filters.category ? 'bg-violet-100 text-violet-700 font-medium' : 'text-slate-600'" @click="onSet('category', null)">ทั้งหมด</button>
        </li>
        <li v-for="cat in categories" :key="cat.id">
          <button class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-violet-50" :class="filters.category === cat.slug ? 'bg-violet-100 text-violet-700 font-medium' : 'text-slate-600'" @click="onSet('category', cat.slug)">
            <span>{{ cat.name }}</span>
            <span class="text-xs text-slate-400">{{ cat.products_count }}</span>
          </button>
        </li>
      </ul>
    </div>

    <div class="box-card p-4">
      <p class="text-sm font-semibold text-fuchsia-700 mb-2 flex items-center gap-2"><i class="fi fi-rr-shop"></i> กลุ่มผู้ขาย</p>
      <ul class="space-y-1 text-sm">
        <li>
          <button class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-violet-50" :class="!filters.group ? 'bg-violet-100 text-violet-700 font-medium' : 'text-slate-600'" @click="onSet('group', null)">ทุกกลุ่ม</button>
        </li>
        <li v-for="g in groups" :key="g.id">
          <button class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-violet-50 truncate" :class="filters.group === g.slug ? 'bg-violet-100 text-violet-700 font-medium' : 'text-slate-600'" @click="onSet('group', g.slug)">{{ g.name }}</button>
        </li>
      </ul>
    </div>

    <div class="box-card p-4">
      <p class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2"><i class="fi fi-rr-coins"></i> ช่วงราคา</p>
      <div class="flex items-center gap-2">
        <input v-model.number="filters.min_price" type="number" min="0" placeholder="ต่ำสุด" class="w-full h-9 px-2 rounded-lg border border-slate-200 text-sm" />
        <span class="text-slate-400">-</span>
        <input v-model.number="filters.max_price" type="number" min="0" placeholder="สูงสุด" class="w-full h-9 px-2 rounded-lg border border-slate-200 text-sm" />
      </div>
      <button class="btn-orange mt-2 w-full h-9 rounded-lg text-sm font-medium" @click="onApply">กรองราคา</button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  categories: { type: Array, default: () => [] },
  groups: { type: Array, default: () => [] },
  filters: { type: Object, required: true },
  onSet: { type: Function, required: true },
  onApply: { type: Function, required: true },
})
</script>
