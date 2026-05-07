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
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center shadow-md">
          <i :class="isEditMode ? 'fi fi-rr-edit' : 'fi fi-rr-plus'"></i>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-800">{{ isEditMode ? 'แก้ไขโควต้าอำเภอ' : 'เพิ่มโควต้าอำเภอ' }}</h3>
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
            <Select
              v-model="form.district"
              :options="districtOptions"
              required
              showClear
              filter
              editable
              placeholder="-- เลือกอำเภอ --"
              class="w-full"
              :disabled="isEditMode"
              @change="onDistrictChange"
            />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1.5 block">จังหวัด</label>
            <InputText v-model="form.province" class="w-full" />
          </div>
        </div>
      </FormSection>

      <!-- History table — when this district already has quota records -->
      <FormSection
        v-if="!isEditMode && form.district && history.length"
        :title="`อำเภอนี้เคยได้รับโควต้าไปแล้ว ${history.length} รอบ`"
        icon="fi fi-rr-time-past"
        tone="amber"
      >
        <div class="overflow-x-auto -mx-1">
          <table class="w-full text-xs">
            <thead>
              <tr class="text-left text-slate-500 border-b border-amber-100">
                <th class="px-2 py-1.5 font-medium">ปี</th>
                <th class="px-2 py-1.5 font-medium">รอบ</th>
                <th class="px-2 py-1.5 font-medium text-right">โควต้า (ก้อน)</th>
                <th class="px-2 py-1.5 font-medium text-right">จัดสรรแล้ว</th>
                <th class="px-2 py-1.5 font-medium text-right">คงเหลือ</th>
                <th class="px-2 py-1.5 font-medium">สถานะ</th>
                <th class="px-2 py-1.5 font-medium text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="h in history"
                :key="h.id"
                :class="['border-b border-slate-100 hover:bg-amber-50/40', editingHistoryId === h.id ? 'bg-amber-100/60' : '']"
              >
                <td class="px-2 py-1.5 font-semibold text-amber-700">{{ h.year }}</td>
                <td class="px-2 py-1.5">
                  <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-700 font-semibold">{{ h.round }}</span>
                </td>
                <td class="px-2 py-1.5 text-right font-semibold">{{ Number(h.quota_bags).toLocaleString() }}</td>
                <td class="px-2 py-1.5 text-right">{{ Number(h.allocations_sum_bags || 0).toLocaleString() }}</td>
                <td class="px-2 py-1.5 text-right">
                  <span :class="['font-semibold', remaining(h) < 0 ? 'text-rose-600' : remaining(h) === 0 ? 'text-amber-600' : 'text-emerald-600']">
                    {{ Number(remaining(h)).toLocaleString() }}
                  </span>
                </td>
                <td class="px-2 py-1.5">
                  <StatusBadge :status="h.is_active ? 'active' : 'inactive'" :label="h.is_active ? 'เปิดใช้' : 'ปิด'" />
                </td>
                <td class="px-2 py-1.5 text-center">
                  <Button
                    v-if="editingHistoryId !== h.id"
                    icon="fi fi-rr-edit"
                    severity="info"
                    text
                    rounded
                    size="small"
                    v-tooltip.top="'แก้ไขรอบนี้'"
                    @click="editFromHistory(h)"
                  />
                  <Button
                    v-else
                    label="กำลังแก้ไข"
                    severity="info"
                    size="small"
                    @click="cancelEditHistory"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3 flex items-center gap-2 flex-wrap">
          <p class="text-xs text-slate-600 flex-1">
            <i class="fi fi-rr-info text-amber-500"></i>
            รวม {{ historyTotalBags.toLocaleString() }} ก้อน · ระบบแนะนำให้เพิ่มเป็น <span class="font-semibold text-amber-700">ปี {{ form.year }} รอบ {{ form.round }}</span>
          </p>
          <Button
            v-if="editingHistoryId"
            label="เพิ่มรอบใหม่"
            icon="fi fi-rr-plus"
            severity="success"
            outlined
            size="small"
            @click="startNewRound"
          />
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
              โควต้า (ก้อน) <span class="text-rose-500">*</span>
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
            <p class="text-xs text-slate-500">จำนวนก้อน</p>
            <p class="font-bold text-emerald-700">{{ Number(form.quota_bags).toLocaleString() }} ก้อน</p>
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
        :label="saving ? 'กำลังบันทึก...' : (isEditMode ? 'บันทึกการแก้ไข' : 'เพิ่มโควต้า')"
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
import StatusBadge from '../../components/StatusBadge.vue'

