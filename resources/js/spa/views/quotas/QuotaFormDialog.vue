<template>
  <Dialog
    v-model:visible="visible"
    :header="isEdit ? 'แก้ไขโควต้าอำเภอ' : 'เพิ่มโควต้าอำเภอ'"
    modal
    :draggable="false"
    :style="{ width: '720px' }"
    :closeOnEscape="!saving"
    :pt="{ root: { class: 'rounded-2xl overflow-hidden' } }"
  >
    <template #header>
      <div class="flex items-center gap-3 w-full">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center shadow-md">
          <i :class="isEdit ? 'fi fi-rr-edit' : 'fi fi-rr-plus'"></i>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-800">{{ isEdit ? 'แก้ไขโควต้าอำเภอ' : 'เพิ่มโควต้าอำเภอ' }}</h3>
          <p class="text-xs text-slate-500">กำหนดโควต้าเห็ดสำหรับแต่ละอำเภอ ปี รอบ</p>
        </div>
      </div>
    </template>

    <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <FormSection title="ข้อมูลพื้นที่" icon="fi fi-rr-marker" tone="violet">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 block">
              อำเภอ <span class="text-rose-500">*</span>
            </label>
            <InputText v-model="form.district" required class="w-full" placeholder="เช่น เมืองนครราชสีมา" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 block">จังหวัด</label>
            <InputText v-model="form.province" class="w-full" />
          </div>
        </div>
      </FormSection>

      <FormSection title="ปี และจำนวนโควต้า" icon="fi fi-rr-calendar" tone="fuchsia">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 block">
              ปี พ.ศ. <span class="text-rose-500">*</span>
            </label>
            <InputNumber v-model="form.year" :min="2500" :max="2600" :useGrouping="false" required fluid />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 block">
              รอบ <span class="text-rose-500">*</span>
            </label>
            <InputNumber v-model="form.round" :min="1" :max="10" required fluid />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 block">
              โควต้า (ถุง) <span class="text-rose-500">*</span>
            </label>
            <InputNumber v-model="form.quota_bags" :min="1" required fluid />
          </div>
        </div>

        <div class="flex items-center gap-2 mt-4">
          <ToggleSwitch v-model="form.is_active" inputId="is_active_toggle" />
          <label for="is_active_toggle" class="text-sm text-slate-700">เปิดใช้งานโควต้านี้</label>
        </div>
      </FormSection>

      <!-- Live calculation box -->
      <div v-if="form.quota_bags" class="rounded-xl border-2 border-emerald-300/70 bg-gradient-to-r from-emerald-50 to-teal-50 p-4">
        <div class="flex items-center gap-2 text-emerald-700 text-sm font-semibold mb-2">
          <i class="fi fi-rr-calculator"></i>
          คำนวณข้อมูลพื้นฐาน
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
          <div class="bg-white rounded-lg p-3 border border-emerald-200">
            <p class="text-xs text-slate-500">จำนวนถุง</p>
            <p class="font-bold text-emerald-700">{{ Number(form.quota_bags).toLocaleString() }} ถุง</p>
          </div>
          <div class="bg-white rounded-lg p-3 border border-emerald-200">
            <p class="text-xs text-slate-500">เทียบเป็นขีด</p>
            <p class="font-bold text-emerald-700">{{ (form.quota_bags * 2).toLocaleString() }} ขีด</p>
          </div>
          <div class="bg-white rounded-lg p-3 border border-emerald-200">
            <p class="text-xs text-slate-500">เทียบเป็น กก.</p>
            <p class="font-bold text-emerald-700">{{ (form.quota_bags * 0.2).toFixed(2) }} กก.</p>
          </div>
          <div class="bg-white rounded-lg p-3 border border-emerald-200">
            <p class="text-xs text-slate-500">รายได้พื้นฐาน</p>
            <p class="font-bold text-emerald-700">{{ (form.quota_bags * 12).toLocaleString() }} บาท</p>
          </div>
        </div>
      </div>

      <FormSection title="หมายเหตุ" icon="fi fi-rr-note" tone="sky">
        <Textarea v-model="form.note" rows="3" class="w-full" autoResize placeholder="ระบุข้อมูลเพิ่มเติม (ถ้ามี)" />
      </FormSection>
    </form>

    <template #footer>
      <Button label="ยกเลิก" severity="secondary" outlined icon="fi fi-rr-cross-small" @click="close" :disabled="saving" />
      <Button
        :label="saving ? 'กำลังบันทึก...' : (isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มโควต้า')"
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
import ToggleSwitch from 'primevue/toggleswitch'
import Button from 'primevue/button'
import Message from 'primevue/message'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  quotaId:    { type: [Number, String, null], default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const isEdit = computed(() => !!props.quotaId)

const defaultForm = () => ({
  district: '',
  province: 'นครราชสีมา',
  year: new Date().getFullYear() + 543,
  round: 1,
  quota_bags: null,
  is_active: true,
  note: '',
})

const form = ref(defaultForm())
const saving = ref(false)
const error = ref('')

watch(() => props.modelValue, async (open) => {
  if (!open) return
  error.value = ''
  if (isEdit.value) {
    try {
      const { data } = await api.get(`/mushroom-quotas/${props.quotaId}`)
      form.value = { ...data, note: data.note ?? '' }
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
    if (isEdit.value) {
      await api.put(`/mushroom-quotas/${props.quotaId}`, form.value)
    } else {
      await api.post('/mushroom-quotas', form.value)
    }
    emit('saved')
    close()
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(', ') : e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally {
    saving.value = false
  }
}

function close() {
  visible.value = false
}
</script>
