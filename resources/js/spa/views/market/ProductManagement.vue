<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-box-open text-violet-600"></i> สินค้า
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">จัดการสินค้าของกลุ่ม — เผยแพร่แล้วจะแสดงที่หน้าร้านทันที</p>
      </div>
      <Button label="เพิ่มสินค้า" icon="fi fi-rr-plus" size="large" @click="openCreate" />
    </div>

    <!-- Filters -->
    <div class="box-card p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <IconField>
          <InputIcon class="fi fi-rr-search" />
          <InputText v-model="filters.q" placeholder="ค้นหาชื่อ/SKU" class="w-full" @input="debouncedReload" />
        </IconField>
        <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="ทุกสถานะ" showClear class="w-full" @change="reload" />
        <Select v-model="filters.category_id" :options="categories" optionLabel="name" optionValue="id" placeholder="ทุกหมวด" showClear filter class="w-full" @change="reload" />
        <Select v-if="isAdmin" v-model="filters.group_id" :options="groups" optionLabel="name" optionValue="id" placeholder="ทุกกลุ่ม" showClear filter class="w-full" @change="reload" />
      </div>
    </div>

    <!-- Table -->
    <div class="box-card overflow-hidden">
      <DataTable :value="rows" :loading="loading" stripedRows scrollable>
        <template #empty><div class="text-center py-10 text-slate-400"><i class="fi fi-rr-box-open text-3xl"></i><p class="mt-2">ยังไม่มีสินค้า</p></div></template>
        <Column header="รูป" style="width:64px">
          <template #body="{ data }">
            <div class="w-12 h-12 rounded-lg bg-slate-100 overflow-hidden flex items-center justify-center">
              <img v-if="data.primary_image_url" :src="data.primary_image_url" class="w-full h-full object-cover" />
              <i v-else class="fi fi-rr-picture text-slate-300"></i>
            </div>
          </template>
        </Column>
        <Column field="name" header="ชื่อสินค้า">
          <template #body="{ data }">
            <p class="font-medium text-slate-700">{{ data.name }}</p>
            <p class="text-xs text-slate-400">{{ data.sku || '-' }}</p>
          </template>
        </Column>
        <Column v-if="isAdmin" header="กลุ่ม"><template #body="{ data }">{{ data.seller_group?.name || '-' }}</template></Column>
        <Column header="หมวด"><template #body="{ data }">{{ data.category?.name || '-' }}</template></Column>
        <Column header="ราคา">
          <template #body="{ data }">
            <span class="font-semibold text-fuchsia-700">฿{{ fmt(data.sale_price ?? data.price) }}</span>
            <span v-if="data.sale_price != null" class="block text-xs text-slate-400 line-through">฿{{ fmt(data.price) }}</span>
          </template>
        </Column>
        <Column header="คงเหลือ"><template #body="{ data }">{{ data.stock_qty }} {{ data.unit }}</template></Column>
        <Column header="สถานะ">
          <template #body="{ data }">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium border" :class="data.status === 'published' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-300'">
              {{ data.status === 'published' ? 'เผยแพร่' : 'ฉบับร่าง' }}
            </span>
          </template>
        </Column>
        <Column header="" style="width:96px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="fi fi-rr-edit" text rounded size="small" @click="openEdit(data)" v-tooltip.top="'แก้ไข'" />
              <Button icon="fi fi-rr-trash" text rounded severity="danger" size="small" @click="confirmDelete(data)" v-tooltip.top="'ลบ'" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
    <Pagination :meta="meta" @change="goPage" />

    <ProductFormDialog v-model="dialogOpen" :product-id="editId" :groups="groups" :categories="categories" :districts="districts" @saved="reload" />
    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useAuth } from '../../composables/useAuth.js'
import ProductFormDialog from './ProductFormDialog.vue'
import Pagination from '../components/Pagination.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import Tooltip from 'primevue/tooltip'

const vTooltip = Tooltip
const { isAdmin } = useAuth()
const confirm = useConfirm()
const toast = useToast()

const rows = ref([])
const meta = ref({})
const loading = ref(false)
const categories = ref([])
const groups = ref([])
const districts = ref([])
const dialogOpen = ref(false)
const editId = ref(null)

const filters = reactive({ q: '', status: null, category_id: null, group_id: null, page: 1 })
const statusOptions = [
  { label: 'เผยแพร่', value: 'published' },
  { label: 'ฉบับร่าง', value: 'draft' },
]

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }

let debounceTimer
function debouncedReload() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(reload, 350)
}

async function reload() {
  loading.value = true
  try {
    const params = { per_page: 20, page: filters.page }
    for (const k of ['q', 'status', 'category_id', 'group_id']) if (filters[k]) params[k] = filters[k]
    const { data } = await api.get('/market/products', { params })
    rows.value = data.data || []
    meta.value = data
  } finally {
    loading.value = false
  }
}

function goPage(p) { filters.page = p; reload() }

function openCreate() { editId.value = null; dialogOpen.value = true }
function openEdit(row) { editId.value = row.id; dialogOpen.value = true }

function confirmDelete(row) {
  confirm.require({
    message: `ลบสินค้า "${row.name}"?`,
    header: 'ยืนยันการลบ', icon: 'fi fi-rr-exclamation',
    acceptLabel: 'ลบ', rejectLabel: 'ยกเลิก', acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/market/products/${row.id}`)
        toast.add({ severity: 'success', summary: 'ลบแล้ว', life: 2000 })
        reload()
      } catch (e) {
        toast.add({ severity: 'error', summary: 'ลบไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 })
      }
    },
  })
}

async function loadFacets() {
  const reqs = [api.get('/market/categories'), api.get('/locations/districts')]
  if (isAdmin.value) reqs.push(api.get('/market/seller-groups'))
  const [catRes, distRes, grpRes] = await Promise.all(reqs)
  categories.value = catRes.data || []
  districts.value = distRes.data || []
  if (grpRes) groups.value = grpRes.data || []
}

onMounted(() => { loadFacets(); reload() })
</script>
