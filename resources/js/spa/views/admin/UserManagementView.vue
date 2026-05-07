<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-users-alt text-violet-600"></i>
          จัดการผู้ใช้
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">เพิ่ม/แก้ไข/รีเซ็ตรหัสผ่าน บัญชีผู้ใช้ในระบบ</p>
      </div>
      <Button label="เพิ่มผู้ใช้" icon="fi fi-rr-user-add" @click="openCreate" />
    </div>

    <div class="box-card p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <IconField>
          <InputIcon class="fi fi-rr-search text-slate-400" />
          <InputText v-model="filters.search" @input="onFilter" placeholder="ค้นหาชื่อ/อีเมล..." class="w-full" />
        </IconField>
        <Select
          v-model="filters.role"
          :options="roleOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="ทุกบทบาท"
          showClear
          @change="onFilter"
          class="w-full"
        />
      </div>
    </div>

    <div class="box-card p-0 overflow-hidden">
      <DataTable :value="items" :loading="loading" stripedRows scrollable scrollHeight="60vh">
        <template #empty>
          <div class="text-center py-12 text-slate-400">
            <i class="fi fi-rr-info text-3xl"></i>
            <p class="mt-2">ไม่พบผู้ใช้</p>
          </div>
        </template>

        <Column field="id" header="ID" :style="{ width: '60px' }" />
        <Column header="ชื่อ-สกุล">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center text-xs font-semibold">
                {{ initials(data.name) }}
              </div>
              <span class="font-medium text-slate-700">{{ data.name }}</span>
            </div>
          </template>
        </Column>
        <Column field="email" header="อีเมล" />
        <Column header="บทบาท" :style="{ width: '160px' }">
          <template #body="{ data }">
            <StatusBadge :status="data.role" :label="data.role === 'superadmin' ? 'ผู้ดูแลระบบ' : 'เจ้าหน้าที่'" />
          </template>
        </Column>
        <Column header="จัดการ" :style="{ width: '180px' }">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="fi fi-rr-edit" severity="info" text rounded @click="openEdit(data)" v-tooltip.top="'แก้ไข'" />
              <Button icon="fi fi-rr-key" severity="warn" text rounded @click="openResetPwd(data)" v-tooltip.top="'รีเซ็ตรหัสผ่าน'" />
              <Button icon="fi fi-rr-time-past" severity="secondary" text rounded @click="viewHistory(data)" v-tooltip.top="'ประวัติการเข้าใช้'" />
              <Button icon="fi fi-rr-trash" severity="danger" text rounded @click="confirmDelete(data)" v-tooltip.top="'ลบ'" />
            </div>
          </template>
        </Column>
      </DataTable>
      <Pagination :meta="meta" @change="onPage" class="p-4" />
    </div>

    <!-- Edit/Create Dialog -->
    <Dialog v-model:visible="dialogOpen" modal :draggable="false" :style="{ width: '520px' }" :breakpoints="{ '767px': '95vw' }">
      <template #header>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center shadow">
            <i :class="editId ? 'fi fi-rr-edit' : 'fi fi-rr-user-add'"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-800">{{ editId ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้' }}</h3>
        </div>
      </template>

      <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

      <form @submit.prevent="save" class="space-y-4">
        <FormSection title="ข้อมูลผู้ใช้" icon="fi fi-rr-id-badge" tone="violet">
          <div class="grid grid-cols-1 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">ชื่อ-สกุล <span class="text-rose-500">*</span></label>
              <InputText v-model="form.name" required class="w-full" />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล <span class="text-rose-500">*</span></label>
              <InputText v-model="form.email" type="email" required class="w-full" />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">บทบาท <span class="text-rose-500">*</span></label>
              <Select v-model="form.role" :options="roleOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div v-if="!editId">
              <label class="text-sm font-medium text-slate-700 mb-1 block">รหัสผ่าน <span class="text-rose-500">*</span></label>
              <Password v-model="form.password" toggleMask fluid inputClass="w-full" />
              <p class="text-[11px] text-slate-400 mt-1">อย่างน้อย 8 ตัวอักษร</p>
            </div>
          </div>
        </FormSection>
      </form>

      <template #footer>
        <Button label="ยกเลิก" severity="secondary" outlined @click="dialogOpen = false" :disabled="saving" />
        <Button :label="saving ? 'กำลังบันทึก...' : 'บันทึก'" :loading="saving" icon="fi fi-rr-disk" @click="save" />
      </template>
    </Dialog>

    <!-- Reset password dialog -->
    <Dialog v-model:visible="resetOpen" modal :draggable="false" :style="{ width: '420px' }" :breakpoints="{ '767px': '95vw' }" header="รีเซ็ตรหัสผ่าน">
      <p class="text-sm text-slate-600 mb-3">
        กำหนดรหัสผ่านใหม่ให้ <span class="font-semibold text-violet-700">{{ resetTarget?.name }}</span>
      </p>
      <Password v-model="resetPwd" toggleMask fluid inputClass="w-full" />
      <template #footer>
        <Button label="ยกเลิก" severity="secondary" outlined @click="resetOpen = false" />
        <Button label="ยืนยัน" icon="fi fi-rr-key" :loading="resetting" severity="warn" @click="doResetPwd" />
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import api from '../../api/index.js'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Password from 'primevue/password'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

