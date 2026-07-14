<template>
  <div class="p-3 sm:p-5 space-y-5">
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-picture text-violet-600"></i> จัดการแบนเนอร์ Hero
      </h1>
      <button class="btn-primary px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-2" @click="openCreate">
        <i class="fi fi-rr-plus"></i> เพิ่มแบนเนอร์
      </button>
    </div>

    <div v-if="showHint" class="box-card p-3 text-sm text-slate-500 flex items-start gap-2">
      <i class="fi fi-rr-info text-violet-500 mt-0.5 shrink-0"></i>
      <span class="flex-1">ลากแถวเพื่อเรียงลำดับ แบนเนอร์ที่ <strong>แสดงอยู่</strong> จะปรากฏที่หน้าร้าน <code class="bg-slate-100 px-1 rounded">/shop</code> ตามลำดับที่กำหนด</span>
      <button @click="showHint = false" class="shrink-0 w-5 h-5 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition">
        <i class="fi fi-rr-cross-small text-sm"></i>
      </button>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="n in 3" :key="n" class="box-card p-4 h-20 animate-pulse bg-slate-100 rounded-xl"></div>
    </div>

    <div v-else-if="!banners.length" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-picture text-4xl"></i>
      <p class="mt-3 font-medium">ยังไม่มีแบนเนอร์</p>
      <p class="text-sm mt-1">กดปุ่ม "เพิ่มแบนเนอร์" เพื่อสร้างแบนเนอร์แรก</p>
    </div>

    <!-- Banner list — draggable -->
    <div v-else class="space-y-2">
      <div v-for="(b, idx) in banners" :key="b.id"
        draggable="true"
        @dragstart="onDragStart(idx)"
        @dragover.prevent="onDragOver(idx)"
        @drop.prevent="onDrop"
        @dragend="onDragEnd"
        class="box-card p-3 flex items-center gap-3 select-none transition"
        :class="[
          dragOver === idx && dragOver !== dragSrc ? 'border-violet-400 bg-violet-50' : 'hover:border-violet-200',
          dragSrc === idx ? 'opacity-40' : '',
        ]">

        <div class="shrink-0 cursor-grab active:cursor-grabbing text-slate-300 hover:text-slate-500 px-1">
          <i class="fi fi-rr-grip-dots-vertical text-xl"></i>
        </div>

        <div class="w-16 h-12 rounded-lg shrink-0 overflow-hidden border border-slate-100">
          <img v-if="b.image_path" :src="`/storage/${b.image_path}`" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-r" :class="b.gradient || 'from-violet-600 to-fuchsia-600'">
            <i class="fi fi-rr-picture text-white/50 text-base"></i>
          </div>
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-semibold text-slate-800 text-sm truncate">{{ b.title || '(ไม่มีหัวข้อ)' }}</p>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-medium shrink-0"
              :class="b.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">
              {{ b.is_active ? 'แสดงอยู่' : 'ซ่อน' }}
            </span>
          </div>
          <p class="text-xs text-slate-400 truncate">{{ b.subtitle || '—' }}</p>
          <p class="text-xs text-slate-400 mt-0.5">
            <i class="fi fi-rr-link mr-1"></i>{{ linkDisplayLabel(b) }}
          </p>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
          <button @click="toggleActive(b)"
            class="w-8 h-8 rounded-full flex items-center justify-center transition text-sm"
            :class="b.is_active ? 'bg-emerald-100 text-emerald-600 hover:bg-emerald-200' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
            :title="b.is_active ? 'ซ่อนแบนเนอร์' : 'เปิดแสดงแบนเนอร์'">
            <i :class="b.is_active ? 'fi fi-rr-eye' : 'fi fi-rr-eye-crossed'"></i>
          </button>
          <button @click="openEdit(b)"
            class="w-8 h-8 rounded-full bg-violet-100 text-violet-600 hover:bg-violet-200 flex items-center justify-center transition text-sm">
            <i class="fi fi-rr-pencil"></i>
          </button>
          <button @click="confirmDelete(b)"
            class="w-8 h-8 rounded-full bg-rose-100 text-rose-500 hover:bg-rose-200 flex items-center justify-center transition text-sm">
            <i class="fi fi-rr-trash"></i>
          </button>
        </div>
      </div>
    </div>

    <div v-if="reordering" class="text-center text-sm text-violet-500 flex items-center justify-center gap-2">
      <i class="fi fi-rr-refresh animate-spin"></i> กำลังบันทึกลำดับ...
    </div>

    <!-- ======= Form Dialog ======= -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'แก้ไขแบนเนอร์' : 'เพิ่มแบนเนอร์'" modal :style="{ width: '580px', maxWidth: '96vw' }">
      <div class="space-y-4 py-1">

        <!-- Hero image upload -->
        <div>
          <label class="form-label">
            รูปภาพ Hero
            <span class="text-slate-400 font-normal">(ไม่บังคับ — ถ้าไม่มีจะใช้สีพื้นหลัง)</span>
          </label>
          <div class="relative border-2 border-dashed rounded-xl overflow-hidden cursor-pointer hover:border-violet-400 transition"
            :class="imagePreview ? 'border-violet-300' : 'border-slate-200'"
            @click="imageInput?.click()">
            <img v-if="imagePreview" :src="imagePreview" class="w-full h-36 object-cover" />
            <div v-else class="h-36 flex flex-col items-center justify-center text-slate-400 gap-2">
              <i class="fi fi-rr-cloud-upload text-3xl"></i>
              <p class="text-sm">คลิกเพื่ออัพโหลดรูป</p>
              <p class="text-xs">JPG, PNG, WebP, GIF · สูงสุด 4MB · จะเปิดหน้าต่าง Crop</p>
            </div>
            <input ref="imageInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onImageSelect" />
          </div>
          <div v-if="imagePreview" class="flex items-center gap-3 mt-1.5">
            <button @click="imageInput?.click()"
              class="text-xs text-violet-600 hover:text-violet-800 flex items-center gap-1">
              <i class="fi fi-rr-crop-alt text-[11px]"></i> เลือกรูปใหม่ / Crop
            </button>
            <button @click="clearImage"
              class="text-xs text-rose-500 hover:text-rose-700 flex items-center gap-1">
              <i class="fi fi-rr-trash text-[11px]"></i> ลบรูป
            </button>
          </div>
        </div>

        <!-- Preview strip -->
        <div class="rounded-2xl overflow-hidden h-16 flex items-center px-5 bg-gradient-to-r text-white relative"
          :class="form.gradient || 'from-violet-700 via-violet-600 to-fuchsia-600'">
          <img v-if="imagePreview" :src="imagePreview"
            class="absolute inset-0 w-full h-full object-cover"
            :class="form.gradient === 'from-white to-white' ? '' : 'opacity-40'" />
          <div class="relative z-10">
            <p class="text-[10px] opacity-70">{{ form.tag || 'ป้ายกำกับ' }}</p>
            <p class="font-bold text-sm leading-tight">{{ form.title || '(หัวข้อแบนเนอร์)' }}</p>
          </div>
        </div>

        <div>
          <label class="form-label">ป้ายกำกับ (tag) <span class="text-slate-400 font-normal">ไม่บังคับ</span></label>
          <input v-model="form.tag" placeholder="เช่น สินค้าแนะนำ" class="form-input" />
        </div>

        <div>
          <label class="form-label">หัวข้อหลัก <span class="text-slate-400 font-normal">ไม่บังคับ (รองรับ HTML เช่น &lt;br&gt;)</span></label>
          <input v-model="form.title" placeholder="เช่น สินค้าสด ส่งตรงจากสวน" class="form-input" />
        </div>

        <div>
          <label class="form-label">คำอธิบายย่อ <span class="text-slate-400 font-normal">ไม่บังคับ</span></label>
          <input v-model="form.subtitle" placeholder="รายละเอียดสั้นๆ" class="form-input" />
        </div>

        <!-- Gradient picker — ซ่อนเมื่อมีรูป (รูปครอบอยู่แล้ว) -->
        <div v-if="!imagePreview">
          <label class="form-label">สีพื้นหลัง <span class="text-slate-400 font-normal">(ใช้เมื่อไม่มีรูป)</span></label>
          <div class="flex flex-wrap gap-2 mt-1">
            <button
              @click="form.gradient = 'from-white to-white'"
              class="w-9 h-9 rounded-xl transition border-2 bg-white flex items-center justify-center"
              :class="form.gradient === 'from-white to-white' ? 'border-slate-700 scale-110 shadow-md' : 'border-slate-200'"
              title="ไม่มีสี">
              <i class="fi fi-rr-ban text-slate-300 text-sm"></i>
            </button>
            <button v-for="g in gradientOptions" :key="g.value"
              @click="form.gradient = g.value"
              class="w-9 h-9 rounded-xl transition border-2 bg-gradient-to-r"
              :class="[g.value, form.gradient === g.value ? 'border-slate-700 scale-110 shadow-md' : 'border-transparent']"
              :title="g.label">
            </button>
          </div>
        </div>
        <div v-else>
          <button @click="showGradientPicker = !showGradientPicker"
            class="text-xs text-slate-500 hover:text-slate-700 flex items-center gap-1">
            <i class="fi fi-rr-palette text-[11px]"></i>
            {{ showGradientPicker ? 'ซ่อนตัวเลือกสีพื้นหลัง' : 'ปรับสีพื้นหลัง (ใต้รูป)' }}
          </button>
          <div v-if="showGradientPicker" class="flex flex-wrap gap-2 mt-2">
            <button
              @click="form.gradient = 'from-white to-white'"
              class="w-9 h-9 rounded-xl transition border-2 bg-white flex items-center justify-center"
              :class="form.gradient === 'from-white to-white' ? 'border-slate-700 scale-110 shadow-md' : 'border-slate-200'"
              title="ไม่มีสี">
              <i class="fi fi-rr-ban text-slate-300 text-sm"></i>
            </button>
            <button v-for="g in gradientOptions" :key="g.value"
              @click="form.gradient = g.value"
              class="w-9 h-9 rounded-xl transition border-2 bg-gradient-to-r"
              :class="[g.value, form.gradient === g.value ? 'border-slate-700 scale-110 shadow-md' : 'border-transparent']"
              :title="g.label">
            </button>
          </div>
        </div>

        <!-- Link section -->
        <div>
          <label class="form-label">ประเภทลิงก์</label>
          <select v-model="form.link_type" @change="onLinkTypeChange" class="form-input">
            <option value="none">ไม่มีลิงก์</option>
            <option value="product">สินค้า</option>
            <option value="category">หมวดหมู่</option>
            <option value="group">กลุ่มผู้ขาย</option>
            <option value="url">URL / Path ตรง</option>
          </select>
        </div>

        <div v-if="form.link_type !== 'none'">
          <!-- Dynamic picker for product/category/group -->
          <div v-if="form.link_type !== 'url'">
            <label class="form-label">เลือก{{ linkTypeLabel }} <span v-if="loadingLinkOpts" class="text-violet-500 text-xs">กำลังโหลด...</span></label>
            <select v-model="form.link_value" class="form-input" :disabled="loadingLinkOpts">
              <option value="">-- เลือก{{ linkTypeLabel }} --</option>
              <option v-for="opt in linkOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <!-- Manual URL input -->
          <div v-else>
            <label class="form-label">URL / Path <span class="text-slate-400 font-normal">ไม่บังคับ</span></label>
            <input v-model="form.link_value" placeholder="เช่น /shop/products" class="form-input" />
          </div>
        </div>

        <!-- CTA -->
        <div>
          <label class="form-label">ข้อความปุ่ม CTA <span class="text-slate-400 font-normal">ไม่บังคับ</span></label>
          <input v-model="form.cta_label" placeholder="เช่น ช้อปเลย" class="form-input" />
        </div>
        <div v-if="form.cta_label">
          <label class="form-label">สีปุ่ม CTA</label>
          <select v-model="form.cta_color" class="form-input">
            <option v-for="c in ctaColors" :key="c.value" :value="c.value">{{ c.label }}</option>
          </select>
        </div>

        <!-- Emojis -->
        <div>
          <label class="form-label">อีโมจิประดับ <span class="text-slate-400 font-normal">คั่นด้วย , สูงสุด 8 ตัว (ไม่บังคับ)</span></label>
          <input v-model="emojiInput" placeholder="🍄,🌿,🍯,🧴,🌾,🧺" class="form-input" />
        </div>

        <!-- Aspect Ratio -->
        <div>
          <label class="form-label">
            ขนาดแบนเนอร์ (Aspect Ratio)
            <span class="text-slate-400 font-normal">— กำหนดความสูงของ Hero section</span>
          </label>
          <div class="grid grid-cols-4 gap-2 mt-1">
            <button v-for="r in aspectRatioOptions" :key="r.value"
              @click="form.aspect_ratio = r.value"
              class="flex flex-col items-center gap-1.5 p-2 rounded-xl border-2 transition text-xs font-medium"
              :class="form.aspect_ratio === r.value
                ? 'border-violet-500 bg-violet-50 text-violet-700'
                : 'border-slate-200 hover:border-violet-300 text-slate-600'">
              <div class="bg-violet-200 rounded" :style="r.previewStyle"></div>
              <span>{{ r.label }}</span>
            </button>
          </div>
        </div>

        <!-- สถานะ -->
        <div class="pt-1">
          <label class="form-label">สถานะ</label>
          <label class="flex items-center gap-2 mt-1.5 cursor-pointer">
            <input type="checkbox" v-model="form.is_active" class="w-4 h-4 accent-violet-600" />
            <span class="text-sm text-slate-700">แสดงบนหน้าร้าน</span>
          </label>
        </div>

        <p v-if="formErr" class="text-rose-500 text-sm flex items-center gap-1.5">
          <i class="fi fi-rr-exclamation"></i> {{ formErr }}
        </p>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <button class="px-4 py-2 rounded-full text-sm text-slate-600 hover:bg-slate-100" @click="showDialog = false">ยกเลิก</button>
          <button class="btn-primary px-5 py-2 rounded-full text-sm font-semibold flex items-center gap-2" :disabled="saving" @click="saveBanner">
            <i v-if="saving" class="fi fi-rr-refresh animate-spin"></i>
            {{ saving ? 'กำลังบันทึก...' : (editing ? 'บันทึกการแก้ไข' : 'เพิ่มแบนเนอร์') }}
          </button>
        </div>
      </template>
    </Dialog>

    <!-- Confirm Delete Dialog -->
    <Dialog v-model:visible="showDeleteDialog" header="ยืนยันการลบ" modal :style="{ width: '380px' }">
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center shrink-0 mt-0.5">
          <i class="fi fi-rr-trash text-rose-500"></i>
        </div>
        <div>
          <p class="text-slate-700 font-medium">ลบแบนเนอร์นี้?</p>
          <p class="text-slate-500 text-sm mt-1">
            "<strong>{{ deleteTarget?.title?.replace(/<[^>]*>/g,'') || '(ไม่มีหัวข้อ)' }}</strong>"
            จะถูกลบออกถาวรพร้อมรูปภาพ
          </p>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button class="px-4 py-2 rounded-full text-sm text-slate-600 hover:bg-slate-100" @click="showDeleteDialog = false">ยกเลิก</button>
          <button class="px-4 py-2 rounded-full text-sm font-semibold bg-rose-500 text-white hover:bg-rose-600 flex items-center gap-2" :disabled="deleting" @click="deleteBanner">
            <i v-if="deleting" class="fi fi-rr-refresh animate-spin"></i>
            {{ deleting ? 'กำลังลบ...' : 'ลบแบนเนอร์' }}
          </button>
        </div>
      </template>
    </Dialog>
  </div>

  <!-- ======= Crop Modal (Teleport — ลอยเหนือ Dialog) ======= -->
  <Teleport to="body">
    <Transition name="crop-fade">
      <div v-if="showCrop"
        class="fixed inset-0 z-[9999] bg-black/85 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl overflow-hidden w-full max-w-2xl shadow-2xl">
          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
              <i class="fi fi-rr-crop-alt text-violet-600"></i> ครอบตัดรูปภาพ
            </h3>
            <button @click="cancelCrop"
              class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 transition">
              <i class="fi fi-rr-cross-small text-base"></i>
            </button>
          </div>

          <!-- Crop canvas area — aspect 16:9 (1920×1080) -->
          <div class="bg-slate-950 px-4 pt-4 pb-2">
            <div
              ref="cropContainer"
              class="relative overflow-hidden rounded-xl mx-auto select-none"
              style="aspect-ratio: 16 / 9; max-width: 100%;">
              <img
                ref="cropImg"
                :src="rawImageUrl"
                class="absolute top-0 left-0"
                :style="{
                  width: cropZoom + '%',
                  height: 'auto',
                  transformOrigin: 'top left',
                  transform: `translate(${cropOffsetX}px, ${cropOffsetY}px)`,
                  cursor: isDragging ? 'grabbing' : 'grab',
                  userSelect: 'none',
                  WebkitUserSelect: 'none',
                  pointerEvents: 'auto',
                  touchAction: 'none',
                }"
                @load="onCropImgLoad"
                @mousedown.prevent="startCropDrag"
                @touchstart.prevent="startCropDrag"
                draggable="false"
              />
              <!-- Rule-of-thirds grid -->
              <div class="absolute inset-0 pointer-events-none rounded-xl"
                style="
                  background-image:
                    linear-gradient(rgba(255,255,255,.18) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,.18) 1px, transparent 1px);
                  background-size: 33.33% 50%;
                  box-shadow: inset 0 0 0 2px rgba(255,255,255,.5);
                ">
              </div>
            </div>
            <!-- Zoom slider -->
            <div class="flex items-center gap-3 mt-3 mb-1">
              <i class="fi fi-rr-search-minus text-slate-500 text-sm shrink-0"></i>
              <input type="range" v-model.number="cropZoom"
                :min="minZoom" max="300" step="1"
                @input="clampCropOffset"
                class="flex-1 accent-violet-500" />
              <i class="fi fi-rr-search-plus text-slate-500 text-sm shrink-0"></i>
              <span class="text-xs text-slate-400 font-mono w-10 text-right">{{ cropZoom }}%</span>
            </div>
            <p class="text-[11px] text-slate-500 text-center pb-2">
              <i class="fi fi-rr-hand"></i> ลากรูปเพื่อปรับตำแหน่ง · แนะนำ 1920×1080 px (16:9)
            </p>
          </div>

          <div class="flex justify-end gap-3 px-5 py-3.5">
            <button @click="cancelCrop"
              class="px-4 py-2 rounded-full text-sm text-slate-600 hover:bg-slate-100 transition">
              ยกเลิก
            </button>
            <button @click="confirmCrop"
              class="btn-primary px-5 py-2 rounded-full text-sm font-semibold flex items-center gap-2">
              <i class="fi fi-rr-check"></i> ยืนยัน Crop
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import Dialog from 'primevue/dialog'
import api from '../../api/index.js'

