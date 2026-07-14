<template>
  <div class="p-3 sm:p-6 space-y-4 sm:space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-file-check text-violet-600"></i> คำขอสมัครขาย
        </h2>
        <p class="text-sm text-slate-500 mt-0.5">
          {{ isSuperAdmin ? 'อนุมัติได้ทันที — ไม่ต้องรอ admin พิจารณาก่อน' : 'พิจารณาขั้นแรก — ส่งต่อ superadmin เพื่ออนุมัติขั้นสุดท้าย' }}
        </p>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="flex gap-2 flex-wrap">
      <button v-for="tab in tabs" :key="tab.value"
        @click="activeTab = tab.value; load()"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition"
        :class="activeTab === tab.value ? 'bg-violet-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-violet-300 hover:bg-violet-50'">
        <i v-if="tab.icon" :class="[tab.icon, 'text-[11px]']"></i>
        {{ tab.label }}
        <span v-if="tabCounts[tab.value] > 0"
          class="min-w-[18px] h-[18px] px-1 rounded-full text-[11px] font-bold flex items-center justify-center"
          :class="activeTab === tab.value ? 'bg-white/30 text-white' : tab.badgeClass || 'bg-slate-200 text-slate-600'">
          {{ tabCounts[tab.value] }}
        </span>
      </button>
    </div>

    <!-- Table -->
    <div class="box-card overflow-hidden">
      <div v-if="loading" class="p-10 text-center text-slate-400">
        <i class="fi fi-rr-spinner animate-spin text-xl"></i>
      </div>
      <div v-else-if="!rows.length" class="p-10 text-center text-slate-400">
        <i class="fi fi-rr-inbox text-3xl mb-2"></i>
        <p>ไม่มีคำขอในสถานะนี้</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-100">
            <tr class="text-left text-xs text-slate-500 font-semibold">
              <th class="px-4 py-3">ผู้สมัคร / กิจการ</th>
              <th class="px-4 py-3">อำเภอ</th>
              <th class="px-4 py-3">กลุ่มที่ขอ</th>
              <th class="px-4 py-3">วันที่สมัคร</th>
              <th class="px-4 py-3">สถานะ</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50/50 transition">
              <td class="px-4 py-3">
                <p class="font-medium text-slate-800">{{ row.applicant_name }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ row.business_name }}</p>
              </td>
              <td class="px-4 py-3 text-slate-500">{{ row.district || '—' }}</td>
              <td class="px-4 py-3 text-slate-500 text-xs">{{ row.requested_group?.name || '—' }}</td>
              <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap">{{ fmtDate(row.created_at) }}</td>
              <td class="px-4 py-3">
                <span class="inline-block text-[11px] font-semibold px-2 py-0.5 rounded-full"
                  :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                <button @click="openDetail(row)" class="text-xs text-violet-600 hover:text-violet-800 font-medium flex items-center gap-1 ml-auto">
                  <i class="fi fi-rr-eye"></i> ดู / พิจารณา
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-100">
        <p class="text-xs text-slate-400">{{ pagination.total }} รายการ</p>
        <div class="flex gap-1">
          <button v-for="p in pagination.last_page" :key="p"
            @click="page = p; load()"
            class="w-7 h-7 rounded-lg text-xs font-medium"
            :class="p === pagination.current_page ? 'bg-violet-600 text-white' : 'text-slate-500 hover:bg-slate-100'">{{ p }}</button>
        </div>
      </div>
    </div>

    <!-- ===== Detail / Review Dialog ===== -->
    <Dialog v-model:visible="dialogOpen" modal header="คำขอสมัครขาย"
      :style="{ width: '44rem' }" :breakpoints="{ '960px': '95vw' }">
      <div v-if="active" class="space-y-5">

        <!-- Info -->
        <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
          <InfoRow label="ชื่อ-นามสกุล"   :value="active.applicant_name" />
          <InfoRow label="เบอร์โทร"        :value="active.applicant_phone" />
          <InfoRow label="อีเมล"           :value="active.applicant_email || '—'" />
          <InfoRow label="เลขบัตรประชาชน" :value="active.applicant_id_card || '—'" />
          <InfoRow label="ชื่อกิจการ"     :value="active.business_name" />
          <InfoRow label="ประเภทสินค้า"   :value="active.business_type || '—'" />
          <InfoRow label="อำเภอ"          :value="active.district || '—'" />
          <InfoRow label="ที่อยู่กิจการ"  :value="active.business_address || '—'" />
          <div class="col-span-2">
            <p class="text-xs text-slate-400 mb-1">สินค้าที่จะขาย</p>
            <p class="text-slate-700 leading-relaxed">{{ active.products_description || '—' }}</p>
          </div>
          <div class="col-span-2" v-if="active.note">
            <p class="text-xs text-slate-400 mb-1">หมายเหตุจากผู้สมัคร</p>
            <p class="text-slate-600">{{ active.note }}</p>
          </div>
          <InfoRow label="กลุ่มที่ขอสังกัด" :value="active.requested_group?.name || '—'" />
          <InfoRow label="วันที่สมัคร" :value="fmtDate(active.created_at)" />
          <InfoRow label="ตำบล/แขวง" :value="active.sub_district || '—'" />
          <div v-if="active.lat && active.lng" class="col-span-1">
            <p class="text-xs text-slate-400">พิกัด</p>
            <p class="font-medium text-slate-800 mt-0.5">{{ active.lat }}°N, {{ active.lng }}°E</p>
          </div>
          <img v-if="active.logo_path" :src="`/storage/${active.logo_path}`" class="w-24 h-24 rounded-full object-cover border-2 border-violet-100 col-span-2" />
          <div v-if="active.business_categories?.length" class="col-span-2">
            <p class="text-xs text-slate-400 mb-1">หมวดหมู่สินค้า</p>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="cat in active.business_categories" :key="cat"
                class="text-xs bg-violet-50 text-violet-700 border border-violet-100 px-2 py-0.5 rounded-full">
                {{ CATEGORY_MAP[cat] ?? cat }}
              </span>
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div v-if="active.documents?.length">
          <p class="text-xs text-slate-400 mb-2">เอกสารแนบ</p>
          <div class="flex flex-wrap gap-2">
            <a v-for="(doc, i) in active.documents" :key="i" :href="`/storage/${doc}`" target="_blank"
              class="flex items-center gap-1.5 text-xs text-violet-600 hover:text-violet-800 bg-violet-50 px-2.5 py-1.5 rounded-lg hover:bg-violet-100 transition">
              <i class="fi fi-rr-file"></i> เอกสาร {{ i + 1 }}
            </a>
          </div>
        </div>

        <!-- Previous admin review info (superadmin viewing) -->
        <div v-if="active.admin_reviewed_at && isSuperAdmin" class="bg-blue-50 rounded-xl p-3 text-sm">
          <p class="font-semibold text-blue-700 mb-1">ผล Admin ({{ active.admin?.name || '?' }})</p>
          <p class="text-blue-600">{{ statusLabel(active.status) }}</p>
          <p v-if="active.admin_note" class="text-blue-500 mt-1 text-xs">{{ active.admin_note }}</p>
        </div>
        <!-- Superadmin เห็น pending ที่ยังไม่มี admin review -->
        <div v-else-if="isSuperAdmin && active.status === 'pending' && !active.admin_reviewed_at"
          class="bg-violet-50 border border-violet-200 rounded-xl p-3 text-sm text-violet-700 flex items-center gap-2">
          <i class="fi fi-rr-info text-violet-500"></i>
          ยังไม่มี admin พิจารณา — superadmin อนุมัติตรงได้เลย
        </div>

        <div v-if="active.status === 'escalated'" class="bg-orange-50 border border-orange-200 rounded-xl p-3 text-sm text-orange-700 flex items-center gap-2">
          <i class="fi fi-rr-clock text-orange-500"></i>
          Admin ไม่ได้พิจารณาภายใน 1 ชั่วโมง — ส่งตรงถึง Superadmin
        </div>

        <!-- ===== ตีกลับให้แก้ไข ===== -->
        <div v-if="canRequestRevision" class="pt-1">
          <button @click="showRevisionForm = !showRevisionForm"
            class="flex items-center gap-2 px-4 h-9 rounded-xl border border-amber-300 text-amber-700 bg-amber-50 hover:bg-amber-100 text-sm font-semibold transition">
            <i class="fi fi-rr-undo"></i> ตีกลับให้แก้ไข
          </button>
          <div v-if="showRevisionForm" class="mt-3 p-4 bg-amber-50 rounded-xl border border-amber-200">
            <p class="text-sm font-medium text-amber-700 mb-2">ระบุสิ่งที่ต้องแก้ไข</p>
            <textarea v-model="revisionNote" rows="3" class="inp w-full"
              placeholder="เช่น กรุณาแนบสำเนาบัตรประชาชน / ข้อมูลที่อยู่ไม่ครบถ้วน"></textarea>
            <p v-if="revisionErr" class="text-xs text-rose-500 mt-1">{{ revisionErr }}</p>
            <div class="flex gap-2 mt-2">
              <button @click="submitRevision" :disabled="revisionSaving"
                class="flex items-center gap-1.5 px-4 h-9 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold transition disabled:opacity-60">
                <i class="fi fi-rr-paper-plane"></i> ส่งคำขอแก้ไข
              </button>
              <button @click="showRevisionForm = false; revisionNote = ''; revisionErr = ''"
                class="px-4 h-9 rounded-xl border border-slate-200 text-slate-600 text-sm hover:bg-slate-50 transition">
                ยกเลิก
              </button>
            </div>
          </div>
        </div>

        <!-- ===== Action: Admin ขั้น 1 ===== -->
        <FormSection v-if="canAdminReview" title="พิจารณาขั้นที่ 1 (Admin)" icon="fi fi-rr-user-check" tone="violet">
          <div class="space-y-3">
            <div>
              <label class="form-label">หมายเหตุ (แสดงให้ superadmin เห็น)</label>
              <textarea v-model="reviewForm.note" rows="2" class="inp w-full"></textarea>
            </div>
            <p v-if="reviewErr" class="text-xs text-rose-500">{{ reviewErr }}</p>
            <div class="flex gap-2">
              <button :disabled="reviewSaving" @click="adminReview('reject')"
                class="flex-1 h-10 rounded-xl border border-rose-200 text-rose-600 text-sm font-semibold hover:bg-rose-50 transition disabled:opacity-60">
                <i class="fi fi-rr-cross-circle mr-1"></i> ปฏิเสธ
              </button>
              <button :disabled="reviewSaving" @click="adminReview('approve')"
                class="flex-1 h-10 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700 transition disabled:opacity-60">
                <i class="fi fi-rr-check mr-1"></i> อนุมัติ → ส่ง Superadmin
              </button>
            </div>
          </div>
        </FormSection>

        <!-- ===== Action: Superadmin ขั้นสุดท้าย ===== -->
        <FormSection v-if="canSuperAdminReview" title="พิจารณาขั้นสุดท้าย (Superadmin)" icon="fi fi-rr-badge-check" tone="emerald">
          <div class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="form-label">กำหนดกลุ่มผู้ขาย</label>
                <select v-model="reviewForm.assigned_group_id" class="inp w-full">
                  <option :value="null">— สร้างกลุ่มใหม่ —</option>
                  <option v-for="g in sellerGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
                </select>
              </div>
              <div v-if="!reviewForm.assigned_group_id">
                <label class="form-label">ชื่อกลุ่มใหม่ *</label>
                <input v-model="reviewForm.new_group_name" class="inp w-full" :placeholder="active.business_name" />
              </div>
              <div class="sm:col-span-2">
                <label class="form-label">รหัสผ่านชั่วคราวสำหรับบัญชีที่สร้าง</label>
                <input v-model="reviewForm.temp_password" class="inp w-full" placeholder="ระบบจะสุ่มให้ถ้าเว้นว่าง" />
              </div>
            </div>
            <div>
              <label class="form-label">หมายเหตุถึงผู้สมัคร</label>
              <textarea v-model="reviewForm.note" rows="2" class="inp w-full"></textarea>
            </div>
            <p v-if="reviewErr" class="text-xs text-rose-500">{{ reviewErr }}</p>
            <div class="flex gap-2">
              <button :disabled="reviewSaving" @click="finalReview('reject')"
                class="flex-1 h-10 rounded-xl border border-rose-200 text-rose-600 text-sm font-semibold hover:bg-rose-50 transition disabled:opacity-60">
                <i class="fi fi-rr-cross-circle mr-1"></i> ปฏิเสธ
              </button>
              <button :disabled="reviewSaving" @click="finalReview('approve')"
                class="flex-1 h-10 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition disabled:opacity-60">
                <i class="fi fi-rr-badge-check mr-1"></i> อนุมัติ + สร้างบัญชี
              </button>
            </div>
          </div>
        </FormSection>

        <!-- Approved result -->
        <div v-if="approvalResult" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 space-y-1">
          <p class="font-semibold text-emerald-700 flex items-center gap-2">
            <i class="fi fi-rr-check-circle"></i> อนุมัติสำเร็จ — สร้างบัญชีแล้ว
          </p>
          <p class="text-sm text-emerald-600">อีเมล: <strong>{{ approvalResult.created_user?.email }}</strong></p>
          <p class="text-sm text-emerald-600">รหัสผ่านชั่วคราว: <strong class="font-mono">{{ approvalResult.temp_password }}</strong></p>
          <p class="text-xs text-emerald-500 mt-1">กรุณาแจ้งข้อมูลนี้ให้ผู้ขายผ่านช่องทางที่ปลอดภัย</p>
        </div>
      </div>

      <template #footer>
        <Button label="ปิด" text @click="dialogOpen = false" />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, defineComponent, h } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useAuth } from '../../composables/useAuth.js'
