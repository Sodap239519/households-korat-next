<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
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
        <Button label="ส่งออก CSV" icon="fi fi-rr-download" severity="secondary" outlined :loading="exporting" @click="exportCsv" />
        <Button label="เพิ่มรายการ" icon="fi fi-rr-plus" @click="$router.push('/app/households/create')" />
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
        <template #footer>
          <div class="flex items-center justify-between text-sm text-slate-600 px-2">
            <span>รวมทั้งหมด: <span class="font-semibold text-violet-700">{{ Number(meta.total || 0).toLocaleString() }}</span> รายการ</span>
            <span class="text-xs text-slate-400">หน้า {{ meta.current_page }} / {{ meta.last_page || 1 }}</span>
          </div>
        </template>

        <Column header="#" :style="{ width: '60px' }">
          <template #body="{ index }">
            <span class="text-xs text-slate-400 font-medium">{{ ((meta.current_page || 1) - 1) * 20 + index + 1 }}</span>
          </template>
        </Column>
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
    <Dialog v-model:visible="viewOpen" modal :draggable="false" :style="{ width: '1000px' }" :breakpoints="{ '1024px': '95vw', '767px': '100vw' }" :contentStyle="{ maxHeight: '80vh' }">
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
        <!-- Priority + Status hero -->
        <div class="rounded-2xl bg-gradient-to-br from-violet-50 via-fuchsia-50 to-rose-50 border-2 border-violet-200 p-5 sm:p-6 relative overflow-hidden">
          <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-violet-200/40 blur-3xl pointer-events-none"></div>
          <div class="relative grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
            <div class="flex items-center gap-4">
              <div :class="['flex-shrink-0 inline-flex items-center justify-center w-20 h-20 rounded-2xl border-2 font-extrabold text-3xl shadow-lg',
                priorityClass]">
                {{ viewItem.priority || '?' }}
              </div>
              <div>
                <p class="text-[11px] uppercase tracking-wide text-slate-500">Priority</p>
                <p class="text-base sm:text-lg font-bold text-slate-800 leading-tight mt-0.5">
                  {{ priorityLabel }}
                </p>
                <p class="text-xs text-slate-500 mt-0.5">คะแนนรวม {{ viewItem.total_score || 0 }} / 700</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <i :class="['text-3xl', viewItem.passed ? 'fi fi-rr-shield-check text-emerald-600' : 'fi fi-rr-cross-circle text-rose-600']"></i>
              <div>
                <p class="text-[11px] uppercase tracking-wide text-slate-500">สถานะ</p>
                <p :class="['text-base sm:text-lg font-bold leading-tight mt-0.5', viewItem.passed ? 'text-emerald-700' : 'text-rose-700']">
                  {{ viewItem.passed ? 'ผ่านเกณฑ์' : 'ไม่ผ่านเกณฑ์' }}
                </p>
                <p class="text-xs text-slate-500 mt-0.5">
                  {{ viewItem.completed ? 'ดำเนินการเสร็จสิ้น' : 'อยู่ระหว่างดำเนินการ' }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <i class="fi fi-rr-user text-3xl text-violet-600"></i>
              <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-wide text-slate-500">ผู้เปราะบาง</p>
                <p class="text-base sm:text-lg font-bold text-slate-800 leading-tight mt-0.5 truncate">
                  {{ `${viewItem.prefix || ''} ${viewItem.first_name || ''} ${viewItem.last_name || ''}`.trim() }}
                </p>
                <p class="text-xs text-slate-500 mt-0.5">
                  {{ viewItem.gender || '-' }} · {{ viewItem.age != null ? `อายุ ${viewItem.age} ปี` : 'ไม่ระบุอายุ' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 1: ข้อมูลครัวเรือน -->
        <FormSection title="ส่วนที่ 1: ข้อมูลครัวเรือน" icon="fi fi-rr-house-blank" tone="violet">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <Field label="รหัสบ้าน" :value="viewItem.household_code" />
            <Field label="จังหวัด"  :value="viewItem.province" />
            <Field label="อำเภอ"     :value="viewItem.district" />
            <Field label="ตำบล"      :value="viewItem.sub_district" />
            <Field label="หมู่ที่"   :value="viewItem.moo_number" />
            <Field label="หมู่บ้าน" :value="viewItem.village" />
            <Field label="บ้านเลขที่" :value="viewItem.house_number" />
            <Field label="รหัสไปรษณีย์" :value="viewItem.postal_code" />
            <Field label="จำนวนสมาชิก" :value="viewItem.members_count" />
            <div class="col-span-2 md:col-span-3">
              <Field label="ชื่อ-นามสกุลหัวหน้าครัวเรือน" :value="viewItem.head_full_name" />
            </div>
          </div>
        </FormSection>

        <!-- Section 2: ข้อมูลผู้เปราะบาง -->
        <FormSection title="ส่วนที่ 2: ข้อมูลผู้เปราะบาง" icon="fi fi-rr-id-badge" tone="fuchsia">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <Field label="คำนำหน้า" :value="viewItem.prefix" />
            <Field label="ชื่อ"        :value="viewItem.first_name" />
            <Field label="นามสกุล"   :value="viewItem.last_name" />
            <Field label="บัตรประชาชน" :value="viewItem.id_card" />
            <Field label="เพศ"      :value="viewItem.gender" />
            <Field label="วัน/เดือน/ปีเกิด" :value="fmtThaiDate(viewItem.dob)" />
            <Field label="อายุ"     :value="viewItem.age" />
            <Field label="เบอร์โทรศัพท์" :value="viewItem.phone" />
            <Field label="การศึกษา" :value="viewItem.education" />
            <div class="col-span-2 md:col-span-3">
              <Field label="สุขภาพ" :value="viewItem.health" />
            </div>
          </div>
        </FormSection>

        <!-- Section 3: เศรษฐกิจ -->
        <FormSection title="ส่วนที่ 3: เศรษฐกิจครัวเรือน" icon="fi fi-rr-money-bill-wave" tone="amber">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <Field label="อาชีพหลัก" :value="viewItem.main_occupation" />
            <Field label="อาชีพเสริม" :value="viewItem.secondary_occupation" />
            <Field label="รายได้/เดือน" :value="fmtMoney(viewItem.income_month)" tone="emerald" />
            <Field label="รายจ่าย/เดือน" :value="fmtMoney(viewItem.expense_month)" tone="amber" />
            <Field label="หนี้สิน" :value="fmtMoney(viewItem.debt_amount)" tone="rose" />
            <Field label="แหล่งเงินกู้" :value="viewItem.debt_source" />
          </div>
        </FormSection>

        <!-- Section 4: เห็ด/เกษตร -->
        <FormSection title="ส่วนที่ 4: เห็ด · เกษตร · ความพร้อม" icon="fi fi-rr-mushroom" tone="emerald">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <Field label="มีพื้นที่เพาะเห็ด" :value="boolLabel(viewItem.has_mushroom_area)" />
            <Field label="ขนาดพื้นที่ (ตร.ม.)" :value="viewItem.mushroom_area_size" />
            <Field label="น้ำใช้" :value="viewItem.water_source" />
            <Field label="มีไฟฟ้า" :value="boolLabel(viewItem.has_electricity)" />
            <Field label="ระยะถึงตลาด (กม.)" :value="viewItem.distance_to_market_km" />
            <Field label="เคยทำเกษตร" :value="boolLabel(viewItem.ever_agriculture)" />
            <Field label="เคยเพาะเห็ด" :value="boolLabel(viewItem.ever_mushroom)" />
            <Field label="ใช้สมาร์ทโฟน" :value="viewItem.smartphone_use" />
            <Field label="ใช้โซเชียล" :value="boolLabel(viewItem.social_media_use)" />
            <Field label="ระดับความสนใจ" :value="viewItem.interest_level" />
            <Field label="ชั่วโมง/สัปดาห์" :value="viewItem.hours_per_week" />
            <Field label="เงินลงทุนเริ่มต้น" :value="fmtMoney(viewItem.initial_investment)" />
            <Field label="สมาชิกกลุ่ม" :value="boolLabel(viewItem.group_member)" />
            <Field label="ความพร้อมรวมกลุ่ม" :value="viewItem.group_readiness" />
            <div></div>
            <div class="col-span-2 md:col-span-3">
              <Field label="เหตุผลที่สนใจ" :value="viewItem.interest_reason" />
            </div>
          </div>
        </FormSection>

        <!-- Section 5: คะแนน -->
        <FormSection title="ส่วนที่ 5: คะแนนประเมิน" icon="fi fi-rr-chart-pie-alt" tone="rose">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <Field label="ความยากจน"  :value="viewItem.poverty_score" />
            <Field label="แรงจูงใจ"    :value="viewItem.motivation_score" />
            <Field label="ประสบการณ์" :value="viewItem.experience_score" />
            <Field label="การรวมกลุ่ม"  :value="viewItem.grouping_score" />
            <Field label="ศักยภาพ"     :value="viewItem.potential_score" />
            <Field label="พื้นที่"     :value="viewItem.area_score" />
            <Field label="การตลาด"   :value="viewItem.market_score" />
            <Field label="คะแนนรวม" :value="viewItem.total_score" highlight />
          </div>
        </FormSection>

        <!-- Section 6: ผู้สำรวจ -->
        <FormSection title="ส่วนที่ 6: ผู้สำรวจ และหมายเหตุ" icon="fi fi-rr-clipboard-user" tone="sky">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <Field label="วันที่สำรวจ" :value="fmtThaiDate(viewItem.survey_date)" />
            <Field label="ชื่อผู้สำรวจ" :value="viewItem.surveyor" />
            <Field label="เปิดใช้งาน" :value="viewItem.is_active ? 'ใช่' : 'ปิด'" />
            <div class="col-span-2 md:col-span-3">
              <Field label="หมายเหตุ" :value="viewItem.note" />
            </div>
          </div>
        </FormSection>
      </div>

      <template #footer>
        <Button label="ปิด" icon="fi fi-rr-cross-small" severity="secondary" outlined @click="viewOpen = false" />
      </template>
    </Dialog>

    <!-- Full Edit/Create Dialog -->
    <HouseholdFormDialog v-model="editOpen" :householdId="editId" @saved="onSaved" />

    <ConfirmDialog />
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h, defineComponent } from 'vue'
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
import HouseholdFormDialog from './HouseholdFormDialog.vue'
import { fmtThaiDate } from '../../utils/date.js'

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
const editId = ref(null)

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

function boolLabel(v) {
  if (v == null) return '-'
  return v ? 'ใช่' : 'ไม่'
}

const PRIORITY_BADGE_CLASS = {
  A: 'bg-slate-800 text-white border-slate-800',
  B: 'bg-sky-400 text-white border-sky-400',
  C: 'bg-amber-200 text-amber-900 border-amber-300',
  D: 'bg-pink-300 text-pink-900 border-pink-400',
}
const PRIORITY_TEXT = {
  A: 'A · ลำดับสูงสุด (เร่งด่วน)',
  B: 'B · ลำดับสูง',
  C: 'C · ลำดับปานกลาง',
  D: 'D · ลำดับต่ำ',
}
const priorityClass = computed(() => PRIORITY_BADGE_CLASS[viewItem.value?.priority] || 'bg-slate-100 text-slate-400 border-slate-200')
const priorityLabel = computed(() => PRIORITY_TEXT[viewItem.value?.priority] || 'ยังไม่ได้ประเมิน')

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
    const { data } = await api.get('/locations/districts')
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

function openCreate() { editId.value = null; editOpen.value = true }
function openEdit(item) { editId.value = item.id; editOpen.value = true }
function openView(item) {
  viewItem.value = item
  viewOpen.value = true
}
function onSaved() {
  toast.add({ severity: 'success', summary: 'สำเร็จ', detail: editId.value ? 'แก้ไขข้อมูลแล้ว' : 'เพิ่มครัวเรือนแล้ว', life: 2500 })
  fetchData()
}

const exporting = ref(false)
async function exportCsv() {
  exporting.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.search)   params.append('search',   filters.value.search)
    if (filters.value.district) params.append('district', filters.value.district)
    if (filters.value.priority) params.append('priority', filters.value.priority)
    if (filters.value.passed !== null && filters.value.passed !== undefined) {
      params.append('passed', filters.value.passed)
    }
    const res = await api.get('/households/export?' + params.toString(), { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([res.data], { type: 'text/csv;charset=utf-8' }))
    const a = document.createElement('a')
    a.href = url
    a.download = `households_${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
    toast.add({ severity: 'success', summary: 'ส่งออกสำเร็จ', life: 2000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: 'ส่งออกไม่สำเร็จ', life: 3000 })
  } finally {
    exporting.value = false
  }
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
const TONE_TEXT = { emerald: 'text-emerald-700', amber: 'text-amber-700', rose: 'text-rose-700' }
const Field = defineComponent({
  props: ['label', 'value', 'highlight', 'tone'],
  setup(p) {
    return () => h('div', { class: 'min-w-0' }, [
      h('p', { class: 'text-[11px] uppercase tracking-wide text-slate-400 mb-0.5' }, p.label),
      h('p', {
        class: [
          'truncate',
          p.highlight ? 'text-violet-700 font-bold text-base'
            : `font-medium ${TONE_TEXT[p.tone] || 'text-slate-700'}`,
        ],
      }, p.value === null || p.value === undefined || p.value === '' ? '-' : String(p.value)),
    ])
  },
})

onMounted(() => {
  fetchData()
  fetchDistricts()
  if (props.autoCreate) editOpen.value = true
})
</script>
