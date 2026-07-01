<template>
  <Dialog :visible="modelValue" @update:visible="$emit('update:modelValue', $event)" modal :header="form.id ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า'" :style="{ width: '46rem' }" :breakpoints="{ '960px': '95vw' }">
    <div class="space-y-4">
      <FormSection title="ข้อมูลสินค้า" icon="fi fi-rr-box-open" tone="violet">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="sm:col-span-2">
            <label class="form-label">ชื่อสินค้า *</label>
            <InputText v-model="form.name" class="w-full" :invalid="!!errors.name" />
            <small v-if="errors.name" class="text-rose-500">{{ errors.name }}</small>
          </div>

          <div v-if="isAdmin">
            <label class="form-label">กลุ่มผู้ขาย *</label>
            <Select v-model="form.seller_group_id" :options="groups" optionLabel="name" optionValue="id" placeholder="เลือกกลุ่ม" class="w-full" filter />
          </div>
          <div>
            <label class="form-label">หมวดหมู่</label>
            <Select v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" placeholder="ไม่ระบุ" class="w-full" showClear filter />
          </div>

          <div>
            <label class="form-label">รหัสสินค้า (SKU)</label>
            <div class="flex gap-2">
              <InputText v-model="form.sku" class="flex-1" placeholder="ว่างไว้ = Gen อัตโนมัติ" />
              <button type="button" @click="form.sku = genSku()"
                class="shrink-0 px-3 h-10 rounded-lg border border-violet-300 text-violet-600 text-sm font-medium hover:bg-violet-50 transition flex items-center gap-1.5">
                <i class="fi fi-rr-refresh"></i> Gen
              </button>
            </div>
          </div>
          <div>
            <label class="form-label">หน่วยนับ</label>
            <InputText v-model="form.unit" class="w-full" placeholder="ชิ้น / กก. / ถุง" />
          </div>

          <div>
            <label class="form-label">ราคาปกติ (บาท) *</label>
            <InputNumber v-model="form.price" mode="decimal" :minFractionDigits="0" :maxFractionDigits="2" class="w-full" :invalid="!!errors.price" />
            <small v-if="errors.price" class="text-rose-500">{{ errors.price }}</small>
          </div>
          <div>
            <label class="form-label">ราคาลด (บาท)</label>
            <InputNumber v-model="form.sale_price" mode="decimal" :minFractionDigits="0" :maxFractionDigits="2" class="w-full" placeholder="เว้นว่าง = ไม่ลด" />
          </div>

          <div>
            <label class="form-label">จำนวนคงเหลือ</label>
            <InputNumber v-model="form.stock_qty" :min="0" class="w-full" />
          </div>
          <div>
            <label class="form-label">อำเภอแหล่งผลิต</label>
            <Select v-model="form.district" :options="districts" placeholder="ไม่ระบุ" class="w-full" showClear filter />
          </div>

          <div class="sm:col-span-2">
            <label class="form-label">คำอธิบายสั้น</label>
            <InputText v-model="form.short_description" class="w-full" maxlength="255" />
          </div>
          <div class="sm:col-span-2">
            <label class="form-label">รายละเอียด</label>
            <Textarea v-model="form.description" rows="4" class="w-full" autoResize />
          </div>

          <div class="flex items-center gap-6 sm:col-span-2">
            <div class="flex items-center gap-2">
              <ToggleSwitch v-model="publishedToggle" inputId="pub" />
              <label for="pub" class="text-sm text-slate-600">เผยแพร่ (แสดงหน้าร้าน)</label>
            </div>
            <div class="flex items-center gap-2">
              <ToggleSwitch v-model="form.is_featured" inputId="feat" />
              <label for="feat" class="text-sm text-slate-600">สินค้าแนะนำ</label>
            </div>
          </div>

          <!-- บริการจัดส่งที่รองรับ -->
          <div class="sm:col-span-2">
            <label class="form-label flex items-center gap-1.5">
              <i class="fi fi-rr-truck-side text-violet-500 text-xs"></i>
              วิธีการจัดส่ง <span class="text-rose-500">*</span>
            </label>

            <!-- โหลด -->
            <div v-if="loadingShipping" class="text-xs text-slate-400 py-2">
              <i class="fi fi-rr-spinner animate-spin mr-1"></i> กำลังโหลด...
            </div>
            <!-- ยังไม่มีบริการ -->
            <div v-else-if="!shippingOptions.length" class="flex items-center gap-2 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
              <i class="fi fi-rr-triangle-warning"></i>
              ยังไม่มีบริการจัดส่งในระบบ —
              <RouterLink to="/app/market/shipping" class="underline font-medium">ตั้งค่าบริการจัดส่ง</RouterLink>
            </div>

            <!-- มีบริการ → แสดง chips -->
            <template v-else>
              <p class="text-[11px] text-slate-400 mb-2">เลือกบริการจัดส่งที่สินค้านี้รองรับ (เลือกได้หลายรายการ)</p>
              <div class="flex flex-wrap gap-2">
                <button v-for="opt in shippingOptions" :key="opt.id" type="button"
                  @click="toggleShipping(opt.id)"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-sm transition-all"
                  :class="form.shipping_option_ids.includes(opt.id)
                    ? 'bg-violet-600 border-violet-600 text-white shadow-sm'
                    : 'bg-white border-slate-200 text-slate-600 hover:border-violet-300 hover:text-violet-700'">
                  <i class="fi fi-rr-truck-side text-xs"></i>
                  {{ opt.name }}
                  <span v-if="opt.carrier" class="text-[10px] opacity-60">({{ opt.carrier }})</span>
                  <span v-if="opt.fee == 0" class="text-[10px] opacity-75 ml-0.5">ฟรี</span>
                  <span v-else class="text-[10px] opacity-75 ml-0.5">฿{{ opt.fee }}</span>
                  <span v-if="(opt.allowed_payment_methods || []).includes('cod')"
                    class="text-[9px] font-bold px-1 py-0.5 rounded ml-0.5"
                    :class="form.shipping_option_ids.includes(opt.id) ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-600'">
                    COD
                  </span>
                </button>
              </div>
              <p v-if="form.shipping_option_ids.length" class="text-[11px] text-violet-600 mt-1.5">
                เลือก {{ form.shipping_option_ids.length }} บริการ
              </p>
              <small v-if="errors.shipping_option_ids" class="text-rose-500 block mt-1">{{ errors.shipping_option_ids }}</small>
            </template>
          </div>
        </div>
      </FormSection>

      <!-- Images -->
      <FormSection title="รูปสินค้า" icon="fi fi-rr-picture" tone="fuchsia">
        <div v-if="!form.id" class="text-sm text-slate-400 py-3 text-center">
          <i class="fi fi-rr-info"></i> บันทึกสินค้าก่อน แล้วจึงเพิ่มรูปได้
        </div>
        <template v-else>
          <div v-if="images.length" class="flex flex-wrap gap-3 mb-3">
            <div v-for="img in images" :key="img.id" class="relative w-24 h-24 rounded-lg overflow-hidden border" :class="img.is_primary ? 'border-violet-500 ring-2 ring-violet-200' : 'border-slate-200'">
              <img :src="img.url" class="w-full h-full object-cover" />
              <span v-if="img.is_primary" class="absolute top-0 left-0 px-1 bg-violet-600 text-white text-[10px]">หลัก</span>
              <button class="absolute top-0 right-0 w-5 h-5 bg-rose-500 text-white text-xs flex items-center justify-center" @click="removeImage(img)">×</button>
            </div>
          </div>
          <input ref="fileInput" type="file" accept="image/*" multiple class="hidden" @change="onFiles" />
          <Button label="อัปโหลดรูป" icon="fi fi-rr-cloud-upload" outlined size="small" :loading="uploading" @click="$refs.fileInput.click()" />
        </template>
      </FormSection>

      <Message v-if="errorMsg" severity="error" :closable="false">{{ errorMsg }}</Message>
    </div>

    <template #footer>
      <Button label="ปิด" text @click="$emit('update:modelValue', false)" />
      <Button :label="form.id ? 'บันทึกการแก้ไข' : 'บันทึกสินค้า'" icon="fi fi-rr-disk" :loading="saving" @click="save" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, watchEffect } from 'vue'
