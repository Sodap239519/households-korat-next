<template>
  <div class="p-3 sm:p-5 space-y-4 max-w-2xl mx-auto">
    <div>
      <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-shop text-violet-600"></i> ตั้งค่าร้านค้าของฉัน
      </h2>
      <p class="text-xs text-slate-400 mt-0.5">แก้ไขข้อมูลร้านค้า ช่องทางการรับเงิน และรูปโลโก้</p>
    </div>

    <div v-if="loading" class="box-card p-12 flex items-center justify-center">
      <i class="fi fi-rr-spinner animate-spin text-3xl text-violet-400"></i>
    </div>

    <template v-else-if="group">
      <!-- โปรไฟล์ร้านค้า -->
      <div class="box-card p-5 space-y-4">
        <h3 class="font-semibold text-slate-700 text-sm flex items-center gap-2">
          <i class="fi fi-rr-document text-violet-500"></i> โปรไฟล์ร้านค้า
        </h3>

        <!-- โลโก้ + ชื่อร้าน -->
        <div class="flex items-start gap-4">
          <!-- Logo -->
          <div class="shrink-0">
            <div class="relative">
              <img v-if="logoPreview || avatarUrl || application?.logo_url"
                :src="logoPreview || avatarUrl || application?.logo_url"
                class="w-20 h-20 rounded-2xl object-cover border-2 border-violet-200 shadow" />
              <div v-else
                class="w-20 h-20 rounded-2xl bg-slate-100 border-2 border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 cursor-pointer hover:border-violet-400 hover:bg-violet-50 transition"
                @click="$refs.logoInput.click()">
                <i class="fi fi-rr-shop text-xl"></i>
                <span class="text-[10px] mt-1">โลโก้</span>
              </div>
            </div>
            <input type="file" accept="image/*" class="hidden" ref="logoInput" @change="onLogoChange" />
            <button type="button"
              class="mt-2 w-20 text-center text-[11px] text-violet-600 hover:text-violet-800 font-medium flex items-center justify-center gap-1 transition"
              @click="$refs.logoInput.click()">
              <i class="fi fi-rr-upload text-[10px]"></i> เปลี่ยน
            </button>
            <button v-if="logoFile" type="button"
              class="w-20 text-center text-[11px] text-amber-500 hover:text-amber-700 flex items-center justify-center gap-1 transition"
              @click="clearLogoSelection">
              <i class="fi fi-rr-cross-circle text-[10px]"></i> ยกเลิก
            </button>
            <button v-else-if="avatarUrl" type="button"
              class="w-20 text-center text-[11px] text-rose-400 hover:text-rose-600 flex items-center justify-center gap-1 transition"
              @click="deleteLogo">
              <i class="fi fi-rr-trash text-[10px]"></i> ลบ
            </button>
          </div>

          <!-- ชื่อร้านค้า + เจ้าของ -->
          <div class="flex-1 min-w-0 space-y-2.5">
            <div>
              <label class="form-label">ชื่อร้านค้า <span class="text-rose-500">*</span></label>
              <input v-model="form.business_name" class="inp" placeholder="ชื่อร้านของคุณ" />
            </div>
            <div v-if="application?.applicant_name" class="text-xs text-slate-500 flex items-center gap-1.5">
              <i class="fi fi-rr-user text-[11px] text-slate-400"></i>
              เจ้าของ: <span class="font-medium text-slate-700">{{ application.applicant_name }}</span>
            </div>
          </div>
        </div>

        <!-- อำเภอที่ขาย + auto-detect โซน -->
        <div>
          <label class="form-label">อำเภอที่ขาย <span class="text-rose-500">*</span></label>
          <div class="relative">
            <select v-model="form.district" class="inp appearance-none pr-8">
              <option value="">— เลือกอำเภอ —</option>
              <template v-if="allZones.length">
                <optgroup v-for="z in allZones" :key="z.id" :label="z.name">
                  <option v-for="d in (z.districts || [])" :key="d" :value="d">{{ d }}</option>
                </optgroup>
              </template>
              <template v-else>
                <option v-for="d in (group.districts || [])" :key="d" :value="d">{{ d }}</option>
              </template>
            </select>
            <i class="fi fi-rr-angle-small-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
          </div>
          <!-- โซนที่ auto-detect จากอำเภอ -->
          <div v-if="form.district" class="mt-1.5 flex items-center gap-1.5 flex-wrap">
            <i class="fi fi-rr-map-marker text-[10px]" :class="detectedZone ? 'text-violet-400' : 'text-amber-400'"></i>
            <span v-if="detectedZone" class="text-[11px] text-slate-500">
              โซน: <span class="font-semibold text-violet-700">{{ detectedZone.name }}</span>
            </span>
            <span v-else class="text-[11px] text-amber-600">ไม่พบโซนที่รองรับอำเภอนี้</span>
            <button type="button" @click="openContact('change_zone')"
              class="ml-auto text-[11px] text-violet-600 hover:text-violet-800 flex items-center gap-1 font-medium transition">
              <i class="fi fi-rr-envelope text-[10px]"></i> ขอเปลี่ยนโซน
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="form-label">ตำบล</label>
            <input v-model="form.sub_district" class="inp" placeholder="ตำบลที่ตั้งร้าน" />
          </div>
          <div>
            <label class="form-label">บ้านเลขที่ / ที่อยู่</label>
            <input v-model="form.business_address" class="inp" placeholder="บ้านเลขที่ หมู่ที่..." />
          </div>
        </div>

        <!-- ประเภทสินค้า (read-only + ขอเปลี่ยนแปลง) -->
        <div v-if="application?.business_categories?.length">
          <div class="flex items-center gap-2 mb-1">
            <label class="form-label !mb-0">ประเภทสินค้า</label>
            <button type="button" @click="openContact('change_categories')"
              class="ml-auto text-[11px] text-violet-600 hover:text-violet-800 flex items-center gap-1 font-medium transition">
              <i class="fi fi-rr-envelope text-[10px]"></i> ขอเปลี่ยนแปลง
            </button>
          </div>
          <div class="flex flex-wrap gap-1.5">
            <span v-for="cat in application.business_categories" :key="cat"
              class="px-2.5 py-1 rounded-full bg-violet-50 border border-violet-200 text-violet-700 text-[11px] font-medium">
              {{ categoryLabel(cat) }}
            </span>
          </div>
        </div>

        <!-- คำอธิบายสินค้า -->
        <div>
          <label class="form-label">คำอธิบายสินค้าที่จำหน่าย</label>
          <textarea v-model="form.products_description" rows="3" class="inp resize-none"
            placeholder="บอกลูกค้าว่าร้านคุณขายอะไร ลักษณะเด่น ที่มาของสินค้า..."></textarea>
        </div>
      </div>

      <!-- ข้อมูลติดต่อ -->
      <div class="box-card p-5 space-y-4">
        <h3 class="font-semibold text-slate-700 text-sm flex items-center gap-2">
          <i class="fi fi-rr-file-edit text-violet-500"></i> ข้อมูลติดต่อ
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="form-label">เบอร์โทรติดต่อ</label>
            <input v-model="form.contact_phone" class="inp" placeholder="0xx-xxx-xxxx" />
          </div>
          <div>
            <label class="form-label">ที่อยู่ติดต่อ (หน้าร้าน)</label>
            <input v-model="form.contact_address" class="inp" placeholder="ที่อยู่สำหรับติดต่อ" />
          </div>
        </div>
      </div>

      <!-- ช่องทางรับเงิน -->
      <div class="box-card p-5 space-y-4">
        <div>
          <h3 class="font-semibold text-slate-700 text-sm flex items-center gap-2">
            <i class="fi fi-rr-bank text-violet-500"></i> ช่องทางรับเงิน
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">ข้อมูลนี้จะแสดงให้ลูกค้าเห็นตอนชำระเงินโอน</p>
        </div>

        <!-- บัญชีธนาคาร -->
        <div class="rounded-xl border border-slate-200 p-4 space-y-3">
          <p class="text-xs font-semibold text-slate-600 flex items-center gap-1.5">
            <i class="fi fi-rr-bank text-slate-500 text-[11px]"></i> บัญชีธนาคาร
          </p>
          <div>
            <label class="form-label">ธนาคาร</label>
            <div class="relative">
              <select v-model="form.bank_name" class="inp appearance-none pr-8">
                <option value="">— ไม่ระบุ —</option>
                <option v-for="b in banks" :key="b" :value="b">{{ b }}</option>
              </select>
              <i class="fi fi-rr-angle-small-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="form-label">เลขที่บัญชี</label>
              <input v-model="form.bank_account_no" class="inp font-mono" placeholder="xxx-x-xxxxx-x" />
            </div>
            <div>
              <label class="form-label">ชื่อบัญชี</label>
              <input v-model="form.bank_account_name" class="inp" placeholder="ชื่อ-นามสกุลผู้รับ" />
            </div>
          </div>
        </div>

        <!-- พร้อมเพย์ -->
        <div class="rounded-xl border border-slate-200 p-4 space-y-3">
          <p class="text-xs font-semibold text-slate-600 flex items-center gap-1.5">
            <i class="fi fi-rr-smartphone text-slate-500 text-[11px]"></i> พร้อมเพย์
          </p>
          <div>
            <label class="form-label">หมายเลขพร้อมเพย์ (เบอร์โทร / เลขบัตรปชช.)</label>
            <input v-model="form.promptpay_id" class="inp font-mono" placeholder="0xx-xxx-xxxx หรือ x-xxxx-xxxxx-xx-x" />
          </div>
        </div>

        <!-- Preview -->
        <div v-if="form.bank_name || form.promptpay_id"
          class="rounded-xl bg-gradient-to-br from-violet-50 to-fuchsia-50 border border-violet-100 p-4">
          <p class="text-[10px] font-semibold text-violet-500 uppercase tracking-wider mb-2">ตัวอย่างที่ลูกค้าเห็น</p>
          <p v-if="form.bank_name" class="text-sm text-slate-600 font-medium">{{ form.bank_name }}</p>
          <p v-if="form.bank_account_no" class="text-base font-bold text-slate-800 font-mono tracking-wide">{{ form.bank_account_no }}</p>
          <p v-if="form.bank_account_name" class="text-sm text-slate-600">{{ form.bank_account_name }}</p>
          <p v-if="form.promptpay_id" class="text-sm text-violet-600 mt-1.5 flex items-center gap-1.5">
            <i class="fi fi-rr-smartphone text-xs"></i> พร้อมเพย์: {{ form.promptpay_id }}
          </p>
        </div>
        <div v-else class="text-xs text-amber-600 flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-xl p-3">
          <i class="fi fi-rr-exclamation shrink-0 mt-0.5"></i>
          <span>ยังไม่มีข้อมูลรับเงิน — ลูกค้าจะไม่เห็นช่องทางโอนเงิน กรุณากรอกอย่างน้อย 1 ช่องทาง</span>
        </div>
      </div>

      <!-- ติดต่อผู้ดูแลระบบ -->
      <div class="box-card p-5 space-y-3">
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-slate-700 text-sm flex items-center gap-2">
            <i class="fi fi-rr-envelope text-violet-500"></i> ติดต่อผู้ดูแลระบบ
          </h3>
          <button type="button" @click="openContact(null)"
            class="ml-auto px-3 py-1.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-[11px] font-semibold flex items-center gap-1.5 transition shadow shadow-violet-400/30">
            <i class="fi fi-rr-paper-plane text-[10px]"></i> ส่งคำขอ
          </button>
        </div>
        <p class="text-xs text-slate-400">สำหรับขอเปลี่ยนแปลงข้อมูลที่แก้ไขเองไม่ได้ เช่น โซนการขาย ประเภทสินค้า</p>

        <!-- ประวัติคำขอ -->
        <div v-if="myRequests.length" class="space-y-2">
          <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">คำขอที่ส่งไว้</p>
          <div v-for="r in myRequests" :key="r.id"
            class="rounded-xl border px-3 py-2.5 flex items-start gap-3"
            :class="{
              'border-amber-200 bg-amber-50': r.status === 'pending',
              'border-blue-200 bg-blue-50': r.status === 'in_progress',
              'border-emerald-200 bg-emerald-50': r.status === 'resolved',
              'border-rose-200 bg-rose-50': r.status === 'rejected',
            }">
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-slate-700">{{ r.topic_label }}</p>
              <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">{{ r.detail }}</p>
              <p v-if="r.admin_note" class="text-[11px] mt-1 text-slate-600 bg-white/60 rounded px-2 py-1">
                <i class="fi fi-rr-comment-alt text-[10px]"></i> {{ r.admin_note }}
              </p>
            </div>
            <div class="shrink-0 text-right">
              <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                :class="{
                  'bg-amber-200 text-amber-800': r.status === 'pending',
                  'bg-blue-200 text-blue-800': r.status === 'in_progress',
                  'bg-emerald-200 text-emerald-800': r.status === 'resolved',
                  'bg-rose-200 text-rose-800': r.status === 'rejected',
                }">{{ r.status_label }}</span>
              <p class="text-[10px] text-slate-400 mt-1">{{ r.created_at }}</p>
            </div>
          </div>
        </div>
        <p v-else class="text-xs text-slate-400 italic">ยังไม่มีคำขอ</p>
      </div>

      <!-- Save -->
      <div class="flex justify-end gap-2 pb-4">
        <button type="button" @click="load"
          class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">
          รีเซ็ต
        </button>
        <button type="button" @click="save" :disabled="saving"
          class="px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white text-sm font-semibold flex items-center gap-2 transition shadow-md shadow-violet-500/30">
          <i :class="saving ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-check'"></i>
          {{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}
        </button>
      </div>
    </template>

    <div v-else-if="!loading" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-shop text-4xl"></i>
      <p class="mt-3 text-sm">ยังไม่ได้สังกัดกลุ่มผู้ขาย</p>
      <p class="text-xs mt-1">ติดต่อผู้ดูแลระบบเพื่อกำหนดกลุ่ม</p>
    </div>

    <!-- Dialog ติดต่อผู้ดูแลระบบ -->
    <Dialog v-model:visible="contactDialog.show" modal :draggable="false"
      header="ส่งคำขอถึงผู้ดูแลระบบ" class="w-full max-w-md mx-4">
      <div class="space-y-4 pt-1">
        <!-- หัวข้อ -->
        <div>
          <label class="form-label">เรื่องที่ต้องการติดต่อ <span class="text-rose-500">*</span></label>
          <div class="space-y-2 mt-1">
            <label v-for="(label, key) in TOPICS" :key="key"
              class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
              :class="contactDialog.topic === key
                ? 'border-violet-400 bg-violet-50'
                : 'border-slate-200 hover:border-violet-200 hover:bg-violet-50/50'">
              <input type="radio" v-model="contactDialog.topic" :value="key"
                class="mt-0.5 accent-violet-600 shrink-0" />
              <div>
                <p class="text-sm font-semibold text-slate-700">{{ label }}</p>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  <template v-if="key === 'change_zone'">ระบุโซน/อำเภอที่ต้องการย้าย</template>
                  <template v-else-if="key === 'change_categories'">ระบุประเภทสินค้าที่ต้องการเพิ่ม/แก้ไข</template>
                  <template v-else>อธิบายเรื่องที่ต้องการสื่อสาร</template>
                </p>
              </div>
            </label>
          </div>
        </div>

        <!-- เลือกโซน (เฉพาะ change_zone) -->
        <div v-if="contactDialog.topic === 'change_zone'">
          <label class="form-label">โซนที่ต้องการย้ายไป <span class="text-rose-500">*</span></label>
          <div class="relative">
            <select v-model="contactDialog.selectedZone" class="inp appearance-none pr-8">
              <option value="">— เลือกโซน —</option>
              <option v-for="z in allZones" :key="z.id" :value="z.name">{{ z.name }}</option>
            </select>
            <i class="fi fi-rr-angle-small-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
          </div>
        </div>

        <!-- เลือกประเภทสินค้า (เฉพาะ change_categories) -->
        <div v-if="contactDialog.topic === 'change_categories'">
          <label class="form-label">ประเภทสินค้าที่ต้องการ <span class="text-rose-500">*</span></label>
          <div class="grid grid-cols-2 gap-2 mt-1">
            <label v-for="(label, key) in categoryLabels" :key="key"
              class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border cursor-pointer transition"
              :class="contactDialog.selectedCategories.includes(key)
                ? 'border-violet-400 bg-violet-50'
                : 'border-slate-200 hover:border-violet-200 hover:bg-slate-50'">
              <input type="checkbox" :value="key" v-model="contactDialog.selectedCategories"
                class="accent-violet-600 shrink-0" />
              <span class="text-xs font-medium text-slate-700">{{ label }}</span>
            </label>
          </div>
        </div>

        <!-- เหตุผล/รายละเอียด -->
        <div v-if="contactDialog.topic">
          <label class="form-label">
            {{ contactDialog.topic === 'other' ? 'รายละเอียด' : 'เหตุผล / รายละเอียดเพิ่มเติม' }}
            <span class="text-rose-500">*</span>
          </label>
          <textarea v-model="contactDialog.detail" rows="3" class="inp resize-none"
            :placeholder="contactDialog.topic === 'change_zone'
              ? 'เหตุผลที่ต้องการย้ายโซน...'
              : contactDialog.topic === 'change_categories'
              ? 'เหตุผลที่ต้องการเปลี่ยนประเภทสินค้า...'
              : 'อธิบายสิ่งที่ต้องการให้ผู้ดูแลระบบดำเนินการ...'"></textarea>
          <p class="text-[11px] text-slate-400 mt-1">{{ contactDialog.detail.length }}/1000 ตัวอักษร</p>
        </div>

        <!-- แจ้งเตือน pending -->
        <div v-if="hasPendingTopic(contactDialog.topic)"
          class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-700 flex items-start gap-2">
          <i class="fi fi-rr-exclamation shrink-0 mt-0.5"></i>
          <span>คุณมีคำขอหัวข้อนี้ที่รอดำเนินการอยู่แล้ว — กรุณารอผลก่อนส่งใหม่</span>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" @click="contactDialog.show = false"
            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 text-sm hover:bg-slate-50 transition">
            ยกเลิก
          </button>
          <button type="button" @click="sendContactRequest"
            :disabled="!contactCanSubmit"
            class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white text-sm font-semibold flex items-center gap-2 transition">
            <i :class="contactDialog.sending ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-paper-plane'"></i>
            {{ contactDialog.sending ? 'กำลังส่ง...' : 'ส่งคำขอ' }}
          </button>
        </div>
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'

const toast = useToast()

const loading     = ref(false)
const saving      = ref(false)
const group       = ref(null)
const application = ref(null)
const allZones    = ref([])
const logoFile    = ref(null)
const logoPreview = ref(null)
const logoInput   = ref(null)
const avatarUrl   = ref(null)   // รูปโปรไฟล์ร้านของตัวเอง (avatar ของ user)

const detectedZone = computed(() => {
  if (!form.district || !allZones.value.length) return null
  return allZones.value.find(z => (z.districts || []).includes(form.district)) ?? null
})

const TOPICS = {
  change_zone:       'ขอเปลี่ยนแปลงโซนการขาย',
  change_categories: 'ขอเปลี่ยนแปลงประเภทสินค้า',
  other:             'เรื่องอื่นๆ',
}

const myRequests    = ref([])
const contactDialog = reactive({
  show: false, topic: '', detail: '', sending: false,
  selectedZone: '',        // สำหรับ change_zone
  selectedCategories: [],  // สำหรับ change_categories
})

const contactCanSubmit = computed(() => {
  if (!contactDialog.topic || contactDialog.sending) return false
  if (hasPendingTopic(contactDialog.topic)) return false
  if (contactDialog.topic === 'change_zone' && !contactDialog.selectedZone) return false
  if (contactDialog.topic === 'change_categories' && !contactDialog.selectedCategories.length) return false
  return contactDialog.detail.length >= 5
})

const form = reactive({
  // กลุ่ม
  description:       '',
  contact_phone:     '',
  contact_address:   '',
  bank_name:         '',
  bank_account_no:   '',
  bank_account_name: '',
  promptpay_id:      '',
  // ใบสมัคร
  business_name:        '',
  products_description: '',
  district:             '',
  sub_district:         '',
  business_address:     '',
})

const banks = [
  'ธนาคารกรุงเทพ', 'ธนาคารกสิกรไทย', 'ธนาคารกรุงไทย', 'ธนาคารทหารไทยธนชาต (TTB)',
  'ธนาคารไทยพาณิชย์', 'ธนาคารกรุงศรีอยุธยา', 'ธนาคารออมสิน', 'ธนาคาร ธ.ก.ส.',
  'ธนาคารอาคารสงเคราะห์ (ธอส.)', 'ธนาคารอิสลามแห่งประเทศไทย', 'ธนาคารซีไอเอ็มบีไทย',
]

const categoryLabels = {
  vegetable_fruit: 'ผัก/ผลไม้',
  processed_food: 'อาหารแปรรูป',
  fresh_food: 'อาหารสด',
  mushroom: 'เห็ด',
  herb: 'สมุนไพร',
  handicraft: 'งานหัตถกรรม',
  textile: 'สิ่งทอ',
  other: 'อื่นๆ',
}
function categoryLabel(cat) { return categoryLabels[cat] || cat }

async function load() {
  loading.value = true
  try {
    const [{ data }, zonesRes] = await Promise.all([
      api.get('/market/seller-groups/my-group'),
      api.get('/market/seller-groups').catch(() => ({ data: [] })),
    ])
    group.value       = data.group
    application.value = data.application
    avatarUrl.value   = data.avatar_url
    allZones.value    = Array.isArray(zonesRes.data) ? zonesRes.data : []

    const g = data.group
    const a = data.application
    Object.assign(form, {
      description:       g.description       ?? '',
      contact_phone:     g.contact_phone     ?? '',
      contact_address:   g.contact_address   ?? '',
      bank_name:         g.bank_name         ?? '',
      bank_account_no:   g.bank_account_no   ?? '',
      bank_account_name: g.bank_account_name ?? '',
      promptpay_id:      g.promptpay_id      ?? '',
      // ใบสมัคร
      business_name:        a?.business_name        ?? '',
      products_description: a?.products_description ?? '',
      district:             a?.district             ?? '',
      sub_district:         a?.sub_district         ?? '',
      business_address:     a?.business_address     ?? '',
    })
    logoFile.value    = null
    logoPreview.value = null
  } catch {
    toast.add({ severity: 'error', summary: 'โหลดไม่สำเร็จ', life: 3000 })
  } finally { loading.value = false }
}

function onLogoChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
  logoFile.value    = file
  logoPreview.value = URL.createObjectURL(file)
}

