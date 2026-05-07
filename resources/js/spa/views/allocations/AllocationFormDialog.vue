<template>
  <Dialog
    v-model:visible="visible"
    modal
    :draggable="false"
    :style="{ width: '720px' }"
    :breakpoints="{ '767px': '95vw' }"
    :closeOnEscape="!saving"
    :pt="{ root: { class: 'rounded-2xl overflow-hidden' } }"
  >
    <template #header>
      <div class="flex items-center gap-3 w-full">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-fuchsia-500 to-pink-600 text-white flex items-center justify-center shadow-md">
          <i :class="isEdit ? 'fi fi-rr-edit' : 'fi fi-rr-plus'"></i>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-800">{{ isEdit ? 'แก้ไขการจัดสรร' : 'เพิ่มการจัดสรร' }}</h3>
          <p class="text-xs text-slate-500">บันทึกการจัดสรรถุงเห็ดให้ครัวเรือน</p>
        </div>
      </div>
    </template>

    <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <FormSection title="เลือกโควต้า + ครัวเรือน" icon="fi fi-rr-users-medical" tone="fuchsia">
        <div class="space-y-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">
              โควต้าอำเภอ <span class="text-rose-500">*</span>
            </label>
            <Select
              v-model="form.quota_id"
              :options="quotaOptions"
              optionLabel="label"
              optionValue="value"
              filter
              placeholder="-- เลือกโควต้า --"
              required
              class="w-full"
              :disabled="isEdit"
            />
            <p v-if="selectedQuota" class="text-xs text-emerald-700 mt-1">
              <i class="fi fi-rr-info"></i>
              คงเหลือ <span class="font-semibold">{{ remainingBags }}</span> ถุง จากทั้งหมด {{ selectedQuota.quota_bags }} ถุง
            </p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">
              ครัวเรือน <span class="text-rose-500">*</span>
            </label>
            <Select
              v-model="form.household_id"
              :options="householdOptions"
              optionLabel="label"
              optionValue="value"
              filter
              placeholder="-- เลือกครัวเรือน --"
              required
              class="w-full"
              :disabled="isEdit"
            />
          </div>
        </div>
      </FormSection>

      <FormSection title="รายละเอียดจัดสรร" icon="fi fi-rr-seedling" tone="emerald">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">
              จำนวนถุง <span class="text-rose-500">*</span>
            </label>
            <InputNumber v-model="form.bags" :min="1" :max="remainingBags || 9999" required fluid suffix=" ถุง" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">วันที่จัดสรร</label>
            <DatePicker v-model="form.allocated_date" dateFormat="dd/mm/yy" showIcon fluid />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">สถานะ</label>
            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>
      </FormSection>

      <FormSection title="หมายเหตุ" icon="fi fi-rr-note" tone="sky">
        <Textarea v-model="form.note" rows="3" autoResize class="w-full" placeholder="ระบุข้อมูลเพิ่มเติม (ถ้ามี)" />
      </FormSection>
    </form>

    <template #footer>
      <Button label="ยกเลิก" severity="secondary" outlined icon="fi fi-rr-cross-small" @click="close" :disabled="saving" />
      <Button
        :label="saving ? 'กำลังบันทึก...' : (isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มการจัดสรร')"
        :loading="saving"
        icon="fi fi-rr-disk"
        @click="handleSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import api from '../../api/index.js'
import FormSection from '../../components/FormSection.vue'

import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'
import Message from 'primevue/message'

const props = defineProps({
  modelValue:    { type: Boolean, default: false },
  allocationId:  { type: [Number, String, null], default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const isEdit = computed(() => !!props.allocationId)

const statusOptions = [
  { label: 'รอดำเนินการ',     value: 'pending' },
  { label: 'กำลังดำเนินการ', value: 'active' },
  { label: 'เสร็จสิ้น',         value: 'completed' },
]

function defaultForm() {
  return {
    quota_id: null, household_id: null,
    bags: null, allocated_date: null,
    status: 'pending', note: '',
  }
}

const form = ref(defaultForm())
const saving = ref(false)
const error = ref('')
const quotas = ref([])
const households = ref([])

const quotaOptions = computed(() => quotas.value.map(q => ({
  label: `${q.district} ปี ${q.year} รอบ ${q.round} (คงเหลือ ${(q.quota_bags - (q.allocations_sum_bags || 0))} ถุง)`,
  value: q.id,
})))

const householdOptions = computed(() => households.value.map(h => ({
  label: `${h.household_code} — ${h.first_name || ''} ${h.last_name || ''} (${h.district || '-'})`.trim(),
  value: h.id,
})))

const selectedQuota = computed(() => quotas.value.find(q => q.id === form.value.quota_id))
const remainingBags = computed(() => {
  if (!selectedQuota.value) return null
  return selectedQuota.value.quota_bags - (selectedQuota.value.allocations_sum_bags || 0)
})

async function loadQuotas() {
  try {
    const { data } = await api.get('/mushroom-quotas', { params: { per_page: 200, active: 1 } })
    quotas.value = data.data
  } catch {}
}
async function loadHouseholds() {
  try {
    const { data } = await api.get('/households', { params: { per_page: 500 } })
    households.value = data.data
  } catch {}
}

watch(() => props.modelValue, async (open) => {
  if (!open) return
  error.value = ''
  await Promise.all([loadQuotas(), loadHouseholds()])
  if (isEdit.value) {
    try {
      const { data } = await api.get(`/mushroom-allocations/${props.allocationId}`)
      form.value = {
        ...data,
        allocated_date: data.allocated_date ? new Date(data.allocated_date) : null,
        note: data.note ?? '',
        status: data.status ?? 'pending',
      }
    } catch (e) {
      error.value = e.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ'
    }
  } else {
    form.value = defaultForm()
  }
})

async function handleSubmit() {
  saving.value = true
  error.value = ''
  try {
    const payload = { ...form.value }
    if (payload.allocated_date instanceof Date) payload.allocated_date = formatDate(payload.allocated_date)
    if (isEdit.value) {
      await api.put(`/mushroom-allocations/${props.allocationId}`, payload)
    } else {
      await api.post('/mushroom-allocations', payload)
    }
    emit('saved')
    close()
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(' • ') : (e.response?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    saving.value = false
  }
}

function close() { visible.value = false }

function formatDate(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}
</script>