import Pagination from '../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import FormSection from '../../components/FormSection.vue'

const vTooltip = Tooltip
const toast = useToast()
const confirm = useConfirm()
const router = useRouter()

const items = ref([])
const meta = ref({})
const loading = ref(false)
const filters = ref({ search: '', role: null })
let currentPage = 1, filterTimer = null

const roleOptions = [
  { label: 'ผู้ดูแลระบบ',  value: 'superadmin' },
  { label: 'เจ้าหน้าที่', value: 'staff' },
]

const dialogOpen = ref(false)
const editId = ref(null)
const form = ref({ name: '', email: '', role: 'staff', password: '' })
const saving = ref(false)
const error = ref('')

const resetOpen = ref(false)
const resetTarget = ref(null)
const resetPwd = ref('')
const resetting = ref(false)

function initials(name) {
  const parts = (name || '').trim().split(/\s+/)
  return (parts[0]?.[0] || '?') + (parts[1]?.[0] || '')
}

async function fetchData() {
  loading.value = true
  try {
    const params = { page: currentPage, per_page: 20 }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.role) params.role = filters.value.role
    const { data } = await api.get('/admin/users', { params })
    items.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
  } finally {
    loading.value = false
  }
}

function onFilter() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => { currentPage = 1; fetchData() }, 300)
}
function onPage(p) { currentPage = p; fetchData() }

function openCreate() {
  editId.value = null
  form.value = { name: '', email: '', role: 'staff', password: '' }
  error.value = ''
  dialogOpen.value = true
}
function openEdit(u) {
  editId.value = u.id
  form.value = { name: u.name, email: u.email, role: u.role, password: '' }
  error.value = ''
  dialogOpen.value = true
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    if (editId.value) {
      await api.put(`/admin/users/${editId.value}`, {
        name: form.value.name, email: form.value.email, role: form.value.role,
      })
    } else {
      await api.post('/admin/users', form.value)
    }
    toast.add({ severity: 'success', summary: 'สำเร็จ', life: 2000 })
    dialogOpen.value = false
    fetchData()
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(', ') : (e.response?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    saving.value = false
  }
}

function openResetPwd(u) { resetTarget.value = u; resetPwd.value = ''; resetOpen.value = true }
async function doResetPwd() {
  if (resetPwd.value.length < 8) {
    toast.add({ severity: 'warn', summary: 'รหัสสั้นเกินไป', detail: 'อย่างน้อย 8 ตัวอักษร', life: 2500 })
    return
  }
  resetting.value = true
  try {
    await api.put(`/admin/users/${resetTarget.value.id}/password`, { new_password: resetPwd.value })
    toast.add({ severity: 'success', summary: 'รีเซ็ตสำเร็จ', life: 2000 })
    resetOpen.value = false
  } catch (e) {
    toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: e.response?.data?.message || '', life: 3000 })
  } finally {
    resetting.value = false
  }
}

function viewHistory(u) {
  router.push(`/app/admin/users/${u.id}/login-history`)
}

function confirmDelete(u) {
  confirm.require({
    message: `ลบผู้ใช้ ${u.name} (${u.email}) ใช่หรือไม่?`,
    header: 'ยืนยันการลบ',
    icon: 'fi fi-rr-triangle-warning',
    rejectLabel: 'ยกเลิก', acceptLabel: 'ลบ',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/admin/users/${u.id}`)
        toast.add({ severity: 'success', summary: 'ลบสำเร็จ', life: 2000 })
        fetchData()
      } catch (e) {
        toast.add({ severity: 'error', summary: 'ผิดพลาด', detail: e.response?.data?.message || '', life: 3000 })
      }
    },
  })
}

onMounted(fetchData)
</script>