// ---- State ----
const banners    = ref([])
const loading    = ref(true)
const showHint   = ref(true)
const showDialog = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formErr    = ref('')
const showDeleteDialog = ref(false)
const deleteTarget = ref(null)
const deleting   = ref(false)
const reordering = ref(false)
const showGradientPicker = ref(false)

// ---- Image upload state ----
const imageInput   = ref(null)
const imagePreview = ref('')
const imageFile    = ref(null)

// ---- Crop state ----
const showCrop     = ref(false)
const rawImageUrl  = ref('')
const cropImg      = ref(null)
const cropContainer = ref(null)
const cropZoom     = ref(100)
const cropOffsetX  = ref(0)
const cropOffsetY  = ref(0)
const naturalW     = ref(0)
const naturalH     = ref(0)
const isDragging   = ref(false)
const minZoom      = ref(100)
let _lastDragX = 0
let _lastDragY = 0

// ---- Link options ----
const linkOptions     = ref([])
const loadingLinkOpts = ref(false)
const linkOptionsCache = {}

// ---- Drag-and-drop state (list reorder) ----
const dragSrc  = ref(null)
const dragOver = ref(null)

// ---- Options ----
const gradientOptions = [
  // ม่วง (default group — หลากหลายเฉด)
  { value: 'from-violet-700 via-violet-600 to-fuchsia-600',  label: 'ม่วงหลัก' },
  { value: 'from-purple-800 via-violet-700 to-purple-600',   label: 'ม่วงเข้ม' },
  { value: 'from-indigo-700 via-violet-600 to-fuchsia-500',  label: 'ม่วง-คราม' },
  { value: 'from-fuchsia-700 via-purple-600 to-indigo-600',  label: 'ม่วง-ฟ้า' },
  { value: 'from-violet-600 via-purple-500 to-pink-500',     label: 'ม่วง-ชมพู' },
  { value: 'from-purple-700 via-fuchsia-600 to-rose-500',    label: 'ม่วง-กุหลาบ' },
  { value: 'from-violet-900 via-indigo-800 to-slate-700',    label: 'ม่วงดำ' },
  { value: 'from-violet-500 via-purple-400 to-fuchsia-300',  label: 'ม่วงอ่อน' },
  // อื่นๆ
  { value: 'from-emerald-700 via-emerald-600 to-teal-500',   label: 'เขียว' },
  { value: 'from-amber-600 via-orange-500 to-rose-500',      label: 'ส้ม-แดง' },
  { value: 'from-blue-700 via-blue-600 to-cyan-500',         label: 'น้ำเงิน' },
  { value: 'from-pink-600 via-rose-500 to-orange-400',       label: 'ชมพู-ส้ม' },
  { value: 'from-slate-700 via-slate-600 to-slate-500',      label: 'เทา' },
  { value: 'from-lime-600 via-green-500 to-teal-400',        label: 'เขียวสด' },
  { value: 'from-red-700 via-rose-600 to-pink-500',          label: 'แดง' },
  { value: 'from-sky-600 via-blue-500 to-indigo-500',        label: 'ฟ้า-คราม' },
]