import FormSection from '../../components/FormSection.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'

const CATEGORY_MAP = {
  vegetable_fruit: 'สินค้าเกษตร (พืช-ผัก-ผลไม้)',
  fresh_food: 'อาหารสด (เนื้อ ปลา ไข่)',
  processed_food: 'อาหารแปรรูป',
  ready_to_eat: 'อาหารพร้อมทาน',
  bakery: 'เบเกอรี่-ขนม',
  beverage: 'เครื่องดื่ม',
  herb: 'สมุนไพร-ยาสมุนไพร',
  household: 'ของใช้ในบ้าน',
  handicraft: 'หัตถกรรม-ของฝาก',
  clothing: 'เสื้อผ้า-สิ่งทอ',
  agriculture: 'เกษตรกรรม-เมล็ดพันธุ์',
  service: 'บริการ (ซ่อม-สอน-ดูแล)',
}

const { isAdmin, isSuperAdmin } = useAuth()
const toast = useToast()

const rows       = ref([])
const loading    = ref(false)
const pagination = ref(null)
const page       = ref(1)
const activeTab  = ref('pending')
const tabCounts  = reactive({ pending: 0, admin_approved: 0, escalated: 0, approved: 0, rejected: 0, revision_requested: 0 })
const dialogOpen  = ref(false)
const active      = ref(null)
const reviewSaving = ref(false)
const reviewErr    = ref('')
const approvalResult = ref(null)
const sellerGroups   = ref([])
const showRevisionForm = ref(false)
const revisionNote     = ref('')
const revisionSaving   = ref(false)
const revisionErr      = ref('')