function clearLogoSelection() {
  if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
  logoFile.value    = null
  logoPreview.value = null
  if (logoInput.value) logoInput.value.value = ''
}

async function deleteLogo() {
  try {
    await api.delete('/market/my-avatar')
    avatarUrl.value = null
    toast.add({ severity: 'success', summary: 'ลบรูปโปรไฟล์แล้ว', life: 2000 })
  } catch {
    toast.add({ severity: 'error', summary: 'ลบรูปโปรไฟล์ไม่สำเร็จ', life: 3000 })
  }
}

async function save() {
  saving.value = true
  try {
    if (logoFile.value) {
      const fd = new FormData()
      fd.append('logo', logoFile.value)
      const { data: logoData } = await api.post(
        '/market/my-avatar', fd,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      )
      avatarUrl.value   = logoData.avatar_url
      logoFile.value    = null
      logoPreview.value = null
    }
    await api.put('/market/seller-groups/my-group', form)
    toast.add({ severity: 'success', summary: 'บันทึกแล้ว', detail: 'อัปเดตข้อมูลร้านค้าสำเร็จ', life: 2500 })
    load()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'บันทึกไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 })
  } finally { saving.value = false }
}

async function loadMyRequests() {
  try {
    const { data } = await api.get('/market/seller-requests/my')
    myRequests.value = data
  } catch { /* ไม่ blocking */ }
}

