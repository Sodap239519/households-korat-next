<template>
  <div class="p-3 sm:p-5 space-y-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-apps text-violet-600"></i> หมวดหมู่สินค้า
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">หมวดกลางใช้ร่วมทุกกลุ่ม (เฉพาะแอดมิน) / หมวดเฉพาะกลุ่ม</p>
      </div>
      <button @click="openCreate"
        class="shrink-0 h-10 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold flex items-center gap-2 shadow-md shadow-violet-500/25 transition">
        <i class="fi fi-rr-plus"></i> เพิ่ม
      </button>
    </div>

    <!-- Card list -->
    <div class="box-card overflow-hidden divide-y divide-slate-100">
      <template v-if="loading">
        <div v-for="n in 5" :key="n" class="p-4 skeleton h-14"></div>
      </template>
      <div v-else-if="!rows.length" class="py-14 text-center text-slate-400">
        <i class="fi fi-rr-apps text-4xl"></i>
        <p class="mt-2 text-sm">ยังไม่มีหมวดหมู่</p>
      </div>
      <div v-else v-for="row in rows" :key="row.id"
        class="flex items-center gap-3 px-4 py-3.5 hover:bg-violet-50/30 transition">
        <!-- Icon -->
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
          :class="row.is_active ? 'bg-violet-100 text-violet-600' : 'bg-slate-100 text-slate-400'">
          <i class="fi fi-rr-apps text-sm"></i>
        </div>
        <!-- Info -->
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-slate-800 text-sm">{{ row.name }}</p>
          <div class="flex items-center gap-2 mt-0.5 flex-wrap">
            <span v-if="!row.seller_group_id" class="text-[11px] px-1.5 py-0.5 rounded-md bg-violet-50 text-violet-600 border border-violet-100 font-medium">กลาง</span>
            <span v-else class="text-[11px] text-slate-400">{{ row.seller_group?.name }}</span>
            <span v-if="row.category_key" class="text-[11px] px-1.5 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-100 font-medium">
              {{ CATEGORY_LABELS[row.category_key] || row.category_key }}
            </span>
            <span class="text-[11px] text-slate-400">{{ row.products_count || 0 }} สินค้า</span>
          </div>
        </div>
        <!-- Status + actions -->
        <div class="flex items-center gap-2 shrink-0">
          <span class="px-2 py-0.5 rounded-full text-[11px] border"
            :class="row.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'">
            {{ row.is_active ? 'ใช้งาน' : 'ปิด' }}
          </span>
          <template v-if="canEdit(row)">
            <button class="w-8 h-8 rounded-lg bg-violet-100 hover:bg-violet-200 text-violet-600 flex items-center justify-center transition"
              @click="openEdit(row)">
              <i class="fi fi-rr-edit text-sm"></i>
            </button>
            <button class="w-8 h-8 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-500 flex items-center justify-center transition"
              @click="confirmDelete(row)">
              <i class="fi fi-rr-trash text-sm"></i>
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Form dialog -->
    <Dialog v-model:visible="dialogOpen" modal :header="form.id ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่'"
      :style="{ width: '95vw', maxWidth: '28rem' }">
      <div class="space-y-3">
        <div>
          <label class="form-label">ชื่อหมวดหมู่ *</label>
          <InputText v-model="form.name" class="w-full" :invalid="!!err.name" />
          <small v-if="err.name" class="text-rose-500 text-xs">{{ err.name }}</small>
        </div>
        <div>
          <label class="form-label">รหัสประเภท (ใช้นำหน้า SKU)</label>
          <InputText v-model="form.code" class="w-full uppercase" placeholder="เช่น MUSH, VEG, HERB" maxlength="20" />
          <small class="text-slate-400 text-xs">ตัวอักษร A-Z ไม่เกิน 20 ตัว — ถ้าไม่ระบุจะใช้ PRD</small>
        </div>
        <div v-if="isAdmin">
          <label class="form-label">ประเภทผู้ขายที่ใช้ได้</label>
          <Select v-model="form.category_key" :options="CATEGORY_OPTIONS" optionLabel="label" optionValue="key"
            placeholder="ไม่จำกัด (ทุกร้านใช้ได้)" class="w-full" showClear />
          <small class="text-slate-400 text-xs">ถ้าเลือก — เฉพาะร้านที่สมัครประเภทนี้จึงเห็นหมวดนี้</small>
        </div>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="form.is_active" inputId="catact" />
          <label for="catact" class="text-sm text-slate-600">ใช้งาน</label>
        </div>
        <div v-if="isAdmin && !form.id" class="flex items-center gap-2">
          <ToggleSwitch v-model="form.is_global" inputId="catglobal" />
          <label for="catglobal" class="text-sm text-slate-600">หมวดกลาง (ใช้ร่วมทุกกลุ่ม)</label>
        </div>
        <Message v-if="errorMsg" severity="error" :closable="false">{{ errorMsg }}</Message>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="dialogOpen = false" />
        <Button label="บันทึก" icon="fi fi-rr-disk" :loading="saving" @click="save" />
      </template>
    </Dialog>

    <ConfirmDialog /><Toast />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useAuth } from '../../composables/useAuth.js'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import Message from 'primevue/message'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const CATEGORY_OPTIONS = [
  { key: 'vegetable_fruit',  label: 'สินค้าเกษตร (พืช-ผัก-ผลไม้)' },
  { key: 'fresh_food',       label: 'อาหารสด (เนื้อ ปลา ไข่)' },
  { key: 'processed_food',   label: 'อาหารแปรรูป' },
  { key: 'ready_to_eat',     label: 'อาหารพร้อมทาน' },
  { key: 'bakery',           label: 'เบเกอรี่-ขนม' },
  { key: 'beverage',         label: 'เครื่องดื่ม' },
  { key: 'mushroom',         label: 'เห็ด' },
  { key: 'herb',             label: 'สมุนไพร-ยาสมุนไพร' },
  { key: 'household',        label: 'ของใช้ในบ้าน' },
  { key: 'handicraft',       label: 'หัตถกรรม-ของฝาก' },
  { key: 'clothing',         label: 'เสื้อผ้า-สิ่งทอ' },
  { key: 'agriculture',      label: 'เกษตรกรรม-เมล็ดพันธุ์' },
  { key: 'service',          label: 'บริการ (ซ่อม-สอน-ดูแล)' },
]
const CATEGORY_LABELS = Object.fromEntries(CATEGORY_OPTIONS.map(o => [o.key, o.label]))