const ctaColors = [
  { value: 'bg-orange-500 hover:bg-orange-600',              label: 'ส้ม' },
  { value: 'bg-violet-500 hover:bg-violet-600',              label: 'ม่วง' },
  { value: 'bg-amber-500 hover:bg-amber-600',                label: 'เหลือง' },
  { value: 'bg-white/20 hover:bg-white/30 border border-white/30', label: 'โปร่งใส' },
  { value: 'bg-emerald-500 hover:bg-emerald-600',            label: 'เขียว' },
]

// ---- Aspect ratio options ----
const aspectRatioOptions = [
  { value: '4/1',  label: 'กว้างมาก',  previewStyle: 'width:56px;height:14px' },
  { value: '3/1',  label: 'กว้าง',     previewStyle: 'width:54px;height:18px' },
  { value: '16/9', label: 'มาตรฐาน',  previewStyle: 'width:48px;height:27px' },
  { value: '2/1',  label: 'สี่เหลี่ยม', previewStyle: 'width:48px;height:24px' },
]

// ---- Form defaults ----
const defaultForm = () => ({
  tag:          '',
  title:        '',
  subtitle:     '',
  gradient:     'from-violet-700 via-violet-600 to-fuchsia-600',
  cta_label:    '',
  cta_icon:     'fi fi-rr-shopping-cart',
  cta_color:    'bg-orange-500 hover:bg-orange-600',
  link_type:    'none',
  link_value:   '',
  emojis:       [],
  is_active:    true,
  aspect_ratio: '16/9',
})