import { RouterLink } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useAuth } from '../../composables/useAuth.js'
import FormSection from '../../components/FormSection.vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import Button from 'primevue/button'
import Message from 'primevue/message'

const props = defineProps({
  modelValue: Boolean,
  productId: { type: [Number, null], default: null },
  groups: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  districts: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const toast = useToast()
const { isAdmin, sellerGroupId } = useAuth()

const shippingOptions = ref([])
const loadingShipping = ref(false)

const blank = () => ({
  id: null, name: '', seller_group_id: sellerGroupId.value, category_id: null,
  sku: '', unit: 'ชิ้น', price: null, sale_price: null, stock_qty: 0,
  district: null, short_description: '', description: '', status: 'draft', is_featured: false,
  shipping_option_ids: [],
})
const form = reactive(blank())
const images = ref([])
const errors = reactive({})
const errorMsg = ref('')
const saving = ref(false)
const uploading = ref(false)
const fileInput = ref(null)

function genSku() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  const rand = (n) => Array.from({ length: n }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
  return `PRD-${rand(3)}-${rand(4)}`
}

const publishedToggle = computed({
  get: () => form.status === 'published',
  set: (v) => { form.status = v ? 'published' : 'draft' },
})

watch(() => props.modelValue, async (open) => {
  if (!open) return
  Object.assign(form, blank())
  images.value = []
  Object.keys(errors).forEach(k => delete errors[k])
  errorMsg.value = ''
  if (props.productId) await loadProduct(props.productId)
})

// โหลด global shipping options เมื่อ dialog เปิด
watchEffect(async () => {
  if (!props.modelValue) { shippingOptions.value = []; return }
  loadingShipping.value = true
  try {
    const { data } = await api.get('/market/shipping')
    shippingOptions.value = (data || []).filter(o => o.is_active)
  } catch { shippingOptions.value = [] }
  finally { loadingShipping.value = false }
})

function toggleShipping(id) {
  const idx = form.shipping_option_ids.indexOf(id)
  if (idx === -1) form.shipping_option_ids.push(id)
  else form.shipping_option_ids.splice(idx, 1)
}

async function loadProduct(id) {
  const { data } = await api.get(`/market/products/${id}`)
  Object.assign(form, {
    id: data.id, name: data.name, seller_group_id: data.seller_group_id, category_id: data.category_id,
    sku: data.sku, unit: data.unit, price: Number(data.price), sale_price: data.sale_price != null ? Number(data.sale_price) : null,
    stock_qty: data.stock_qty, district: data.district, short_description: data.short_description,
    description: data.description, status: data.status, is_featured: !!data.is_featured,
    shipping_option_ids: data.shipping_option_ids || [],
  })
  images.value = data.images || []
}

async function save() {
  saving.value = true
  errorMsg.value = ''
  Object.keys(errors).forEach(k => delete errors[k])

  // ตรวจ client-side: ต้องเลือกบริการจัดส่งอย่างน้อย 1
  if (shippingOptions.value.length > 0 && form.shipping_option_ids.length === 0) {
    errors.shipping_option_ids = 'กรุณาเลือกบริการจัดส่งอย่างน้อย 1 รายการ'
    errorMsg.value = 'กรุณาเลือกบริการจัดส่งอย่างน้อย 1 รายการ'
    saving.value = false
    return
  }

  try {
    const payload = { ...form }
    let res
    if (form.id) {
      res = await api.put(`/market/products/${form.id}`, payload)
    } else {
      res = await api.post('/market/products', payload)
      form.id = res.data.id
      images.value = res.data.images || []
      toast.add({ severity: 'success', summary: 'บันทึกสินค้าแล้ว', detail: 'เพิ่มรูปภาพได้เลย', life: 2500 })
      saving.value = false
      emit('saved')
      return // เปิด dialog ค้างไว้ให้เพิ่มรูป
    }
    toast.add({ severity: 'success', summary: 'บันทึกแล้ว', life: 2000 })
    emit('saved')
    emit('update:modelValue', false)
  } catch (e) {
    if (e.response?.status === 422) {
      const errs = e.response.data.errors || {}
      Object.entries(errs).forEach(([k, v]) => { errors[k] = v[0] })
      errorMsg.value = 'กรุณาตรวจสอบข้อมูลที่กรอก'
    } else {
      errorMsg.value = e.response?.data?.message || 'เกิดข้อผิดพลาด'
    }
  } finally {
    saving.value = false
  }
}

async function onFiles(e) {
  const files = Array.from(e.target.files || [])
  if (!files.length) return
  uploading.value = true
  try {
    const fd = new FormData()
    files.forEach(f => fd.append('images[]', f))
    const { data } = await api.post(`/market/products/${form.id}/images`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    images.value = data.images || []
    emit('saved')
  } catch (err) {
    toast.add({ severity: 'error', summary: 'อัปโหลดไม่สำเร็จ', detail: err.response?.data?.message || '', life: 3000 })
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

async function removeImage(img) {
  try {
    const { data } = await api.delete(`/market/products/${form.id}/images/${img.id}`)
    images.value = data.images || []
    emit('saved')
  } catch {
    toast.add({ severity: 'error', summary: 'ลบรูปไม่สำเร็จ', life: 2500 })
  }
}
</script>
