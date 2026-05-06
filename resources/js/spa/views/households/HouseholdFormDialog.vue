<template>
  <Dialog
    v-model:visible="visible"
    modal
    :draggable="false"
    :style="{ width: '1100px' }"
    :contentStyle="{ maxHeight: '78vh' }"
    :closeOnEscape="!saving"
    :pt="{ root: { class: 'rounded-2xl overflow-hidden' } }"
  >
    <template #header>
      <div class="flex items-center gap-3 w-full">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center shadow-md">
          <i :class="isEdit ? 'fi fi-rr-edit' : 'fi fi-rr-add-document'"></i>
        </div>
        <div class="flex-1">
          <h3 class="text-lg font-bold text-slate-800">
            {{ isEdit ? 'แก้ไขข้อมูลครัวเรือน' : 'เพิ่มข้อมูลครัวเรือน' }}
          </h3>
          <p class="text-xs text-slate-500">{{ form.household_code || 'กรอกข้อมูลให้ครบทั้ง 6 ส่วน' }}</p>
        </div>
      </div>
    </template>

    <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

    <form @submit.prevent="handleSubmit" class="space-y-5">

      <!-- Section 1: ข้อมูลครัวเรือน -->
      <FormSection title="ส่วนที่ 1: ข้อมูลครัวเรือน" icon="fi fi-rr-house-blank" tone="violet">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Field label="รหัสบ้าน" required>
            <InputText v-model="form.household_code" required placeholder="11 หลัก" class="w-full" />
          </Field>
          <Field label="จังหวัด">
            <Select v-model="form.province" :options="locations.provinces" placeholder="-- เลือก --" showClear editable filter class="w-full" />
          </Field>
          <Field label="อำเภอ">
            <Select v-model="form.district" :options="locations.districts" placeholder="-- เลือก --" showClear editable filter class="w-full" @change="onDistrictChange" />
          </Field>
          <Field label="ตำบล">
            <Select v-model="form.sub_district" :options="locations.subDistricts" placeholder="-- เลือก --" showClear editable filter class="w-full" @change="onSubDistrictChange" />
          </Field>
          <Field label="หมู่ที่"><InputText v-model="form.moo_number" class="w-full" /></Field>
          <Field label="หมู่บ้าน">
            <Select v-model="form.village" :options="locations.villages" placeholder="-- เลือก --" showClear editable filter class="w-full" />
          </Field>
          <Field label="บ้านเลขที่"><InputText v-model="form.house_number" class="w-full" /></Field>
          <Field label="รหัสไปรษณีย์"><InputText v-model="form.postal_code" class="w-full" /></Field>
          <Field label="จำนวนสมาชิก">
            <InputNumber v-model="form.members_count" :min="1" :max="100" fluid />
          </Field>
          <div class="md:col-span-3">
            <Field label="ชื่อ-นามสกุลหัวหน้าครัวเรือน">
              <InputText v-model="form.head_full_name" class="w-full" placeholder="ชื่อ-นามสกุล" />
            </Field>
          </div>
        </div>
      </FormSection>

      <!-- Section 2: ข้อมูลผู้เปราะบาง -->
      <FormSection title="ส่วนที่ 2: ข้อมูลผู้เปราะบาง" icon="fi fi-rr-id-badge" tone="fuchsia">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Field label="คำนำหน้า">
            <Select v-model="form.prefix" :options="OPT.prefix" placeholder="-- เลือก --" showClear class="w-full" />
          </Field>
          <Field label="ชื่อ" required>
            <InputText v-model="form.first_name" required class="w-full" />
          </Field>
          <Field label="นามสกุล" required>
            <InputText v-model="form.last_name" required class="w-full" />
          </Field>
          <Field label="บัตรประชาชน">
            <InputText v-model="form.id_card" maxlength="13" class="w-full" placeholder="13 หลัก" />
          </Field>
          <Field label="เพศ">
            <Select v-model="form.gender" :options="OPT.gender" placeholder="-- เลือก --" showClear class="w-full" />
          </Field>
          <Field label="วันเกิด">
            <DatePicker v-model="form.dob" dateFormat="dd/mm/yy" showIcon fluid />
          </Field>
          <Field label="อายุ (ปี)">
            <InputNumber v-model="form.age" :min="0" :max="120" fluid />
          </Field>
          <Field label="เบอร์โทรศัพท์">
            <InputText v-model="form.phone" maxlength="10" class="w-full" placeholder="10 หลัก" />
          </Field>
          <Field label="การศึกษา">
            <Select v-model="form.education" :options="OPT.education" placeholder="-- เลือก --" showClear filter class="w-full" />
          </Field>
          <div class="md:col-span-3">
            <Field label="สุขภาพ">
              <Select v-model="form.health" :options="OPT.health" placeholder="-- เลือก --" showClear editable filter class="w-full" />
            </Field>
          </div>
        </div>
      </FormSection>

      <!-- Section 3: เศรษฐกิจ -->
      <FormSection title="ส่วนที่ 3: เศรษฐกิจครัวเรือน" icon="fi fi-rr-money-bill-wave" tone="amber">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <Field label="อาชีพหลัก"><InputText v-model="form.main_occupation" class="w-full" /></Field>
          <Field label="อาชีพเสริม"><InputText v-model="form.secondary_occupation" class="w-full" /></Field>
          <Field label="รายได้/เดือน (บาท)">
            <InputNumber v-model="form.income_month" :min="0" :minFractionDigits="0" :maxFractionDigits="2" fluid suffix=" บาท" />
          </Field>
          <Field label="รายจ่าย/เดือน (บาท)">
            <InputNumber v-model="form.expense_month" :min="0" :minFractionDigits="0" :maxFractionDigits="2" fluid suffix=" บาท" />
          </Field>
          <Field label="หนี้สินคงค้าง (บาท)">
            <InputNumber v-model="form.debt_amount" :min="0" :minFractionDigits="0" :maxFractionDigits="2" fluid suffix=" บาท" />
          </Field>
          <Field label="แหล่งเงินกู้"><InputText v-model="form.debt_source" class="w-full" placeholder="เช่น กองทุนหมู่บ้าน, ธ.ก.ส." /></Field>
        </div>
        <div v-if="netIncome !== null" class="mt-3 p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs flex items-center gap-4">
          <span class="text-slate-500">ส่วนต่างรายรับ-รายจ่าย:</span>
          <span :class="['font-bold', netIncome >= 0 ? 'text-emerald-700' : 'text-rose-700']">
            {{ netIncome >= 0 ? '+' : '' }}{{ Number(netIncome).toLocaleString() }} บาท/เดือน
          </span>
        </div>
      </FormSection>

      <!-- Section 4: เห็ด/เกษตร -->
      <FormSection title="ส่วนที่ 4: เห็ด · เกษตร · ความพร้อม" icon="fi fi-rr-leaf" tone="emerald">
        <!-- Toggles -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4 p-3 rounded-lg bg-emerald-50/50 border border-emerald-200/50">
          <ToggleField v-model="form.has_mushroom_area"  label="มีพื้นที่เพาะเห็ด" />
          <ToggleField v-model="form.has_electricity"    label="มีไฟฟ้า" />
          <ToggleField v-model="form.ever_agriculture"   label="เคยทำเกษตร" />
          <ToggleField v-model="form.ever_mushroom"      label="เคยเพาะเห็ด" />
          <ToggleField v-model="form.social_media_use"   label="ใช้โซเชียลมีเดีย" />
          <ToggleField v-model="form.group_member"       label="เป็นสมาชิกกลุ่ม/วิสาหกิจ" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Field label="ขนาดพื้นที่ (ตร.ม.)">
            <InputNumber v-model="form.mushroom_area_size" :min="0" :minFractionDigits="0" :maxFractionDigits="2" fluid />
          </Field>
          <Field label="น้ำใช้">
            <Select v-model="form.water_source" :options="OPT.water" placeholder="-- เลือก --" showClear editable class="w-full" />
          </Field>
          <Field label="ระยะถึงตลาด (กม.)">
            <InputNumber v-model="form.distance_to_market_km" :min="0" :minFractionDigits="0" :maxFractionDigits="2" fluid />
          </Field>
          <Field label="การใช้สมาร์ทโฟน">
            <Select v-model="form.smartphone_use" :options="OPT.smartphone" placeholder="-- เลือก --" showClear class="w-full" />
          </Field>
          <Field label="ระดับความสนใจ">
            <Select v-model="form.interest_level" :options="OPT.interest" placeholder="-- เลือก --" showClear class="w-full" />
          </Field>
          <Field label="ความพร้อมรวมกลุ่ม">
            <Select v-model="form.group_readiness" :options="OPT.groupReady" placeholder="-- เลือก --" showClear class="w-full" />
          </Field>
          <Field label="ชั่วโมง/สัปดาห์ที่ทำได้">
            <InputNumber v-model="form.hours_per_week" :min="0" :max="168" fluid suffix=" ชม." />
          </Field>
          <Field label="เงินลงทุนเริ่มต้น (บาท)">
            <InputNumber v-model="form.initial_investment" :min="0" :minFractionDigits="0" :maxFractionDigits="2" fluid suffix=" บาท" />
          </Field>
          <div class="md:col-span-3">
            <Field label="เหตุผลที่สนใจ">
              <Textarea v-model="form.interest_reason" rows="2" autoResize class="w-full" />
            </Field>
          </div>
        </div>
      </FormSection>

      <!-- Section 5: คะแนนประเมิน -->
      <FormSection title="ส่วนที่ 5: คะแนนประเมิน (ระบบคำนวณ Priority อัตโนมัติ)" icon="fi fi-rr-chart-pie-alt" tone="rose">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <ScoreField v-model="form.poverty_score"    label="ความยากจน" />
          <ScoreField v-model="form.motivation_score" label="แรงจูงใจ" />
          <ScoreField v-model="form.experience_score" label="ประสบการณ์" />
          <ScoreField v-model="form.grouping_score"   label="การรวมกลุ่ม" />
          <ScoreField v-model="form.potential_score"  label="ศักยภาพ" />
          <ScoreField v-model="form.area_score"       label="พื้นที่" />
          <ScoreField v-model="form.market_score"     label="การตลาด" />
          <Field label="คะแนนรวม (ระบบคำนวณ)">
            <InputNumber :modelValue="form.total_score" disabled fluid />
          </Field>
        </div>
        <div class="mt-4 p-4 rounded-lg bg-gradient-to-r from-violet-50 via-fuchsia-50 to-rose-50 border-2 border-rose-200/70">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
            <div>
              <p class="text-xs text-slate-500 mb-1">Priority (คำนวณอัตโนมัติจากคะแนนเฉลี่ย)</p>
              <div class="flex items-center gap-2">
                <span v-if="form.priority" :class="['inline-flex items-center justify-center w-12 h-12 rounded-xl font-bold text-xl border-2', priorityClass]">
                  {{ form.priority }}
                </span>
                <span v-else class="text-slate-400 text-sm">รอคะแนน</span>
                <div class="text-xs text-slate-500">
                  <p v-if="form.total_score">คะแนน: <span class="font-semibold text-slate-700">{{ form.total_score }} / 700</span></p>
                  <p v-if="avgScore">เฉลี่ย: <span class="font-semibold text-slate-700">{{ avgScore }} / 100</span></p>
                </div>
              </div>
              <p class="text-[11px] text-slate-500 mt-2">
                เกณฑ์: A ≥ 75 · B ≥ 60 · C ≥ 40 · D &lt; 40
              </p>
            </div>
            <ToggleField v-model="form.passed"    label="ผ่านเกณฑ์" />
            <ToggleField v-model="form.completed" label="ดำเนินการเสร็จสิ้น" />
          </div>
        </div>
      </FormSection>

      <!-- Section 6: ผู้สำรวจ + meta -->
      <FormSection title="ส่วนที่ 6: ผู้สำรวจ และหมายเหตุ" icon="fi fi-rr-clipboard-user" tone="sky">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <Field label="วันที่สำรวจ">
            <DatePicker v-model="form.survey_date" dateFormat="dd/mm/yy" showIcon fluid />
          </Field>
          <Field label="ชื่อผู้สำรวจ"><InputText v-model="form.surveyor" class="w-full" /></Field>
          <div class="md:col-span-2">
            <Field label="หมายเหตุ">
              <Textarea v-model="form.note" rows="3" autoResize class="w-full" />
            </Field>
          </div>
          <ToggleField v-model="form.is_active" label="เปิดใช้งานข้อมูลนี้" />
        </div>
      </FormSection>
    </form>

    <template #footer>
      <Button label="ยกเลิก" severity="secondary" outlined icon="fi fi-rr-cross-small" @click="visible = false" :disabled="saving" />
      <Button
        :label="saving ? 'กำลังบันทึก...' : (isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มครัวเรือน')"
        :loading="saving"
        icon="fi fi-rr-disk"
        @click="handleSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch, h } from 'vue'
