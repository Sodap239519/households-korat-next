<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 pb-8">

    <!-- ===== Banner หัว (สไตล์ Flash Sale) ===== -->
    <div class="-mx-4 sm:-mx-6 -mt-0 mb-6 overflow-hidden"
      style="background:linear-gradient(135deg,#4c1d95 0%,#7c3aed 50%,#f97316 100%)">
      <div class="px-5 pt-5 pb-3 flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-white/20 border border-white/25 flex items-center justify-center text-white text-2xl shrink-0 shadow-lg backdrop-blur-sm">
          <i class="fi fi-rr-shop"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-white font-extrabold text-xl tracking-wide leading-none">สมัครร้านค้า</p>
          <p class="text-white/70 text-xs mt-1.5 leading-relaxed">ร่วมขายสินค้าชุมชนโคราช — เข้าถึงลูกค้าทั่วไปได้ทันที</p>
        </div>
        <i class="fi fi-rr-badge-check text-white/15 text-6xl shrink-0 leading-none select-none hidden sm:block"></i>
      </div>
      <!-- ขั้นตอน -->
      <div class="px-5 pb-4 flex items-center gap-2 text-xs">
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition"
          :class="!submitted ? 'bg-white/25 text-white font-semibold' : 'bg-white/15 text-white/60'">
          <i :class="submitted ? 'fi fi-rr-check' : 'fi fi-rr-edit'"></i> กรอกข้อมูล
        </div>
        <i class="fi fi-rr-angle-right text-white/40 text-[10px]"></i>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition"
          :class="submitted ? 'bg-white/25 text-white font-semibold' : 'bg-white/10 text-white/40'">
          <i class="fi fi-rr-paper-plane"></i> ส่งคำขอ
        </div>
        <i class="fi fi-rr-angle-right text-white/40 text-[10px]"></i>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 text-white/40">
          <i class="fi fi-rr-badge-check"></i> รอผล
        </div>
      </div>
    </div>

    <!-- ===== ฟอร์ม ===== -->
    <div v-if="!submitted" class="space-y-5">

      <!-- แบนเนอร์ตีกลับ -->
      <div v-if="revisionNote" class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
        <p class="text-sm font-semibold text-amber-700 mb-1 flex items-center gap-2">
          <i class="fi fi-rr-undo"></i> ข้อมูลที่ต้องแก้ไข
        </p>
        <p class="text-sm text-amber-800 leading-relaxed">{{ revisionNote }}</p>
      </div>

      <!-- ข้อมูลผู้สมัคร -->
      <div class="box-card p-5">
        <h2 class="font-bold text-slate-800 flex items-center gap-2 mb-4">
          <i class="fi fi-rr-user text-violet-600"></i> ข้อมูลผู้สมัคร
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="form-label">ชื่อ-นามสกุล *</label>
            <input v-model="form.applicant_name" class="inp w-full" :class="{ 'border-rose-400': err.applicant_name }" />
            <p v-if="err.applicant_name" class="text-xs text-rose-500 mt-1">{{ err.applicant_name }}</p>
          </div>
          <div>
            <label class="form-label">เบอร์โทรศัพท์ *</label>
            <input v-model="form.applicant_phone" class="inp w-full" type="tel" :class="{ 'border-rose-400': err.applicant_phone }" />
            <p v-if="err.applicant_phone" class="text-xs text-rose-500 mt-1">{{ err.applicant_phone }}</p>
          </div>
          <div>
            <label class="form-label">อีเมล <span class="text-rose-400">*</span></label>
            <input v-model="form.applicant_email" class="inp w-full" type="email" required
              :class="{ 'border-rose-400': err.applicant_email }"
              placeholder="example@email.com" />
            <p v-if="err.applicant_email" class="text-xs text-rose-500 mt-1">{{ err.applicant_email }}</p>
            <p class="text-xs text-slate-400 mt-1">ใช้สำหรับเข้าสู่ระบบหลังได้รับการอนุมัติ</p>
          </div>
          <div class="sm:col-span-2">
            <label class="form-label">เลขบัตรประชาชน <span class="text-slate-400 font-normal">(ไม่บังคับ)</span></label>
            <input v-model="form.applicant_id_card" class="inp w-full" maxlength="13" />
          </div>
        </div>
      </div>

      <!-- ข้อมูลกิจการ -->
      <div class="box-card p-5">
        <h2 class="font-bold text-slate-800 flex items-center gap-2 mb-4">
          <i class="fi fi-rr-shop text-violet-600"></i> ข้อมูลกิจการ
        </h2>
        <div class="space-y-4">

          <!-- รูปโปรไฟล์ร้านค้า -->
          <div class="flex flex-col items-center pb-2">
            <input ref="logoInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onLogoChange" />
            <div
              class="relative w-24 h-24 rounded-full overflow-hidden bg-violet-50 border-2 border-dashed border-violet-200 cursor-pointer transition hover:border-violet-400 group"
              @click="logoInput.click()">
              <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex flex-col items-center justify-center text-violet-300">
                <i class="fi fi-rr-shop text-3xl leading-none"></i>
              </div>
              <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                <i class="fi fi-rr-camera text-white text-xl leading-none"></i>
              </div>
            </div>
            <button type="button" @click="logoInput.click()"
              class="mt-2 text-xs text-violet-600 hover:underline font-medium">
              {{ logoPreview ? 'เปลี่ยนรูปโปรไฟล์ร้าน' : 'เพิ่มรูปโปรไฟล์ร้านค้า' }}
            </button>
            <p class="text-[11px] text-slate-400 mt-0.5">JPG / PNG ไม่เกิน 2 MB</p>
          </div>

          <div>
            <label class="form-label">ชื่อร้าน / ชื่อกิจการ *</label>
            <input v-model="form.business_name" class="inp w-full"
              :class="{ 'border-rose-400': err.business_name }"
              placeholder="เช่น ร้านผักสวนครัวนายดำ" />
            <p v-if="err.business_name" class="text-xs text-rose-500 mt-1">{{ err.business_name }}</p>
          </div>

          <!-- ประเภทสินค้า/บริการ — multi-select chips -->
          <div>
            <label class="form-label">
              ประเภทสินค้า / บริการ
              <span class="text-slate-400 font-normal">(เลือกได้หลายข้อ)</span>
            </label>
            <div class="flex flex-wrap gap-2 mt-2">
              <button v-for="cat in CATEGORY_OPTIONS" :key="cat.key" type="button"
                @click="toggleCategory(cat.key)"
                class="px-3.5 py-1.5 rounded-full border text-sm font-medium transition-all"
                :class="form.business_categories.includes(cat.key)
                  ? 'bg-violet-600 border-violet-600 text-white shadow-sm scale-[1.03]'
                  : 'bg-white border-slate-200 text-slate-600 hover:border-violet-300 hover:text-violet-700'">
                {{ cat.label }}
              </button>
            </div>
            <p v-if="err.business_categories" class="text-xs text-rose-500 mt-1">{{ err.business_categories }}</p>
          </div>

          <!-- อำเภอ + ตำบล -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label">อำเภอที่ตั้งกิจการ</label>
              <select v-model="form.district" class="inp w-full" @change="onDistrictChange">
                <option value="">— เลือกอำเภอ —</option>
                <option v-for="d in districts" :key="d" :value="d">{{ d }}</option>
              </select>
            </div>
            <div>
              <label class="form-label">ตำบล / แขวง</label>
              <input v-model="form.sub_district" class="inp w-full" list="subdistrict-list" placeholder="ระบุตำบล / แขวง" />
              <datalist id="subdistrict-list">
                <option v-for="s in subDistricts" :key="s" :value="s"></option>
              </datalist>
            </div>
          </div>

          <div>
            <label class="form-label">ที่อยู่กิจการ</label>
            <input v-model="form.business_address" class="inp w-full" placeholder="บ้านเลขที่ หมู่บ้าน ถนน" />
          </div>

          <div>
            <label class="form-label">รายละเอียดสินค้า / บริการ <span class="text-slate-400 font-normal">(ไม่บังคับ)</span></label>
            <textarea v-model="form.products_description" rows="2" class="inp w-full"
              placeholder="อธิบายเพิ่มเติม เช่น จำนวน ราคาโดยประมาณ แหล่งที่มา"></textarea>
          </div>
        </div>
      </div>

      <!-- พิกัดร้านค้า -->
      <div class="box-card p-5">
        <h2 class="font-bold text-slate-800 flex items-center gap-2 mb-1">
          <i class="fi fi-rr-map-marker text-violet-600"></i> พิกัดที่ตั้งร้าน
        </h2>
        <p class="text-xs text-slate-400 mb-3">ใช้สำหรับแสดงระยะทางและค้นหาร้านใกล้เคียง</p>

        <div class="grid grid-cols-2 gap-3 mb-3">
          <div>
            <label class="form-label">Latitude (ละติจูด)</label>
            <input v-model="form.lat" class="inp w-full font-mono text-sm"
              placeholder="14.9718" type="text" inputmode="decimal"
              :class="{ 'border-rose-400': err.lat }" />
          </div>
          <div>
            <label class="form-label">Longitude (ลองจิจูด)</label>
            <input v-model="form.lng" class="inp w-full font-mono text-sm"
              placeholder="102.1006" type="text" inputmode="decimal"
              :class="{ 'border-rose-400': err.lng }" />
          </div>
        </div>

        <button type="button" @click="getGPS" :disabled="gettingGPS"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-violet-300 text-violet-700 text-sm font-medium hover:bg-violet-50 active:scale-95 transition disabled:opacity-50">
          <i class="fi fi-rr-map-marker-check"></i>
          {{ gettingGPS ? 'กำลังระบุตำแหน่ง...' : 'รับตำแหน่ง GPS อัตโนมัติ' }}
        </button>
        <p v-if="form.lat && form.lng" class="text-xs text-violet-600 mt-2 font-mono">
          <i class="fi fi-rr-check mr-1"></i>{{ form.lat }}, {{ form.lng }}
        </p>
        <p v-if="gpsError" class="text-xs text-rose-500 mt-2">{{ gpsError }}</p>
      </div>

      <!-- กลุ่มผู้ขายที่ต้องการสังกัด -->
      <div class="box-card p-5">
        <h2 class="font-bold text-slate-800 flex items-center gap-2 mb-1">
          <i class="fi fi-rr-users-alt text-violet-600"></i> กลุ่มผู้ขายที่ต้องการสังกัด
        </h2>
        <p class="text-xs text-slate-400 mb-3">ไม่บังคับ — admin จะกำหนดกลุ่มที่เหมาะสมเมื่ออนุมัติ</p>
        <div class="space-y-2">
          <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition"
            :class="form.requested_group_id === null ? 'border-violet-400 bg-violet-50' : 'border-slate-100 hover:border-violet-200'"
            @click="form.requested_group_id = null">
            <div class="w-4 h-4 rounded-full border-2 mt-0.5 shrink-0 transition"
              :class="form.requested_group_id === null ? 'border-violet-500 bg-violet-500' : 'border-slate-300'"></div>
            <span class="text-sm text-slate-600">ยังไม่แน่ใจ / ให้ admin กำหนดให้</span>
          </label>
          <label v-for="g in sellerGroups" :key="g.id"
            class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition"
            :class="form.requested_group_id === g.id ? 'border-violet-400 bg-violet-50' : 'border-slate-100 hover:border-violet-200'"
            @click="form.requested_group_id = g.id">
            <div class="w-4 h-4 rounded-full border-2 mt-0.5 shrink-0 transition"
              :class="form.requested_group_id === g.id ? 'border-violet-500 bg-violet-500' : 'border-slate-300'"></div>
            <div>
              <p class="text-sm font-medium text-slate-800">{{ g.name }}</p>
              <p class="text-xs text-slate-400">{{ (g.districts || []).join(', ') }}</p>
            </div>
          </label>
        </div>
      </div>

      <!-- เอกสารแนบ -->
      <div class="box-card p-5">
        <h2 class="font-bold text-slate-800 flex items-center gap-2 mb-1">
          <i class="fi fi-rr-file-upload text-violet-600"></i> เอกสารแนบ
          <span class="text-slate-400 font-normal text-sm">(ไม่บังคับ)</span>
        </h2>
        <p class="text-xs text-slate-400 mb-3">สำเนาบัตรประชาชน, ทะเบียนพาณิชย์, รูปสินค้า — PDF/JPG/PNG ไม่เกิน 5 MB/ไฟล์ สูงสุด 5 ไฟล์</p>
        <input type="file" ref="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png" @change="onFiles" class="hidden" />
        <button type="button" @click="fileInput.click()"
          class="border-2 border-dashed border-slate-200 rounded-xl p-4 w-full text-slate-400 hover:border-violet-300 hover:text-violet-600 transition flex items-center justify-center gap-2 text-sm">
          <i class="fi fi-rr-paperclip"></i> เลือกไฟล์
        </button>
        <ul v-if="files.length" class="mt-2 space-y-1">
          <li v-for="(f, i) in files" :key="i" class="flex items-center justify-between text-sm bg-slate-50 px-3 py-2 rounded-lg">
            <span class="truncate flex-1 text-slate-700"><i class="fi fi-rr-file mr-1.5 text-slate-400"></i>{{ f.name }}</span>
            <button @click="files.splice(i, 1)" class="text-rose-400 hover:text-rose-600 ml-2">
              <i class="fi fi-rr-trash text-xs"></i>
            </button>
          </li>
        </ul>
      </div>

      <!-- หมายเหตุ -->
      <div class="box-card p-5">
        <label class="form-label">หมายเหตุเพิ่มเติม <span class="text-slate-400 font-normal">(ไม่บังคับ)</span></label>
        <textarea v-model="form.note" rows="2" class="inp w-full"
          placeholder="ข้อมูลเพิ่มเติมที่อยากให้ admin ทราบ"></textarea>
      </div>

      <p v-if="errorMsg" class="text-sm text-rose-500 text-center">{{ errorMsg }}</p>

      <button :disabled="submitting" @click="submit"
        class="btn-sheen w-full h-13 rounded-full btn-orange font-semibold text-base disabled:opacity-60 flex items-center justify-center gap-2">
        <i class="fi fi-rr-paper-plane"></i>
        {{ submitting ? 'กำลังส่ง...' : editToken ? 'แก้ไขและส่งใหม่' : 'ส่งคำขอสมัคร' }}
      </button>
      <p class="text-xs text-center text-slate-400">ทีมงานจะตรวจสอบและติดต่อกลับภายใน 1-3 วันทำการ</p>
    </div>

    <!-- ===== หน้าสำเร็จ ===== -->
    <div v-else class="box-card p-8 text-center">
      <div class="w-20 h-20 rounded-full bg-violet-100 flex items-center justify-center text-4xl text-violet-500 mx-auto mb-4">
        <i class="fi fi-rr-check"></i>
      </div>
      <h2 class="text-xl font-bold text-slate-800 mb-1">ส่งคำขอสำเร็จ!</h2>
      <p class="text-slate-500 text-sm mb-6">ทีมงานจะตรวจสอบและติดต่อกลับทางเบอร์โทรที่ให้ไว้</p>

      <div class="bg-violet-50 border border-violet-100 rounded-xl p-4 text-center mb-6">
        <p class="text-sm font-medium text-violet-700">
          <i class="fi fi-rr-bookmark mr-1.5"></i> บันทึกคำขอในอุปกรณ์นี้แล้ว
        </p>
        <p class="text-xs text-slate-400 mt-1">ดูสถานะได้ตลอดเวลาที่เมนู "ติดตามสถานะ" โดยไม่ต้องจำรหัส</p>
      </div>

      <div class="flex flex-col gap-3">
        <RouterLink :to="`/shop/seller-apply/status/${trackingToken}`"
          class="btn-sheen btn-orange h-11 rounded-full font-semibold text-sm px-6 inline-flex items-center justify-center gap-2">
          <i class="fi fi-rr-eye"></i> ดูสถานะคำขอนี้
        </RouterLink>
        <RouterLink to="/shop/seller-apply/my"
          class="h-11 rounded-full font-semibold text-sm px-6 inline-flex items-center justify-center gap-2 border border-violet-200 text-violet-600 hover:bg-violet-50 transition">
          <i class="fi fi-rr-list"></i> ดูคำขอทั้งหมดของฉัน
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../../api/index.js'

