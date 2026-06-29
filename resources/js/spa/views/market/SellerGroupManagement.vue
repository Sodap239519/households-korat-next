<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-users-alt text-violet-600"></i> กลุ่มผู้ขาย
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">
          {{ isAdmin ? 'จัดการ 5 กลุ่ม: อำเภอที่ดูแล สมาชิก และการแจ้งเตือน LINE' : 'แก้ไขข้อมูลติดต่อและการแจ้งเตือน LINE ของกลุ่ม' }}
        </p>
      </div>
      <Button v-if="isAdmin" label="เพิ่มกลุ่ม" icon="fi fi-rr-plus" size="large" @click="openCreate" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div v-for="g in rows" :key="g.id" class="box-card p-4">
        <div class="flex items-start gap-3">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center text-xl shrink-0"><i class="fi fi-rr-shop"></i></div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <h3 class="font-bold text-slate-800 truncate">{{ g.name }}</h3>
              <span v-if="!g.is_active" class="text-xs text-slate-400">(ปิด)</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">{{ (g.districts || []).join(', ') || 'ยังไม่ระบุอำเภอ' }}</p>
            <div class="flex gap-3 mt-2 text-xs text-slate-500">
              <span><i class="fi fi-rr-box-open"></i> {{ g.products_count ?? 0 }} สินค้า</span>
              <span><i class="fi fi-rr-user"></i> {{ g.members_count ?? 0 }} สมาชิก</span>
              <span :class="g.line_target_id ? 'text-emerald-600' : 'text-slate-400'">
                <i class="fi fi-rr-bell"></i> LINE {{ g.line_target_id ? (g.line_notify_enabled ? 'เปิด' : 'พัก') : 'ยังไม่ตั้ง' }}
              </span>
            </div>
          </div>
          <Button icon="fi fi-rr-edit" text rounded size="small" @click="openEdit(g)" />
        </div>
      </div>
    </div>

    <!-- Edit dialog -->
    <Dialog v-model:visible="dialogOpen" modal :header="form.id ? 'แก้ไขกลุ่มผู้ขาย' : 'เพิ่มกลุ่มผู้ขาย'" :style="{ width: '40rem' }" :breakpoints="{ '960px': '95vw' }">
      <div class="space-y-4">
        <FormSection title="ข้อมูลกลุ่ม" icon="fi fi-rr-shop" tone="violet">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="sm:col-span-2">
              <label class="form-label">ชื่อกลุ่ม *</label>
              <InputText v-model="form.name" class="w-full" :disabled="!isAdmin" :invalid="!!err.name" />
              <small v-if="err.name" class="text-rose-500">{{ err.name }}</small>
            </div>
            <div class="sm:col-span-2">
              <label class="form-label">รายละเอียด</label>
              <Textarea v-model="form.description" rows="2" class="w-full" autoResize />
            </div>
            <div>
              <label class="form-label">เบอร์ติดต่อ</label>
              <InputText v-model="form.contact_phone" class="w-full" />
            </div>
            <div>
              <label class="form-label">ที่อยู่/ที่ตั้ง</label>
              <InputText v-model="form.contact_address" class="w-full" />
            </div>
            <div class="sm:col-span-2" v-if="isAdmin">
              <label class="form-label">อำเภอที่ดูแล</label>
              <MultiSelect v-model="form.districts" :options="districts" placeholder="เลือกอำเภอ" filter class="w-full" display="chip" />
            </div>
            <!-- พิกัดแผนที่ -->
            <div v-if="isAdmin">
              <label class="form-label">ละติจูด (lat)</label>
              <InputText v-model="form.lat" class="w-full" placeholder="เช่น 14.9736" />
            </div>
            <div v-if="isAdmin">
              <label class="form-label">ลองจิจูด (lng)</label>
              <InputText v-model="form.lng" class="w-full" placeholder="เช่น 102.1015" />
            </div>
            <div v-if="isAdmin" class="sm:col-span-2">
              <label class="form-label">ป้ายชื่อบนแผนที่ <span class="text-slate-400 font-normal">(ถ้าไม่กรอก ใช้ชื่อกลุ่ม)</span></label>
              <InputText v-model="form.map_label" class="w-full" placeholder="เช่น ตลาดชุมชนเมือง" />
            </div>
            <!-- Map picker -->
            <div v-if="isAdmin" class="sm:col-span-2">
              <button type="button" @click="toggleMapPicker"
                class="flex items-center gap-2 text-xs font-semibold transition"
                :class="mapPickerOpen ? 'text-slate-500 hover:text-slate-700' : 'text-violet-700 hover:text-violet-800'">
                <i :class="mapPickerOpen ? 'fi fi-rr-cross-small' : 'fi fi-rr-map-marker'"></i>
                {{ mapPickerOpen ? 'ซ่อนแผนที่' : 'คลิกบนแผนที่เพื่อปักพิกัด' }}
              </button>
              <div v-if="mapPickerOpen" class="mt-2 rounded-xl overflow-hidden border border-violet-100">
                <div ref="mapPickerEl" style="height: 260px;"></div>
                <div class="bg-violet-50 px-3 py-2 flex items-center justify-between text-xs">
                  <span class="text-slate-400">
                    {{ form.lat && form.lng ? `${Number(form.lat).toFixed(5)}, ${Number(form.lng).toFixed(5)}` : 'คลิกบนแผนที่หรือลากหมุดเพื่อตั้งพิกัด' }}
                  </span>
                  <button type="button" @click="useMyLocation"
                    class="text-violet-600 font-semibold hover:underline flex items-center gap-1">
                    <i class="fi fi-rr-crosshairs text-[10px]"></i> ใช้ตำแหน่งปัจจุบัน
                  </button>
                </div>
              </div>
            </div>
          </div>
        </FormSection>

        <FormSection title="บัญชีรับเงิน" icon="fi fi-rr-bank" tone="amber">
          <p class="text-xs text-slate-500 mb-2">แสดงในหน้าโอนเงินของลูกค้า และใช้เทียบชื่อผู้รับเมื่อตรวจสลิป</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="form-label">ธนาคาร</label>
              <InputText v-model="form.bank_name" class="w-full" placeholder="เช่น ธ.กรุงไทย" />
            </div>
            <div>
              <label class="form-label">เลขที่บัญชี</label>
              <InputText v-model="form.bank_account_no" class="w-full" />
            </div>
            <div>
              <label class="form-label">ชื่อบัญชี</label>
              <InputText v-model="form.bank_account_name" class="w-full" />
            </div>
            <div>
              <label class="form-label">พร้อมเพย์ (ไม่บังคับ)</label>
              <InputText v-model="form.promptpay_id" class="w-full" placeholder="เบอร์/เลขบัตรประชาชน" />
            </div>
          </div>
        </FormSection>

        <FormSection title="แจ้งเตือน LINE" icon="fi fi-rr-bell" tone="emerald">
          <p class="text-xs text-slate-500 mb-2">
            ใช้ LINE Official Account กลางของระบบ — กรอก "ปลายทาง" (LINE group id / user id) ที่จะรับแจ้งเตือนคำสั่งซื้อ/การชำระเงิน/การคืนสินค้า
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="sm:col-span-2">
              <label class="form-label">LINE target id</label>
              <InputText v-model="form.line_target_id" class="w-full" placeholder="เช่น Cxxxxxxxx (group id) หรือ Uxxxxxxxx (user id)" />
            </div>
            <div class="flex items-center gap-2">
              <ToggleSwitch v-model="form.line_notify_enabled" inputId="lineon" />
              <label for="lineon" class="text-sm text-slate-600">เปิดแจ้งเตือน LINE</label>
            </div>
          </div>
        </FormSection>

        <FormSection v-if="isAdmin && form.id" title="สมาชิกกลุ่ม (เจ้าหน้าที่)" icon="fi fi-rr-users-alt" tone="fuchsia">
          <MultiSelect v-model="form.member_ids" :options="staffUsers" optionLabel="name" optionValue="id" placeholder="เลือกเจ้าหน้าที่" filter class="w-full" display="chip" />
          <p class="text-xs text-slate-400 mt-1">เจ้าหน้าที่ที่เลือกจะจัดการเฉพาะข้อมูลของกลุ่มนี้</p>
        </FormSection>

        <div v-if="isAdmin && form.id" class="flex items-center gap-2">
          <ToggleSwitch v-model="form.is_active" inputId="grpact" />
          <label for="grpact" class="text-sm text-slate-600">เปิดใช้งานกลุ่ม</label>
        </div>

        <Message v-if="errorMsg" severity="error" :closable="false">{{ errorMsg }}</Message>
      </div>
      <template #footer>
        <Button label="ปิด" text @click="dialogOpen = false" />
        <Button label="บันทึก" icon="fi fi-rr-disk" :loading="saving" @click="save" />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useAuth } from '../../composables/useAuth.js'
