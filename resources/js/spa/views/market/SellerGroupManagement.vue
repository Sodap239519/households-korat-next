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
      <div v-for="g in rows" :key="g.id" class="box-card p-4"
        :class="g.shop_status === 'banned' ? 'border border-rose-200 bg-rose-50/30' : g.shop_status === 'suspended' ? 'border border-amber-200 bg-amber-50/30' : ''">
        <div class="flex items-start gap-3">
          <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 flex items-center justify-center bg-gradient-to-br from-violet-500 to-fuchsia-600">
            <img v-if="g.logo_url" :src="g.logo_url" class="w-full h-full object-cover" alt="" />
            <i v-else class="fi fi-rr-shop text-white text-xl"></i>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="font-bold text-slate-800 truncate">{{ g.name }}</h3>
              <span v-if="!g.is_active" class="text-xs text-slate-400">(ปิด)</span>
              <!-- สถานะร้าน -->
              <span v-if="g.shop_status === 'banned'"
                class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">
                <i class="fi fi-rr-ban mr-0.5"></i> แบนถาวร
              </span>
              <span v-else-if="g.shop_status === 'suspended'"
                class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                <i class="fi fi-rr-time-half-past mr-0.5"></i> ระงับชั่วคราว
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">{{ (g.districts || []).join(', ') || 'ยังไม่ระบุอำเภอ' }}</p>

            <!-- ban/suspend info -->
            <div v-if="g.shop_status !== 'active' && g.ban_reason" class="mt-1 text-xs text-rose-600">
              <i class="fi fi-rr-comment-alt mr-0.5"></i> {{ g.ban_reason }}
              <span v-if="g.suspended_until"> — ถึง {{ fmtDate(g.suspended_until) }}</span>
            </div>

            <div class="flex gap-3 mt-2 text-xs text-slate-500">
              <span><i class="fi fi-rr-box-open"></i> {{ g.products_count ?? 0 }} สินค้า</span>
              <span><i class="fi fi-rr-user"></i> {{ g.members_count ?? 0 }} สมาชิก</span>
              <span :class="g.line_target_id ? 'text-emerald-600' : 'text-slate-400'">
                <i class="fi fi-rr-bell"></i> LINE {{ g.line_target_id ? (g.line_notify_enabled ? 'เปิด' : 'พัก') : 'ยังไม่ตั้ง' }}
              </span>
            </div>

            <!-- สมาชิกผู้ดูแลร้าน -->
            <div v-if="g.members?.length" class="mt-2 flex flex-wrap gap-1">
              <span v-for="m in g.members" :key="m.id"
                class="text-[11px] bg-violet-50 text-violet-700 border border-violet-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                <i class="fi fi-rr-user-headset"></i> {{ m.name }}
              </span>
            </div>
            <p v-else class="mt-1 text-[11px] text-slate-400 italic">ยังไม่มีผู้ดูแล</p>
          </div>
          <div class="flex flex-col gap-1 items-end">
            <Button icon="fi fi-rr-truck-side" text rounded size="small" v-tooltip.top="'บริการจัดส่ง'" @click="openShipDialog(g)" />
            <Button icon="fi fi-rr-edit" text rounded size="small" v-tooltip.top="'แก้ไขกลุ่ม'" @click="openEdit(g)" />
            <Button v-if="isAdmin && g.shop_status === 'active'" icon="fi fi-rr-ban" text rounded size="small"
              severity="danger" v-tooltip.top="'แบน/ระงับ'" @click="openBanDialog(g)" />
            <Button v-else-if="isAdmin && g.shop_status !== 'active'" icon="fi fi-rr-check-circle" text rounded size="small"
              severity="success" v-tooltip.top="'คืนสถานะ'" @click="restoreGroup(g)" />
          </div>
        </div>
      </div>
    </div>

    <!-- Ban/Suspend Dialog -->
    <Dialog v-model:visible="banDialogOpen" modal header="แบน / ระงับร้านค้า" :style="{ width: '32rem' }" :breakpoints="{ '960px': '95vw' }">
      <div v-if="banTarget" class="space-y-4">
        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
          <i class="fi fi-rr-shop text-violet-600 text-xl"></i>
          <div>
            <p class="font-semibold text-slate-800">{{ banTarget.name }}</p>
            <p class="text-xs text-slate-400">{{ (banTarget.districts || []).join(', ') }}</p>
          </div>
        </div>

        <div class="flex gap-2">
          <button @click="banMode = 'suspend'"
            class="flex-1 h-10 rounded-xl border text-sm font-semibold transition"
            :class="banMode === 'suspend' ? 'bg-amber-500 border-amber-500 text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
            <i class="fi fi-rr-time-half-past mr-1"></i> ระงับชั่วคราว
          </button>
          <button @click="banMode = 'ban'"
            class="flex-1 h-10 rounded-xl border text-sm font-semibold transition"
            :class="banMode === 'ban' ? 'bg-rose-600 border-rose-600 text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
            <i class="fi fi-rr-ban mr-1"></i> แบนถาวร
          </button>
        </div>

        <div v-if="banMode === 'suspend'">
          <label class="form-label">ระงับถึงวันที่</label>
          <input v-model="banForm.suspended_until" type="datetime-local" class="inp w-full" />
        </div>

        <div>
          <label class="form-label">เหตุผล *</label>
          <textarea v-model="banForm.reason" rows="3" class="inp w-full"
            :placeholder="banMode === 'ban' ? 'เช่น ละเมิดข้อตกลง ขายสินค้าผิดกฎหมาย' : 'เช่น รอตรวจสอบเรื่องร้องเรียน'"></textarea>
          <p v-if="banErr" class="text-xs text-rose-500 mt-1">{{ banErr }}</p>
        </div>

        <div class="flex gap-2">
          <Button label="ยกเลิก" text class="flex-1" @click="banDialogOpen = false" />
          <Button :label="banMode === 'ban' ? 'แบนถาวร' : 'ระงับชั่วคราว'"
            :severity="banMode === 'ban' ? 'danger' : 'warning'"
            class="flex-1" :loading="banSaving" @click="submitBan" />
        </div>
      </div>
    </Dialog>

    <!-- Edit dialog -->
    <Dialog v-model:visible="dialogOpen" modal :header="form.id ? 'แก้ไขกลุ่มผู้ขาย' : 'เพิ่มกลุ่มผู้ขาย'" :style="{ width: '40rem' }" :breakpoints="{ '960px': '95vw' }">
      <div class="space-y-4">
        <FormSection title="ข้อมูลกลุ่ม" icon="fi fi-rr-shop" tone="violet">
          <!-- Logo upload (แสดงเฉพาะตอนแก้ไข — ต้องมี id ก่อน) -->
          <div v-if="form.id" class="flex items-center gap-4 mb-3">
            <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 flex items-center justify-center bg-gradient-to-br from-violet-500 to-fuchsia-600 border border-violet-100">
              <img v-if="form.logo_url" :src="form.logo_url" class="w-full h-full object-cover" alt="โลโก้" />
              <i v-else class="fi fi-rr-shop text-white text-2xl"></i>
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-slate-700 mb-1">โลโก้/รูปกลุ่ม</p>
              <div class="flex items-center gap-2 flex-wrap">
                <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-violet-50 border border-violet-200 text-sm text-violet-700 hover:bg-violet-100 transition font-medium">
                  <i class="fi fi-rr-upload text-xs"></i>
                  <span>{{ form.logo_url ? 'เปลี่ยนรูป' : 'อัปโหลด' }}</span>
                  <input type="file" accept="image/*" class="sr-only" @change="onLogoChange" />
                </label>
                <button v-if="form.logo_url" type="button" @click="deleteLogo"
                  class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-rose-200 text-rose-500 text-sm hover:bg-rose-50 transition">
                  <i class="fi fi-rr-trash text-xs"></i> ลบ
                </button>
              </div>
              <p class="text-xs text-slate-400 mt-1">JPG/PNG/WebP ขนาดไม่เกิน 2MB</p>
            </div>
          </div>

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

    <!-- ===== Shipping options dialog ===== -->
    <Dialog v-model:visible="shipDialogOpen" modal :header="`บริการจัดส่ง — ${activeShipGroup?.name ?? ''}`"
      :style="{ width: '38rem' }" :breakpoints="{ '960px': '95vw' }">
      <div class="space-y-3">
        <!-- List -->
        <div v-if="shipRows.length" class="space-y-2">
          <div v-for="opt in shipRows" :key="opt.id"
            class="flex items-center gap-3 p-3 rounded-xl border"
            :class="opt.is_active ? 'border-slate-100 bg-white' : 'border-slate-100 bg-slate-50 opacity-60'">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-medium text-slate-800 text-sm">{{ opt.name }}</span>
                <span v-if="opt.carrier" class="text-[10px] bg-sky-50 text-sky-600 border border-sky-100 px-1.5 py-0.5 rounded">{{ opt.carrier }}</span>
                <span v-if="opt.is_default" class="text-[10px] bg-violet-100 text-violet-700 px-1.5 py-0.5 rounded font-semibold">ค่าเริ่มต้น</span>
                <span v-if="!opt.is_active" class="text-[10px] bg-slate-100 text-slate-400 px-1.5 py-0.5 rounded">ปิด</span>
              </div>
              <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-3">
                <span :class="Number(opt.fee) === 0 ? 'text-emerald-600 font-medium' : ''">
                  {{ Number(opt.fee) === 0 ? 'ฟรี' : `฿${Number(opt.fee).toLocaleString()}` }}
                </span>
                <span><i class="fi fi-rr-clock text-[10px]"></i>
                  {{ opt.days_min === opt.days_max ? `${opt.days_min} วัน` : `${opt.days_min}–${opt.days_max} วัน` }}
                </span>
              </div>
            </div>
            <div class="flex gap-1 shrink-0">
              <button v-if="!opt.is_default" @click="setShipDefault(opt)"
                class="text-xs text-slate-400 hover:text-violet-600 px-2 py-1 rounded-lg hover:bg-violet-50 transition">ตั้งเป็นหลัก</button>
              <button @click="openShipEdit(opt)"
                class="text-xs text-violet-600 hover:text-violet-800 px-2 py-1 rounded-lg hover:bg-violet-50 transition flex items-center gap-1">
                <i class="fi fi-rr-edit text-[10px]"></i> แก้
              </button>
              <button @click="deleteShip(opt)"
                class="text-xs text-rose-400 hover:text-rose-600 px-2 py-1 rounded-lg hover:bg-rose-50 transition flex items-center gap-1">
                <i class="fi fi-rr-trash text-[10px]"></i>
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-slate-400 text-center py-3">ยังไม่มีบริการจัดส่ง — กด "+ เพิ่ม" เพื่อสร้างตัวเลือกแรก</p>

        <!-- Add / Edit form -->
        <div class="border border-dashed border-violet-200 rounded-xl p-4 bg-violet-50/40">
          <p class="text-sm font-semibold text-violet-700 mb-3 flex items-center gap-2">
            <i class="fi fi-rr-truck-side"></i>
            {{ shipEditId ? 'แก้ไขบริการจัดส่ง' : 'เพิ่มบริการจัดส่ง' }}
          </p>
          <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
              <label class="form-label">ชื่อบริการ *</label>
              <InputText v-model="shipForm.name" class="w-full" placeholder="เช่น จัดส่งโดยผู้ขาย, ส่งด่วน Kerry" :invalid="!!shipErr.name" />
              <small v-if="shipErr.name" class="text-rose-500">{{ shipErr.name }}</small>
            </div>
            <div class="col-span-2">
              <label class="form-label">บริษัทขนส่ง</label>
              <Select v-model="shipForm.carrier" :options="CARRIERS" placeholder="— เลือกบริษัทขนส่ง —"
                showClear class="w-full" />
            </div>
            <div>
              <label class="form-label">ค่าจัดส่ง (฿)</label>
              <InputNumber v-model="shipForm.fee" class="w-full" :min="0" :max="9999" :minFractionDigits="0" :maxFractionDigits="2" placeholder="0 = ฟรี" />
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="form-label">วันต่ำสุด</label>
                <InputNumber v-model="shipForm.days_min" class="w-full" :min="0" :max="90" />
              </div>
              <div>
                <label class="form-label">วันสูงสุด</label>
                <InputNumber v-model="shipForm.days_max" class="w-full" :min="0" :max="90" />
              </div>
            </div>
            <div class="flex items-center gap-2">
              <ToggleSwitch v-model="shipForm.is_default" inputId="shipdef" />
              <label for="shipdef" class="text-sm text-slate-600">ตั้งเป็นค่าเริ่มต้น</label>
            </div>
            <div class="flex items-center gap-2">
              <ToggleSwitch v-model="shipForm.is_active" inputId="shipon" />
              <label for="shipon" class="text-sm text-slate-600">เปิดใช้งาน</label>
            </div>
          </div>
          <Message v-if="shipErrMsg" severity="error" :closable="false" class="mt-2">{{ shipErrMsg }}</Message>
          <div class="flex gap-2 mt-3 justify-end">
            <Button v-if="shipEditId" label="ยกเลิกแก้ไข" text size="small" @click="resetShipForm" />
            <Button :label="shipEditId ? 'บันทึกการแก้ไข' : 'เพิ่มบริการนี้'" icon="fi fi-rr-disk" size="small" :loading="shipSaving" @click="saveShip" />
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="ปิด" text @click="shipDialogOpen = false" />
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
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import MultiSelect from 'primevue/multiselect'
import ToggleSwitch from 'primevue/toggleswitch'
import Select from 'primevue/select'
import Message from 'primevue/message'

