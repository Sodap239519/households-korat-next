<template>
  <!-- Mobile: full-screen list → tap → full-screen chat. Desktop: side-by-side. -->
  <div class="flex overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    style="height: calc(100vh - 5.5rem)">

    <!-- ===== ซ้าย: รายการสนทนา ===== -->
    <div class="shrink-0 border-r border-slate-100 flex flex-col transition-all duration-300"
      :class="activeConv ? 'hidden lg:flex lg:w-72' : 'flex w-full lg:w-72'">
      <!-- Header -->
      <div class="px-4 py-3.5 border-b border-slate-100 bg-gradient-to-r from-violet-50 to-fuchsia-50 shrink-0">
        <h2 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
          <i class="fi fi-rr-comment-dots text-violet-500"></i> ข้อความลูกค้า
          <span v-if="unread" class="ml-auto min-w-[20px] h-5 px-1.5 rounded-full bg-orange-500 text-white text-xs font-bold flex items-center justify-center">{{ unread }}</span>
        </h2>
      </div>
      <!-- List -->
      <div class="flex-1 overflow-y-auto divide-y divide-slate-50">
        <div v-if="loadingList" class="flex flex-col gap-3 p-4">
          <div v-for="n in 5" :key="n" class="skeleton h-16 rounded-xl"></div>
        </div>
        <div v-else-if="!conversations.length" class="flex flex-col items-center justify-center h-full text-slate-400 gap-2">
          <i class="fi fi-rr-comment-alt text-4xl"></i>
          <p class="text-sm">ยังไม่มีบทสนทนา</p>
        </div>
        <button v-else v-for="conv in conversations" :key="conv.id"
          @click="openConv(conv)"
          class="w-full flex items-center gap-3 p-3.5 hover:bg-violet-50/50 active:bg-violet-100 transition text-left"
          :class="activeConv?.id === conv.id ? 'bg-violet-50' : ''">
          <div class="w-11 h-11 rounded-full overflow-hidden bg-gradient-to-br from-violet-400 to-fuchsia-500 text-white flex items-center justify-center shrink-0 text-sm font-bold shadow-sm">
            <img v-if="conv.customer?.avatar_path" :src="`/storage/${conv.customer.avatar_path}`" class="w-full h-full object-cover" />
            <template v-else>{{ (conv.customer?.name || '?')[0].toUpperCase() }}</template>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-1">
              <p class="font-semibold text-slate-800 text-sm truncate">{{ conv.customer?.name }}</p>
              <span class="text-[10px] text-slate-400 shrink-0">{{ fmtTime(conv.last_message_at) }}</span>
            </div>
            <p class="text-xs text-slate-500 truncate mt-0.5">{{ conv.last_message_preview || '—' }}</p>
          </div>
          <span v-if="!conv.is_read_by_seller" class="w-2.5 h-2.5 rounded-full bg-orange-500 shrink-0"></span>
        </button>
      </div>
    </div>

    <!-- ===== ขวา: ห้องแชท ===== -->
    <div class="flex-1 flex flex-col overflow-hidden"
      :class="activeConv ? 'flex' : 'hidden lg:flex'">
      <template v-if="activeConv">
        <!-- Chat header -->
        <div class="px-3 py-3 border-b border-slate-100 bg-white shrink-0 flex items-center gap-3">
          <!-- Back button (mobile only) -->
          <button @click="activeConv = null"
            class="lg:hidden w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition shrink-0">
            <i class="fi fi-rr-arrow-left text-sm"></i>
          </button>
          <div class="w-9 h-9 rounded-full overflow-hidden bg-gradient-to-br from-violet-400 to-fuchsia-500 text-white flex items-center justify-center font-bold text-sm shrink-0">
            <img v-if="activeConv.customer?.avatar_path" :src="`/storage/${activeConv.customer.avatar_path}`" class="w-full h-full object-cover" />
            <template v-else>{{ (activeConv.customer?.name || '?')[0].toUpperCase() }}</template>
          </div>
          <div>
            <p class="font-semibold text-slate-800 text-sm">{{ activeConv.customer?.name }}</p>
            <p class="text-xs text-slate-400">ลูกค้า · ตอบภายในแชท</p>
          </div>
        </div>

        <!-- Messages area -->
        <div ref="msgBox" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50/80">
          <div v-if="loadingMsg" class="flex flex-col items-center justify-center h-32 gap-2 text-violet-400">
            <i class="fi fi-rr-spinner animate-spin text-2xl"></i>
            <p class="text-xs">กำลังโหลด...</p>
          </div>
          <template v-else>
            <div v-for="msg in messages" :key="msg.id" :data-msg-id="msg.id"
              class="flex gap-2 group"
              :class="msg.sender_type === 'staff' ? 'justify-end' : 'justify-start'">
              <div v-if="msg.sender_type !== 'staff'"
                class="w-7 h-7 rounded-full overflow-hidden bg-gradient-to-br from-violet-300 to-fuchsia-400 text-white flex items-center justify-center text-xs font-bold shrink-0 mt-1">
                <img v-if="msg.sender?.avatar_path" :src="`/storage/${msg.sender.avatar_path}`" class="w-full h-full object-cover" />
                <template v-else>{{ (msg.sender?.name || '?')[0].toUpperCase() }}</template>
              </div>
              <div class="max-w-[75%] lg:max-w-[65%] space-y-1.5">
                <!-- Inline product card -->
                <RouterLink v-if="msg.product" :to="`/shop/products/${msg.product.slug}`"
                  class="block rounded-2xl overflow-hidden border shadow-sm transition hover:shadow-md"
                  :class="msg.sender_type === 'staff' ? 'bg-violet-50 border-violet-200' : 'bg-white border-slate-100'">
                  <div class="flex gap-2.5 p-2.5">
                    <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 bg-slate-100">
                      <img v-if="msg.product.primary_image_url" :src="msg.product.primary_image_url"
                        class="w-full h-full object-cover" />
                      <i v-else class="fi fi-rr-picture text-slate-300 text-2xl flex items-center justify-center h-full"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-semibold text-slate-700 line-clamp-2 leading-snug">{{ msg.product.name }}</p>
                      <div class="flex items-center gap-0.5 mt-1">
                        <template v-for="s in 5" :key="s">
                          <i class="fi text-[9px]" :class="s <= Math.round(msg.product.rating_avg || 0) ? 'fi-sr-star text-amber-400' : 'fi-rr-star text-slate-200'"></i>
                        </template>
                        <span v-if="msg.product.rating_count" class="text-[10px] text-slate-400 ml-1">({{ msg.product.rating_count }})</span>
                      </div>
                      <p class="text-sm font-bold text-fuchsia-700 mt-1">
                        ฿{{ fmtPrice(msg.product.sale_price || msg.product.price) }}
                        <span v-if="msg.product.sale_price && msg.product.sale_price < msg.product.price"
                          class="text-[11px] text-slate-400 line-through font-normal ml-1">฿{{ fmtPrice(msg.product.price) }}</span>
                      </p>
                    </div>
                  </div>
                  <div class="px-2.5 pb-2.5">
                    <div class="text-center text-xs font-semibold py-1.5 rounded-lg bg-violet-600 text-white">ดูสินค้า</div>
                  </div>
                </RouterLink>

                <!-- Image -->
                <div v-if="msg.image_url"
                  class="overflow-hidden rounded-2xl cursor-pointer max-w-[220px]"
                  :class="msg.sender_type === 'staff' ? 'rounded-br-sm ml-auto' : 'rounded-bl-sm'"
                  @click="previewImage = msg.image_url"
                  @contextmenu="handleContextMenu($event, msg)"
                  @touchstart.passive="startLongPress($event, msg)"
                  @touchend="cancelLongPress"
                  @touchmove.passive="cancelLongPress">
                  <img :src="msg.image_url" class="w-full object-cover block" style="max-height:260px" />
                </div>

                <!-- Text bubble -->
                <div v-if="msg.body"
                  class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm"
                  :class="msg.sender_type === 'staff'
                    ? 'bg-gradient-to-br from-violet-600 to-fuchsia-600 text-white rounded-br-sm'
                    : 'bg-white text-slate-700 border border-slate-100 rounded-bl-sm'"
                  @contextmenu="handleContextMenu($event, msg)"
                  @touchstart.passive="startLongPress($event, msg)"
                  @touchend="cancelLongPress"
                  @touchmove.passive="cancelLongPress">
                  <!-- Quoted reply -->
                  <div v-if="msg.reply_to"
                    class="mb-1.5 pl-2 border-l-2 rounded text-xs opacity-90"
                    :class="msg.sender_type === 'staff' ? 'border-white/60' : 'border-violet-400'">
                    <span class="block font-semibold" :class="msg.sender_type === 'staff' ? 'text-white/90' : 'text-violet-600'">
                      {{ msg.reply_to.sender_type === 'staff' ? 'ร้านค้า' : 'ลูกค้า' }}
                    </span>
                    <span class="block truncate" :class="msg.sender_type === 'staff' ? 'text-white/75' : 'text-slate-500'">
                      {{ msg.reply_to.body || '[ภาพ]' }}
                    </span>
                  </div>
                  <span class="whitespace-pre-line">{{ msg.body }}</span>
                </div>

                <!-- Quoted reply (เฉพาะข้อความที่มีแต่รูป ไม่มี body) -->
                <div v-else-if="msg.reply_to && msg.image_url"
                  class="px-3 py-2 rounded-xl bg-white border border-slate-100 text-xs shadow-sm"
                  :class="msg.sender_type === 'staff' ? 'ml-auto' : ''">
                  <span class="block font-semibold text-violet-600">
                    ↩ ตอบกลับ {{ msg.reply_to.sender_type === 'staff' ? 'ร้านค้า' : 'ลูกค้า' }}
                  </span>
                  <span class="block truncate text-slate-500">{{ msg.reply_to.body || '[ภาพ]' }}</span>
                </div>

                <p class="text-[10px] text-slate-400 mt-0.5 px-1"
                  :class="msg.sender_type === 'staff' ? 'text-right' : ''">
                  {{ fmtTime(msg.created_at) }}
                </p>
              </div>

              <!-- ปุ่มเมนู (⋮) — เปิดเมนูตอบกลับ/ลบ (ไม่ต้องคลิกขวา) -->
              <button
                class="self-center w-7 h-7 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center shrink-0 transition opacity-100 lg:opacity-0 lg:group-hover:opacity-100"
                :class="msg.sender_type === 'staff' ? 'order-first' : ''"
                @click.stop="openMenuButton($event, msg)"
                title="ตัวเลือก">
                <i class="fi fi-rr-menu-dots-vertical text-xs"></i>
              </button>
            </div>
          </template>
        </div>

        <!-- Reply context bar -->
        <Transition name="slide-down">
          <div v-if="replyTo" class="shrink-0 px-3 pt-2.5 bg-white border-t border-slate-100">
            <div class="flex items-center gap-2.5 p-2.5 bg-violet-50 rounded-xl border-l-4 border-violet-400 relative">
              <i class="fi fi-rr-reply-all text-violet-500 text-sm shrink-0"></i>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-violet-700">
                  ตอบกลับ {{ replyTo.sender_type === 'staff' ? 'ร้านค้า' : (activeConv.customer?.name || 'ลูกค้า') }}
                </p>
                <p class="text-xs text-slate-500 truncate">{{ replyTo.body || '[ภาพ]' }}</p>
              </div>
              <button @click="replyTo = null"
                class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-200 hover:bg-slate-300 text-slate-500 text-[10px] shrink-0">
                <i class="fi fi-rr-cross-small"></i>
              </button>
            </div>
          </div>
        </Transition>

        <!-- Input bar -->
        <div class="p-3 border-t border-slate-100 bg-white shrink-0">
          <!-- ข้อผิดพลาดในการส่ง -->
          <div v-if="sendError" class="mb-2 flex items-center gap-2 text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2">
            <i class="fi fi-rr-exclamation shrink-0"></i>
            <span class="flex-1">{{ sendError }}</span>
            <button @click="sendError = ''" class="text-rose-400 hover:text-rose-600"><i class="fi fi-rr-cross-small"></i></button>
          </div>
          <!-- Image preview before send -->
          <div v-if="pendingImage" class="mb-2 relative inline-block">
            <img :src="pendingImageUrl" class="h-20 w-auto rounded-xl object-cover border border-violet-200" />
            <button @click="clearPendingImage"
              class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-slate-700 text-white text-[10px] flex items-center justify-center hover:bg-rose-600 transition">
              <i class="fi fi-rr-cross-small"></i>
            </button>
          </div>
          <div class="flex items-end gap-2">
            <!-- Image upload -->
            <button @click="imgInput?.click()" title="แนบรูปภาพ"
              class="w-11 h-11 rounded-2xl border border-slate-200 text-slate-500 flex items-center justify-center shrink-0 hover:border-violet-400 hover:text-violet-600 transition">
              <i class="fi fi-rr-picture text-base"></i>
            </button>
            <input ref="imgInput" type="file" accept="image/*" class="hidden" @change="onImageSelect" />
            <textarea v-model="newMsg" @keydown.enter.exact.prevent="send" rows="1"
              placeholder="พิมพ์ข้อความตอบลูกค้า..."
              class="flex-1 px-3.5 py-2.5 rounded-2xl border border-slate-200 text-sm resize-none focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-400/20 max-h-28 transition"
              style="min-height: 42px;"></textarea>
            <button @click="send" :disabled="(!newMsg.trim() && !pendingImage) || sending"
              class="w-11 h-11 rounded-2xl bg-gradient-to-br from-violet-600 to-fuchsia-600 text-white flex items-center justify-center hover:opacity-90 transition disabled:opacity-40 shrink-0 shadow-md shadow-violet-500/30">
              <i class="fi fi-rr-paper-plane"></i>
            </button>
          </div>
        </div>
      </template>

      <!-- Empty state -->
      <div v-else class="flex-1 flex items-center justify-center text-slate-300 flex-col gap-3">
        <div class="w-20 h-20 rounded-3xl bg-slate-100 flex items-center justify-center">
          <i class="fi fi-rr-comment-alt text-4xl"></i>
        </div>
        <div class="text-center">
          <p class="text-sm font-medium text-slate-400">เลือกบทสนทนา</p>
          <p class="text-xs text-slate-300 mt-0.5">จากรายการทางซ้าย</p>
        </div>
      </div>
    </div>

    <!-- Image preview lightbox -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="previewImage"
          class="fixed inset-0 z-[300] bg-black/85 flex items-center justify-center p-4"
          @click="previewImage = null">
          <img :src="previewImage" class="max-w-full max-h-full rounded-2xl shadow-2xl object-contain" @click.stop />
          <button @click="previewImage = null"
            class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white/20 text-white hover:bg-white/40 flex items-center justify-center transition">
            <i class="fi fi-rr-cross text-sm"></i>
          </button>
        </div>
      </Transition>
    </Teleport>

    <!-- Context menu -->
    <Teleport to="body">
      <div v-if="ctxMenu.visible"
        class="fixed z-[200] bg-white rounded-xl shadow-xl border border-slate-100 py-1 min-w-[150px] overflow-hidden"
        :style="{ top: ctxMenu.y + 'px', left: ctxMenu.x + 'px' }"
        @click.stop>
        <button @click="startReply"
          class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-violet-50 transition">
          <i class="fi fi-rr-reply-all text-violet-500"></i> ตอบกลับ
        </button>
        <button v-if="ctxMenu.msg?.sender_type === 'staff'" @click="deleteMsg"
          class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition">
          <i class="fi fi-rr-trash"></i> ลบ / ยกเลิกการส่ง
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue'
import api from '../../api/index.js'