const form       = ref(defaultForm())
const emojiInput = ref('')

// ---- Computed ----
const linkTypeLabel = computed(() => ({
  product:  'สินค้า',
  category: 'หมวดหมู่',
  group:    'กลุ่มผู้ขาย',
  url:      'URL',
  none:     '',
}[form.value.link_type] || ''))

// ---- API ----
async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/market/banners')
    banners.value = data
  } finally {
    loading.value = false
  }
}

async function loadLinkOptions(type) {
  if (type === 'url' || type === 'none') { linkOptions.value = []; return }
  if (linkOptionsCache[type]) { linkOptions.value = linkOptionsCache[type]; return }

  loadingLinkOpts.value = true
  try {
    let items = []
    if (type === 'product') {
      const { data } = await api.get('/market/products', { params: { per_page: 200 } })
      items = (data.data || data).map(p => ({ value: p.slug, label: `${p.name} (${p.slug})` }))
    } else if (type === 'category') {
      const { data } = await api.get('/market/categories')
      items = (data.data || data).map(c => ({ value: c.slug, label: c.name }))
    } else if (type === 'group') {
      const { data } = await api.get('/market/seller-groups')
      items = (data.data || data).map(g => ({ value: g.slug, label: g.name }))
    }
    linkOptionsCache[type] = items
    linkOptions.value = items
  } catch {
    linkOptions.value = []
  } finally {
    loadingLinkOpts.value = false
  }
}

