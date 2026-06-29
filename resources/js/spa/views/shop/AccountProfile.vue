<template>
  <div class="max-w-lg mx-auto px-4 sm:px-6 py-6">
    <Breadcrumb :items="[{ label: 'บัญชีของฉัน' }, { label: 'โปรไฟล์' }]" class="mb-4" />
    <h1 class="text-xl font-bold text-slate-800 mb-5">โปรไฟล์ของฉัน</h1>

    <div class="box-card p-5 space-y-4">
      <!-- Avatar -->
      <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
        <!-- รูปโปรไฟล์ + upload -->
        <div class="relative shrink-0 group cursor-pointer" @click="$refs.avatarInput.click()">
          <div class="w-16 h-16 rounded-full overflow-hidden bg-gradient-to-br from-violet-400 to-fuchsia-500 flex items-center justify-center text-white text-2xl font-bold">
            <img v-if="user?.avatar_path" :src="`/storage/${user.avatar_path}`" :alt="user.name"
              class="w-full h-full object-cover" />
            <span v-else>{{ initials }}</span>
          </div>
          <!-- hover overlay -->
          <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
            <i class="fi fi-rr-camera text-white text-sm"></i>
          </div>
          <!-- spinner during upload -->
          <div v-if="avatarUploading" class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center">
            <i class="fi fi-rr-spinner animate-spin text-white text-sm"></i>
          </div>
        </div>
        <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onFileSelected" />

        <div>
          <p class="font-semibold text-slate-800">{{ user?.name }}</p>
          <p class="text-sm text-slate-400">{{ user?.email }}</p>
          <button @click="$refs.avatarInput.click()" class="mt-1 text-xs text-violet-600 hover:underline flex items-center gap-1">
            <i class="fi fi-rr-camera text-[10px]"></i>
            {{ user?.avatar_path ? 'เปลี่ยนรูปภาพ' : 'เพิ่มรูปภาพ' }}
          </button>
        </div>
      </div>

      <!-- Info form -->
      <div class="space-y-3">
        <div>
          <label class="form-label">ชื่อ-นามสกุล</label>
          <input v-model="form.name" class="inp w-full" />
        </div>
        <div>
          <label class="form-label">อีเมล</label>
          <input v-model="form.email" type="email" class="inp w-full" />
        </div>
        <p v-if="infoErr" class="text-sm text-rose-500">{{ infoErr }}</p>
        <button :disabled="infoSaving" @click="saveInfo" class="btn-orange btn-sheen w-full h-10 rounded-full font-semibold text-sm disabled:opacity-60">
          {{ infoSaving ? 'กำลังบันทึก...' : 'บันทึกข้อมูล' }}
        </button>
      </div>
    </div>

    <!-- Change password -->
    <div class="box-card p-5 mt-4 space-y-3">
      <h2 class="font-semibold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-lock text-violet-500"></i> เปลี่ยนรหัสผ่าน
      </h2>
      <div>
        <label class="form-label">รหัสผ่านปัจจุบัน</label>
        <div class="relative">
          <input v-model="pw.current" :type="showPw.current ? 'text' : 'password'" class="inp w-full pr-10" />
          <button type="button" @click="showPw.current = !showPw.current" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"><i :class="showPw.current ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye'"></i></button>
        </div>
      </div>
      <div>
        <label class="form-label">รหัสผ่านใหม่</label>
        <div class="relative">
          <input v-model="pw.new" :type="showPw.new ? 'text' : 'password'" class="inp w-full pr-10" />
          <button type="button" @click="showPw.new = !showPw.new" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"><i :class="showPw.new ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye'"></i></button>
        </div>
      </div>
      <div>
        <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
        <input v-model="pw.confirm" type="password" class="inp w-full" />
      </div>
      <p v-if="pwErr" class="text-sm text-rose-500">{{ pwErr }}</p>
      <button :disabled="pwSaving" @click="savePassword" class="w-full h-10 rounded-full border border-violet-200 text-violet-700 hover:bg-violet-50 font-semibold text-sm transition disabled:opacity-60">
        {{ pwSaving ? 'กำลังเปลี่ยน...' : 'เปลี่ยนรหัสผ่าน' }}
      </button>
    </div>
  </div>

  <!-- Crop Modal -->
  <Teleport to="body">
    <Transition name="crop-fade">
      <div v-if="cropOpen" class="fixed inset-0 z-[9999] flex flex-col bg-black">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-black/80 backdrop-blur shrink-0">
          <button @click="cancelCrop" class="text-white/70 hover:text-white flex items-center gap-1.5 text-sm">
            <i class="fi fi-rr-arrow-left"></i> ยกเลิก
          </button>
          <h3 class="text-white font-semibold text-sm">ครอบตัดรูปโปรไฟล์</h3>
          <button @click="applyCrop" :disabled="avatarUploading"
            class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold px-4 py-1.5 rounded-full transition disabled:opacity-60 flex items-center gap-1.5">
            <i v-if="avatarUploading" class="fi fi-rr-spinner animate-spin text-xs"></i>
            <i v-else class="fi fi-rr-check text-xs"></i>
            {{ avatarUploading ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>

        <!-- Cropper area -->
        <div class="flex-1 overflow-hidden flex items-center justify-center bg-black min-h-0">
          <img ref="cropImgEl" :src="cropSrc" class="max-w-full max-h-full block" style="opacity:0" />
        </div>

        <!-- Controls -->
        <div class="shrink-0 bg-black/80 backdrop-blur px-4 py-3 flex items-center justify-center gap-6">
          <button @click="cropperInst?.rotate(-90)" class="crop-btn" title="หมุนซ้าย">
            <i class="fi fi-rr-rotate-left text-lg"></i>
          </button>
          <button @click="cropperInst?.rotate(90)" class="crop-btn" title="หมุนขวา">
            <i class="fi fi-rr-rotate-right text-lg"></i>
          </button>
          <button @click="cropperInst?.scaleX(cropFlipX = -cropFlipX)" class="crop-btn" title="พลิกซ้าย-ขวา">
            <i class="fi fi-rr-reflect text-lg"></i>
          </button>
          <button @click="cropperInst?.zoom(0.1)" class="crop-btn" title="ซูมเข้า">
            <i class="fi fi-rr-zoom-in text-lg"></i>
          </button>
          <button @click="cropperInst?.zoom(-0.1)" class="crop-btn" title="ซูมออก">
            <i class="fi fi-rr-zoom-out text-lg"></i>
          </button>
          <button @click="cropperInst?.reset()" class="crop-btn" title="รีเซ็ต">
            <i class="fi fi-rr-refresh text-lg"></i>
          </button>
        </div>

        <!-- Circle preview hint -->
        <div class="shrink-0 bg-black/60 px-4 pb-3 text-center">
          <p class="text-white/50 text-xs">ลากเพื่อย้าย • หยิกเพื่อซูม • รูปจะถูกตัดเป็นวงกลม</p>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuth } from '../../composables/useAuth.js'
import api from '../../api/index.js'
import Breadcrumb from './components/Breadcrumb.vue'
import 'cropperjs/dist/cropper.css'

const toast = useToast()
const { user, fetchUser } = useAuth()

const form = reactive({ name: '', email: '' })
const infoSaving = ref(false)
const infoErr = ref('')
const avatarUploading = ref(false)

// ──── Crop state ────
const cropOpen   = ref(false)
const cropSrc    = ref('')
const cropImgEl  = ref(null)
let cropperInst  = null
let cropFlipX    = 1

function onFileSelected(e) {
  const file = e.target.files?.[0]
  if (!file) return
  e.target.value = ''
  const reader = new FileReader()
  reader.onload = ev => {
    cropSrc.value = ev.target.result
    cropOpen.value = true
    nextTick(() => initCropper())
  }
  reader.readAsDataURL(file)
}

async function initCropper() {
  if (!cropImgEl.value) return
  const { default: Cropper } = await import('cropperjs')
  destroyCropper()
  cropFlipX = 1
  cropImgEl.value.style.opacity = '1'
  cropperInst = new Cropper(cropImgEl.value, {
    aspectRatio: 1,
    viewMode: 1,
    dragMode: 'move',
    autoCropArea: 0.85,
    restore: false,
    guides: true,
    center: true,
    highlight: false,
    cropBoxMovable: true,
    cropBoxResizable: true,
    toggleDragModeOnDblclick: false,
  })
}

function destroyCropper() {
  if (cropperInst) {
    cropperInst.destroy()
    cropperInst = null
  }
}

function cancelCrop() {
  destroyCropper()
  cropOpen.value = false
  cropSrc.value  = ''
}

async function applyCrop() {
  if (!cropperInst || avatarUploading.value) return
  const canvas = cropperInst.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' })
  canvas.toBlob(async blob => {
    destroyCropper()
    cropOpen.value = false
    cropSrc.value  = ''
    if (!blob) return
    avatarUploading.value = true
    try {
      const fd = new FormData()
      fd.append('avatar', blob, 'avatar.jpg')
      await api.post('/profile/avatar', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      await fetchUser()
      toast.add({ severity: 'success', summary: 'อัปเดตรูปโปรไฟล์แล้ว', life: 2000 })
    } catch {
      toast.add({ severity: 'warn', summary: 'อัปโหลดไม่สำเร็จ', detail: 'ไฟล์ต้องเป็น jpg/png/webp ขนาดไม่เกิน 2MB', life: 3000 })
    } finally {
      avatarUploading.value = false
    }
  }, 'image/jpeg', 0.9)
}

onUnmounted(() => destroyCropper())

// ──── Password ────
const pw = reactive({ current: '', new: '', confirm: '' })
const showPw = reactive({ current: false, new: false })
const pwSaving = ref(false)
const pwErr = ref('')

const initials = computed(() => (user.value?.name || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase())

async function saveInfo() {
  if (!form.name.trim()) { infoErr.value = 'กรุณากรอกชื่อ'; return }
  infoSaving.value = true
  infoErr.value = ''
  try {
    await api.put('/profile', { name: form.name, email: form.email })
    await fetchUser()
    toast.add({ severity: 'success', summary: 'บันทึกข้อมูลแล้ว', life: 2000 })
  } catch (e) {
    infoErr.value = e.response?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    infoSaving.value = false
  }
}

async function savePassword() {
  if (!pw.current || !pw.new) { pwErr.value = 'กรุณากรอกรหัสผ่าน'; return }
  if (pw.new !== pw.confirm) { pwErr.value = 'รหัสผ่านใหม่ไม่ตรงกัน'; return }
  if (pw.new.length < 8) { pwErr.value = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'; return }
  pwSaving.value = true
  pwErr.value = ''
  try {
    await api.put('/profile/password', { current_password: pw.current, password: pw.new, password_confirmation: pw.confirm })
    pw.current = pw.new = pw.confirm = ''
    toast.add({ severity: 'success', summary: 'เปลี่ยนรหัสผ่านแล้ว', life: 2000 })
  } catch (e) {
    pwErr.value = e.response?.data?.message || e.response?.data?.errors?.current_password?.[0] || 'เปลี่ยนรหัสผ่านไม่สำเร็จ'
  } finally {
    pwSaving.value = false
  }
}

onMounted(async () => {
  if (!user.value) await fetchUser()
  form.name  = user.value?.name  || ''
  form.email = user.value?.email || ''
})
</script>

<style scoped>
.inp { height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); }
.inp:focus { outline: none; border-color: rgb(167 139 250); }

.crop-btn {
  @apply flex items-center justify-center w-10 h-10 rounded-full text-white/70 hover:text-white hover:bg-white/10 transition;
}

.crop-fade-enter-active,
.crop-fade-leave-active {
  transition: opacity 0.2s ease;
}
.crop-fade-enter-from,
.crop-fade-leave-to {
  opacity: 0;
}
</style>