const CARRIERS = [
  'ผู้ขายจัดส่งเอง',
  'ไปรษณีย์ไทย (EMS)',
  'Kerry Express',
  'Flash Express',
  'J&T Express',
  'Shopee Express',
  'DHL',
  'Ninja Van',
  'Best Express',
  'อื่นๆ',
]
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

const blank = () => ({ id: null, name: '', description: '', contact_phone: '', contact_address: '', bank_name: '', bank_account_no: '', bank_account_name: '', promptpay_id: '', districts: [], lat: '', lng: '', map_label: '', line_target_id: '', line_notify_enabled: true, is_active: true, member_ids: [], logo_url: null })
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
    logo_url: g.logo_url || null,
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

// ===== Logo upload =====
const logoUploading = ref(false)

async function onLogoChange(e) {
  const file = e.target.files?.[0]
  if (!file || !form.id) return
  logoUploading.value = true
  const fd = new FormData()
  fd.append('logo', file)
  try {
    const { data } = await api.post(`/market/seller-groups/${form.id}/logo`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    form.logo_url = data.logo_url
    const row = rows.value.find(r => r.id === form.id)
    if (row) row.logo_url = data.logo_url
    toast.add({ severity: 'success', summary: 'อัปโหลดโลโก้แล้ว', life: 2000 })
  } catch {
    toast.add({ severity: 'error', summary: 'อัปโหลดไม่สำเร็จ', life: 2500 })
  } finally { logoUploading.value = false }
}

async function deleteLogo() {
  if (!form.id || !form.logo_url) return
  try {
    await api.delete(`/market/seller-groups/${form.id}/logo`)
    form.logo_url = null
    const row = rows.value.find(r => r.id === form.id)
    if (row) row.logo_url = null
    toast.add({ severity: 'info', summary: 'ลบโลโก้แล้ว', life: 1800 })
  } catch {
    toast.add({ severity: 'error', summary: 'ลบไม่สำเร็จ', life: 2000 })
  }
}

// ===== Shipping options management =====
const shipDialogOpen  = ref(false)
const activeShipGroup = ref(null)
const shipRows        = ref([])
const shipSaving      = ref(false)
const shipEditId      = ref(null)
const shipErr         = reactive({})
const shipErrMsg      = ref('')

const blankShip = () => ({ name: '', carrier: '', fee: 0, days_min: 1, days_max: 7, is_default: false, is_active: true })
const shipForm = reactive(blankShip())

function resetShipForm() {
  Object.assign(shipForm, blankShip())
  shipEditId.value = null
  Object.keys(shipErr).forEach(k => delete shipErr[k])
  shipErrMsg.value = ''
}

async function loadShipOptions() {
  if (!activeShipGroup.value) return
  const { data } = await api.get(`/market/seller-groups/${activeShipGroup.value.id}/shipping`)
  shipRows.value = data || []
}

async function openShipDialog(group) {
  activeShipGroup.value = group
  resetShipForm()
  shipRows.value = []
  shipDialogOpen.value = true
  await loadShipOptions()
}

function openShipEdit(opt) {
  shipEditId.value = opt.id
  Object.assign(shipForm, {
    name: opt.name, carrier: opt.carrier || '', fee: Number(opt.fee),
    days_min: opt.days_min, days_max: opt.days_max,
    is_default: opt.is_default, is_active: opt.is_active,
  })
  shipErrMsg.value = ''
  Object.keys(shipErr).forEach(k => delete shipErr[k])
}

async function saveShip() {
  if (!shipForm.name?.trim()) { shipErr.name = 'กรุณากรอกชื่อบริการ'; return }
  shipSaving.value = true
  shipErrMsg.value = ''
  Object.keys(shipErr).forEach(k => delete shipErr[k])
  try {
    const groupId = activeShipGroup.value.id
    const payload = {
      name: shipForm.name.trim(), carrier: shipForm.carrier || null,
      fee: shipForm.fee ?? 0,
      days_min: shipForm.days_min ?? 1, days_max: shipForm.days_max ?? 7,
      is_default: shipForm.is_default, is_active: shipForm.is_active,
    }
    if (shipEditId.value) {
      await api.put(`/market/seller-groups/${groupId}/shipping/${shipEditId.value}`, payload)
    } else {
      await api.post(`/market/seller-groups/${groupId}/shipping`, payload)
    }
    toast.add({ severity: 'success', summary: shipEditId.value ? 'แก้ไขแล้ว' : 'เพิ่มแล้ว', life: 1800 })
    resetShipForm()
    await loadShipOptions()
  } catch (e) {
    if (e.response?.status === 422) {
      Object.entries(e.response.data.errors || {}).forEach(([k, v]) => shipErr[k] = v[0])
      shipErrMsg.value = 'ตรวจสอบข้อมูล'
    } else {
      shipErrMsg.value = e.response?.data?.message || 'เกิดข้อผิดพลาด'
    }
  } finally {
    shipSaving.value = false
  }
}

async function deleteShip(opt) {
  if (!confirm(`ลบ "${opt.name}" ออก?`)) return
  await api.delete(`/market/seller-groups/${activeShipGroup.value.id}/shipping/${opt.id}`)
  toast.add({ severity: 'info', summary: 'ลบแล้ว', life: 1500 })
  await loadShipOptions()
}

async function setShipDefault(opt) {
  await api.put(`/market/seller-groups/${activeShipGroup.value.id}/shipping/${opt.id}`, { is_default: true })
  await loadShipOptions()
}

// ===== Ban / Suspend / Restore =====
const banDialogOpen = ref(false)
const banTarget = ref(null)
const banMode = ref('suspend') // 'suspend' | 'ban'
const banForm = reactive({ reason: '', suspended_until: '' })
const banErr = ref('')
const banSaving = ref(false)

function fmtDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('th-TH', { dateStyle: 'short', timeStyle: 'short' })
}

