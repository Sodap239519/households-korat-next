<template>
  <div class="p-6 max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ isEdit ? 'แก้ไขการจัดสรร' : 'เพิ่มการจัดสรร' }}</h2>

    <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ error }}</div>

    <form @submit.prevent="handleSubmit" class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="form-label">โควต้าอำเภอ *</label>
          <select v-model.number="form.quota_id" required class="input-field w-full">
            <option value="">-- เลือกโควต้า --</option>
            <option v-for="q in quotas" :key="q.id" :value="q.id">
              {{ q.district }} ปี {{ q.year }} รอบ {{ q.round }} (คงเหลือ {{ remainingBags(q) }} ถุง)
            </option>
          </select>
        </div>
        <div class="col-span-2">
          <label class="form-label">ครัวเรือน *</label>
          <select v-model.number="form.household_id" required class="input-field w-full">
            <option value="">-- เลือกครัวเรือน --</option>
            <option v-for="h in households" :key="h.id" :value="h.id">
              {{ h.household_code }} – {{ h.first_name }} {{ h.last_name }} ({{ h.district }})
            </option>
          </select>
        </div>
        <div>
          <label class="form-label">จำนวนถุง *</label>
          <input v-model.number="form.bags" type="number" min="1" required class="input-field w-full" />
        </div>
        <div>
          <label class="form-label">วันที่จัดสรร</label>
          <input v-model="form.allocated_date" type="date" class="input-field w-full" />
        </div>
        <div>
          <label class="form-label">สถานะ</label>
          <select v-model="form.status" class="input-field w-full">
            <option value="pending">รอดำเนินการ</option>
            <option value="active">กำลังดำเนินการ</option>
            <option value="completed">เสร็จสิ้น</option>
          </select>
        </div>
      </div>
      <div>
        <label class="form-label">หมายเหตุ</label>
        <textarea v-model="form.note" rows="2" class="input-field w-full"></textarea>
      </div>

      <div class="flex gap-3">
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}
        </button>
        <router-link to="/app/allocations" class="btn-secondary">ยกเลิก</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/index.js'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const error = ref('')
const saving = ref(false)

const form = ref({
  quota_id: '',
  household_id: '',
  bags: null,
  allocated_date: '',
  status: 'pending',
  note: '',
})

const quotas = ref([])
const households = ref([])

function remainingBags(q) {
  return q.quota_bags - (q.allocations_sum_bags ?? 0)
}

async function loadSelects() {
  const [qRes, hRes] = await Promise.all([
    api.get('/mushroom-quotas', { params: { per_page: 200, active: 1 } }),
    api.get('/households', { params: { per_page: 500 } }),
  ])
  quotas.value = qRes.data.data
  households.value = hRes.data.data
}

async function fetchAllocation() {
  const { data } = await api.get(`/mushroom-allocations/${route.params.id}`)
  form.value = {
    quota_id: data.quota_id,
    household_id: data.household_id,
    bags: data.bags,
    allocated_date: data.allocated_date ?? '',
    status: data.status,
    note: data.note ?? '',
  }
}

async function handleSubmit() {
  saving.value = true
  error.value = ''
  try {
    if (isEdit.value) {
      await api.put(`/mushroom-allocations/${route.params.id}`, form.value)
    } else {
      await api.post('/mushroom-allocations', form.value)
    }
    router.push('/app/allocations')
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(', ') : e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadSelects()
  if (isEdit.value) await fetchAllocation()
})
</script>
