<template>
  <div class="p-6 space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-house-blank text-violet-600"></i>
          รายการครัวเรือน
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">จัดการข้อมูลครัวเรือนเปราะบางในจังหวัดนครราชสีมา</p>
      </div>
      <div class="flex gap-2">
        <Button label="ส่งออก CSV" icon="fi fi-rr-download" severity="secondary" outlined @click="exportCsv" />
        <Button label="เพิ่มรายการ" icon="fi fi-rr-plus" @click="openCreate" />
      </div>
    </div>

    <!-- Filter box -->
    <div class="box-card p-4">
      <div class="flex items-center gap-2 mb-3 text-sm text-violet-700 font-semibold">
        <i class="fi fi-rr-filter"></i> ตัวกรอง
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <IconField>
          <InputIcon class="fi fi-rr-search text-slate-400" />
          <InputText v-model="filters.search" @input="onFilterChange" placeholder="ค้นหารหัสบ้าน/ชื่อ/บัตร..." class="w-full" />
        </IconField>
        <Select
          v-model="filters.district"
          :options="districtOptions"
          placeholder="ทุกอำเภอ"
          showClear filter
          @change="onFilterChange"
          class="w-full"
        />
        <Select
          v-model="filters.priority"
          :options="priorityOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="ทุก Priority"
          showClear
          @change="onFilterChange"
          class="w-full"
        />
        <Select
          v-model="filters.passed"
          :options="passedOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="ทุกสถานะ"
          showClear
          @change="onFilterChange"
          class="w-full"
        />
      </div>
    </div>

    <!-- DataTable -->
    <div class="box-card p-0 overflow-hidden">
      <DataTable
        :value="items"
        :loading="loading"
        stripedRows
        scrollable
        scrollHeight="62vh"
        :pt="{ table: { class: 'text-sm' } }"
      >
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="fi fi-rr-info text-3xl"></i>
            <p class="mt-2">ไม่พบข้อมูลครัวเรือน</p>
          </div>
        </template>
        <template #loading>
          <div class="text-center py-12 text-violet-400">
            <i class="fi fi-rr-loading text-3xl animate-spin"></i>
          </div>
        </template>

        <Column field="household_code" header="รหัสบ้าน" sortable :style="{ minWidth: '130px' }">
          <template #body="{ data }">
            <span class="font-mono text-violet-700 font-medium">{{ data.household_code }}</span>
          </template>
        </Column>
        <Column header="ชื่อ" :style="{ minWidth: '200px' }">
          <template #body="{ data }">
            <div class="font-medium text-slate-800">
              {{ data.prefix }} {{ data.first_name }} {{ data.last_name }}
            </div>
            <div v-if="data.id_card" class="text-xs text-slate-400 font-mono">{{ data.id_card }}</div>
          </template>
        </Column>
        <Column field="age" header="อายุ" sortable :style="{ width: '70px' }">
          <template #body="{ data }">{{ data.age ?? '-' }}</template>
        </Column>
        <Column field="health" header="สุขภาพ" :style="{ minWidth: '140px' }">
          <template #body="{ data }">
            <span :class="['text-xs', data.health === 'ปกติ' ? 'text-emerald-600' : 'text-amber-700']">
              {{ data.health || '-' }}
            </span>
          </template>
        </Column>
        <Column field="district" header="อำเภอ" sortable :style="{ minWidth: '120px' }" />
        <Column field="sub_district" header="ตำบล" :style="{ minWidth: '110px' }" />
        <Column field="moo_number" header="หมู่ที่" :style="{ width: '70px' }">
          <template #body="{ data }">{{ data.moo_number ?? '-' }}</template>
        </Column>
        <Column field="village" header="หมู่บ้าน" :style="{ minWidth: '140px' }">
          <template #body="{ data }">{{ data.village ?? '-' }}</template>
        </Column>
        <Column field="house_number" header="บ้านเลขที่" :style="{ width: '90px' }">
          <template #body="{ data }">{{ data.house_number ?? '-' }}</template>
        </Column>
        <Column header="Priority" :style="{ width: '90px' }">
          <template #body="{ data }">
            <StatusBadge v-if="data.priority" :status="data.priority" :label="data.priority" />
            <span v-else class="text-slate-300 text-xs">-</span>
          </template>
        </Column>
        <Column header="สถานะ" :style="{ width: '110px' }">
          <template #body="{ data }">
            <StatusBadge
              :status="data.passed ? 'success' : 'failed'"
              :label="data.passed ? 'ผ่าน' : 'ไม่ผ่าน'"
            />
          </template>
        </Column>
        <Column header="จัดการ" :style="{ width: '140px' }">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="fi fi-rr-eye"   severity="info" text rounded @click="openView(data)"   v-tooltip.top="'ดูรายละเอียด'" />
              <Button icon="fi fi-rr-edit"  severity="warn" text rounded @click="openEdit(data)"   v-tooltip.top="'แก้ไข'" />
              <Button icon="fi fi-rr-trash" severity="danger" text rounded @click="confirmDelete(data)" v-tooltip.top="'ลบ'" />
            </div>
          </template>
        </Column>
      </DataTable>

      <Pagination :meta="meta" @change="onPage" class="p-4" />
    </div>

    <!-- View Dialog (read-only) -->
    <Dialog v-model:visible="viewOpen" modal :draggable="false" :style="{ width: '900px' }">
      <template #header>
        <div class="flex items-center gap-3 w-full">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center shadow-md">
            <i class="fi fi-rr-house-blank"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-800">รายละเอียดครัวเรือน</h3>
            <p class="text-xs text-slate-500">{{ viewItem?.household_code }}</p>
          </div>
        </div>
      </template>

      <div v-if="viewItem" class="space-y-4">
        <FormSection title="ข้อมูลพื้นฐาน" icon="fi fi-rr-id-badge" tone="violet">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <Field label="รหัสบ้าน" :value="viewItem.household_code" />
            <Field label="ชื่อ-นามสกุล" :value="`${viewItem.prefix || ''} ${viewItem.first_name || ''} ${viewItem.last_name || ''}`.trim()" />
            <Field label="บัตรประชาชน" :value="viewItem.id_card" />
            <Field label="วัน/เดือน/ปีเกิด" :value="viewItem.dob" />
            <Field label="อายุ" :value="viewItem.age" />
            <Field label="เพศ" :value="viewItem.gender" />
            <Field label="การศึกษา" :value="viewItem.education" />
            <Field label="สุขภาพ" :value="viewItem.health" />
            <Field label="เบอร์โทร" :value="viewItem.phone" />
          </div>
        </FormSection>

        <FormSection title="ที่อยู่" icon="fi fi-rr-marker" tone="sky">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <Field label="จังหวัด" :value="viewItem.province" />
            <Field label="อำเภอ" :value="viewItem.district" />
            <Field label="ตำบล" :value="viewItem.sub_district" />
            <Field label="หมู่ที่" :value="viewItem.moo_number" />
            <Field label="หมู่บ้าน" :value="viewItem.village" />
            <Field label="บ้านเลขที่" :value="viewItem.house_number" />
          </div>
        </FormSection>

        <FormSection title="เศรษฐกิจครัวเรือน" icon="fi fi-rr-money-bill-wave" tone="amber">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <Field label="อาชีพหลัก" :value="viewItem.main_occupation" />
            <Field label="อาชีพเสริม" :value="viewItem.secondary_occupation" />
            <Field label="รายได้/เดือน" :value="fmtMoney(viewItem.income_month)" />
            <Field label="รายจ่าย/เดือน" :value="fmtMoney(viewItem.expense_month)" />
            <Field label="หนี้สิน" :value="fmtMoney(viewItem.debt_amount)" />
            <Field label="แหล่งเงินกู้" :value="viewItem.debt_source" />
          </div>
        </FormSection>

        <FormSection title="คะแนนประเมิน" icon="fi fi-rr-chart-pie-alt" tone="fuchsia">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <Field label="ความยากจน" :value="viewItem.poverty_score" />
            <Field label="แรงจูงใจ" :value="viewItem.motivation_score" />
            <Field label="ประสบการณ์" :value="viewItem.experience_score" />
            <Field label="กลุ่ม" :value="viewItem.grouping_score" />
            <Field label="ศักยภาพ" :value="viewItem.potential_score" />
            <Field label="พื้นที่" :value="viewItem.area_score" />
            <Field label="การตลาด" :value="viewItem.market_score" />
            <Field label="คะแนนรวม" :value="viewItem.total_score" highlight />
          </div>
          <div class="mt-3 flex items-center gap-3">
            <span class="text-xs text-slate-500">Priority:</span>
            <StatusBadge v-if="viewItem.priority" :status="viewItem.priority" :label="viewItem.priority" />
            <span class="text-xs text-slate-500 ml-3">สถานะ:</span>
            <StatusBadge :status="viewItem.passed ? 'success' : 'failed'" :label="viewItem.passed ? 'ผ่าน' : 'ไม่ผ่าน'" />
          </div>
        </FormSection>
      </div>

      <template #footer>
        <Button label="ปิด" icon="fi fi-rr-cross-small" severity="secondary" outlined @click="viewOpen = false" />
      </template>
    </Dialog>

    <!-- Edit/Create placeholder dialog (full form coming next) -->
    <Dialog v-model:visible="editOpen" modal :draggable="false" :style="{ width: '500px' }" header="กำลังพัฒนา">
      <div class="text-center py-6">
        <i class="fi fi-rr-tools text-4xl text-violet-500"></i>
        <p class="mt-3 text-slate-600">ฟอร์มเพิ่ม/แก้ไขครัวเรือน (50+ ช่อง) อยู่ระหว่างพัฒนา</p>
        <p class="text-xs text-slate-400 mt-1">จะเปิดใช้ในรอบถัดไป</p>
      </div>
      <template #footer>
        <Button label="เข้าใจแล้ว" @click="editOpen = false" />
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