// ---- Dialog open ----
function openCreate() {
  editing.value    = null
  form.value       = defaultForm()
  emojiInput.value = ''
  formErr.value    = ''
  imagePreview.value = ''
  imageFile.value    = null
  rawImageUrl.value  = ''
  showGradientPicker.value = false
  showDialog.value = true
  loadLinkOptions(form.value.link_type)
}

function openEdit(b) {
  editing.value = b
  form.value = {
    tag:        b.tag        || '',
    title:      b.title      || '',
    subtitle:   b.subtitle   || '',
    gradient:   b.gradient   || 'from-violet-700 via-violet-600 to-fuchsia-600',
    cta_label:  b.cta_label  || '',
    cta_icon:   b.cta_icon   || 'fi fi-rr-shopping-cart',
    cta_color:  b.cta_color  || 'bg-orange-500 hover:bg-orange-600',
    link_type:  b.link_type  || 'none',
    link_value: b.link_value || '',
    emojis:       b.emojis       || [],
    is_active:    b.is_active    ?? true,
    aspect_ratio: b.aspect_ratio || '16/9',
  }
  emojiInput.value   = (b.emojis || []).join(',')
  formErr.value      = ''
  imagePreview.value = b.image_path ? `/storage/${b.image_path}` : ''
  imageFile.value    = null
  rawImageUrl.value  = ''
  showGradientPicker.value = false
  showDialog.value   = true
  loadLinkOptions(form.value.link_type)
}

