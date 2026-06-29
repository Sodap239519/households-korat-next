<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-picture text-violet-600"></i> จัดการแบนเนอร์ Hero
      </h1>
      <button class="btn-primary px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-2" @click="openCreate">
        <i class="fi fi-rr-plus"></i> เพิ่มแบนเนอร์
      </button>
    </div>

    <!-- Preview hint -->
    <div class="box-card p-3 text-sm text-slate-500 flex items-start gap-2">
      <i class="fi fi-rr-info text-violet-500 mt-0.5 shrink-0"></i>
      <span>แบนเนอร์ที่ active จะแสดงที่หน้าร้าน <code class="bg-slate-100 px-1 rounded">/shop</code> ตามลำดับที่กำหนด เรียง Sort Order น้อยสุดก่อน</span>
    </div>

    <!-- Banner list -->
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 3" :key="n" class="box-card p-4 skeleton h-20"></div>
    </div>
    <div v-else-if="!banners.length" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-picture text-4xl"></i>
      <p class="mt-3 font-medium">ยังไม่มีแบนเนอร์</p>
      <p class="text-sm mt-1">ระบบจะแสดงแบนเนอร์ default หน้าร้าน</p>
    </div>
    <div v-else class="space-y-3">
      <div v-for="b in banners" :key="b.id"
        class="box-card p-4 flex items-center gap-4 hover:border-violet-200 transition">
        <!-- Preview gradient bar -->
        <div class="w-16 h-12 rounded-xl shrink-0 flex items-center justify-center text-white text-lg font-bold shadow-sm bg-gradient-to-r"
          :class="b.gradient">
          <i class="fi fi-rr-picture text-white/70 text-base"></i>
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-semibold text-slate-800 text-sm" v-html="b.title.replace(/<[^>]*>/g,'')"></p>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-medium"
              :class="b.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
              {{ b.is_active ? 'แสดงอยู่' : 'ซ่อน' }}
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-0.5 truncate">{{ b.subtitle || '—' }}</p>
          <div class="flex items-center gap-3 mt-1 text-xs text-slate-400">
            <span><i class="fi fi-rr-link mr-1"></i>{{ b.link_type }}: {{ b.link_value || '—' }}</span>
            <span>Sort: {{ b.sort_order }}</span>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button @click="toggleActive(b)" :title="b.is_active ? 'ซ่อน' : 'แสดง'"
            class="w-9 h-9 rounded-full flex items-center justify-center transition"
            :class="b.is_active ? 'bg-emerald-100 text-emerald-600 hover:bg-emerald-200' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'">
            <i :class="b.is_active ? 'fi fi-rr-eye' : 'fi fi-rr-eye-crossed'"></i>
          </button>
          <button @click="openEdit(b)" class="w-9 h-9 rounded-full bg-violet-100 text-violet-600 hover:bg-violet-200 flex items-center justify-center transition">
            <i class="fi fi-rr-pencil"></i>
          </button>
          <button @click="confirmDelete(b)" class="w-9 h-9 rounded-full bg-rose-100 text-rose-500 hover:bg-rose-200 flex items-center justify-center transition">
            <i class="fi fi-rr-trash"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Form Dialog -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'แก้ไขแบนเนอร์' : 'เพิ่มแบนเนอร์'" modal :style="{ width: '560px', maxWidth: '96vw' }">
      <div class="space-y-4 py-1">
        <!-- Preview -->
        <div class="rounded-2xl overflow-hidden h-20 flex items-center px-5 bg-gradient-to-r text-white relative"
          :class="form.gradient">
          <div>
            <p class="text-xs opacity-70">{{ form.tag || 'ป้ายกำกับ' }}</p>
            <p class="font-bold text-base leading-tight">{{ form.title || 'หัวข้อแบนเนอร์' }}</p>
            <p class="text-xs opacity-70 mt-0.5">{{ form.subtitle || 'คำอธิบาย' }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">ป้ายกำกับ (tag)</label>
            <input v-model="form.tag" placeholder="เช่น สินค้าแนะนำ" class="form-input" />
          </div>
          <div>
            <label class="form-label">Sort Order <span class="text-slate-400 font-normal">(น้อย=แสดงก่อน)</span></label>
            <input v-model.number="form.sort_order" type="number" min="0" max="255" class="form-input" />
          </div>
        </div>

        <div>
          <label class="form-label">หัวข้อหลัก <span class="text-rose-500">*</span></label>
          <input v-model="form.title" placeholder="หัวข้อแบนเนอร์" class="form-input" />
          <p class="text-xs text-slate-400 mt-1">รองรับ HTML เช่น <code>บรรทัดแรก&lt;br&gt;บรรทัดสอง</code></p>
        </div>

        <div>
          <label class="form-label">คำอธิบายย่อ</label>
          <input v-model="form.subtitle" placeholder="รายละเอียดสั้นๆ" class="form-input" />
        </div>

        <!-- Gradient picker -->
        <div>
          <label class="form-label">สี Background</label>
          <div class="flex flex-wrap gap-2 mt-1">
            <button v-for="g in gradientOptions" :key="g.value"
              @click="form.gradient = g.value"
              class="w-10 h-10 rounded-xl transition border-2 bg-gradient-to-r"
              :class="[g.value, form.gradient === g.value ? 'border-slate-700 scale-110' : 'border-transparent']"
              :title="g.label">
            </button>
          </div>
        </div>

        <!-- Link -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">ประเภทลิงก์</label>
            <select v-model="form.link_type" class="form-input">
              <option value="product">สินค้า (slug)</option>
              <option value="category">หมวดหมู่ (slug)</option>
              <option value="group">กลุ่มผู้ขาย (slug)</option>
              <option value="url">URL ตรง</option>
            </select>
          </div>
          <div>
            <label class="form-label">{{ linkValueLabel }}</label>
            <input v-model="form.link_value" :placeholder="linkValuePlaceholder" class="form-input" />
          </div>
        </div>

        <!-- CTA -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">ข้อความปุ่ม CTA</label>
            <input v-model="form.cta_label" placeholder="ช้อปเลย" class="form-input" />
          </div>
          <div>
            <label class="form-label">สีปุ่ม CTA</label>
            <select v-model="form.cta_color" class="form-input">
              <option v-for="c in ctaColors" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
          </div>
        </div>

        <!-- Emojis -->
        <div>
          <label class="form-label">อีโมจิประดับ <span class="text-slate-400 font-normal">(คั่นด้วย , สูงสุด 8 ตัว)</span></label>
          <input v-model="emojiInput" placeholder="🍄,🌿,🍯,🧴,🌾,🧺" class="form-input" />
        </div>

        <!-- Status -->
        <div class="flex items-center gap-3">
          <input type="checkbox" v-model="form.is_active" id="banner-active" class="w-4 h-4 accent-violet-600" />
          <label for="banner-active" class="text-sm text-slate-700 cursor-pointer">แสดงบนหน้าร้าน</label>
        </div>

        <p v-if="formErr" class="text-rose-500 text-sm">{{ formErr }}</p>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <button class="px-4 py-2 rounded-full text-sm text-slate-600 hover:bg-slate-100" @click="showDialog = false">ยกเลิก</button>
          <button class="btn-primary px-5 py-2 rounded-full text-sm font-semibold" :disabled="saving" @click="saveBanner">
            {{ saving ? 'กำลังบันทึก...' : (editing ? 'บันทึกการแก้ไข' : 'เพิ่มแบนเนอร์') }}
          </button>
        </div>
      </template>
    </Dialog>

    <!-- Confirm Delete Dialog -->
    <Dialog v-model:visible="showDeleteDialog" header="ยืนยันการลบ" modal :style="{ width: '380px' }">
      <p class="text-slate-600 text-sm">ต้องการลบแบนเนอร์ "<strong>{{ deleteTarget?.title?.replace(/<[^>]*>/g,'') }}</strong>" ใช่หรือไม่?</p>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button class="px-4 py-2 rounded-full text-sm text-slate-600 hover:bg-slate-100" @click="showDeleteDialog = false">ยกเลิก</button>
          <button class="px-4 py-2 rounded-full text-sm font-semibold bg-rose-500 text-white hover:bg-rose-600" :disabled="deleting" @click="deleteBanner">
            {{ deleting ? 'กำลังลบ...' : 'ลบแบนเนอร์' }}
          </button>
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Dialog from 'primevue/dialog'
import api from '../../api/index.js'

const banners    = ref([])
const loading    = ref(true)
const showDialog = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formErr    = ref('')
const showDeleteDialog = ref(false)
const deleteTarget = ref(null)
const deleting   = ref(false)

const gradientOptions = [
  { value: 'from-violet-700 via-violet-600 to-fuchsia-600', label: 'ม่วง' },
  { value: 'from-emerald-700 via-emerald-600 to-teal-500', label: 'เขียว' },
  { value: 'from-amber-600 via-orange-500 to-rose-500', label: 'ส้ม-แดง' },
  { value: 'from-blue-700 via-blue-600 to-cyan-500', label: 'น้ำเงิน' },
  { value: 'from-pink-600 via-rose-500 to-orange-400', label: 'ชมพู-ส้ม' },
  { value: 'from-slate-700 via-slate-600 to-slate-500', label: 'เทา' },
  { value: 'from-fuchsia-700 via-purple-600 to-indigo-600', label: 'ม่วงอ่อน' },
  { value: 'from-lime-600 via-green-500 to-teal-400', label: 'เขียวสด' },
]

const ctaColors = [
  { value: 'bg-orange-500 hover:bg-orange-600', label: 'ส้ม' },
  { value: 'bg-violet-500 hover:bg-violet-600', label: 'ม่วง' },
  { value: 'bg-amber-500 hover:bg-amber-600', label: 'เหลือง' },
  { value: 'bg-white/20 hover:bg-white/30 border border-white/30', label: 'โปร่งใส' },
  { value: 'bg-emerald-500 hover:bg-emerald-600', label: 'เขียว' },
]

const defaultForm = () => ({
  tag: '',
  title: '',
  subtitle: '',
  gradient: 'from-violet-700 via-violet-600 to-fuchsia-600',
  cta_label: 'ดูสินค้า',
  cta_icon: 'fi fi-rr-shopping-cart',
  cta_color: 'bg-orange-500 hover:bg-orange-600',
  link_type: 'url',
  link_value: '/shop/products',
  emojis: [],
  sort_order: 0,
  is_active: true,
})

const form = ref(defaultForm())
const emojiInput = ref('')

const linkValueLabel = computed(() => ({
  product: 'Product slug',
  category: 'Category slug',
  group: 'Group slug',
  url: 'URL / Path',
}[form.value.link_type] || 'ค่า'))

const linkValuePlaceholder = computed(() => ({
  product: 'เช่น hed-homhang',
  category: 'เช่น hed-fresh',
  group: 'เช่น korat-zone-muang',
  url: 'เช่น /shop/products',
}[form.value.link_type] || ''))

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/market/banners')
    banners.value = data
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  form.value = defaultForm()
  emojiInput.value = ''
  formErr.value = ''
  showDialog.value = true
}

