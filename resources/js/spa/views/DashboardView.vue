<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">แดชบอร์ด</h2>

    <div v-if="loading" class="text-gray-500">กำลังโหลด...</div>

    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <StatCard label="ครัวเรือนทั้งหมด" :value="stats.total_households" icon="🏠" color="blue" />
      <StatCard label="โควต้าทั้งหมด" :value="stats.total_quotas" icon="📋" color="green" />
      <StatCard label="การจัดสรร" :value="stats.total_allocations" icon="🌱" color="yellow" />
      <StatCard label="ติดตามผล" :value="stats.total_followups" icon="📝" color="purple" />
    </div>

    <div v-if="!loading" class="grid grid-cols-2 md:grid-cols-3 gap-4">
      <StatCard label="โควต้ารวม (ถุง)" :value="stats.total_bags_quota?.toLocaleString()" icon="🛍️" color="green" />
      <StatCard label="จัดสรรแล้ว (ถุง)" :value="stats.total_bags_allocated?.toLocaleString()" icon="✅" color="teal" />
      <StatCard label="ผลผลิตรวม (กก.)" :value="stats.total_harvest_kg?.toFixed(2)" icon="🍄" color="orange" />
      <StatCard label="ขายได้รวม (กก.)" :value="stats.total_sold_kg?.toFixed(2)" icon="🛒" color="red" />
      <StatCard label="รายได้รวม (บาท)" :value="stats.total_revenue?.toLocaleString('th-TH', {minimumFractionDigits:2})" icon="💰" color="emerald" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, defineComponent, h } from 'vue'
import api from '../api/index.js'

const stats = ref({})
const loading = ref(true)

const colorMap = {
  blue:    'bg-blue-50 border-blue-200 text-blue-700',
  green:   'bg-green-50 border-green-200 text-green-700',
  yellow:  'bg-yellow-50 border-yellow-200 text-yellow-700',
  purple:  'bg-purple-50 border-purple-200 text-purple-700',
  teal:    'bg-teal-50 border-teal-200 text-teal-700',
  orange:  'bg-orange-50 border-orange-200 text-orange-700',
  red:     'bg-red-50 border-red-200 text-red-700',
  emerald: 'bg-emerald-50 border-emerald-200 text-emerald-700',
}

const StatCard = defineComponent({
  props: ['label', 'value', 'icon', 'color'],
  setup(props) {
    return () => h('div', {
      class: `border rounded-xl p-4 ${colorMap[props.color] || colorMap.blue}`,
    }, [
      h('div', { class: 'text-2xl mb-1' }, props.icon),
      h('div', { class: 'text-2xl font-bold' }, props.value ?? '-'),
      h('div', { class: 'text-xs mt-1 opacity-70' }, props.label),
    ])
  },
})

onMounted(async () => {
  try {
    const { data } = await api.get('/reports/dashboard')
    stats.value = data
  } finally {
    loading.value = false
  }
})
</script>