// ---- Image / Crop ----
function onImageSelect(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (imageInput.value) imageInput.value.value = ''
  rawImageUrl.value  = URL.createObjectURL(file)
  cropZoom.value     = 100
  cropOffsetX.value  = 0
  cropOffsetY.value  = 0
  naturalW.value     = 0
  naturalH.value     = 0
  showCrop.value     = true
}

function clearImage() {
  imagePreview.value = ''
  imageFile.value    = null
  rawImageUrl.value  = ''
  if (imageInput.value) imageInput.value.value = ''
}

// -- Crop --
function onCropImgLoad() {
  const img = cropImg.value
  if (!img) return
  naturalW.value = img.naturalWidth
  naturalH.value = img.naturalHeight
  nextTick(() => {
    const cw = cropContainer.value?.clientWidth || 400
    const ch = cw * 9 / 16   // 16:9 ratio
    // Minimum zoom: image must cover container height
    const hAt100 = cw * naturalH.value / naturalW.value
    const mz = hAt100 < ch ? Math.ceil((ch / hAt100) * 100) : 100
    minZoom.value = mz
    cropZoom.value = mz
    // Center image
    const dw = cw * cropZoom.value / 100
    const dh = dw * naturalH.value / naturalW.value
    cropOffsetX.value = (cw - dw) / 2
    cropOffsetY.value = (ch - dh) / 2
    clampCropOffset()
  })
}