function openBanDialog(g) {
  banTarget.value = g
  banMode.value = 'suspend'
  banForm.reason = ''
  banForm.suspended_until = ''
  banErr.value = ''
  banDialogOpen.value = true
}

async function submitBan() {
  if (!banForm.reason.trim()) { banErr.value = 'กรุณาระบุเหตุผล'; return }
  if (banMode.value === 'suspend' && !banForm.suspended_until) { banErr.value = 'กรุณาระบุวันที่ระงับ'; return }
  banSaving.value = true; banErr.value = ''
  try {
    const url = `/market/seller-groups/${banTarget.value.id}/${banMode.value}`
    const payload = { reason: banForm.reason }
    if (banMode.value === 'suspend') payload.suspended_until = banForm.suspended_until
    await api.post(url, payload)
    toast.add({ severity: banMode.value === 'ban' ? 'error' : 'warn', summary: banMode.value === 'ban' ? 'แบนร้านค้าแล้ว' : 'ระงับชั่วคราวแล้ว', life: 2500 })
    banDialogOpen.value = false
    reload()
  } catch (e) {
    banErr.value = e.response?.data?.message || 'เกิดข้อผิดพลาด'
  } finally { banSaving.value = false }
}

async function restoreGroup(g) {
  try {
    await api.post(`/market/seller-groups/${g.id}/restore`)
    toast.add({ severity: 'success', summary: 'คืนสถานะร้านค้าแล้ว', life: 2000 })
    reload()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message || 'เกิดข้อผิดพลาด', life: 2500 })
  }
}

onMounted(() => { loadFacets(); reload() })

onUnmounted(() => {
  if (mapPickerInst) { mapPickerInst.remove(); mapPickerInst = null }
})
</script>