const APPLY_HISTORY_KEY = 'seller_apply_history'

function saveToHistory(token, businessName) {
  try {
    const history = JSON.parse(localStorage.getItem(APPLY_HISTORY_KEY) || '[]')
    history.unshift({ token, business_name: businessName, submitted_at: new Date().toISOString() })
    localStorage.setItem(APPLY_HISTORY_KEY, JSON.stringify(history.slice(0, 20)))
  } catch { /* localStorage unavailable */ }
}

const CATEGORY_OPTIONS = [
  { key: 'vegetable_fruit',  label: 'สินค้าเกษตร (พืช-ผัก-ผลไม้)' },
  { key: 'fresh_food',       label: 'อาหารสด (เนื้อ ปลา ไข่)' },
  { key: 'processed_food',   label: 'อาหารแปรรูป' },
  { key: 'ready_to_eat',     label: 'อาหารพร้อมทาน' },
  { key: 'bakery',           label: 'เบเกอรี่-ขนม' },
  { key: 'beverage',         label: 'เครื่องดื่ม' },
  { key: 'herb',             label: 'สมุนไพร-ยาสมุนไพร' },
  { key: 'household',        label: 'ของใช้ในบ้าน' },
  { key: 'handicraft',       label: 'หัตถกรรม-ของฝาก' },
  { key: 'clothing',         label: 'เสื้อผ้า-สิ่งทอ' },
  { key: 'agriculture',      label: 'เกษตรกรรม-เมล็ดพันธุ์' },
  { key: 'service',          label: 'บริการ (ซ่อม-สอน-ดูแล)' },
]