import Pagination from '../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import FormSection from '../../components/FormSection.vue'

const props = defineProps({
  autoCreate: { type: Boolean, default: false },
})

const vTooltip = Tooltip
const confirm = useConfirm()
const toast = useToast()

const items = ref([])
const meta = ref({})
const loading = ref(false)
const filters = ref({ search: '', district: null, priority: null, passed: null })

const viewOpen = ref(false)
const viewItem = ref(null)
const editOpen = ref(false)

let currentPage = 1
let filterTimer = null

const priorityOptions = [
  { label: 'A (สูงสุด)', value: 'A' },
  { label: 'B (สูง)',   value: 'B' },
  { label: 'C (กลาง)',  value: 'C' },
  { label: 'D (ต่ำ)',    value: 'D' },
]
const passedOptions = [
  { label: 'ผ่าน',      value: 1 },
  { label: 'ไม่ผ่าน', value: 0 },
]
const districtOptions = ref([])

function fmtMoney(v) {
  return v == null ? '-' : Number(v).toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' บ.'
}

async function fetchData() {
  loading.value = true
  try {
    const params = { page: currentPage, per_page: 20 }
    if (filters.value.search)   params.search   = filters.value.search
    if (filters.value.district) params.district = filters.value.district
    if (filters.value.priority) params.priority = filters.value.priority
    if (filters.value.passed !== null) params.passed = filters.value.passed
    const { data } = await api.get('/households', { params })
    items.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } catch {
    toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: 'โหลดข้อมูลไม่สำเร็จ', life: 3000 })
  } finally {
    loading.value = false
  }
}

