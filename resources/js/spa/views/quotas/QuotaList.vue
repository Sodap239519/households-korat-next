<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">โควต้าอำเภอ</h2>
      <router-link to="/app/quotas/create" class="btn-primary">+ เพิ่มโควต้า</router-link>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border p-4 mb-4 flex gap-4 flex-wrap">
      <input v-model="filters.district" @input="fetchData" placeholder="ค้นหาอำเภอ..."
        class="input-field w-40" />
      <select v-model="filters.year" @change="fetchData" class="input-field w-32">
        <option value="">ทุกปี</option>
        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="px-4 py-3 text-left text-gray-600">อำเภอ</th>
            <th class="px-4 py-3 text-left text-gray-600">ปี/รอบ</th>
            <th class="px-4 py-3 text-right text-gray-600">โควต้า (ถุง)</th>
            <th class="px-4 py-3 text-right text-gray-600">จัดสรรแล้ว</th>
            <th class="px-4 py-3 text-right text-gray-600">คงเหลือ</th>
            <th class="px-4 py-3 text-center text-gray-600">สถานะ</th>
            <th class="px-4 py-3 text-center text-gray-600">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading" class="text-center"><td colspan="7" class="py-8 text-gray-400">กำลังโหลด...</td></tr>
          <tr v-else-if="!items.length" class="text-center"><td colspan="7" class="py-8 text-gray-400">ไม่มีข้อมูล</td></tr>
          <tr v-for="item in items" :key="item.id" class="border-b hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">{{ item.district }}</td>
            <td class="px-4 py-3">{{ item.year }} / รอบ {{ item.round }}</td>
            <td class="px-4 py-3 text-right">{{ item.quota_bags?.toLocaleString() }}</td>
            <td class="px-4 py-3 text-right">{{ item.allocations_sum_bags ?? 0 }}</td>
            <td class="px-4 py-3 text-right" :class="remaining(item) < 0 ? 'text-red-600 font-bold' : ''">
              {{ remaining(item) }}
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="item.is_active ? 'badge-green' : 'badge-gray'">
                {{ item.is_active ? 'เปิดใช้' : 'ปิด' }}
              </span>
            </td>
            <td class="px-4 py-3 text-center space-x-2">
              <router-link :to="`/app/quotas/${item.id}/edit`" class="text-blue-600 hover:underline">แก้ไข</router-link>
              <button @click="deleteItem(item)" class="text-red-500 hover:underline">ลบ</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <Pagination :meta="meta" @change="onPage" class="mt-4" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/index.js'
import Pagination from '../components/Pagination.vue'

const items = ref([])
const meta = ref({})
const loading = ref(false)
const years = ref([])
const filters = ref({ district: '', year: '' })
let currentPage = 1

function remaining(item) {
  return item.quota_bags - (item.allocations_sum_bags ?? 0)
}

async function fetchData() {
  loading.value = true
  try {
    const { data } = await api.get('/mushroom-quotas', {
      params: { ...filters.value, page: currentPage, per_page: 20 },
    })
    items.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

async function fetchYears() {
  const { data } = await api.get('/reports/years')
  years.value = data
}

function onPage(page) {
  currentPage = page
  fetchData()
}

async function deleteItem(item) {
  if (!confirm(`ลบโควต้า ${item.district} ปี ${item.year} รอบ ${item.round}?`)) return
  try {
    await api.delete(`/mushroom-quotas/${item.id}`)
    fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'เกิดข้อผิดพลาด')
  }
}

onMounted(() => {
  fetchData()
  fetchYears()
})
</script>