const router             = useRouter()
const route              = useRoute()
const districts          = ref([])
const sellerGroups       = ref([])
const subDistricts       = ref([])
const loadingSubDistricts = ref(false)
const submitted          = ref(false)
const submitting         = ref(false)
const trackingToken      = ref('')
const errorMsg           = ref('')
const fileInput          = ref(null)
const files              = ref([])
const err                = reactive({})
const gettingGPS         = ref(false)
const gpsError           = ref('')
const logoInput          = ref(null)
const logoFile           = ref(null)
const logoPreview        = ref('')
const editToken          = ref(null)
const revisionNote       = ref('')

const form = reactive({
  applicant_name: '', applicant_phone: '', applicant_email: '', applicant_id_card: '',
  business_name: '',
  business_categories: [],
  district: '', sub_district: '', business_address: '',
  lat: '', lng: '',
  products_description: '', note: '',
  requested_group_id: null,
})

function toggleCategory(key) {
  const idx = form.business_categories.indexOf(key)
  if (idx === -1) form.business_categories.push(key)
  else form.business_categories.splice(idx, 1)
}

async function onDistrictChange() {
  form.sub_district = ''
  subDistricts.value = []
  if (!form.district) return
  loadingSubDistricts.value = true
  try {
    const { data } = await api.get('/locations/sub-districts', { params: { district: form.district } })
    subDistricts.value = data || []
  } catch { subDistricts.value = [] }
  finally { loadingSubDistricts.value = false }
}