const reviewForm = reactive({
  note: '', assigned_group_id: null, new_group_name: '', temp_password: '',
})

const tabs = computed(() => {
  const base = [
    { label: 'รอพิจารณา (Admin)', value: 'pending',        icon: 'fi fi-rr-time-half-past', badgeClass: 'bg-amber-500 text-white' },
    { label: 'รอ Superadmin',     value: 'admin_approved', icon: 'fi fi-rr-user-check',     badgeClass: 'bg-blue-500 text-white' },
  ]
  if (isSuperAdmin.value) {
    return [
      ...base,
      { label: 'เลยกำหนด',   value: 'escalated',          icon: 'fi fi-rr-alarm-exclamation', badgeClass: 'bg-orange-500 text-white' },
      { label: 'ขอแก้ไข',    value: 'revision_requested', icon: 'fi fi-rr-undo',               badgeClass: 'bg-amber-500 text-white' },
      { label: 'อนุมัติแล้ว', value: 'approved',           icon: 'fi fi-rr-check-circle',      badgeClass: 'bg-emerald-500 text-white' },
      { label: 'ปฏิเสธ',     value: 'rejected',           icon: 'fi fi-rr-cross-circle',      badgeClass: 'bg-rose-500 text-white' },
      { label: 'ทั้งหมด',     value: '',                   icon: 'fi fi-rr-apps' },
    ]
  }
  return base
})