const conversations = ref([])
const activeConv    = ref(null)
const messages      = ref([])
const newMsg        = ref('')
const unread        = ref(0)
const loadingList   = ref(true)
const loadingMsg    = ref(false)
const sending       = ref(false)
const msgBox        = ref(null)
const replyTo       = ref(null)
const imgInput      = ref(null)
const pendingImage    = ref(null)
const pendingImageUrl = ref(null)
const previewImage    = ref(null)
const ctxMenu       = ref({ visible: false, msg: null, x: 0, y: 0 })
const sendError     = ref('')

let pollTimer      = null
let longPressTimer = null

function fmtPrice(v) {
  return Number(v || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function fmtTime(d) {
  if (!d) return ''
  const dt = new Date(d)
  const now = new Date()
  if (dt.toDateString() === now.toDateString())
    return dt.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
  return dt.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' })
}

async function loadConversations() {
  loadingList.value = true
  try {
    const [convRes, unreadRes] = await Promise.all([
      api.get('/market/chat/conversations'),
      api.get('/market/chat/unread'),
    ])
    conversations.value = convRes.data
    unread.value = unreadRes.data.count
  } finally { loadingList.value = false }
}

async function openConv(conv) {
  activeConv.value = conv
  replyTo.value = null
  clearPendingImage()
  loadingMsg.value = true
  try {
    const { data } = await api.get(`/market/chat/conversations/${conv.id}/messages`)
    messages.value = data
    conv.is_read_by_seller = true
    unread.value = Math.max(0, unread.value - 1)
  } finally { loadingMsg.value = false }
  scrollBottom()
  startPolling(conv.id)
}

function onImageSelect(e) {
  const file = e.target.files?.[0]
  if (!file) return
  pendingImage.value = file
  pendingImageUrl.value = URL.createObjectURL(file)
  if (imgInput.value) imgInput.value.value = ''
}

function clearPendingImage() {
  if (pendingImageUrl.value) URL.revokeObjectURL(pendingImageUrl.value)
  pendingImage.value = null
  pendingImageUrl.value = null
}

async function send() {
  if ((!newMsg.value.trim() && !pendingImage.value) || sending.value) return
  const body       = newMsg.value.trim()
  const imgFile    = pendingImage.value
  const replyTo0   = replyTo.value
  const replyId    = replyTo.value?.id || null

  newMsg.value = ''
  replyTo.value = null
  sendError.value = ''
  // ยังไม่เคลียร์รูปจนกว่าจะส่งสำเร็จ (กันหายถ้า error)
  sending.value = true

  try {
    let data
    if (imgFile) {
      const fd = new FormData()
      fd.append('image', imgFile)
      if (body) fd.append('body', body)
      if (replyId) fd.append('reply_to_id', replyId)
      ;({ data } = await api.post(`/market/chat/conversations/${activeConv.value.id}/messages`, fd))
    } else {
      ;({ data } = await api.post(`/market/chat/conversations/${activeConv.value.id}/messages`, {
        body,
        reply_to_id: replyId,
      }))
    }
    clearPendingImage()
    messages.value.push(data)
    scrollBottom()
  } catch (e) {
    // กู้ข้อความที่พิมพ์คืน + แจ้ง error ให้เห็น (ไม่ให้หายเงียบ)
    newMsg.value = body
    replyTo.value = replyTo0
    const status = e.response?.status
    sendError.value = status === 419 || status === 401
      ? 'เซสชันหมดอายุ — กรุณารีเฟรชหน้าแล้วเข้าสู่ระบบใหม่'
      : (e.response?.data?.message || 'ส่งข้อความไม่สำเร็จ ลองใหม่อีกครั้ง')
  } finally { sending.value = false }
}

async function pollMessages(convId) {
  if (!activeConv.value || activeConv.value.id !== convId) return
  try {
    const { data } = await api.get(`/market/chat/conversations/${convId}/messages`)
    const lastLocal  = messages.value.at(-1)?.id
    const lastRemote = data.at(-1)?.id
    if (lastRemote !== lastLocal || data.length !== messages.value.length) {
      messages.value = data
      scrollBottom()
    }
  } catch { /* ignore */ }
}

function startPolling(convId) {
  clearInterval(pollTimer)
  pollTimer = setInterval(() => pollMessages(convId), 4000)
}

function scrollBottom() {
  nextTick(() => { if (msgBox.value) msgBox.value.scrollTop = msgBox.value.scrollHeight })
}

// ===== Context menu (ตอบกลับ / ลบ) =====
function handleContextMenu(event, msg) {
  event.preventDefault()
  const x = Math.min(event.clientX, window.innerWidth - 170)
  const y = Math.min(event.clientY, window.innerHeight - 110)
  ctxMenu.value = { visible: true, msg, x, y }
}

// เปิดเมนูจากปุ่ม ⋮ (วิธีหลัก — ไม่ต้องพึ่งคลิกขวาที่ browser อาจดักไว้)
function openMenuButton(event, msg) {
  const rect = event.currentTarget.getBoundingClientRect()
  const x = Math.min(rect.left, window.innerWidth - 170)
  const y = Math.min(rect.bottom + 4, window.innerHeight - 110)
  ctxMenu.value = { visible: true, msg, x, y }
}

function startLongPress(event, msg) {
  cancelLongPress()
  const touch = event.touches[0]
  longPressTimer = setTimeout(() => {
    const x = Math.min(touch.clientX, window.innerWidth - 170)
    const y = Math.min(touch.clientY - 60, window.innerHeight - 110)
    ctxMenu.value = { visible: true, msg, x, y }
    if (navigator.vibrate) navigator.vibrate(50)
  }, 600)
}

function cancelLongPress() {
  clearTimeout(longPressTimer)
}

function closeMenu() {
  ctxMenu.value.visible = false
}

function startReply() {
  const msg = ctxMenu.value.msg
  closeMenu()
  if (!msg) return
  replyTo.value = { id: msg.id, body: msg.body, sender_type: msg.sender_type }
}

async function deleteMsg() {
  const msg    = ctxMenu.value.msg
  const convId = activeConv.value?.id
  closeMenu()
  if (!msg || !convId) return
  try {
    await api.delete(`/market/chat/conversations/${convId}/messages/${msg.id}`)
    messages.value = messages.value.filter(m => m.id !== msg.id)
    if (replyTo.value?.id === msg.id) replyTo.value = null
  } catch { /* ignore */ }
}

onMounted(() => {
  document.addEventListener('click', closeMenu)
  loadConversations()
})

onUnmounted(() => {
  clearInterval(pollTimer)
  cancelLongPress()
  document.removeEventListener('click', closeMenu)
})
</script>
