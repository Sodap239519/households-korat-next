<template>
  <div class="p-4 sm:p-6 space-y-4">
    <Toast />

    <!-- Header -->
    <div class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 p-5 text-white shadow">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl shrink-0">
          <i class="fi fi-rr-bolt"></i>
        </div>
        <div class="flex-1 min-w-0">
          <h1 class="text-base font-bold leading-tight">Flash Sale</h1>
          <p class="text-orange-100 text-xs mt-0.5">จัดการช่วงเวลาโปรโมชั่นราคาพิเศษ</p>
        </div>
        <button
          v-if="isSuperAdmin"
          @click="openEventDialog()"
          class="flex items-center gap-1 px-3 py-2 rounded-xl bg-white/25 hover:bg-white/35 text-white text-xs font-medium transition shrink-0"
        >
          <i class="fi fi-rr-plus"></i> สร้าง Event
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loadingEvents" class="box-card text-center py-14 text-slate-400">
      <i class="fi fi-rr-loading text-2xl animate-spin"></i>
    </div>

    <!-- Empty -->
    <div v-else-if="!events.length" class="box-card text-center py-14">
      <i class="fi fi-rr-calendar text-3xl text-orange-200"></i>
      <p class="mt-3 text-sm font-medium text-slate-500">ยังไม่มี Flash Sale Events</p>
      <p v-if="isSuperAdmin" class="text-xs text-slate-400 mt-1">กดปุ่ม "สร้าง Event" ด้านบนเพื่อเริ่มต้น</p>
    </div>

    <!-- Events list -->
    <div v-else class="space-y-3">
      <div v-for="ev in events" :key="ev.id" class="box-card overflow-hidden p-0">

        <!-- Event info -->
        <div class="p-4">
          <div class="flex items-center gap-3">
            <div
              class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
              :class="{
                'bg-green-100 text-green-600':  ev.is_currently_active,
                'bg-blue-100 text-blue-500':    ev.is_upcoming && !ev.is_currently_active,
                'bg-slate-100 text-slate-400':  !ev.is_active || (!ev.is_currently_active && !ev.is_upcoming),
              }"
            ><i class="fi fi-rr-bolt text-base"></i></div>

            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-1.5">
                <span class="font-semibold text-slate-800 text-sm">{{ ev.title }}</span>
                <span
                  class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                  :class="{
                    'bg-green-100 text-green-700':  ev.is_currently_active,
                    'bg-blue-100 text-blue-600':    ev.is_upcoming && !ev.is_currently_active,
                    'bg-slate-100 text-slate-500':  !ev.is_active || (!ev.is_currently_active && !ev.is_upcoming),
                  }"
                >{{ ev.status_label }}</span>
                <span class="text-[10px] text-slate-400">{{ ev.items_count }} สินค้า</span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                <i class="fi fi-rr-calendar text-[10px] mr-1"></i>
                {{ fmtDate(ev.starts_at) }} — {{ fmtDate(ev.ends_at) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="px-4 pb-4 flex gap-2 border-t border-slate-50 pt-3">
          <button
            @click="toggleItems(ev)"
            :class="[
              'flex-1 py-2 text-xs rounded-xl flex items-center justify-center gap-1.5 font-medium transition',
              selectedEvent?.id === ev.id
                ? 'bg-violet-700 text-white'
                : 'bg-violet-600 text-white hover:bg-violet-700',
            ]"
          >
            <i class="fi fi-rr-list"></i>
            {{ selectedEvent?.id === ev.id ? 'ซ่อนสินค้า' : 'จัดการสินค้า' }}
          </button>
          <template v-if="isSuperAdmin">
            <button
              @click="openEventDialog(ev)"
              class="px-3 py-2 text-xs rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center gap-1 transition"
            >
              <i class="fi fi-rr-edit"></i> แก้ไข
            </button>
            <button
              @click="deleteEvent(ev)"
              :disabled="deletingEventId === ev.id"
              class="px-3 py-2 text-xs rounded-xl border border-red-100 text-red-400 hover:bg-red-50 disabled:opacity-40 transition"
            >
              <i class="fi fi-rr-trash"></i>
            </button>
          </template>
        </div>

        <!-- Inline items panel -->
        <div v-if="selectedEvent?.id === ev.id" class="border-t border-violet-100 bg-slate-50/60">
          <div class="p-4 space-y-3">

            <!-- Header + add button -->
            <div class="flex items-center justify-between">
              <p class="text-xs font-semibold text-violet-700 flex items-center gap-1">
                <i class="fi fi-rr-box-open text-violet-400"></i> สินค้าใน event
              </p>
              <button
                @click="openAddPanel"
                class="flex items-center gap-1 px-3 py-1.5 text-xs rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition"
              >
                <i class="fi fi-rr-plus"></i> เพิ่มสินค้า
              </button>
            </div>

            <!-- Add panel -->
            <div v-if="addPanelOpen" class="rounded-xl border border-orange-200 bg-white p-3 space-y-2">
              <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-medium text-orange-700">เลือกสินค้า + ระบุราคา Flash Sale</p>
                <button @click="closeAddPanel" class="text-slate-400 hover:text-slate-600">
                  <i class="fi fi-rr-cross text-[10px]"></i>
                </button>
              </div>
              <input
                v-model="addSearch"
                type="text"
                placeholder="ค้นหาสินค้า..."
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-400"
              />
              <div v-if="loadingAll" class="text-center py-4 text-slate-400 text-xs">
                <i class="fi fi-rr-loading animate-spin"></i>
              </div>
              <div v-else class="max-h-64 overflow-y-auto space-y-2 pr-0.5">
                <div
                  v-for="p in filteredAll"
                  :key="p.id"
                  @click="toggleEventSelect(p)"
                  :class="[
                    'rounded-xl border cursor-pointer select-none transition-all flex items-center gap-3 bg-white p-3',
                    eventAddSelected.find(s => s.product_id === p.id)
                      ? 'border-orange-400 ring-2 ring-orange-100'
                      : 'border-slate-100 hover:border-orange-300 hover:shadow-sm',
                    inCurrentEvent(p.id) ? 'opacity-40 pointer-events-none' : '',
                  ]"
                >
                  <img
                    :src="primaryImage(p)"
                    class="w-14 h-14 rounded-xl object-cover bg-slate-100 shrink-0"
                    @error="e => e.target.src = '/storage/placeholder.png'"
                  />
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 line-clamp-2 leading-snug">{{ p.name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">ราคาปกติ {{ fmt(p.price) }} บาท</p>
                    <span v-if="inCurrentEvent(p.id)" class="text-[10px] text-violet-500 font-medium">✓ อยู่ใน event แล้ว</span>
                  </div>
                  <div v-if="eventAddSelected.find(s => s.product_id === p.id)" @click.stop class="shrink-0">
                    <label class="text-[10px] text-orange-600 font-medium block mb-1 text-center">ราคา Flash Sale</label>
                    <input
                      v-model.number="eventAddSelected.find(s => s.product_id === p.id).sale_price"
                      type="number" min="0" step="0.01" placeholder="0.00"
                      class="w-24 border-2 border-orange-400 rounded-xl px-2 py-1.5 text-sm text-center font-bold text-orange-600 focus:outline-none focus:border-orange-500"
                    />
                  </div>
                  <div
                    v-if="eventAddSelected.find(s => s.product_id === p.id)"
                    class="w-5 h-5 rounded-full bg-orange-500 flex items-center justify-center shrink-0 shadow-sm"
                  >
                    <i class="fi fi-rr-check text-[9px] text-white"></i>
                  </div>
                </div>
                <p v-if="!filteredAll.length" class="text-center text-sm text-slate-400 py-4">ไม่พบสินค้า</p>
              </div>
              <div v-if="eventAddSelected.length" class="flex items-center justify-between pt-2 border-t border-orange-100">
                <p class="text-xs text-orange-700">เลือก {{ eventAddSelected.length }} รายการ</p>
                <button
                  @click="submitAddToEvent"
                  :disabled="eventAddSaving"
                  class="flex items-center gap-1 px-3 py-1.5 text-xs rounded-lg bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-50"
                >
                  <i v-if="eventAddSaving" class="fi fi-rr-loading animate-spin"></i>
                  <i v-else class="fi fi-rr-bolt"></i>
                  เพิ่มเข้า Event
                </button>
              </div>
            </div>

            <!-- Items list -->
            <div v-if="loadingItems" class="text-center py-8 text-slate-400 text-xs">
              <i class="fi fi-rr-loading animate-spin text-lg"></i>
            </div>
            <div v-else-if="!eventItems.length" class="text-center py-8 text-slate-400">
              <i class="fi fi-rr-shopping-cart text-2xl text-slate-200"></i>
              <p class="mt-2 text-xs">ยังไม่มีสินค้าใน event นี้</p>
            </div>
            <div v-else class="max-h-64 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-100 bg-white">
              <div
                v-for="item in eventItems"
                :key="item.id"
                class="flex items-center gap-2 px-3 py-2"
              >
                <i class="fi fi-rr-bolt text-orange-400 text-xs shrink-0"></i>
                <p class="flex-1 min-w-0 text-xs text-slate-700 line-clamp-1">{{ item.product?.name }}</p>
                <div class="flex items-center gap-1 shrink-0">
                  <span class="text-xs font-bold text-orange-600">{{ fmt(item.sale_price) }}</span>
                  <span class="text-[10px] text-slate-300 line-through">{{ fmt(item.product?.price) }}</span>
                  <span class="text-[9px] bg-red-100 text-red-500 font-bold px-1 py-0.5 rounded-full">
                    -{{ discountPct(item.product?.price, item.sale_price) }}%
                  </span>
                </div>
                <button
                  @click="removeEventItem(item)"
                  :disabled="removingItemId === item.id"
                  class="text-red-300 hover:text-red-500 p-1 disabled:opacity-40 transition shrink-0"
                >
                  <i class="fi fi-rr-trash text-[10px]"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Dialog: Create / Edit Event -->
    <Dialog
      v-model:visible="eventDialogOpen"
      modal
      :header="eventForm.id ? 'แก้ไข Flash Sale Event' : 'สร้าง Flash Sale Event'"
      :style="{ width: '34rem', maxWidth: '96vw' }"
    >
      <div class="space-y-4 py-1">
        <div>
          <label class="form-label">ชื่อ Event <span class="text-red-500">*</span></label>
          <InputText v-model="eventForm.title" class="w-full" maxlength="100" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="form-label">เริ่มต้น <span class="text-red-500">*</span></label>
            <input
              v-model="eventForm.starts_at"
              type="datetime-local"
              class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-orange-400"
            />
          </div>
          <div>
            <label class="form-label">สิ้นสุด <span class="text-red-500">*</span></label>
            <input
              v-model="eventForm.ends_at"
              type="datetime-local"
              class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-orange-400"
            />
          </div>
        </div>
        <div>
          <label class="form-label">คำอธิบาย</label>
          <Textarea v-model="eventForm.description" rows="2" class="w-full" />
        </div>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="eventForm.is_active" inputId="ev_active" />
          <label for="ev_active" class="text-sm text-slate-600">เปิดใช้งาน</label>
        </div>
        <Message v-if="eventError" severity="error" :closable="false">{{ eventError }}</Message>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="eventDialogOpen = false" />
        <Button
          :label="eventForm.id ? 'บันทึกการแก้ไข' : 'สร้าง Event'"
          :loading="eventSaving"
          @click="saveEvent"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api/index.js'
