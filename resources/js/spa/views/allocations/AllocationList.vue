<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">การจัดสรรถุงเห็ด</h2>
      <router-link to="/app/allocations/create" class="btn-primary">+ เพิ่มการจัดสรร</router-link>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="px-4 py-3 text-left text-gray-600">ครัวเรือน</th>
            <th class="px-4 py-3 text-left text-gray-600">โควต้า (อำเภอ/ปี/รอบ)</th>
            <th class="px-4 py-3 text-right text-gray-600">จำนวนถุง</th>
            <th class="px-4 py-3 text-left text-gray-600">วันที่จัดสรร</th>
            <th class="px-4 py-3 text-center text-gray-600">สถานะ</th>
            <th class="px-4 py-3 text-center text-gray-600">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="6" class="py-8 text-center text-gray-400">กำลังโหลด...</td></tr>
          <tr v-else-if="!items.length"><td colspan="6" class="py-8 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
          <tr v-for="item in items" :key="item.id" class="border-b hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="font-medium">{{ item.household?.first_name }} {{ item.household?.last_name }}</div>
              <div class="text-xs text-gray-400">{{ item.household?.household_code }}</div>
            </td>
            <td class="px-4 py-3">{{ item.quota?.district }} / {{ item.quota?.year }} / รอบ {{ item.quota?.round }}</td>
            <td class="px-4 py-3 text-right font-medium">{{ item.bags }}</td>
            <td class="px-4 py-3">{{ item.allocated_date ?? '-' }}</td>
            <td class="px-4 py-3 text-center">
              <span :class="statusBadge(item.status)">{{ statusLabel(item.status) }}</span>
            </td>
            <td class="px-4 py-3 text-center space-x-2">
              <router-link :to="`/app/allocations/${item.id}/edit`" class="text-blue-600 hover:underline">แก้ไข</router-link>
              <button @click="deleteItem(item)" class="text-red-500 hover:underline">ลบ</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
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
let currentPage = 1

const statusMap = {
  pending: { label: 'รอดำเนินการ', cls: 'badge-yellow' },
  active: { label: 'กำลังดำเนินการ', cls: 'badge-green' },
  completed: { label: 'เสร็จสิ้น', cls: 'badge-gray' },
}
function statusLabel(s) { return statusMap[s]?.label ?? s }
function statusBadge(s) { return statusMap[s]?.cls ?? 'badge-gray' }

async function fetchData() {
  loading.value = true
  try {
    const { data } = await api.get('/mushroom-allocations', { params: { page: currentPage, per_page: 20 } })
    items.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

function onPage(page) { currentPage = page; fetchData() }

async function deleteItem(item) {
  if (!confirm('ลบการจัดสรรนี้?')) return
  try {
    await api.delete(`/mushroom-allocations/${item.id}`)
    fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'เกิดข้อผิดพลาด')
  }
}

onMounted(fetchData)
</script>
