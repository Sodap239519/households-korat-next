<template>
  <div class="p-6 max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ isEdit ? 'แก้ไขการติดตามผล' : 'เพิ่มการติดตามผล' }}</h2>

    <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ error }}</div>

    <form @submit.prevent="handleSubmit" class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="form-label">การจัดสรร *</label>
          <select v-model.number="form.allocation_id" required class="input-field w-full">
            <option value="">-- เลือกการจัดสรร --</option>
            <option v-for="a in allocations" :key="a.id" :value="a.id">
              {{ a.household?.first_name }} {{ a.household?.last_name }} ({{ a.household?.household_code }}) –
              {{ a.quota?.district }} ปี {{ a.quota?.year }} รอบ {{ a.quota?.round }} | {{ a.bags }} ถุง
            </option>
          </select>
        </div>
        <div>
          <label class="form-label">รอบติดตาม *</label>
          <input v-model.number="form.followup_round" type="number" min="1" required class="input-field w-full" />
        </div>
        <div>
          <label class="form-label">วันที่ติดตาม</label>
          <input v-model="form.followup_date" type="date" class="input-field w-full" />
        </div>
        <div>
          <label class="form-label">ผลผลิต (กก.)</label>
          <input v-model.number="form.harvest_kg" type="number" step="0.001" min="0" class="input-field w-full" />
        </div>
        <div>
          <label class="form-label">ขายได้ (กก.)</label>
          <input v-model.number="form.sold_kg" type="number" step="0.001" min="0" class="input-field w-full" @input="calcRevenue" />
        </div>
        <div>
          <label class="form-label">ราคา/กก. (บาท)</label>
          <input v-model.number="form.price_per_kg" type="number" step="0.01" min="0" class="input-field w-full" @input="calcRevenue" />
        </div>
        <div>
          <label class="form-label">รายได้ (บาท)</label>
          <input v-model.number="form.revenue" type="number" step="0.01" min="0" class="input-field w-full" />
          <p class="text-xs text-gray-400 mt-0.5">คำนวณอัตโนมัติจากขาย × ราคา (แก้ไขได้)</p>
        </div>
        <div>
          <label class="form-label">ช่องทางขาย</label>
          <select v-model="form.sale_channel" class="input-field w-full">
            <option value="">-- เลือก --</option>
            <option value="direct">ขายตรง</option>
            <option value="online">ออนไลน์</option>
            <option value="enterprise">วิสาหกิจ</option>
            <option value="market">ตลาด</option>
          </select>
        </div>
        <div>
          <label class="form-label">ชื่อสถานที่/ร้าน</label>
          <input v-model="form.sale_place" class="input-field w-full" />
        </div>
        <div class="flex items-center gap-2 pt-4">
          <input v-model="form.enterprise_member" type="checkbox" id="ent_member" class="w-4 h-4" />
          <label for="ent_member" class="text-sm text-gray-700">สมาชิกวิสาหกิจ</label>
        </div>
        <div v-if="form.enterprise_member">
          <label class="form-label">ชื่อวิสาหกิจ</label>
          <input v-model="form.enterprise_name" class="input-field w-full" />
        </div>
      </div>
      <div>
        <label class="form-label">หมายเหตุ</label>
        <textarea v-model="form.note" rows="2" class="input-field w-full"></textarea>
      </div>

      <div class="flex gap-3">
        <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}</button>
        <router-link to="/app/followups" class="btn-secondary">ยกเลิก</router-link>
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
const allocations = ref([])

const form = ref({
  allocation_id: '',
  followup_round: 1,
  followup_date: '',
  harvest_kg: null,
  sold_kg: null,
  price_per_kg: null,
  revenue: null,
  sale_channel: '',
  sale_place: '',
  enterprise_member: false,
  enterprise_name: '',
  note: '',
})

function calcRevenue() {
  if (form.value.sold_kg && form.value.price_per_kg) {
    form.value.revenue = parseFloat((form.value.sold_kg * form.value.price_per_kg).toFixed(2))
  }
}

async function loadAllocations() {
  const { data } = await api.get('/mushroom-allocations', { params: { per_page: 500 } })
  allocations.value = data.data
}

async function fetchFollowup() {
  const { data } = await api.get(`/mushroom-followups/${route.params.id}`)
  form.value = {
    allocation_id: data.allocation_id,
    followup_round: data.followup_round,
    followup_date: data.followup_date ?? '',
    harvest_kg: data.harvest_kg,
    sold_kg: data.sold_kg,
    price_per_kg: data.price_per_kg,
    revenue: data.revenue,
    sale_channel: data.sale_channel ?? '',
    sale_place: data.sale_place ?? '',
    enterprise_member: data.enterprise_member,
    enterprise_name: data.enterprise_name ?? '',
    note: data.note ?? '',
  }
}

async function handleSubmit() {
  saving.value = true
  error.value = ''
  try {
    if (isEdit.value) {
      await api.put(`/mushroom-followups/${route.params.id}`, form.value)
    } else {
      await api.post('/mushroom-followups', form.value)
    }
    router.push('/app/followups')
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(', ') : e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadAllocations()
  if (isEdit.value) await fetchFollowup()
})
</script>