const canAdminReview    = computed(() => active.value?.status === 'pending' && isAdmin.value && !isSuperAdmin.value)
const canSuperAdminReview = computed(() => isSuperAdmin.value && active.value && ['pending', 'admin_approved', 'escalated'].includes(active.value.status))
const canRequestRevision  = computed(() => (isAdmin.value || isSuperAdmin.value) && active.value &&
  ['pending', 'admin_approved', 'escalated', 'revision_requested'].includes(active.value.status))

function statusLabel(s) {
  const MAP = {
    pending: 'รอ Admin', admin_approved: 'รอ Superadmin', escalated: 'เลยกำหนด',
    approved: 'อนุมัติ', rejected: 'ปฏิเสธ', revision_requested: 'ขอแก้ไข / ตีกลับ',
  }
  return MAP[s] ?? s
}

function statusClass(s) {
  const MAP = {
    pending: 'bg-amber-100 text-amber-700', admin_approved: 'bg-blue-100 text-blue-700',
    escalated: 'bg-orange-100 text-orange-700', approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-rose-100 text-rose-700', revision_requested: 'bg-amber-100 text-amber-700',
  }
  return MAP[s] ?? 'bg-slate-100 text-slate-600'
}

function fmtDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('th-TH', { dateStyle: 'short', timeStyle: 'short' })
}