function getGPS() {
  gpsError.value = ''
  if (!navigator.geolocation) { gpsError.value = 'เบราว์เซอร์ไม่รองรับ GPS'; return }
  gettingGPS.value = true
  navigator.geolocation.getCurrentPosition(
    pos => {
      form.lat = pos.coords.latitude.toFixed(6)
      form.lng = pos.coords.longitude.toFixed(6)
      gettingGPS.value = false
    },
    () => {
      gpsError.value = 'ไม่สามารถรับตำแหน่งได้ — กรุณากรอกพิกัดด้วยตนเอง'
      gettingGPS.value = false
    },
    { timeout: 10000 }
  )
}

function onLogoChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  logoFile.value = file
  logoPreview.value = URL.createObjectURL(file)
  e.target.value = ''
}

function onFiles(e) {
  const combined = [...files.value, ...Array.from(e.target.files || [])].slice(0, 5)
  files.value = combined
  e.target.value = ''
}

async function submit() {
  Object.keys(err).forEach(k => delete err[k])
  errorMsg.value = ''
  submitting.value = true
  try {
    const fd = new FormData()
    const { business_categories, requested_group_id, ...rest } = form
    Object.entries(rest).forEach(([k, v]) => { if (v !== '' && v !== null) fd.append(k, v) })
    business_categories.forEach(c => fd.append('business_categories[]', c))
    if (requested_group_id !== null) fd.append('requested_group_id', requested_group_id)
    if (logoFile.value) fd.append('logo', logoFile.value)
    files.value.forEach((f, i) => fd.append(`documents[${i}]`, f))

    const { data } = editToken.value
      ? await api.put(`/seller/apply/${editToken.value}`, fd)
      : await api.post('/seller/apply', fd)
    trackingToken.value = data.token
    saveToHistory(data.token, form.business_name)
    submitted.value = true
  } catch (e) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors || {}
      console.error('[SellerApply 422 errors]', errors)
      Object.entries(errors).forEach(([k, v]) => { err[k] = v[0] })
      errorMsg.value = 'กรุณาตรวจสอบข้อมูลที่กรอก: ' + Object.entries(errors).map(([k, v]) => `${k}: ${v[0]}`).join(' | ')
    } else {
      errorMsg.value = e.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
    }
  } finally { submitting.value = false }
}

