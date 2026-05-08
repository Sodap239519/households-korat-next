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
          <p class="text-xs text-slate-500">บันทึกการจัดสรรก้อนเห็ดให้ครัวเรือน</p>
        </div>
      </div>
    </template>

    <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

    <!-- Mode switch: individual vs group (only when creating, not when editing) -->
    <div v-if="!isEditMode" class="mb-4">
      <SelectButton
        v-model="mode"
        :options="modeOptions"
        optionLabel="label"
        optionValue="value"
        aria-labelledby="alloc-mode"
        :allowEmpty="false"
        class="w-full"
        :pt="{
          root: { class: 'grid grid-cols-2 gap-2' },
        }"
      >
        <template #option="{ option }">
          <span class="flex items-center justify-center gap-2 w-full px-3 py-1">
            <i :class="option.icon"></i> {{ option.label }}
          </span>
        </template>
      </SelectButton>
      <p class="text-[11px] text-slate-500 mt-1.5">
        <i class="fi fi-rr-info text-violet-500"></i>
        {{ mode === 'group'
            ? 'จัดสรรรวมเป็นกลุ่ม — ระบบจะหารเฉลี่ยจำนวนก้อนเท่า ๆ กันให้ทุกคน'
            : 'จัดสรรแยกเป็นรายครัวเรือน (รูปแบบเดิม)' }}
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <FormSection title="เลือกอำเภอ + ครัวเรือน + โควต้า" icon="fi fi-rr-users-medical" tone="fuchsia">
        <div class="space-y-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">
              อำเภอ <span class="text-rose-500">*</span>
            </label>
            <Select
              v-model="selectedDistrict"
              :options="districtOptions"
              filter
              showClear
              placeholder="-- เลือกอำเภอ --"
              class="w-full"
              :disabled="isEditMode"
              @change="onDistrictChange"
            />
            <p v-if="!selectedDistrict" class="text-[11px] text-slate-400 mt-1">
              <i class="fi fi-rr-info"></i> เลือกอำเภอก่อนเพื่อกรองครัวเรือนและโควต้า
            </p>
          </div>
          <!-- Individual mode: single household picker -->
          <div v-if="mode === 'individual' || isEditMode">
            <label class="text-sm font-medium text-slate-700 mb-1 block">
              ครัวเรือน <span class="text-rose-500">*</span>
              <span v-if="selectedDistrict && households.length" class="text-[11px] text-slate-400 ml-2">
                ({{ households.length }} รายการในอำเภอ {{ selectedDistrict }})
              </span>
            </label>
            <Select
              v-model="form.household_id"
              :options="householdOptions"
              optionLabel="label"
              optionValue="value"
              filter
              :placeholder="selectedDistrict ? '-- เลือกครัวเรือน --' : 'เลือกอำเภอก่อน'"
              required
              class="w-full"
              :disabled="isEditMode || !selectedDistrict"
              @change="onHouseholdChange"
            />
          </div>

          <!-- Group mode: multi-select with "ทั้งอำเภอ" shortcut + group label -->
          <template v-else>
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">
                ชื่อกลุ่ม
                <span class="text-[11px] text-slate-400 ml-2 font-normal">— เลือก/พิมพ์เพื่อแสดงในรายงาน</span>
              </label>
              <InputText v-model="groupLabel" placeholder="เช่น กลุ่มเพาะเห็ดบ้านนาดี" class="w-full" />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">
                ครัวเรือนในกลุ่ม <span class="text-rose-500">*</span>
                <span v-if="selectedHouseholdIds.length" class="text-[11px] text-emerald-600 ml-2">
                  เลือกแล้ว {{ selectedHouseholdIds.length }}/{{ households.length }} รายการ
                </span>
              </label>
              <div class="flex items-center gap-2 mb-2 flex-wrap">
                <Button
                  type="button"
                  size="small"
                  severity="success"
                  outlined
                  icon="fi fi-rr-check"
                  label="เลือกทั้งอำเภอ"
                  :disabled="!selectedDistrict || !households.length"
                  @click="selectAllHouseholds"
                />
                <Button
                  type="button"
                  size="small"
                  severity="secondary"
                  outlined
                  icon="fi fi-rr-cross-small"
                  label="ล้าง"
                  :disabled="!selectedHouseholdIds.length"
                  @click="selectedHouseholdIds = []"
                />
              </div>
              <MultiSelect
                v-model="selectedHouseholdIds"
                :options="householdOptions"
                optionLabel="label"
                optionValue="value"
                filter
                display="chip"
                :placeholder="selectedDistrict ? '-- เลือกหลายครัวเรือน --' : 'เลือกอำเภอก่อน'"
                class="w-full"
                :disabled="!selectedDistrict"
              />
            </div>
          </template>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">
              โควต้าอำเภอ <span class="text-rose-500">*</span>
              <span v-if="selectedDistrict && quotas.length" class="text-[11px] text-slate-400 ml-2">
                ({{ quotas.length }} รอบ)
              </span>
            </label>
            <Select
              v-model="form.quota_id"
              :options="quotaOptions"
              optionLabel="label"
              optionValue="value"
              filter
              :placeholder="selectedDistrict ? '-- เลือกโควต้า --' : 'เลือกอำเภอก่อน'"
              required
              class="w-full"
              :disabled="isEditMode || !selectedDistrict"
            />
            <p v-if="selectedQuota" class="text-xs text-emerald-700 mt-1">
              <i class="fi fi-rr-info"></i>
              คงเหลือ <span class="font-semibold">{{ remainingBags }}</span> ก้อน จากทั้งหมด {{ selectedQuota.quota_bags }} ก้อน
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
                <th class="px-2 py-1.5 font-medium text-right">ก้อน</th>
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
            ครัวเรือนนี้ได้รับการจัดสรรไปแล้ว {{ history.length }} ครั้ง · รวม {{ historyTotalBags }} ก้อน
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
              {{ mode === 'group' && !isEditMode ? 'จำนวนก้อนรวมทั้งกลุ่ม' : 'จำนวนก้อน' }}
              <span class="text-rose-500">*</span>
            </label>
            <InputNumber v-model="form.bags" :min="1" :max="remainingBags || 9999" required fluid suffix=" ก้อน" />
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

        <!-- Group preview: average per household -->
        <div
          v-if="mode === 'group' && !isEditMode && groupPreview"
          class="mt-3 rounded-xl border-2 border-emerald-200 bg-emerald-50/40 p-3"
        >
          <p class="text-xs font-semibold text-emerald-700 mb-1">
            <i class="fi fi-rr-calculator"></i> หารเฉลี่ย
          </p>
          <p class="text-sm text-slate-700">
            <span class="font-bold text-emerald-700">{{ groupPreview.totalBags }}</span> ก้อน
            ÷ <span class="font-bold text-emerald-700">{{ groupPreview.n }}</span> ครัวเรือน
            = ครัวเรือนละ
            <span class="font-bold text-emerald-700">{{ groupPreview.base }}</span> ก้อน
            <span v-if="groupPreview.extra > 0" class="text-amber-700">
              (+1 ก้อนสำหรับ {{ groupPreview.extra }} ครัวเรือนแรก)
            </span>
          </p>
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
import { useAuth } from '../../composables/useAuth.js'