function openEdit(b) {
  editing.value = b
  form.value = {
    tag: b.tag || '',
    title: b.title || '',
    subtitle: b.subtitle || '',
    gradient: b.gradient || 'from-violet-700 via-violet-600 to-fuchsia-600',
    cta_label: b.cta_label || 'ดูสินค้า',
    cta_icon: b.cta_icon || 'fi fi-rr-shopping-cart',
    cta_color: b.cta_color || 'bg-orange-500 hover:bg-orange-600',
    link_type: b.link_type || 'url',
    link_value: b.link_value || '',
    emojis: b.emojis || [],
    sort_order: b.sort_order ?? 0,
    is_active: b.is_active ?? true,
  }
  emojiInput.value = (b.emojis || []).join(',')
  formErr.value = ''
  showDialog.value = true
}

async function saveBanner() {
  if (!form.value.title.trim()) { formErr.value = 'กรุณากรอกหัวข้อแบนเนอร์'; return }
  formErr.value = ''
  saving.value = true
  try {
    const payload = {
      ...form.value,
      emojis: emojiInput.value ? emojiInput.value.split(',').map(e => e.trim()).filter(Boolean) : [],
    }
    if (editing.value) {
      const { data } = await api.put(`/market/banners/${editing.value.id}`, payload)
      const idx = banners.value.findIndex(b => b.id === editing.value.id)
      if (idx >= 0) banners.value[idx] = data
    } else {
      const { data } = await api.post('/market/banners', payload)
      banners.value.push(data)
    }
    showDialog.value = false
  } catch (e) {
    formErr.value = e.response?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    saving.value = false
  }
}

async function toggleActive(b) {
  try {
    const { data } = await api.patch(`/market/banners/${b.id}/toggle`)
    const idx = banners.value.findIndex(x => x.id === b.id)
    if (idx >= 0) banners.value[idx] = data
  } catch { /* ignore */ }
}

function confirmDelete(b) {
  deleteTarget.value = b
  showDeleteDialog.value = true
}

async function deleteBanner() {
  deleting.value = true
  try {
    await api.delete(`/market/banners/${deleteTarget.value.id}`)
    banners.value = banners.value.filter(b => b.id !== deleteTarget.value.id)
    showDeleteDialog.value = false
  } catch { /* ignore */ } finally {
    deleting.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.form-label { display: block; font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }
.form-input { width: 100%; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .45rem .65rem; font-size: .875rem; }
.form-input:focus { outline: none; border-color: #a78bfa; }
select.form-input { background-color: white; }
</style>