function copyToken() { navigator.clipboard?.writeText(trackingToken.value) }

onMounted(async () => {
  try {
    const [dRes, gRes] = await Promise.all([
      api.get('/locations/districts'),
      api.get('/seller/apply/groups'),
    ])
    districts.value   = dRes.data || []
    sellerGroups.value = gRes.data || []
  } catch { /* ignore */ }

  // โหมดแก้ไข — โหลดข้อมูลคำขอเดิม
  const token = route.query.edit
  if (token) {
    try {
      const { data } = await api.get(`/seller/apply/${token}/edit`)
      editToken.value = token
      revisionNote.value = data.revision_note || ''
      // pre-fill form
      Object.assign(form, {
        applicant_name:       data.applicant_name    || '',
        applicant_phone:      data.applicant_phone   || '',
        applicant_email:      data.applicant_email   || '',
        applicant_id_card:    data.applicant_id_card || '',
        business_name:        data.business_name     || '',
        business_categories:  data.business_categories || [],
        district:             data.district          || '',
        sub_district:         data.sub_district      || '',
        business_address:     data.business_address  || '',
        lat:                  data.lat               || '',
        lng:                  data.lng               || '',
        products_description: data.products_description || '',
        note:                 data.note              || '',
        requested_group_id:   data.requested_group_id ?? null,
      })
      if (data.district) {
        // โหลดตำบลของอำเภอที่เลือก
        try {
          const sRes = await api.get('/locations/sub-districts', { params: { district: data.district } })
          subDistricts.value = sRes.data || []
        } catch { /* ignore */ }
      }
    } catch { /* ถ้าโหลดไม่ได้ ให้ใช้ฟอร์มเปล่าตามปกติ */ }
  }
})
</script>

<style scoped>
.inp { height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); background: white; width: 100%; font-size: 0.875rem; }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
.inp:disabled { background: rgb(248 250 252); color: rgb(148 163 184); cursor: not-allowed; }
textarea.inp { height: auto; padding: 0.5rem 0.75rem; }
select.inp { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.75rem center; padding-right: 2rem; }
.h-13 { height: 3.25rem; }
</style>
