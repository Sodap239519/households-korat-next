<template>
  <div class="p-6 max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ isEdit ? 'แก้ไขโควต้า' : 'เพิ่มโควต้าอำเภอ' }}</h2>

    <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ error }}</div>

    <form @submit.prevent="handleSubmit" class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="form-label">อำเภอ *</label>
          <input v-model="form.district" required class="input-field w-full" placeholder="เช่น เมืองนครราชสีมา" />
        </div>
        <div>
          <label class="form-label">จังหวัด</label>
          <input v-model="form.province" class="input-field w-full" />
        </div>
        <div>
          <label class="form-label">ปี พ.ศ. *</label>
          <input v-model.number="form.year" type="number" min="2500" max="2600" required class="input-field w-full" />
        </div>
        <div>
          <label class="form-label">รอบ *</label>
          <input v-model.number="form.round" type="number" min="1" max="10" required class="input-field w-full" />
        </div>
        <div>
          <label class="form-label">จำนวนโควต้า (ถุง) *</label>
          <input v-model.number="form.quota_bags" type="number" min="1" required class="input-field w-full" />
        </div>
        <div class="flex items-center gap-2 pt-6">
          <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4" />
          <label for="is_active" class="text-sm text-gray-700">เปิดใช้งาน</label>
        </div>
      </div>
      <div>
        <label class="form-label">หมายเหตุ</label>
        <textarea v-model="form.note" rows="3" class="input-field w-full"></textarea>
      </div>

      <!-- Baseline info -->
      <div v-if="form.quota_bags" class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700">
        <strong>ข้อมูลพื้นฐาน:</strong>
        {{ form.quota_bags }} ถุง = {{ form.quota_bags * 2 }} ขีด = {{ (form.quota_bags * 0.2).toFixed(2) }} กก.
        | รายได้พื้นฐาน {{ (form.quota_bags * 12).toLocaleString() }} บาท
      </div>

      <div class="flex gap-3">
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}
        </button>
        <router-link to="/app/quotas" class="btn-secondary">ยกเลิก</router-link>
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
  district: '',
  province: 'นครราชสีมา',
  year: new Date().getFullYear() + 543,
  round: 1,
  quota_bags: null,
  is_active: true,
  note: '',
})

async function fetchQuota() {
  const { data } = await api.get(`/mushroom-quotas/${route.params.id}`)
  form.value = { ...data, note: data.note ?? '' }
}

async function handleSubmit() {
  saving.value = true
  error.value = ''
  try {
    if (isEdit.value) {
      await api.put(`/mushroom-quotas/${route.params.id}`, form.value)
    } else {
      await api.post('/mushroom-quotas', form.value)
    }
    router.push('/app/quotas')
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(', ') : e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (isEdit.value) fetchQuota()
})
</script>