import { useToast } from 'primevue/usetoast'
import { useAuth } from '../../composables/useAuth.js'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'
import Button from 'primevue/button'
import Message from 'primevue/message'

const toast = useToast()
const { isSuperAdmin } = useAuth()

// ===== Events =====
const events          = ref([])
const loadingEvents   = ref(false)
const deletingEventId = ref(null)

// ===== Inline items panel =====
const selectedEvent  = ref(null)
const eventItems     = ref([])
const loadingItems   = ref(false)
const removingItemId = ref(null)

// ===== Add panel =====
const addPanelOpen     = ref(false)
const eventAddSelected = ref([])
const eventAddSaving   = ref(false)
const allProducts      = ref([])
const loadingAll       = ref(false)
const addSearch        = ref('')

// ===== Event Dialog (create/edit) =====
const eventDialogOpen = ref(false)
const eventSaving     = ref(false)
const eventError      = ref('')
const blankEvent      = () => ({ id: null, title: '', description: '', starts_at: '', ends_at: '', is_active: true })
const eventForm       = ref(blankEvent())

// ===== Computed =====
const filteredAll = computed(() => {
  const q = addSearch.value.trim().toLowerCase()
  if (!q) return allProducts.value
  return allProducts.value.filter(p =>
    p.name.toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q)
  )
})

