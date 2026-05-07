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
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-fuchsia-500 to-pink-600 text-white flex items-center justify-center shadow-md">
          <i :class="isEditMode ? 'fi fi-rr-edit' : 'fi fi-rr-plus'"></i>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-800">{{ isEditMode ? 'แก้ไขการจัดสรร' : 'เพิ่มการจัดสรร' }}</h3>
          <p class="text-xs text-slate-500">บันทึกการจัดสรรถุงเห็ดให้ครัวเรือน</p>
        </div>
      </div>
    </template>

    <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <FormSection title="เลือกครัวเรือน + โควต้า" icon="fi fi-rr-users-medical" tone="fuchsia">
        <div class="space-y-3">
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
              :disabled="isEditMode"
              @change="onHouseholdChange"
            />
          </div>
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
              :disabled="isEditMode"
            />
            <p v-if="selectedQuota" class="text-xs text-emerald-700 mt-1">
              <i class="fi fi-rr-info"></i>
              คงเหลือ <span class="font-semibold">{{ remainingBags }}</span> ถุง จากทั้งหมด {{ selectedQuota.quota_bags }} ถุง
            </p>
          </div>
        </div>
      </FormSection>

      <!-- History table — shown only when a household is selected and has past allocations -->
      <FormSection
        v-if="!isEditMode && form.household_id && history.length"
        :title="`ประวัติการได้รับจัดสรร (${history.length} ครั้ง)`"
        icon="fi fi-rr-time-past"
        tone="violet"
      >
        <div class="overflow-x-auto -mx-1">
          <table class="w-full text-xs">
            <thead>
              <tr class="text-left text-slate-500 border-b border-violet-100">
                <th class="px-2 py-1.5 font-medium">รอบ</th>
                <th class="px-2 py-1.5 font-medium">โควต้า (อำเภอ/ปี/รอบ)</th>
                <th class="px-2 py-1.5 font-medium text-right">ถุง</th>
                <th class="px-2 py-1.5 font-medium">วันที่</th>
                <th class="px-2 py-1.5 font-medium">สถานะ</th>
                <th class="px-2 py-1.5 font-medium text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(h, idx) in history" :key="h.id"
                  :class="['border-b border-slate-100 hover:bg-violet-50/40', editingHistoryId === h.id ? 'bg-violet-100/60' : '']">
                <td class="px-2 py-1.5">
                  <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-violet-100 text-violet-700 font-semibold">{{ idx + 1 }}</span>
                </td>
                <td class="px-2 py-1.5 text-slate-700">
                  {{ h.quota?.district }} · {{ h.quota?.year }} · รอบ {{ h.quota?.round }}
                </td>
                <td class="px-2 py-1.5 text-right font-semibold">{{ h.bags }}</td>
                <td class="px-2 py-1.5 whitespace-nowrap">{{ fmtThaiDate(h.allocated_date, { short: true }) }}</td>
                <td class="px-2 py-1.5">
                  <StatusBadge :status="h.status === 'completed' ? 'completed' : h.status === 'active' ? 'active' : 'pending'"
                               :label="STATUS_LABEL[h.status] || h.status" />
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
          <p class="text-xs text-slate-500 flex-1">
            <i class="fi fi-rr-info text-violet-500"></i>
            ครัวเรือนนี้ได้รับการจัดสรรไปแล้ว {{ history.length }} ครั้ง · รวม {{ historyTotalBags }} ถุง
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

      <FormSection title="รายละเอียดการจัดสรร" icon="fi fi-rr-seedling" tone="emerald">
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
        :label="saving ? 'กำลังบันทึก...' : (isEditMode ? 'บันทึกการแก้ไข' : 'เพิ่มการจัดสรร')"
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
import { fmtThaiDate } from '../../utils/date.js'

import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const props = defineProps({
  modelValue:    { type: Boolean, default: false },
  allocationId:  { type: [Number, String, null], default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// Local edit override (so editing from history works without parent prop change)
const editingHistoryId = ref(null)
const effectiveId = computed(() => editingHistoryId.value || props.allocationId)
const isEditMode  = computed(() => !!effectiveId.value)

const STATUS_LABEL = {
  pending:   'รอดำเนินการ',
  active:    'กำลังดำเนินการ',
  completed: 'เสร็จสิ้น',
}
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
const history = ref([])

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
  let remaining = selectedQuota.value.quota_bags - (selectedQuota.value.allocations_sum_bags || 0)
  // If we're editing an existing allocation tied to this quota, add back its bags
  if (isEditMode.value && form.value.bags && selectedQuota.value.id === form.value.quota_id) {
    // The current bags are already counted in the allocations_sum_bags from server,
    // so on edit, give back the original bags amount for fair calculation.
    remaining += Number(history.value.find(h => h.id === effectiveId.value)?.bags || 0)
  }
  return remaining
})

const historyTotalBags = computed(() => history.value.reduce((acc, h) => acc + Number(h.bags || 0), 0))

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

async function loadHistory(householdId) {
  if (!householdId) { history.value = []; return }
  try {
    const { data } = await api.get('/mushroom-allocations', {
      params: { household_id: householdId, per_page: 100 },
    })
    history.value = data.data || []
  } catch { history.value = [] }
}

async function onHouseholdChange() {
  if (!form.value.household_id) {
    history.value = []
    return
  }
  await loadHistory(form.value.household_id)
}

function editFromHistory(row) {
  editingHistoryId.value = row.id
  form.value = {
    quota_id:       row.quota_id,
    household_id:   row.household_id,
    bags:           row.bags,
    allocated_date: row.allocated_date ? new Date(row.allocated_date) : null,
    status:         row.status ?? 'pending',
    note:           row.note ?? '',
  }
  scrollDialogToBottom()
}

function cancelEditHistory() {
  editingHistoryId.value = null
  startNewRound()
}

function startNewRound() {
  editingHistoryId.value = null
  // Keep household_id, blank everything else; suggest last bags as starter
  const last = history.value[0] // first row is most recent (orderBy allocated_date desc)
  form.value = {
    quota_id:       null,
    household_id:   form.value.household_id,
    bags:           last?.bags ?? null,
    allocated_date: null,
    status:         'pending',
    note:           '',
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
    return
  }
  error.value = ''
  await Promise.all([loadQuotas(), loadHouseholds()])
  if (props.allocationId) {
    try {
      const { data } = await api.get(`/mushroom-allocations/${props.allocationId}`)
      form.value = {
        ...data,
        allocated_date: data.allocated_date ? new Date(data.allocated_date) : null,
        note: data.note ?? '',
        status: data.status ?? 'pending',
      }
      // Also load history for this household
      await loadHistory(data.household_id)
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
    const payload = { ...form.value }
    if (payload.allocated_date instanceof Date) payload.allocated_date = formatDate(payload.allocated_date)
    if (effectiveId.value) {
      await api.put(`/mushroom-allocations/${effectiveId.value}`, payload)
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