const { isAdmin, canActInGroup } = useAuth()
const confirm = useConfirm()
const toast = useToast()

const rows = ref([])
const loading = ref(false)
const dialogOpen = ref(false)
const saving = ref(false)
const err = reactive({})
const errorMsg = ref('')
const form = reactive({ id: null, name: '', code: '', category_key: null, is_active: true, is_global: false })

function canEdit(cat) {
  if (!cat.seller_group_id) return isAdmin.value
  return canActInGroup(cat.seller_group_id)
}

async function reload() {
  loading.value = true
  try { rows.value = (await api.get('/market/categories')).data || [] }
  finally { loading.value = false }
}

function openCreate() { Object.assign(form, { id: null, name: '', code: '', category_key: null, is_active: true, is_global: false }); clearErr(); dialogOpen.value = true }
function openEdit(c) { Object.assign(form, { id: c.id, name: c.name, code: c.code ?? '', category_key: c.category_key ?? null, is_active: !!c.is_active, is_global: !c.seller_group_id }); clearErr(); dialogOpen.value = true }
function clearErr() { Object.keys(err).forEach(k => delete err[k]); errorMsg.value = '' }

async function save() {
  saving.value = true; clearErr()
  try {
    const code = form.code.trim().toUpperCase() || null
    const category_key = form.category_key || null
    if (form.id) await api.put(`/market/categories/${form.id}`, { name: form.name, code, category_key, is_active: form.is_active })
    else await api.post('/market/categories', { name: form.name, code, category_key, is_active: form.is_active, is_global: form.is_global })
    toast.add({ severity: 'success', summary: 'บันทึกแล้ว', life: 2000 })
    dialogOpen.value = false; reload()
  } catch (e) {
    if (e.response?.status === 422) { Object.entries(e.response.data.errors || {}).forEach(([k, v]) => err[k] = v[0]); errorMsg.value = 'ตรวจสอบข้อมูล' }
    else errorMsg.value = e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally { saving.value = false }
}

function confirmDelete(c) {
  confirm.require({
    message: `ลบหมวด "${c.name}"?`, header: 'ยืนยันการลบ', icon: 'fi fi-rr-exclamation',
    acceptLabel: 'ลบ', rejectLabel: 'ยกเลิก', acceptClass: 'p-button-danger',
    accept: async () => {
      try { await api.delete(`/market/categories/${c.id}`); toast.add({ severity: 'success', summary: 'ลบแล้ว', life: 2000 }); reload() }
      catch (e) { toast.add({ severity: 'error', summary: 'ลบไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
    },
  })
}

onMounted(reload)
</script>