function openContact(topic) {
  contactDialog.topic              = topic ?? ''
  contactDialog.detail             = ''
  contactDialog.selectedZone       = ''
  contactDialog.selectedCategories = []
  contactDialog.sending            = false
  contactDialog.show               = true
}

function hasPendingTopic(topic) {
  if (!topic) return false
  return myRequests.value.some(r => r.topic === topic && ['pending', 'in_progress'].includes(r.status))
}

async function sendContactRequest() {
  if (!contactCanSubmit.value) return
  contactDialog.sending = true

  let fullDetail = ''
  if (contactDialog.topic === 'change_zone') {
    fullDetail = `โซนที่ต้องการ: ${contactDialog.selectedZone}`
    if (contactDialog.detail) fullDetail += `\nเหตุผล: ${contactDialog.detail}`
  } else if (contactDialog.topic === 'change_categories') {
    const catNames = contactDialog.selectedCategories.map(c => categoryLabels[c] || c).join(', ')
    fullDetail = `ประเภทสินค้าที่ต้องการ: ${catNames}`
    if (contactDialog.detail) fullDetail += `\nเหตุผล: ${contactDialog.detail}`
  } else {
    fullDetail = contactDialog.detail
  }

  try {
    const { data } = await api.post('/market/seller-requests', {
      topic:  contactDialog.topic,
      detail: fullDetail,
    })
    toast.add({ severity: 'success', summary: 'ส่งคำขอแล้ว', detail: data.message, life: 5000 })
    contactDialog.show = false
    loadMyRequests()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'ส่งไม่สำเร็จ', detail: e.response?.data?.message || '', life: 4000 })
  } finally { contactDialog.sending = false }
}

load()
loadMyRequests()
</script>

<style scoped>
.inp {
  display: block;
  width: 100%;
  height: 2.75rem;
  padding: 0 0.75rem;
  border-radius: 0.75rem;
  border: 1.5px solid rgb(203 213 225);
  background: #fff;
  font-size: 0.875rem;
  color: rgb(30 41 59);
  outline: none;
  transition: border-color .15s;
}
.inp:focus {
  border-color: rgb(139 92 246);
  box-shadow: 0 0 0 3px rgba(139, 92, 246, .12);
}
.inp::placeholder { color: rgb(148 163 184); }
textarea.inp { height: auto; padding: 0.6rem 0.75rem; }
select.inp { appearance: none; }
</style>