const { isAdmin, assignedDistricts } = useAuth()

import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'
import MultiSelect from 'primevue/multiselect'
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
const districtOptions = ref([])
const selectedDistrict = ref(null)

// --- Mode (individual | group) ---
const mode = ref('individual')
const modeOptions = [
  { label: 'รายเดี่ยว',   value: 'individual', icon: 'fi fi-rr-user' },
  { label: 'รายกลุ่ม',    value: 'group',      icon: 'fi fi-rr-users-alt' },
]
const groupLabel = ref('')
const selectedHouseholdIds = ref([])

function selectAllHouseholds() {
  selectedHouseholdIds.value = households.value.map(h => h.id)
}

const groupPreview = computed(() => {
  if (mode.value !== 'group') return null
  const n = selectedHouseholdIds.value.length
  const total = Number(form.value.bags || 0)
  if (!n || !total) return null
  const base = Math.floor(total / n)
  const extra = total - (base * n)
  return { n, totalBags: total, base, extra }
})

const quotaOptions = computed(() => quotas.value.map(q => ({
  label: `${q.district} ปี ${q.year} รอบ ${q.round} (คงเหลือ ${(q.quota_bags - (q.allocations_sum_bags || 0))} ก้อน)`,
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

async function loadDistricts() {
  try {
    const { data } = await api.get('/locations/districts')
    // For area_staff, restrict the list to their assigned districts
    if (!isAdmin.value && Array.isArray(assignedDistricts.value) && assignedDistricts.value.length) {
      districtOptions.value = data.filter(d => assignedDistricts.value.includes(d))
    } else {
      districtOptions.value = data
    }
  } catch {}
}

async function loadQuotas(district = null) {
  if (!district) { quotas.value = []; return }
  try {
    const { data } = await api.get('/mushroom-quotas', {
      params: { per_page: 200, active: 1, district },
    })
    quotas.value = data.data
  } catch {}
}

async function loadHouseholds(district = null) {
  if (!district) { households.value = []; return }
  try {
    const { data } = await api.get('/households', {
      params: { per_page: 500, district },
    })
    households.value = data.data
  } catch {}
}

async function onDistrictChange() {
  // Reset dependent selections
  form.value.household_id = null
  form.value.quota_id = null
  selectedHouseholdIds.value = []
  history.value = []
  if (selectedDistrict.value) {
    await Promise.all([
      loadHouseholds(selectedDistrict.value),
      loadQuotas(selectedDistrict.value),
    ])
  } else {
    households.value = []
    quotas.value = []
  }
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
    selectedDistrict.value = null
    mode.value = 'individual'
    selectedHouseholdIds.value = []
    groupLabel.value = ''
    return
  }
  error.value = ''
  await loadDistricts()
  if (props.allocationId) {
    try {
      const { data } = await api.get(`/mushroom-allocations/${props.allocationId}`)
      // Pre-set district from the loaded allocation's quota
      selectedDistrict.value = data.quota?.district || null
      if (selectedDistrict.value) {
        await Promise.all([
          loadHouseholds(selectedDistrict.value),
          loadQuotas(selectedDistrict.value),
        ])
      }
      form.value = {
        ...data,
        allocated_date: data.allocated_date ? new Date(data.allocated_date) : null,
        note: data.note ?? '',
        status: data.status ?? 'pending',
      }
      await loadHistory(data.household_id)
    } catch (e) {
      error.value = e.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ'
    }
  } else {
    form.value = defaultForm()
    history.value = []
    households.value = []
    quotas.value = []
    selectedDistrict.value = null
  }
})

async function handleSubmit() {
  // Group mode validation
  if (mode.value === 'group' && !isEditMode.value) {
    if (!selectedHouseholdIds.value.length) {
      error.value = 'กรุณาเลือกครัวเรือนในกลุ่มอย่างน้อย 1 ครัวเรือน'
      return
    }
    if (!form.value.bags || form.value.bags < selectedHouseholdIds.value.length) {
      error.value = `จำนวนก้อนรวมต้องอย่างน้อย ${selectedHouseholdIds.value.length} ก้อน (1 ก้อน/ครัวเรือน)`
      return
    }
  }

  saving.value = true
  error.value = ''
  try {
    if (mode.value === 'group' && !isEditMode.value) {
      // ----- Group submit -----
      const payload = {
        quota_id:       form.value.quota_id,
        household_ids:  [...selectedHouseholdIds.value],
        total_bags:     form.value.bags,
        allocated_date: form.value.allocated_date instanceof Date ? formatDate(form.value.allocated_date) : form.value.allocated_date,
        status:         form.value.status,
        note:           form.value.note,
        group_label:    groupLabel.value || null,
      }
      await api.post('/mushroom-allocations/group', payload)
    } else {
      // ----- Individual submit (existing path) -----
      const payload = { ...form.value }
      if (payload.allocated_date instanceof Date) payload.allocated_date = formatDate(payload.allocated_date)
      if (effectiveId.value) {
        await api.put(`/mushroom-allocations/${effectiveId.value}`, payload)
      } else {
        await api.post('/mushroom-allocations', payload)
      }
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