import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  quotaId:    { type: [Number, String, null], default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const editingHistoryId = ref(null)
const effectiveId = computed(() => editingHistoryId.value || props.quotaId)
const isEditMode  = computed(() => !!effectiveId.value)

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
const districtOptions = ref([])
const history = ref([])

const historyTotalBags = computed(() => history.value.reduce((acc, h) => acc + Number(h.quota_bags || 0), 0))

function remaining(h) {
  return Number(h.quota_bags || 0) - Number(h.allocations_sum_bags || 0)
}

async function loadDistricts() {
  if (districtOptions.value.length) return
  try {
    const { data } = await api.get('/locations/districts')
    districtOptions.value = data
  } catch {}
}

async function loadHistory(district) {
  if (!district) { history.value = []; return }
  try {
    const { data } = await api.get('/mushroom-quotas', {
      params: { district, per_page: 100 },
    })
    history.value = data.data || []
  } catch { history.value = [] }
}

async function onDistrictChange() {
  if (!form.value.district) {
    history.value = []
    return
  }
  await loadHistory(form.value.district)
  // Auto-suggest next year/round in CREATE mode
  if (!isEditMode.value && history.value.length) {
    const maxYear = Math.max(...history.value.map(h => Number(h.year) || 0))
    const sameYear = history.value.filter(h => Number(h.year) === maxYear)
    const maxRound = Math.max(...sameYear.map(h => Number(h.round) || 0))
    form.value.year = maxYear
    form.value.round = maxRound + 1
  }
}

function editFromHistory(row) {
  editingHistoryId.value = row.id
  form.value = {
    district:   row.district,
    province:   row.province ?? 'นครราชสีมา',
    year:       row.year,
    round:      row.round,
    quota_bags: row.quota_bags,
    is_active:  !!row.is_active,
    note:       row.note ?? '',
  }
  scrollDialogToBottom()
}

function cancelEditHistory() {
  editingHistoryId.value = null
  startNewRound()
}

function startNewRound() {
  editingHistoryId.value = null
  // Compute next year/round for current district
  const district = form.value.district
  let nextYear  = new Date().getFullYear() + 543
  let nextRound = 1
  if (history.value.length) {
    const maxYear = Math.max(...history.value.map(h => Number(h.year) || 0))
    const sameYear = history.value.filter(h => Number(h.year) === maxYear)
    const maxRound = Math.max(...sameYear.map(h => Number(h.round) || 0))
    nextYear  = maxYear
    nextRound = maxRound + 1
  }
  form.value = {
    ...defaultForm(),
    district,
    year:  nextYear,
    round: nextRound,
  }
  scrollDialogToBottom()
}

function scrollDialogToBottom() {
  setTimeout(() => {
    document.querySelector('.p-dialog-content')?.scrollTo({ top: 99999, behavior: 'smooth' })
  }, 50)
}

watch(() => props.modelValue, async (open) => {
  if (!open) {
    editingHistoryId.value = null
    history.value = []
    return
  }
  error.value = ''
  loadDistricts()
  if (props.quotaId) {
    try {
      const { data } = await api.get(`/mushroom-quotas/${props.quotaId}`)
      form.value = { ...data, note: data.note ?? '' }
      // Load district history (for context, even in edit mode)
      await loadHistory(data.district)
    } catch (e) {
      error.value = e.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ'
    }
  } else {
    form.value = defaultForm()
    history.value = []
  }
})

async function handleSubmit() {
  saving.value = true
  error.value = ''
  try {
    if (effectiveId.value) {
      await api.put(`/mushroom-quotas/${effectiveId.value}`, form.value)
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
