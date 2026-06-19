<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-apps text-violet-600"></i> หมวดหมู่สินค้า
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">หมวดกลางใช้ร่วมทุกกลุ่ม (เฉพาะแอดมิน) หรือหมวดเฉพาะกลุ่ม</p>
      </div>
      <Button label="เพิ่มหมวดหมู่" icon="fi fi-rr-plus" size="large" @click="openCreate" />
    </div>

    <div class="box-card overflow-hidden">
      <DataTable :value="rows" :loading="loading" stripedRows>
        <template #empty><div class="text-center py-10 text-slate-400">ยังไม่มีหมวดหมู่</div></template>
        <Column field="name" header="ชื่อหมวด">
          <template #body="{ data }"><span class="font-medium text-slate-700">{{ data.name }}</span></template>
        </Column>
        <Column header="ขอบเขต">
          <template #body="{ data }">
            <span v-if="!data.seller_group_id" class="px-2 py-0.5 rounded-full text-xs bg-violet-50 text-violet-700 border border-violet-200">กลาง</span>
            <span v-else class="text-sm text-slate-600">{{ data.seller_group?.name }}</span>
          </template>
        </Column>
        <Column field="products_count" header="จำนวนสินค้า"><template #body="{ data }">{{ data.products_count }}</template></Column>
        <Column header="สถานะ">
          <template #body="{ data }">
            <span class="px-2 py-0.5 rounded-full text-xs border" :class="data.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-300'">{{ data.is_active ? 'ใช้งาน' : 'ปิด' }}</span>
          </template>
        </Column>
        <Column header="" style="width:96px">
          <template #body="{ data }">
            <div class="flex gap-1" v-if="canEdit(data)">
              <Button icon="fi fi-rr-edit" text rounded size="small" @click="openEdit(data)" />
              <Button icon="fi fi-rr-trash" text rounded severity="danger" size="small" @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="dialogOpen" modal :header="form.id ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่'" :style="{ width: '28rem' }">
      <div class="space-y-3">
        <div>
          <label class="form-label">ชื่อหมวดหมู่ *</label>
          <InputText v-model="form.name" class="w-full" :invalid="!!err.name" />
          <small v-if="err.name" class="text-rose-500">{{ err.name }}</small>
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
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import ToggleSwitch from 'primevue/toggleswitch'
import Message from 'primevue/message'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

const { isAdmin, canActInGroup } = useAuth()
const confirm = useConfirm()
const toast = useToast()

const rows = ref([])
const loading = ref(false)
const dialogOpen = ref(false)
const saving = ref(false)
const err = reactive({})
const errorMsg = ref('')
const form = reactive({ id: null, name: '', is_active: true, is_global: false })

function canEdit(cat) {
  if (!cat.seller_group_id) return isAdmin.value
  return canActInGroup(cat.seller_group_id)
}

async function reload() {
  loading.value = true
  try { rows.value = (await api.get('/market/categories')).data || [] }
  finally { loading.value = false }
}

function openCreate() { Object.assign(form, { id: null, name: '', is_active: true, is_global: false }); clearErr(); dialogOpen.value = true }
function openEdit(c) { Object.assign(form, { id: c.id, name: c.name, is_active: !!c.is_active, is_global: !c.seller_group_id }); clearErr(); dialogOpen.value = true }
function clearErr() { Object.keys(err).forEach(k => delete err[k]); errorMsg.value = '' }

async function save() {
  saving.value = true; clearErr()
  try {
    if (form.id) await api.put(`/market/categories/${form.id}`, { name: form.name, is_active: form.is_active })
    else await api.post('/market/categories', { name: form.name, is_active: form.is_active, is_global: form.is_global })
    toast.add({ severity: 'success', summary: 'บันทึกแล้ว', life: 2000 })
    dialogOpen.value = false
    reload()
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
