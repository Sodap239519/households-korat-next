<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">รายงาน</h2>

    <!-- Tab navigation -->
    <div class="flex gap-2 mb-6 border-b border-gray-200">
      <button
        v-for="tab in tabs" :key="tab.key"
        @click="activeTab = tab.key"
        class="px-4 py-2 text-sm font-medium border-b-2 transition"
        :class="activeTab === tab.key ? 'border-green-600 text-green-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- By District -->
    <div v-if="activeTab === 'district'">
      <div v-if="loadingDistrict" class="text-gray-400">กำลังโหลด...</div>
      <table v-else class="w-full text-sm bg-white rounded-xl shadow-sm border overflow-hidden">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="px-4 py-3 text-left text-gray-600">อำเภอ</th>
            <th class="px-4 py-3 text-right text-gray-600">ครัวเรือน</th>
            <th class="px-4 py-3 text-right text-gray-600">ถุงที่จัดสรร</th>
            <th class="px-4 py-3 text-right text-gray-600">ผลผลิต (กก.)</th>
            <th class="px-4 py-3 text-right text-gray-600">ขาย (กก.)</th>
            <th class="px-4 py-3 text-right text-gray-600">รายได้ (บาท)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in districtData" :key="r.district" class="border-b hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">{{ r.district }}</td>
            <td class="px-4 py-3 text-right">{{ r.participating_households }}</td>
            <td class="px-4 py-3 text-right">{{ r.total_allocated_bags }}</td>
            <td class="px-4 py-3 text-right">{{ r.total_harvest_kg }}</td>
            <td class="px-4 py-3 text-right">{{ r.total_sold_kg }}</td>
            <td class="px-4 py-3 text-right font-semibold text-green-700">{{ Number(r.total_revenue).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Quota vs Allocated -->
    <div v-if="activeTab === 'quota'">
      <div v-if="loadingQuota" class="text-gray-400">กำลังโหลด...</div>
      <table v-else class="w-full text-sm bg-white rounded-xl shadow-sm border overflow-hidden">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="px-4 py-3 text-left text-gray-600">อำเภอ</th>
            <th class="px-4 py-3 text-center text-gray-600">ปี/รอบ</th>
            <th class="px-4 py-3 text-right text-gray-600">โควต้า</th>
            <th class="px-4 py-3 text-right text-gray-600">จัดสรร</th>
            <th class="px-4 py-3 text-right text-gray-600">คงเหลือ</th>
            <th class="px-4 py-3 text-right text-gray-600">%จัดสรร</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in quotaData" :key="r.quota_id" class="border-b hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">{{ r.district }}</td>
            <td class="px-4 py-3 text-center">{{ r.year }} / รอบ {{ r.round }}</td>
            <td class="px-4 py-3 text-right">{{ r.quota_bags }}</td>
            <td class="px-4 py-3 text-right">{{ r.total_allocated }}</td>
            <td class="px-4 py-3 text-right" :class="r.remaining < 0 ? 'text-red-600 font-bold' : ''">{{ r.remaining }}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <div class="w-16 bg-gray-200 rounded-full h-2">
                  <div class="bg-green-500 h-2 rounded-full" :style="{ width: Math.min(r.pct_allocated, 100) + '%' }"></div>
                </div>
                <span>{{ r.pct_allocated }}%</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Enterprise -->
    <div v-if="activeTab === 'enterprise'">
      <div v-if="loadingEnterprise" class="text-gray-400">กำลังโหลด...</div>
      <div v-else-if="!enterpriseData.length" class="text-gray-400">ไม่มีข้อมูลวิสาหกิจ</div>
      <table v-else class="w-full text-sm bg-white rounded-xl shadow-sm border overflow-hidden">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="px-4 py-3 text-left text-gray-600">วิสาหกิจ</th>
            <th class="px-4 py-3 text-right text-gray-600">ครัวเรือน</th>
            <th class="px-4 py-3 text-right text-gray-600">ขาย (กก.)</th>
            <th class="px-4 py-3 text-right text-gray-600">รายได้ (บาท)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in enterpriseData" :key="r.enterprise_name" class="border-b hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">{{ r.enterprise_name }}</td>
            <td class="px-4 py-3 text-right">{{ r.households_count }}</td>
            <td class="px-4 py-3 text-right">{{ r.total_sold_kg }}</td>
            <td class="px-4 py-3 text-right font-semibold text-green-700">{{ Number(r.total_revenue).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../../api/index.js'

const tabs = [
  { key: 'district', label: 'รายได้รายอำเภอ' },
  { key: 'quota', label: 'โควต้า vs จัดสรร' },
  { key: 'enterprise', label: 'วิสาหกิจ' },
]
const activeTab = ref('district')

const districtData = ref([])
const quotaData = ref([])
const enterpriseData = ref([])
const loadingDistrict = ref(false)
const loadingQuota = ref(false)
const loadingEnterprise = ref(false)

async function loadDistrict() {
  loadingDistrict.value = true
  const { data } = await api.get('/reports/by-district')
  districtData.value = data
  loadingDistrict.value = false
}

async function loadQuota() {
  loadingQuota.value = true
  const { data } = await api.get('/reports/quota-vs-allocated')
  quotaData.value = data
  loadingQuota.value = false
}

async function loadEnterprise() {
  loadingEnterprise.value = true
  const { data } = await api.get('/reports/by-enterprise')
  enterpriseData.value = data
  loadingEnterprise.value = false
}

watch(activeTab, (tab) => {
  if (tab === 'district' && !districtData.value.length) loadDistrict()
  if (tab === 'quota' && !quotaData.value.length) loadQuota()
  if (tab === 'enterprise' && !enterpriseData.value.length) loadEnterprise()
})

onMounted(loadDistrict)
</script>
