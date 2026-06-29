<template>
  <div class="p-3 sm:p-5 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-box-open text-violet-600"></i> สินค้า
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">เผยแพร่แล้วจะแสดงที่หน้าร้านทันที</p>
      </div>
      <button @click="openCreate"
        class="shrink-0 h-10 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold flex items-center gap-2 shadow-md shadow-violet-500/25 transition">
        <i class="fi fi-rr-plus"></i> เพิ่มสินค้า
      </button>
    </div>

    <!-- Filters -->
    <div class="box-card p-3 space-y-2.5">
      <IconField>
        <InputIcon class="fi fi-rr-search" />
        <InputText v-model="filters.q" placeholder="ค้นหาชื่อ/SKU..." class="w-full" @input="debouncedReload" />
      </IconField>
      <div class="grid grid-cols-2 gap-2">
        <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
          placeholder="ทุกสถานะ" showClear class="w-full" @change="reload" />
        <Select v-model="filters.category_id" :options="categories" optionLabel="name" optionValue="id"
          placeholder="ทุกหมวด" showClear filter class="w-full" @change="reload" />
      </div>
      <Select v-if="isAdmin" v-model="filters.group_id" :options="groups" optionLabel="name" optionValue="id"
        placeholder="ทุกกลุ่มผู้ขาย" showClear filter class="w-full" @change="reload" />
    </div>

    <!-- Card list -->
    <div class="space-y-2.5">
      <template v-if="loading">
        <div v-for="n in 4" :key="n" class="box-card p-4 skeleton h-24"></div>
      </template>
      <div v-else-if="!rows.length" class="box-card py-14 text-center text-slate-400">
        <i class="fi fi-rr-box-open text-4xl"></i>
        <p class="mt-2 text-sm">ยังไม่มีสินค้า</p>
      </div>
      <div v-else v-for="row in rows" :key="row.id"
        class="box-card flex items-center gap-3 p-3 hover:bg-violet-50/30 transition">
        <!-- Thumbnail -->
        <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden flex items-center justify-center shrink-0 border border-slate-200">
          <img v-if="row.primary_image_url" :src="row.primary_image_url" class="w-full h-full object-cover" />
          <i v-else class="fi fi-rr-picture text-slate-300 text-xl"></i>
        </div>
        <!-- Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="font-semibold text-slate-800 text-sm truncate">{{ row.name }}</p>
              <p class="text-[11px] text-slate-400 mt-0.5">{{ row.category?.name || '—' }} · {{ row.sku || 'ไม่มี SKU' }}</p>
            </div>
            <span class="shrink-0 px-2 py-0.5 rounded-full text-[11px] font-medium border"
              :class="row.status === 'published' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'">
              {{ row.status === 'published' ? 'เผยแพร่' : 'ร่าง' }}
            </span>
          </div>
          <div class="flex items-center justify-between mt-2">
            <div>
              <span class="font-bold text-fuchsia-700">฿{{ fmt(row.sale_price ?? row.price) }}</span>
              <span v-if="row.sale_price != null" class="text-xs text-slate-400 line-through ml-1">฿{{ fmt(row.price) }}</span>
              <span class="text-[11px] text-slate-400 ml-2">สต็อก: {{ row.stock_qty }} {{ row.unit }}</span>
            </div>
            <div class="flex gap-1.5">
              <button class="w-8 h-8 rounded-lg bg-violet-100 hover:bg-violet-200 text-violet-600 flex items-center justify-center transition"
                @click="openEdit(row)" title="แก้ไข">
                <i class="fi fi-rr-edit text-sm"></i>
              </button>
              <button class="w-8 h-8 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-500 flex items-center justify-center transition"
                @click="confirmDelete(row)" title="ลบ">
                <i class="fi fi-rr-trash text-sm"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
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
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

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
  } finally { loading.value = false }
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