import FormSection from '../../components/FormSection.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import MultiSelect from 'primevue/multiselect'
import ToggleSwitch from 'primevue/toggleswitch'
import Message from 'primevue/message'
import Toast from 'primevue/toast'

const { isAdmin } = useAuth()
const toast = useToast()

const rows = ref([])
const districts = ref([])
const staffUsers = ref([])
const dialogOpen = ref(false)
const saving = ref(false)
const err = reactive({})
const errorMsg = ref('')

const mapPickerEl   = ref(null)
const mapPickerOpen = ref(false)
let mapPickerInst   = null
let pickerMarker    = null
let L               = null

async function initMapPicker() {
  L = await import('leaflet')
  L = L.default || L
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
    shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
  })
  await import('leaflet/dist/leaflet.css')

  const lat = Number(form.lat) || 14.9736
  const lng = Number(form.lng) || 102.1015
  const zoom = (form.lat && form.lng) ? 14 : 10

  mapPickerInst = L.map(mapPickerEl.value).setView([lat, lng], zoom)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 18,
  }).addTo(mapPickerInst)

  function placeMarker(mlat, mlng) {
    if (pickerMarker) mapPickerInst.removeLayer(pickerMarker)
    pickerMarker = L.marker([mlat, mlng], { draggable: true })
      .addTo(mapPickerInst)
      .bindPopup('พิกัดร้าน').openPopup()
    pickerMarker.on('dragend', e => {
      const p = e.target.getLatLng()
      form.lat = p.lat.toFixed(6)
      form.lng = p.lng.toFixed(6)
    })
  }

  if (form.lat && form.lng) placeMarker(Number(form.lat), Number(form.lng))

  mapPickerInst.on('click', e => {
    form.lat = e.latlng.lat.toFixed(6)
    form.lng = e.latlng.lng.toFixed(6)
    placeMarker(e.latlng.lat, e.latlng.lng)
  })
}