// ===== Helpers =====
function discountPct(price, salePrice) {
  if (!salePrice || !price) return 0
  return Math.round((1 - Number(salePrice) / Number(price)) * 100)
}

function fmtDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('th-TH', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function primaryImage(p) {
  const imgs = p?.images || []
  const primary = imgs.find(i => i.is_primary) || imgs[0]
  return primary ? `/storage/${primary.path}` : '/storage/placeholder.png'
}

function fmt(v) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function toDatetimeLocal(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  const pad = n => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function inCurrentEvent(productId) {
  return eventItems.value.some(i => i.product_id === productId)
}

// ===== Events API =====
async function loadEvents() {
  loadingEvents.value = true
  try {
    const { data } = await api.get('/market/flash-sale-events')
    events.value = data
  } catch {
    toast.add({ severity: 'error', summary: 'โหลด events ล้มเหลว', life: 3000 })
  } finally {
    loadingEvents.value = false
  }
}

async function toggleItems(ev) {
  if (selectedEvent.value?.id === ev.id) {
    selectedEvent.value = null
    addPanelOpen.value = false
    eventAddSelected.value = []
    return
  }
  selectedEvent.value = ev
  addPanelOpen.value = false
  eventAddSelected.value = []
  addSearch.value = ''
  await loadEventItems(ev.id)
  loadAll()
}

async function loadEventItems(eventId) {
  loadingItems.value = true
  try {
    const { data } = await api.get(`/market/flash-sale-events/${eventId}/items`)
    eventItems.value = data
  } catch {
    toast.add({ severity: 'error', summary: 'โหลดสินค้าล้มเหลว', life: 3000 })
  } finally {
    loadingItems.value = false
  }
}

async function removeEventItem(item) {
  removingItemId.value = item.id
  try {
    await api.delete(`/market/flash-sale-events/${selectedEvent.value.id}/items/${item.id}`)
    toast.add({ severity: 'success', summary: 'ลบสินค้าออกจาก event แล้ว', life: 2500 })
    await loadEventItems(selectedEvent.value.id)
    await loadEvents()
  } catch {
    toast.add({ severity: 'error', summary: 'เกิดข้อผิดพลาด', life: 3000 })
  } finally {
    removingItemId.value = null
  }
}

async function loadAll() {
  if (allProducts.value.length) return
  loadingAll.value = true
  try {
    const { data } = await api.get('/market/products', { params: { status: 'published', per_page: 200 } })
    allProducts.value = data.data || data
  } catch {
    toast.add({ severity: 'error', summary: 'โหลดสินค้าล้มเหลว', life: 3000 })
  } finally {
    loadingAll.value = false
  }
}

function openAddPanel() {
  addPanelOpen.value = true
  eventAddSelected.value = []
  addSearch.value = ''
}

function closeAddPanel() {
  addPanelOpen.value = false
  eventAddSelected.value = []
}

function toggleEventSelect(p) {
  if (inCurrentEvent(p.id)) return
  const idx = eventAddSelected.value.findIndex(s => s.product_id === p.id)
  if (idx === -1) {
    eventAddSelected.value.push({ product_id: p.id, sale_price: null })
  } else {
    eventAddSelected.value.splice(idx, 1)
  }
}

async function submitAddToEvent() {
  const items = eventAddSelected.value.filter(s => s.sale_price > 0)
  if (!items.length) {
    toast.add({ severity: 'warn', summary: 'ระบุราคา flash sale ก่อน', life: 2500 })
    return
  }
  eventAddSaving.value = true
  try {
    const { data } = await api.post(`/market/flash-sale-events/${selectedEvent.value.id}/items`, { items })
    toast.add({ severity: 'success', summary: `เพิ่ม ${data.added} สินค้าแล้ว`, life: 2500 })
    closeAddPanel()
    await loadEventItems(selectedEvent.value.id)
    await loadEvents()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message || 'เกิดข้อผิดพลาด', life: 3000 })
  } finally {
    eventAddSaving.value = false
  }
}