async function load() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (activeTab.value) params.status = activeTab.value
    const { data } = await api.get('/market/seller-applications', { params })
    rows.value = data.data || []
    pagination.value = data
  } finally { loading.value = false }
}

async function loadCounts() {
  try {
    const { data } = await api.get('/market/seller-applications/counts')
    Object.assign(tabCounts, data)
  } catch { /* ignore */ }
}

async function openDetail(row) {
  approvalResult.value = null
  reviewErr.value = ''
  showRevisionForm.value = false
  revisionNote.value = ''
  revisionErr.value = ''
  Object.assign(reviewForm, { note: '', assigned_group_id: null, new_group_name: '', temp_password: '' })
  const { data } = await api.get(`/market/seller-applications/${row.id}`)
  active.value = data
  dialogOpen.value = true
}

async function adminReview(action) {
  reviewSaving.value = true; reviewErr.value = ''
  try {
    await api.post(`/market/seller-applications/${active.value.id}/admin-review`, {
      action, note: reviewForm.note,
    })
    toast.add({ severity: action === 'approve' ? 'success' : 'info', summary: action === 'approve' ? 'ส่งต่อ Superadmin แล้ว' : 'ปฏิเสธแล้ว', life: 2000 })
    dialogOpen.value = false
    load()
    loadCounts()
  } catch (e) {
    reviewErr.value = e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally { reviewSaving.value = false }
}

async function finalReview(action) {
  if (action === 'approve' && !reviewForm.assigned_group_id && !reviewForm.new_group_name) {
    const firstGroup = sellerGroups.value[0]
    if (!firstGroup) { reviewErr.value = 'กรุณาเลือกกลุ่มหรือใส่ชื่อกลุ่มใหม่'; return }
  }
  reviewSaving.value = true; reviewErr.value = ''
  try {
    const payload = {
      action, note: reviewForm.note,
      assigned_group_id: reviewForm.assigned_group_id || null,
      new_group_name: !reviewForm.assigned_group_id ? (reviewForm.new_group_name || active.value.business_name) : null,
      temp_password: reviewForm.temp_password || null,
    }
    const { data } = await api.post(`/market/seller-applications/${active.value.id}/final-review`, payload)
    if (action === 'approve') {
      approvalResult.value = data
      active.value.status = 'approved'
    } else {
      toast.add({ severity: 'info', summary: 'ปฏิเสธแล้ว', life: 2000 })
      dialogOpen.value = false
    }
    load()
  } catch (e) {
    reviewErr.value = e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally { reviewSaving.value = false }
}

async function submitRevision() {
  if (!revisionNote.value.trim()) { revisionErr.value = 'กรุณาระบุสิ่งที่ต้องแก้ไข'; return }
  revisionSaving.value = true; revisionErr.value = ''
  try {
    await api.post(`/market/seller-applications/${active.value.id}/request-revision`, { note: revisionNote.value })
    toast.add({ severity: 'warn', summary: 'ตีกลับให้แก้ไขแล้ว', life: 2500 })
    showRevisionForm.value = false
    revisionNote.value = ''
    active.value.status = 'revision_requested'
    dialogOpen.value = false
    load()
    loadCounts()
  } catch (e) {
    revisionErr.value = e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally { revisionSaving.value = false }
}

// inline InfoRow component
const InfoRow = defineComponent({
  props: { label: String, value: String },
  setup(props) {
    return () => h('div', {}, [
      h('p', { class: 'text-xs text-slate-400' }, props.label),
      h('p', { class: 'font-medium text-slate-800 mt-0.5' }, props.value),
    ])
  }
})

onMounted(async () => {
  load()
  loadCounts()
  if (isSuperAdmin.value) {
    try {
      const { data } = await api.get('/market/seller-groups')
      sellerGroups.value = data || []
    } catch { /* ignore */ }
  }
})
</script>

<style scoped>
.inp { height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); background: white; width: 100%; }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
textarea.inp { height: auto; padding: 0.5rem 0.75rem; }
select.inp { appearance: none; }
</style>