import api from '../../api/index.js'
import FormSection from '../../components/FormSection.vue'

import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import ToggleSwitch from 'primevue/toggleswitch'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip

const props = defineProps({
  modelValue:  { type: Boolean, default: false },
  householdId: { type: [Number, String, null], default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const isEdit = computed(() => !!props.householdId)

const OPT = {
  prefix:    ['นาย', 'นาง', 'นางสาว', 'เด็กชาย', 'เด็กหญิง'],
  gender:    ['ชาย', 'หญิง'],
  education: [
    'ไม่ได้เรียน', 'ประถมศึกษา', 'มัธยมต้น', 'มัธยมปลาย/ปวช.',
    'ปวส./อนุปริญญา', 'ปริญญาตรี', 'สูงกว่าปริญญาตรี',
  ],
  health: [
    'ปกติ',
    'ป่วยเรื้อรัง (หัวใจ,เบาหวาน)',
    'ป่วยเรื้อรัง (ความดัน)',
    'พิการพึ่งตนเองได้',
    'พิการ',
  ],
  water:      ['ประปา', 'บาดาล', 'น้ำฝน', 'ลำห้วย/หนอง', 'อื่นๆ'],
  smartphone: ['ใช้ประจำ', 'ใช้บางครั้ง', 'ไม่ใช้'],
  interest:   ['สูงมาก', 'สูง', 'ปานกลาง', 'น้อย', 'ไม่มี'],
  groupReady: ['พร้อม', 'พร้อมบางส่วน', 'ไม่พร้อม'],
}

// Location data loaded from API (cached on first dialog open)
const locations = ref({ provinces: [], districts: [], subDistricts: [], villages: [] })

function defaultForm() {
  return {
    // s1
    household_code: '', province: 'นครราชสีมา', district: '', sub_district: '',
    moo_number: '', village: '', house_number: '', postal_code: '',
    head_full_name: '', members_count: 1,
    // s2
    prefix: '', first_name: '', last_name: '', id_card: '', gender: '',
    dob: null, age: null, phone: '', education: '', health: '',
    // s3
    main_occupation: '', secondary_occupation: '',
    income_month: null, expense_month: null, debt_amount: null, debt_source: '',
    // s4
    has_mushroom_area: false, mushroom_area_size: null, water_source: '',
    has_electricity: false, distance_to_market_km: null,
    ever_agriculture: false, ever_mushroom: false,
    smartphone_use: '', social_media_use: false,
    interest_level: '', interest_reason: '',
    hours_per_week: null, initial_investment: null,
    group_member: false, group_readiness: '',
    // s5
    poverty_score: null, motivation_score: null, experience_score: null,
    grouping_score: null, potential_score: null, area_score: null,
    market_score: null, total_score: null,
    priority: '', passed: false, completed: false,
    // s6
    survey_date: null, surveyor: '', is_active: true, note: '',
  }
}

const form = ref(defaultForm())
const saving = ref(false)
const error = ref('')

// Auto-calc net income (display only)
const netIncome = computed(() => {
  const i = form.value.income_month, e = form.value.expense_month
  if (i == null && e == null) return null
  return (Number(i) || 0) - (Number(e) || 0)
})

const SCORE_KEYS = ['poverty_score', 'motivation_score', 'experience_score', 'grouping_score', 'potential_score', 'area_score', 'market_score']

const avgScore = computed(() => {
  const valid = SCORE_KEYS.map(k => Number(form.value[k]) || 0)
  if (valid.every(v => v === 0)) return null
  return Math.round((valid.reduce((a, b) => a + b, 0) / SCORE_KEYS.length) * 100) / 100
})

const priorityClass = computed(() => {
  const map = {
    A: 'bg-slate-800 text-white border-slate-800',
    B: 'bg-sky-400 text-white border-sky-400',
    C: 'bg-amber-200 text-amber-900 border-amber-300',
    D: 'bg-pink-300 text-pink-900 border-pink-400',
  }
  return map[form.value.priority] || ''
})

// Auto-calc total + priority whenever any score changes
watch(SCORE_KEYS.map(k => () => form.value[k]), () => {
  const sum = SCORE_KEYS.reduce((acc, k) => acc + (Number(form.value[k]) || 0), 0)
  form.value.total_score = Math.round(sum * 100) / 100
  const avg = sum / SCORE_KEYS.length
  if (sum === 0) {
    form.value.priority = ''
  } else if (avg >= 75) form.value.priority = 'A'
  else if (avg >= 60)   form.value.priority = 'B'
  else if (avg >= 40)   form.value.priority = 'C'
  else                  form.value.priority = 'D'
})

// Auto-calc age from dob
watch(() => form.value.dob, (val) => {
  if (!val) return
  try {
    const d = new Date(val)
    const today = new Date()
    let age = today.getFullYear() - d.getFullYear()
    const m = today.getMonth() - d.getMonth()
    if (m < 0 || (m === 0 && today.getDate() < d.getDate())) age--
    if (age >= 0 && age < 120) form.value.age = age
  } catch {}
})

async function loadLocations() {
  try {
    const [p, d, s, v] = await Promise.all([
      api.get('/locations/provinces'),
      api.get('/locations/districts'),
      api.get('/locations/sub-districts'),
      api.get('/locations/villages'),
    ])
    locations.value = { provinces: p.data, districts: d.data, subDistricts: s.data, villages: v.data }
  } catch {}
}

async function onDistrictChange() {
  // Reload sub-districts filtered by selected district
  if (!form.value.district) return
  try {
    const { data } = await api.get('/locations/sub-districts', { params: { district: form.value.district } })
    locations.value.subDistricts = data
  } catch {}
}
async function onSubDistrictChange() {
  if (!form.value.sub_district) return
  try {
    const { data } = await api.get('/locations/villages', {
      params: { district: form.value.district, sub_district: form.value.sub_district },
    })
    locations.value.villages = data
  } catch {}
}

watch(() => props.modelValue, async (open) => {
  if (!open) return
  error.value = ''
  if (!locations.value.districts.length) loadLocations()
  if (isEdit.value) {
    try {
      const { data } = await api.get(`/households/${props.householdId}`)
      const merged = { ...defaultForm(), ...data }
      // ensure date fields are JS Date objects for DatePicker
      if (merged.dob)         merged.dob = new Date(merged.dob)
      if (merged.survey_date) merged.survey_date = new Date(merged.survey_date)
      form.value = merged
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
    // Format dates to Y-m-d before sending
    const payload = { ...form.value }
    if (payload.dob instanceof Date)         payload.dob = formatDate(payload.dob)
    if (payload.survey_date instanceof Date) payload.survey_date = formatDate(payload.survey_date)

    if (isEdit.value) {
      await api.put(`/households/${props.householdId}`, payload)
    } else {
      await api.post('/households', payload)
    }
    emit('saved')
    visible.value = false
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(' • ') : (e.response?.data?.message || 'เกิดข้อผิดพลาด')
    // Scroll dialog to top to show error
    setTimeout(() => document.querySelector('.p-dialog-content')?.scrollTo({ top: 0, behavior: 'smooth' }), 50)
  } finally {
    saving.value = false
  }
}

function formatDate(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

// Inline helper components
const Field = {
  props: ['label', 'required'],
  setup(p, { slots }) {
    return () => h('div', {}, [
      h('label', { class: 'text-sm font-medium text-slate-700 mb-1 block' }, [
        p.label,
        p.required ? h('span', { class: 'text-rose-500 ml-0.5' }, '*') : null,
      ]),
      slots.default?.(),
    ])
  },
}

const ScoreField = {
  props: ['modelValue', 'label'],
  emits: ['update:modelValue'],
  setup(p, { emit }) {
    return () => h('div', {}, [
      h('label', { class: 'text-sm font-medium text-slate-700 mb-1 block' }, p.label),
      h(InputNumber, {
        modelValue: p.modelValue,
        'onUpdate:modelValue': v => emit('update:modelValue', v),
        min: 0, max: 100, minFractionDigits: 0, maxFractionDigits: 2,
        fluid: true,
        suffix: ' / 100',
      }),
    ])
  },
}

const ToggleField = {
  props: ['modelValue', 'label'],
  emits: ['update:modelValue'],
  setup(p, { emit }) {
    return () => h('div', { class: 'flex items-center gap-2' }, [
      h(ToggleSwitch, {
        modelValue: p.modelValue,
        'onUpdate:modelValue': v => emit('update:modelValue', v),
      }),
      h('span', { class: 'text-sm text-slate-700' }, p.label),
    ])
  },
}
</script>