// ===== Event Dialog =====
function openEventDialog(ev = null) {
  eventError.value = ''
  if (ev) {
    eventForm.value = {
      id: ev.id,
      title: ev.title,
      description: ev.description || '',
      starts_at: toDatetimeLocal(ev.starts_at),
      ends_at: toDatetimeLocal(ev.ends_at),
      is_active: ev.is_active,
    }
  } else {
    eventForm.value = blankEvent()
  }
  eventDialogOpen.value = true
}

async function saveEvent() {
  eventError.value = ''
  eventSaving.value = true
  try {
    const payload = {
      ...eventForm.value,
      starts_at: eventForm.value.starts_at ? new Date(eventForm.value.starts_at).toISOString() : '',
      ends_at:   eventForm.value.ends_at   ? new Date(eventForm.value.ends_at).toISOString()   : '',
    }
    if (payload.id) {
      await api.put(`/market/flash-sale-events/${payload.id}`, payload)
      toast.add({ severity: 'success', summary: 'อัปเดต event แล้ว', life: 2000 })
    } else {
      await api.post('/market/flash-sale-events', payload)
      toast.add({ severity: 'success', summary: 'สร้าง event แล้ว', life: 2000 })
    }
    eventDialogOpen.value = false
    await loadEvents()
  } catch (e) {
    const errs = e.response?.data?.errors
    eventError.value = errs
      ? Object.values(errs).flat().join(' | ')
      : (e.response?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    eventSaving.value = false
  }
}

async function deleteEvent(ev) {
  if (!confirm(`ลบ event "${ev.title}" ? สินค้าทั้งหมดในนี้จะถูกลบออกด้วย`)) return
  deletingEventId.value = ev.id
  try {
    await api.delete(`/market/flash-sale-events/${ev.id}`)
    toast.add({ severity: 'success', summary: 'ลบ event แล้ว', life: 2000 })
    if (selectedEvent.value?.id === ev.id) selectedEvent.value = null
    await loadEvents()
  } catch {
    toast.add({ severity: 'error', summary: 'ลบไม่สำเร็จ', life: 3000 })
  } finally {
    deletingEventId.value = null
  }
}

onMounted(loadEvents)
</script>
