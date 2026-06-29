<template>
  <div class="space-y-4">
    <!-- Language toggle -->
    <div class="flex justify-end">
      <button
        @click="toggleLocale"
        class="flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-semibold transition"
        :class="locale === 'en' ? 'border-orange-300 bg-orange-50 text-orange-600' : 'border-violet-200 bg-violet-50 text-violet-700'"
      >
        <i class="fi fi-rr-globe text-[11px]"></i>
        {{ locale === 'th' ? 'TH · เปลี่ยนเป็น EN' : 'EN · Switch to TH' }}
      </button>
    </div>

    <!-- หมวดหมู่ -->
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-violet-700 mb-2 flex items-center gap-2">
        <i class="fi fi-rr-apps"></i> {{ t('หมวดหมู่') }}
      </p>
      <ul class="space-y-1 text-sm">
        <li>
          <button
            class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-violet-50"
            :class="!filters.category ? 'bg-violet-100 text-violet-700 font-medium' : 'text-slate-600'"
            @click="onSet('category', null)"
          >{{ t('ทั้งหมด') }}</button>
        </li>
        <li v-for="cat in categories" :key="cat.id">
          <button
            class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-violet-50"
            :class="filters.category === cat.slug ? 'bg-violet-100 text-violet-700 font-medium' : 'text-slate-600'"
            @click="onSet('category', cat.slug)"
          >
            <span>{{ cat.name }}</span>
            <span class="text-xs text-slate-400">{{ cat.products_count }}</span>
          </button>
        </li>
      </ul>
    </div>

    <!-- กลุ่มผู้ขาย -->
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-fuchsia-700 mb-2 flex items-center gap-2">
        <i class="fi fi-rr-shop"></i> {{ t('กลุ่มผู้ขาย') }}
      </p>
      <ul class="space-y-1 text-sm">
        <li>
          <button
            class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-violet-50"
            :class="!filters.group ? 'bg-violet-100 text-violet-700 font-medium' : 'text-slate-600'"
            @click="onSet('group', null)"
          >{{ t('ทุกกลุ่ม') }}</button>
        </li>
        <li v-for="g in groups" :key="g.id">
          <button
            class="w-full text-left px-2 py-1.5 rounded-lg hover:bg-violet-50 truncate"
            :class="filters.group === g.slug ? 'bg-violet-100 text-violet-700 font-medium' : 'text-slate-600'"
            @click="onSet('group', g.slug)"
          >{{ g.name }}</button>
        </li>
      </ul>
    </div>

    <!-- ช่วงราคา -->
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
        <i class="fi fi-rr-coins"></i> {{ t('ช่วงราคา') }}
      </p>
      <div class="flex items-center gap-2">
        <input
          v-model.number="filters.min_price"
          type="number" min="0"
          :placeholder="t('ต่ำสุด')"
          class="w-full h-9 px-2 rounded-lg border border-slate-200 text-sm"
        />
        <span class="text-slate-400">-</span>
        <input
          v-model.number="filters.max_price"
          type="number" min="0"
          :placeholder="t('สูงสุด')"
          class="w-full h-9 px-2 rounded-lg border border-slate-200 text-sm"
        />
      </div>
      <button class="btn-orange mt-2 w-full h-9 rounded-lg text-sm font-medium" @click="onApply">
        {{ t('กรองราคา') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useLocale } from '../../../composables/useLocale.js'

const { locale, t, toggleLocale } = useLocale()

defineProps({
  categories: { type: Array, default: () => [] },
  groups:     { type: Array, default: () => [] },
  filters:    { type: Object, required: true },
  onSet:      { type: Function, required: true },
  onApply:    { type: Function, required: true },
})
</script>
