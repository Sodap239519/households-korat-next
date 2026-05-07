<template>
  <Dialog
    v-model:visible="visible"
    modal
    :draggable="false"
    :style="{ width: '780px' }"
    :breakpoints="{ '767px': '95vw' }"
    :closeOnEscape="!saving"
    :pt="{ root: { class: 'rounded-2xl overflow-hidden' } }"
  >
    <template #header>
      <div class="flex items-center gap-3 w-full">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-rose-500 text-white flex items-center justify-center shadow-md">
          <i :class="isEdit ? 'fi fi-rr-edit' : 'fi fi-rr-plus'"></i>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-800">{{ isEdit ? 'แก้ไขการติดตามผล' : 'เพิ่มการติดตามผล' }}</h3>
          <p class="text-xs text-slate-500">บันทึกผลผลิตและรายได้</p>
        </div>
      </div>
    </template>

    <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <FormSection title="ครัวเรือน + รอบติดตาม" icon="fi fi-rr-house-blank" tone="violet">
        <div class="space-y-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">
              การจัดสรร <span class="text-rose-500">*</span>
            </label>
            <Select
              v-model="form.allocation_id"
              :options="allocationOptions"
              optionLabel="label"
              optionValue="value"
              filter
              placeholder="-- เลือกการจัดสรร --"
              required
              class="w-full"
              :disabled="isEdit"
            />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">
                รอบติดตาม <span class="text-rose-500">*</span>
              </label>
              <InputNumber v-model="form.followup_round" :min="1" required fluid />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">วันที่ติดตาม</label>
              <DatePicker v-model="form.followup_date" dateFormat="dd/mm/yy" showIcon fluid />
            </div>
          </div>
        </div>
      </FormSection>

      <FormSection title="ผลผลิตและการขาย" icon="fi fi-rr-leaf" tone="emerald">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">ผลผลิต (กก.)</label>
            <InputNumber v-model="form.harvest_kg" :min="0" :minFractionDigits="0" :maxFractionDigits="3" fluid />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">ขายได้ (กก.)</label>
            <InputNumber v-model="form.sold_kg" :min="0" :minFractionDigits="0" :maxFractionDigits="3" fluid @update:modelValue="recalc" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">ราคา/กก. (บาท)</label>
            <InputNumber v-model="form.price_per_kg" :min="0" :minFractionDigits="0" :maxFractionDigits="2" fluid @update:modelValue="recalc" />
          </div>
          <div class="md:col-span-3">
            <label class="text-sm font-medium text-slate-700 mb-1 block">รายได้ (บาท)</label>
            <InputNumber v-model="form.revenue" :min="0" :minFractionDigits="0" :maxFractionDigits="2" fluid />
            <p class="text-[11px] text-slate-400 mt-1">คำนวณอัตโนมัติจากขาย × ราคา (แก้ไขได้)</p>
          </div>
        </div>
      </FormSection>

      <FormSection title="ช่องทางขาย" icon="fi fi-rr-shop" tone="amber">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">ช่องทาง</label>
            <Select v-model="form.sale_channel" :options="channelOptions" optionLabel="label" optionValue="value" placeholder="-- เลือก --" showClear class="w-full" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">ชื่อตลาด/ร้าน/แพลตฟอร์ม</label>
            <InputText v-model="form.sale_place" class="w-full" />
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
          <div class="flex items-center gap-2">
            <ToggleSwitch v-model="form.enterprise_member" inputId="ent_member" />
            <label for="ent_member" class="text-sm text-slate-700">สมาชิกวิสาหกิจชุมชน</label>
          </div>
          <div v-if="form.enterprise_member">
            <label class="text-sm font-medium text-slate-700 mb-1 block">ชื่อวิสาหกิจ</label>
            <InputText v-model="form.enterprise_name" class="w-full" />
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
        :label="saving ? 'กำลังบันทึก...' : (isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มการติดตาม')"
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
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'
import Message from 'primevue/message'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  followupId: { type: [Number, String, null], default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const isEdit = computed(() => !!props.followupId)

const channelOptions = [
  { label: 'ขายตรง',   value: 'direct' },
  { label: 'ออนไลน์', value: 'online' },
  { label: 'วิสาหกิจ', value: 'enterprise' },
  { label: 'ตลาด',     value: 'market' },
]

function defaultForm() {
  return {
    allocation_id: null, followup_round: 1, followup_date: null,
    harvest_kg: null, sold_kg: null, price_per_kg: null, revenue: null,
    sale_channel: null, sale_place: '',
    enterprise_member: false, enterprise_name: '',
    note: '',
  }
}

const form = ref(defaultForm())
const saving = ref(false)
const error = ref('')
const allocations = ref([])

const allocationOptions = computed(() => allocations.value.map(a => ({
  label: `${a.household?.first_name || ''} ${a.household?.last_name || ''} (${a.household?.household_code || '-'}) — ${a.quota?.district || ''} ปี ${a.quota?.year || ''} รอบ ${a.quota?.round || ''} | ${a.bags} ถุง`,
  value: a.id,
})))

function recalc() {
  if (form.value.sold_kg && form.value.price_per_kg) {
    form.value.revenue = Math.round(form.value.sold_kg * form.value.price_per_kg * 100) / 100
  }
}

async function loadAllocations() {
  try {
    const { data } = await api.get('/mushroom-allocations', { params: { per_page: 500 } })
    allocations.value = data.data
  } catch {}
}

watch(() => props.modelValue, async (open) => {
  if (!open) return
  error.value = ''
  await loadAllocations()
  if (isEdit.value) {
    try {
      const { data } = await api.get(`/mushroom-followups/${props.followupId}`)
      form.value = {
        ...data,
        followup_date: data.followup_date ? new Date(data.followup_date) : null,
        sale_place: data.sale_place ?? '',
        enterprise_name: data.enterprise_name ?? '',
        note: data.note ?? '',
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
    if (payload.followup_date instanceof Date) payload.followup_date = formatDate(payload.followup_date)
    if (isEdit.value) {
      await api.put(`/mushroom-followups/${props.followupId}`, payload)
    } else {
      await api.post('/mushroom-followups', payload)
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