async function fetchDistricts() {
  try {
    const { data } = await api.get('/reports/districts')
    districtOptions.value = data
  } catch {}
}

function onFilterChange() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    currentPage = 1
    fetchData()
  }, 300)
}
function onPage(p) { currentPage = p; fetchData() }

function openCreate() { editOpen.value = true }
function openEdit(item) { editOpen.value = true }
function openView(item) {
  viewItem.value = item
  viewOpen.value = true
}

function exportCsv() {
  toast.add({ severity: 'info', summary: 'กำลังพัฒนา', detail: 'ฟีเจอร์ Export CSV จะมาในรอบถัดไป', life: 2500 })
}

function confirmDelete(item) {
  confirm.require({
    message: `ต้องการลบครัวเรือน ${item.household_code} (${item.first_name} ${item.last_name}) ใช่หรือไม่?`,
    header: 'ยืนยันการลบ',
    icon: 'fi fi-rr-triangle-warning',
    rejectLabel: 'ยกเลิก',
    acceptLabel: 'ลบ',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/households/${item.id}`)
        toast.add({ severity: 'success', summary: 'ลบสำเร็จ', life: 2000 })
        fetchData()
      } catch (e) {
        toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: e.response?.data?.message || 'ลบไม่สำเร็จ', life: 3000 })
      }
    },
  })
}

// Inline Field display helper
const Field = {
  props: ['label', 'value', 'highlight'],
  setup(p) {
    return () => h('div', { class: 'min-w-0' }, [
      h('p', { class: 'text-[11px] uppercase tracking-wide text-slate-400 mb-0.5' }, p.label),
      h('p', {
        class: ['truncate', p.highlight ? 'text-violet-700 font-bold text-base' : 'text-slate-700 font-medium'],
      }, p.value === null || p.value === undefined || p.value === '' ? '-' : String(p.value)),
    ])
  },
}

onMounted(() => {
  fetchData()
  fetchDistricts()
  if (props.autoCreate) editOpen.value = true
})
</script>