function clampCropOffset() {
  if (!cropContainer.value || !naturalW.value) return
  const cw = cropContainer.value.clientWidth
  const ch = cw * 9 / 16   // 16:9 ratio
  const dw = cw * cropZoom.value / 100
  const dh = dw * naturalH.value / naturalW.value
  cropOffsetX.value = Math.min(0, Math.max(cropOffsetX.value, cw - dw))
  cropOffsetY.value = Math.min(0, Math.max(cropOffsetY.value, ch - dh))
}

function startCropDrag(e) {
  isDragging.value = true
  const pt = e.touches?.[0] ?? e
  _lastDragX = pt.clientX
  _lastDragY = pt.clientY
}

function onCropMouseMove(e) {
  if (!isDragging.value) return
  cropOffsetX.value += e.clientX - _lastDragX
  cropOffsetY.value += e.clientY - _lastDragY
  _lastDragX = e.clientX
  _lastDragY = e.clientY
  clampCropOffset()
}

function onCropTouchMove(e) {
  if (!isDragging.value || !e.touches.length) return
  const pt = e.touches[0]
  cropOffsetX.value += pt.clientX - _lastDragX
  cropOffsetY.value += pt.clientY - _lastDragY
  _lastDragX = pt.clientX
  _lastDragY = pt.clientY
  clampCropOffset()
}

function stopCropDrag() {
  isDragging.value = false
}

function confirmCrop() {
  if (!naturalW.value || !naturalH.value || !cropContainer.value) return
  const canvas = document.createElement('canvas')
  canvas.width  = 1920
  canvas.height = 1080
  const ctx = canvas.getContext('2d')

  const cw = cropContainer.value.clientWidth
  const dw = cw * cropZoom.value / 100
  const dh = dw * naturalH.value / naturalW.value

  const scaleX = naturalW.value / dw
  const scaleY = naturalH.value / dh

  ctx.drawImage(
    cropImg.value,
    -cropOffsetX.value * scaleX,
    -cropOffsetY.value * scaleY,
    cw * scaleX,
    (cw * 9 / 16) * scaleY,
    0, 0, 1920, 1080,
  )

  canvas.toBlob(blob => {
    if (!blob) return
    imageFile.value    = new File([blob], 'banner.jpg', { type: 'image/jpeg' })
    imagePreview.value = URL.createObjectURL(blob)
    showCrop.value     = false
    rawImageUrl.value  = ''
  }, 'image/jpeg', 0.92)
}