async function toggleMapPicker() {
  if (mapPickerOpen.value) {
    if (mapPickerInst) { mapPickerInst.remove(); mapPickerInst = null; pickerMarker = null }
    mapPickerOpen.value = false
    return
  }
  mapPickerOpen.value = true
  await nextTick()
  await initMapPicker()
}

function useMyLocation() {
  if (!navigator.geolocation) return
  navigator.geolocation.getCurrentPosition(pos => {
    form.lat = pos.coords.latitude.toFixed(6)
    form.lng = pos.coords.longitude.toFixed(6)
    if (mapPickerInst && L) {
      if (pickerMarker) mapPickerInst.removeLayer(pickerMarker)
      pickerMarker = L.marker([Number(form.lat), Number(form.lng)], { draggable: true })
        .addTo(mapPickerInst)
        .bindPopup('ตำแหน่งของคุณ').openPopup()
      mapPickerInst.setView([Number(form.lat), Number(form.lng)], 14)
      pickerMarker.on('dragend', e => {
        const p = e.target.getLatLng()
        form.lat = p.lat.toFixed(6)
        form.lng = p.lng.toFixed(6)
      })
    }
  })
}

watch(dialogOpen, val => {
  if (!val && mapPickerInst) { mapPickerInst.remove(); mapPickerInst = null; pickerMarker = null; mapPickerOpen.value = false }
})

