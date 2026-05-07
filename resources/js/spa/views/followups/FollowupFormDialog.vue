<template>
  <Dialog
    v-model:visible="visible"
    modal
    :draggable="false"
    :style="{ width: '820px' }"
    :breakpoints="{ '767px': '95vw' }"
    :closeOnEscape="!saving"
    :pt="{ root: { class: 'rounded-2xl overflow-hidden' } }"
  >
    <template #header>
      <div class="flex items-center gap-3 w-full">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-rose-500 text-white flex items-center justify-center shadow-md">
          <i :class="isEditMode ? 'fi fi-rr-edit' : 'fi fi-rr-plus'"></i>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-800">{{ isEditMode ? 'แก้ไขการติดตามผล' : 'เพิ่มการติดตามผล' }}</h3>
          <p class="text-xs text-slate-500">บันทึกผลผลิตและรายได้</p>
        </div>
      </div>
    </template>

    <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <FormSection title="เลือกการจัดสรร" icon="fi fi-rr-house-blank" tone="violet">
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
            :disabled="isEditMode"
            @change="onAllocationChange"
          />
        </div>
      </FormSection>

      <!-- History table — shown when allocation selected and has past followups -->
      <FormSection
        v-if="!isEditMode && form.allocation_id && history.length"
        :title="`ประวัติการติดตามของรายการนี้ (${history.length} รอบ)`"
        icon="fi fi-rr-time-past"
        tone="fuchsia"
      >
        <div class="overflow-x-auto -mx-1">
          <table class="w-full text-xs">
            <thead>
              <tr class="text-left text-slate-500 border-b border-fuchsia-100">
                <th class="px-2 py-1.5 font-medium">รอบ</th>
                <th class="px-2 py-1.5 font-medium">วันที่</th>
                <th class="px-2 py-1.5 font-medium text-right">ผลิต (กก.)</th>
                <th class="px-2 py-1.5 font-medium text-right">ขาย (กก.)</th>
                <th class="px-2 py-1.5 font-medium text-right">รายได้</th>
                <th class="px-2 py-1.5 font-medium">ช่องทาง</th>
                <th class="px-2 py-1.5 font-medium text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="h in history" :key="h.id"
                  :class="['border-b border-slate-100 hover:bg-fuchsia-50/40', editingHistoryId === h.id ? 'bg-fuchsia-100/60' : '']">
                <td class="px-2 py-1.5">
                  <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-fuchsia-100 text-fuchsia-700 font-semibold">{{ h.followup_round }}</span>
                </td>
                <td class="px-2 py-1.5 whitespace-nowrap">{{ fmtThaiDate(h.followup_date, { short: true }) }}</td>
                <td class="px-2 py-1.5 text-right">{{ Number(h.harvest_kg || 0).toFixed(2) }}</td>
                <td class="px-2 py-1.5 text-right">{{ Number(h.sold_kg || 0).toFixed(2) }}</td>
                <td class="px-2 py-1.5 text-right font-semibold text-emerald-700">{{ Number(h.revenue || 0).toLocaleString() }}</td>
                <td class="px-2 py-1.5">
                  <StatusBadge v-if="h.sale_channel" :status="h.sale_channel" :label="CHANNEL_LABEL[h.sale_channel] || h.sale_channel" />
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="px-2 py-1.5 text-center">
                  <Button v-if="editingHistoryId !== h.id"
                          icon="fi fi-rr-edit" severity="info" text rounded size="small"
                          v-tooltip.top="'แก้ไขรอบนี้'" @click="editFromHistory(h)" />
                  <Button v-else label="กำลังแก้ไข" severity="info" size="small" @click="cancelEditHistory" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3 flex items-center gap-2 flex-wrap">
          <p class="text-xs text-slate-500 flex-1">
            <i class="fi fi-rr-info text-fuchsia-500"></i>
            มีการติดตามแล้ว {{ history.length }} รอบ · ผลผลิตรวม {{ totalHarvest }} กก. · รายได้รวม {{ totalRevenue }} บาท
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

      <FormSection
        :title="isEditMode ? 'แก้ไขรอบที่กำลังเลือก' : `บันทึกรอบที่ ${form.followup_round}`"
        icon="fi fi-rr-list-check"
        tone="amber"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">
              รอบติดตาม <span class="text-rose-500">*</span>
            </label>
            <InputNumber v-model="form.followup_round" :min="1" required fluid />
            <p v-if="!isEditMode && history.length" class="text-[11px] text-violet-600 mt-1">
              <i class="fi fi-rr-info"></i> ระบบตั้งให้เป็นรอบที่ {{ form.followup_round }} อัตโนมัติ (ต่อจากรอบล่าสุด)
            </p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">วันที่ติดตาม</label>
            <DatePicker v-model="form.followup_date" dateFormat="dd/mm/yy" showIcon fluid />
          </div>
        </div>
      </FormSection>

      <FormSection title="ผลผลิตและการขาย" icon="fi fi-rr-mushroom" tone="emerald">
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
        :label="saving ? 'กำลังบันทึก...' : (isEditMode ? 'บันทึกการแก้ไข' : 'เพิ่มการติดตาม')"
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
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  followupId: { type: [Number, String, null], default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const editingHistoryId = ref(null)
const effectiveId = computed(() => editingHistoryId.value || props.followupId)
const isEditMode  = computed(() => !!effectiveId.value)

const channelOptions = [
  { label: 'ขายตรง',   value: 'direct' },
  { label: 'ออนไลน์', value: 'online' },
  { label: 'วิสาหกิจ', value: 'enterprise' },
  { label: 'ตลาด',     value: 'market' },
]
const CHANNEL_LABEL = {
  direct: 'ขายตรง', online: 'ออนไลน์', enterprise: 'วิสาหกิจ', market: 'ตลาด',
}

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
const history = ref([])

const allocationOptions = computed(() => allocations.value.map(a => ({
  label: `${a.household?.first_name || ''} ${a.household?.last_name || ''} (${a.household?.household_code || '-'}) — ${a.quota?.district || ''} ปี ${a.quota?.year || ''} รอบ ${a.quota?.round || ''} | ${a.bags} ถุง`,
  value: a.id,
})))

const totalHarvest = computed(() => history.value.reduce((a, x) => a + Number(x.harvest_kg || 0), 0).toFixed(2))
const totalRevenue = computed(() => history.value.reduce((a, x) => a + Number(x.revenue    || 0), 0).toLocaleString())

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

async function loadHistory(allocationId) {
  if (!allocationId) { history.value = []; return }
  try {
    const { data } = await api.get('/mushroom-followups', {
      params: { allocation_id: allocationId, per_page: 100 },
    })
    history.value = data.data || []
  } catch { history.value = [] }
}

async function onAllocationChange() {
  if (!form.value.allocation_id) {
    history.value = []
    return
  }
  await loadHistory(form.value.allocation_id)
  // Auto-set next round in create mode
  if (!isEditMode.value) {
    const maxRound = history.value.reduce((m, h) => Math.max(m, Number(h.followup_round) || 0), 0)
    form.value.followup_round = maxRound + 1
  }
}

function editFromHistory(row) {
  editingHistoryId.value = row.id
  form.value = {
    allocation_id:     row.allocation_id,
    followup_round:    row.followup_round,
    followup_date:     row.followup_date ? new Date(row.followup_date) : null,
    harvest_kg:        row.harvest_kg,
    sold_kg:           row.sold_kg,
    price_per_kg:      row.price_per_kg,
    revenue:           row.revenue,
    sale_channel:      row.sale_channel,
    sale_place:        row.sale_place ?? '',
    enterprise_member: !!row.enterprise_member,
    enterprise_name:   row.enterprise_name ?? '',
    note:              row.note ?? '',
  }
  scrollDialogToBottom()
}

function cancelEditHistory() {
  editingHistoryId.value = null
  startNewRound()
}

function startNewRound() {
  editingHistoryId.value = null
  const maxRound = history.value.reduce((m, h) => Math.max(m, Number(h.followup_round) || 0), 0)
  form.value = {
    ...defaultForm(),
    allocation_id: form.value.allocation_id,
    followup_round: maxRound + 1,
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
  await loadAllocations()
  if (props.followupId) {
    try {
      const { data } = await api.get(`/mushroom-followups/${props.followupId}`)
      form.value = {
        ...data,
        followup_date: data.followup_date ? new Date(data.followup_date) : null,
        sale_place: data.sale_place ?? '',
        enterprise_name: data.enterprise_name ?? '',
        note: data.note ?? '',
      }
      await loadHistory(data.allocation_id)
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
    if (payload.followup_date instanceof Date) payload.followup_date = formatDate(payload.followup_date)
    if (effectiveId.value) {
      await api.put(`/mushroom-followups/${effectiveId.value}`, payload)
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