function cancelCrop() {
  showCrop.value    = false
  rawImageUrl.value = ''
  if (imageInput.value) imageInput.value.value = ''
}

// ---- Link type change ----
function onLinkTypeChange() {
  form.value.link_value = ''
  loadLinkOptions(form.value.link_type)
}

// ---- Save ----
async function saveBanner() {
  formErr.value = ''
  saving.value  = true
  try {
    const payload = {
      ...form.value,
      emojis: emojiInput.value
        ? emojiInput.value.split(',').map(e => e.trim()).filter(Boolean)
        : [],
    }

    let saved
    if (editing.value) {
      const { data } = await api.put(`/market/banners/${editing.value.id}`, payload)
      saved = data
      const idx = banners.value.findIndex(b => b.id === editing.value.id)
      if (idx >= 0) banners.value[idx] = saved
    } else {
      const { data } = await api.post('/market/banners', payload)
      saved = data
      banners.value.push(saved)
    }

    // Upload image if selected
    if (imageFile.value && saved?.id) {
      const fd = new FormData()
      fd.append('image', imageFile.value)
      const { data: updated } = await api.post(`/market/banners/${saved.id}/image`, fd, {
        headers: { 'Content-Type': undefined },
      })
      const idx = banners.value.findIndex(b => b.id === saved.id)
      if (idx >= 0) banners.value[idx] = updated
    }

    showDialog.value = false
  } catch (e) {
    formErr.value = e.response?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    saving.value = false
  }
}

// ---- Toggle ----
async function toggleActive(b) {
  try {
    const { data } = await api.patch(`/market/banners/${b.id}/toggle`)
    const idx = banners.value.findIndex(x => x.id === b.id)
    if (idx >= 0) banners.value[idx] = data
  } catch { /* ignore */ }
}

// ---- Delete ----
function confirmDelete(b) {
  deleteTarget.value  = b
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

// ---- Drag-to-reorder (list) ----
function onDragStart(idx) { dragSrc.value = idx; dragOver.value = idx }
function onDragOver(idx) { dragOver.value = idx }
function onDrop() {
  const from = dragSrc.value
  const to   = dragOver.value
  if (from === null || to === null || from === to) return
  const arr = [...banners.value]
  const [moved] = arr.splice(from, 1)
  arr.splice(to, 0, moved)
  banners.value = arr
  saveReorder()
}
function onDragEnd() { dragSrc.value = null; dragOver.value = null }

async function saveReorder() {
  reordering.value = true
  try {
    await api.post('/market/banners/reorder', { ids: banners.value.map(b => b.id) })
  } catch { /* ignore */ } finally {
    reordering.value = false
  }
}

// ---- Link display label ----
function linkDisplayLabel(b) {
  if (!b.link_type || b.link_type === 'none' || !b.link_value) return 'ไม่มีลิงก์'
  const type = { product: 'สินค้า', category: 'หมวด', group: 'กลุ่ม', url: 'URL' }[b.link_type] || b.link_type
  return `${type}: ${b.link_value}`
}

onMounted(() => {
  load()
  window.addEventListener('mousemove', onCropMouseMove)
  window.addEventListener('mouseup', stopCropDrag)
  window.addEventListener('touchmove', onCropTouchMove, { passive: false })
  window.addEventListener('touchend', stopCropDrag)
})

onUnmounted(() => {
  window.removeEventListener('mousemove', onCropMouseMove)
  window.removeEventListener('mouseup', stopCropDrag)
  window.removeEventListener('touchmove', onCropTouchMove)
  window.removeEventListener('touchend', stopCropDrag)
})
</script>

<style scoped>
.form-label  { display: block; font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }
.form-input  { width: 100%; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .45rem .65rem; font-size: .875rem; background: white; }
.form-input:focus { outline: none; border-color: #a78bfa; }
.crop-fade-enter-active { transition: opacity .15s ease; }
.crop-fade-leave-active { transition: opacity .12s ease; }
.crop-fade-enter-from, .crop-fade-leave-to { opacity: 0; }
</style>