const blank = () => ({ id: null, name: '', description: '', contact_phone: '', contact_address: '', bank_name: '', bank_account_no: '', bank_account_name: '', promptpay_id: '', districts: [], lat: '', lng: '', map_label: '', line_target_id: '', line_notify_enabled: true, is_active: true, member_ids: [] })
const form = reactive(blank())

async function reload() {
  rows.value = (await api.get('/market/seller-groups')).data || []
}

async function loadFacets() {
  districts.value = (await api.get('/locations/districts')).data || []
  if (isAdmin.value) {
    try {
      const { data } = await api.get('/admin/users', { params: { per_page: 200 } })
      const list = data.data || data
      staffUsers.value = (list || []).filter(u => u.role !== 'customer')
    } catch { /* ไม่ใช่ admin หรือไม่มีสิทธิ์ */ }
  }
}

function openCreate() { Object.assign(form, blank()); clearErr(); dialogOpen.value = true }
function openEdit(g) {
  Object.assign(form, {
    id: g.id, name: g.name, description: g.description || '', contact_phone: g.contact_phone || '',
    contact_address: g.contact_address || '',
    bank_name: g.bank_name || '', bank_account_no: g.bank_account_no || '', bank_account_name: g.bank_account_name || '', promptpay_id: g.promptpay_id || '',
    districts: g.districts || [], lat: g.lat || '', lng: g.lng || '', map_label: g.map_label || '',
    line_target_id: g.line_target_id || '',
    line_notify_enabled: g.line_notify_enabled !== false, is_active: g.is_active !== false,
    member_ids: (g.members || []).map(m => m.id),
  })
  clearErr()
  dialogOpen.value = true
  if (isAdmin.value && g.id) loadMembers(g.id)
}
function clearErr() { Object.keys(err).forEach(k => delete err[k]); errorMsg.value = '' }

async function loadMembers(id) {
  try {
    const { data } = await api.get(`/market/seller-groups/${id}`)
    form.member_ids = (data.members || []).map(m => m.id)
  } catch { /* ignore */ }
}

async function save() {
  saving.value = true; clearErr()
  try {
    const bank = { bank_name: form.bank_name, bank_account_no: form.bank_account_no, bank_account_name: form.bank_account_name, promptpay_id: form.promptpay_id }
    const payload = isAdmin.value
      ? { name: form.name, description: form.description, contact_phone: form.contact_phone, contact_address: form.contact_address, ...bank, districts: form.districts, lat: form.lat || null, lng: form.lng || null, map_label: form.map_label || null, line_target_id: form.line_target_id, line_notify_enabled: form.line_notify_enabled, is_active: form.is_active }
      : { description: form.description, contact_phone: form.contact_phone, contact_address: form.contact_address, ...bank, line_target_id: form.line_target_id, line_notify_enabled: form.line_notify_enabled }

    if (form.id) await api.put(`/market/seller-groups/${form.id}`, payload)
    else { const res = await api.post('/market/seller-groups', payload); form.id = res.data.id }

    // สมาชิก (admin)
    if (isAdmin.value && form.id) {
      await api.post(`/market/seller-groups/${form.id}/members`, { user_ids: form.member_ids })
    }

    toast.add({ severity: 'success', summary: 'บันทึกแล้ว', life: 2000 })
    dialogOpen.value = false
    reload()
  } catch (e) {
    if (e.response?.status === 422) { Object.entries(e.response.data.errors || {}).forEach(([k, v]) => err[k] = v[0]); errorMsg.value = 'ตรวจสอบข้อมูล' }
    else errorMsg.value = e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally { saving.value = false }
}

onMounted(() => { loadFacets(); reload() })

onUnmounted(() => {
  if (mapPickerInst) { mapPickerInst.remove(); mapPickerInst = null }
})
</script>
