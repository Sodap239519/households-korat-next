<template>
  <div class="flex flex-col h-[calc(100dvh-7.5rem)] lg:h-[calc(100dvh-3.5rem)] overflow-hidden">

    <!-- รายการสนทนา -->
    <template v-if="!activeConv">
      <div class="flex flex-col flex-1 overflow-hidden sm:box-card sm:rounded-2xl max-w-2xl w-full mx-auto">
        <div class="flex items-center gap-3 p-4 border-b border-slate-100 bg-white">
          <i class="fi fi-rr-comment-dots text-violet-500 text-lg"></i>
          <h1 class="font-bold text-slate-800 text-lg">ข้อความของฉัน</h1>
        </div>

        <div v-if="loadingList" class="flex-1 flex items-center justify-center">
          <i class="fi fi-rr-spinner animate-spin text-violet-400 text-2xl"></i>
        </div>

        <div v-else-if="!conversations.length" class="flex-1 flex flex-col items-center justify-center text-slate-400 gap-3 p-8">
          <i class="fi fi-rr-comment-alt text-5xl"></i>
          <p class="font-medium">ยังไม่มีบทสนทนา</p>
          <RouterLink to="/shop" class="text-sm text-violet-600 hover:underline">เริ่มช้อปปิ้งและพูดคุยกับผู้ขาย</RouterLink>
        </div>

        <div v-else class="flex-1 overflow-y-auto divide-y divide-slate-50">
          <button v-for="conv in conversations" :key="conv.id" @click="openConv(conv)"
            class="w-full flex items-center gap-3 p-4 hover:bg-violet-50/50 transition text-left">
            <!-- Avatar: ถ้ามีร้านค้าย่อย → avatar ผู้ขาย, fallback → โลโก้กลุ่ม -->
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center text-lg shrink-0 overflow-hidden">
              <img v-if="conv.seller_user?.avatar_path"
                :src="`/storage/${conv.seller_user.avatar_path}`"
                class="w-full h-full object-cover" />
              <img v-else-if="conv.seller_group?.logo_path"
                :src="`/storage/${conv.seller_group.logo_path}`"
                class="w-full h-full object-cover" />
              <i v-else class="fi fi-rr-shop"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <!-- ชื่อ: ร้านค้าย่อย (ถ้ามี) หรือโซน -->
                <p class="font-semibold text-slate-800 truncate">
                  {{ conv.seller_user?.shop_name || conv.seller_user?.name || conv.seller_group?.name }}
                </p>
                <span class="text-xs text-slate-400 shrink-0">{{ fmtTime(conv.last_message_at) }}</span>
              </div>
              <!-- sub-label: แสดงโซนถ้าเป็นร้านค้าย่อย -->
              <p v-if="conv.seller_user" class="text-[11px] text-slate-400 truncate">{{ conv.seller_group?.name }}</p>
              <p class="text-sm text-slate-500 truncate mt-0.5">{{ conv.last_message_preview || 'เริ่มบทสนทนา...' }}</p>
            </div>
            <span v-if="!conv.is_read_by_customer" class="w-2.5 h-2.5 rounded-full bg-orange-500 shrink-0"></span>
          </button>
        </div>
      </div>
    </template>

    <!-- ห้องแชท -->
    <template v-else>
      <div class="flex flex-col flex-1 overflow-hidden sm:box-card sm:rounded-2xl max-w-2xl w-full mx-auto">
        <!-- Header -->
        <div class="flex items-center gap-3 p-3 border-b border-slate-100 bg-white shrink-0">
          <button @click="activeConv = null; loadConversations()" class="p-2 rounded-full hover:bg-slate-100 text-slate-500 -ml-1">
            <i class="fi fi-rr-arrow-small-left text-lg"></i>
          </button>
          <!-- Avatar header: ลิงก์ไปหน้าร้านค้า (ร้านย่อย หรือโซน) -->
          <RouterLink
            :to="activeConv.seller_user
              ? `/shop/sellers/${activeConv.seller_group?.slug}?member=${activeConv.seller_user.id}`
              : `/shop/sellers/${activeConv.seller_group?.slug}`"
            class="w-9 h-9 rounded-xl overflow-hidden bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center shrink-0">
            <img v-if="activeConv.seller_user?.avatar_path"
              :src="`/storage/${activeConv.seller_user.avatar_path}`"
              class="w-full h-full object-cover" />
            <img v-else-if="activeConv.seller_group?.logo_path"
              :src="`/storage/${activeConv.seller_group.logo_path}`"
              class="w-full h-full object-cover" />
            <i v-else class="fi fi-rr-shop text-sm"></i>
          </RouterLink>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-800 truncate text-sm">
              {{ activeConv.seller_user?.shop_name || activeConv.seller_user?.name || activeConv.seller_group?.name }}
            </p>
            <p class="text-xs text-slate-400">
              {{ activeConv.seller_user ? activeConv.seller_group?.name : 'ผู้ขาย' }}
            </p>
          </div>
        </div>

        <!-- Messages — LINE style layout, original color scheme -->
        <div ref="msgBox" class="flex-1 overflow-y-auto px-3 py-3 bg-slate-50">
          <div v-if="loadingMsg" class="flex justify-center py-4">
            <i class="fi fi-rr-spinner animate-spin text-violet-500"></i>
          </div>
          <div v-else-if="!messages.length" class="flex flex-col items-center justify-center h-full text-slate-500 text-sm gap-2">
            <i class="fi fi-rr-comment-alt text-3xl text-slate-400"></i>
            ส่งข้อความเพื่อเริ่มบทสนทนา
          </div>
          <template v-else>
            <template v-for="(msg, idx) in messages" :key="msg.id">

              <!-- Date separator -->
              <div v-if="needsDateSep(msg, idx)" class="flex justify-center my-3">
                <span class="bg-black/25 text-white text-[11px] px-3 py-1 rounded-full select-none">
                  {{ fmtDate(msg.created_at) }}
                </span>
              </div>

              <!-- Unread divider -->
              <div v-if="msg.id === firstUnreadId"
                data-unread-divider
                class="flex items-center gap-2 my-2">
                <div class="flex-1 h-px bg-slate-400/40"></div>
                <span class="text-[11px] text-slate-500 bg-white/70 backdrop-blur px-2.5 py-0.5 rounded-full whitespace-nowrap select-none">
                  ข้อความที่ยังไม่อ่าน
                </span>
                <div class="flex-1 h-px bg-slate-400/40"></div>
              </div>

              <!-- Message row -->
              <div :data-msg-id="msg.id"
                class="flex mb-1.5 group"
                :class="msg.sender_type === 'customer' ? 'justify-end items-center gap-1' : 'justify-start items-end gap-2'">

                <!-- Seller avatar (กลม, แสดงเฉพาะข้อความสุดท้ายของกลุ่ม) -->
                <div v-if="msg.sender_type !== 'customer'"
                  class="w-9 h-9 rounded-full overflow-hidden shrink-0 bg-gradient-to-br from-violet-500 to-fuchsia-600 flex items-center justify-center"
                  :class="isLastInGroup(msg, idx) ? 'visible' : 'invisible'">
                  <img v-if="activeConv.seller_user?.avatar_path"
                    :src="`/storage/${activeConv.seller_user.avatar_path}`"
                    class="w-full h-full object-cover" />
                  <img v-else-if="activeConv.seller_group?.logo_path"
                    :src="`/storage/${activeConv.seller_group.logo_path}`"
                    class="w-full h-full object-cover" />
                  <i v-else class="fi fi-rr-shop text-white text-sm"></i>
                </div>

                <!-- Bubble column -->
                <div class="max-w-[75%]" :class="msg.sender_type === 'customer' ? '' : ''">
                  <!-- Seller name (แสดงเฉพาะข้อความแรกของกลุ่ม) -->
                  <p v-if="msg.sender_type !== 'customer' && isFirstInGroup(msg, idx)"
                    class="text-xs text-slate-600 font-medium ml-1 mb-1 truncate">
                    {{ activeConv.seller_user?.shop_name || activeConv.seller_user?.name || activeConv.seller_group?.name }}
                  </p>

                  <!-- Product card -->
                  <RouterLink v-if="msg.product" :to="`/shop/products/${msg.product.slug}`"
                    class="block rounded-2xl overflow-hidden border shadow-sm transition hover:shadow-md mb-1"
                    :class="msg.sender_type === 'customer'
                      ? 'bg-violet-50 border-violet-200'
                      : 'bg-white border-slate-200'">
                    <div class="flex gap-2.5 p-2.5">
                      <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 bg-slate-100">
                        <img v-if="msg.product.primary_image_url" :src="msg.product.primary_image_url"
                          class="w-full h-full object-cover" />
                        <i v-else class="fi fi-rr-picture text-slate-300 text-2xl flex items-center justify-center h-full"></i>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-700 line-clamp-2 leading-snug">{{ msg.product.name }}</p>
                        <div class="flex items-center gap-0.5 mt-1">
                          <i v-for="s in 5" :key="s" class="fi text-[9px]"
                            :class="s <= Math.round(msg.product.rating_avg || 0) ? 'fi-sr-star text-amber-400' : 'fi-rr-star text-slate-200'"></i>
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
                    class="overflow-hidden rounded-2xl cursor-pointer mb-0.5 max-w-[220px]"
                    :class="msg.sender_type === 'customer' ? 'rounded-br-sm' : 'rounded-bl-sm'"
                    @click="previewImage = msg.image_url"
                    @contextmenu="handleContextMenu($event, msg)"
                    @touchstart.passive="startLongPress($event, msg)"
                    @touchend="cancelLongPress"
                    @touchmove.passive="cancelLongPress">
                    <img :src="msg.image_url" class="w-full object-cover block" style="max-height:260px" />
                  </div>

                  <!-- Text bubble -->
                  <div v-if="msg.body"
                    class="px-3.5 py-2.5 text-sm leading-relaxed inline-block"
                    :class="msg.sender_type === 'customer'
                      ? 'bg-violet-600 text-white rounded-2xl rounded-br-sm shadow-sm'
                      : 'bg-white text-slate-700 border border-slate-100 rounded-2xl rounded-bl-sm shadow-sm'"
                    @contextmenu="handleContextMenu($event, msg)"
                    @touchstart.passive="startLongPress($event, msg)"
                    @touchend="cancelLongPress"
                    @touchmove.passive="cancelLongPress">
                    <!-- Quoted reply -->
                    <div v-if="msg.reply_to"
                      class="mb-1.5 pl-2 border-l-2 rounded text-xs opacity-90"
                      :class="msg.sender_type === 'customer' ? 'border-white/60' : 'border-violet-400'">
                      <span class="block font-semibold" :class="msg.sender_type === 'customer' ? 'text-white/90' : 'text-violet-600'">
                        {{ msg.reply_to.sender_type === 'customer' ? 'คุณ' : 'ร้านค้า' }}
                      </span>
                      <span class="block truncate" :class="msg.sender_type === 'customer' ? 'text-white/75' : 'text-slate-500'">
                        {{ msg.reply_to.body || '[ภาพ]' }}
                      </span>
                    </div>
                    <span class="whitespace-pre-line">{{ msg.body }}</span>
                    <RouterLink
                      v-if="msg.sender_type !== 'customer' && extractOrderNo(msg.body)"
                      :to="`/shop/account/orders/${extractOrderNo(msg.body)}`"
                      class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-violet-700 bg-violet-50 border border-violet-200 rounded-xl px-3 py-2 hover:bg-violet-100 transition">
                      <i class="fi fi-rr-box-open text-[11px]"></i>
                      ดูรายละเอียดคำสั่งซื้อ #{{ extractOrderNo(msg.body) }}
                    </RouterLink>
                  </div>

                  <!-- Timestamp + read receipt -->
                  <div class="flex items-center gap-1 mt-0.5"
                    :class="msg.sender_type === 'customer' ? 'justify-end' : 'justify-start ml-1'">
                    <p class="text-[10px] text-slate-500">{{ fmtTime(msg.created_at) }}</p>
                    <span v-if="msg.sender_type === 'customer'"
                      class="flex items-center text-[10px] leading-none"
                      :class="msg.is_read ? 'text-violet-500' : 'text-slate-400'"
                      :title="msg.is_read ? 'อ่านแล้ว' : 'ส่งแล้ว'">
                      <i class="fi fi-sr-check"></i>
                      <i v-if="msg.is_read" class="fi fi-sr-check -ml-1"></i>
                    </span>
                  </div>
                </div>

                <!-- ปุ่มเมนู (⋮) — ตอบกลับ/ลบ (ไม่ต้องคลิกขวา) -->
                <button
                  class="self-center w-7 h-7 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center shrink-0 transition opacity-100 lg:opacity-0 lg:group-hover:opacity-100"
                  :class="msg.sender_type === 'customer' ? 'order-first' : ''"
                  @click.stop="openMenuButton($event, msg)"
                  title="ตัวเลือก">
                  <i class="fi fi-rr-menu-dots-vertical text-xs"></i>
                </button>
              </div>

            </template>
          </template>
        </div>

        <!-- Product context card (จาก "แชทเลย") -->
        <Transition name="slide-down">
          <div v-if="productContext" class="shrink-0 px-3 pt-2.5 bg-white border-t border-slate-100">
            <div class="flex gap-3 p-2.5 bg-violet-50 rounded-xl border border-violet-100 relative">
              <button @click="productContext = null"
                class="absolute top-1.5 right-1.5 w-5 h-5 flex items-center justify-center rounded-full bg-slate-200 hover:bg-slate-300 text-slate-500 text-[10px]">
                <i class="fi fi-rr-cross-small"></i>
              </button>
              <div class="w-14 h-14 rounded-lg bg-slate-200 overflow-hidden shrink-0">
                <img v-if="productContext.primary_image_url" :src="productContext.primary_image_url" class="w-full h-full object-cover" />
                <i v-else class="fi fi-rr-picture text-slate-300 text-2xl flex items-center justify-center h-full"></i>
              </div>
              <div class="flex-1 min-w-0 pr-4">
                <p class="text-sm font-semibold text-slate-700 line-clamp-2 leading-snug">{{ productContext.name }}</p>
                <p class="text-sm font-bold text-fuchsia-700 mt-1">฿{{ fmtPrice(productContext.effective_price ?? productContext.price) }}</p>
              </div>
            </div>
          </div>
        </Transition>

        <!-- Reply context bar -->
        <Transition name="slide-down">
          <div v-if="replyTo" class="shrink-0 px-3 pt-2.5 bg-white border-t border-slate-100">
            <div class="flex items-center gap-2.5 p-2.5 bg-violet-50 rounded-xl border-l-4 border-violet-400 relative">
              <i class="fi fi-rr-reply-all text-violet-500 text-sm shrink-0"></i>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-violet-700">
                  ตอบกลับ {{ replyTo.sender_type === 'customer' ? 'ตัวคุณเอง' : (activeConv.seller_user?.shop_name || activeConv.seller_group?.name || 'ร้านค้า') }}
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

        <!-- Input -->
        <div class="p-3 border-t border-slate-100 bg-white shrink-0">
          <!-- Image preview before send -->
          <div v-if="pendingImage" class="mb-2 relative inline-block">
            <img :src="pendingImageUrl" class="h-20 w-auto rounded-xl object-cover border border-violet-200" />
            <button @click="clearPendingImage"
              class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-slate-700 text-white text-[10px] flex items-center justify-center hover:bg-rose-600 transition">
              <i class="fi fi-rr-cross-small"></i>
            </button>
          </div>
          <div class="flex items-end gap-2">
            <!-- Image upload button -->
            <button @click="imgInput?.click()" title="แนบรูปภาพ"
              class="w-10 h-10 rounded-xl border border-slate-200 text-slate-500 flex items-center justify-center shrink-0 hover:border-violet-400 hover:text-violet-600 transition">
              <i class="fi fi-rr-picture text-base"></i>
            </button>
            <input ref="imgInput" type="file" accept="image/*" class="hidden" @change="onImageSelect" />
            <textarea
              v-model="newMsg"
              @keydown.enter.exact.prevent="send"
              rows="1"
              placeholder="พิมพ์ข้อความ..."
              class="flex-1 px-3 py-2.5 rounded-xl border border-slate-200 text-sm resize-none focus:outline-none focus:border-violet-400 max-h-28"
              style="min-height:42px;"
            ></textarea>
            <button @click="send" :disabled="(!newMsg.trim() && !pendingImage) || sending"
              class="w-10 h-10 rounded-xl bg-violet-600 text-white flex items-center justify-center shrink-0 hover:bg-violet-700 transition disabled:opacity-40">
              <i class="fi fi-rr-paper-plane text-sm"></i>
            </button>
          </div>
        </div>
      </div>
    </template>

  <!-- Image preview lightbox -->
  <Teleport to="body">
    <Transition name="fade-slide">
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
      <button v-if="ctxMenu.msg?.sender_type === 'customer'" @click="deleteMsg"
        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition">
        <i class="fi fi-rr-trash"></i> ลบ / ยกเลิกการส่ง
      </button>
    </div>
  </Teleport>

  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/index.js'

const route  = useRoute()
const router = useRouter()

const conversations   = ref([])
const activeConv      = ref(null)
const messages        = ref([])
const newMsg          = ref('')
const loadingList     = ref(true)
const loadingMsg      = ref(false)
const sending         = ref(false)
const msgBox          = ref(null)
const productContext  = ref(null)
const ctxMenu         = ref({ visible: false, msg: null, x: 0, y: 0 })
const replyTo         = ref(null)
const imgInput        = ref(null)
const pendingImage    = ref(null)
const pendingImageUrl = ref(null)
const previewImage    = ref(null)
const firstUnreadId   = ref(null)   // id ข้อความแรกที่ยังไม่ได้อ่าน

let pollTimer      = null
let longPressTimer = null

function needsDateSep(msg, idx) {
  if (idx === 0) return true
  const prev = messages.value[idx - 1]
  return new Date(msg.created_at).toDateString() !== new Date(prev.created_at).toDateString()
}

function isFirstInGroup(msg, idx) {
  if (idx === 0) return true
  const prev = messages.value[idx - 1]
  return prev.sender_type !== msg.sender_type
}

function isLastInGroup(msg, idx) {
  const next = messages.value[idx + 1]
  if (!next) return true
  return next.sender_type !== msg.sender_type
}

function fmtDate(d) {
  const dt = new Date(d)
  if (dt.toDateString() === new Date().toDateString()) return 'วันนี้'
  return dt.toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short', year: '2-digit' })
}

function extractOrderNo(body) {
  const m = body?.match(/\bOR[A-Z0-9]{6,}\b/)
  return m ? m[0] : null
}

function fmtPrice(v) {
  return Number(v || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

function fmtTime(d) {
  if (!d) return ''
  const dt = new Date(d)
  const now = new Date()
  if (dt.toDateString() === now.toDateString()) {
    return dt.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
  }
  return dt.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' })
}

async function loadConversations() {
  loadingList.value = true
  try {
    const { data } = await api.get('/shop/chat/conversations')
    conversations.value = data
  } finally { loadingList.value = false }
}

async function openConv(conv) {
  activeConv.value = conv
  // ใช้ค่าจาก backend (คำนวณก่อน mark as read) — แม่นยำกว่าคำนวณเอง
  const presetUnreadId = conv.is_read_by_customer === false ? (conv.first_unread_message_id ?? null) : null
  firstUnreadId.value = null
  loadingMsg.value = true
  try {
    const { data } = await api.get(`/shop/chat/conversations/${conv.id}/messages`)
    messages.value = data
    firstUnreadId.value = presetUnreadId
  } finally {
    loadingMsg.value = false
  }
  // scroll หลัง loadingMsg=false เพื่อให้ messages DOM render ก่อน
  if (firstUnreadId.value) {
    scrollToUnread()
  } else {
    scrollBottom()
  }
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
  const body      = newMsg.value.trim()
  const productId = productContext.value?.id || null
  const imgFile   = pendingImage.value
  const replyId   = replyTo.value?.id || null

  newMsg.value = ''
  productContext.value = null
  replyTo.value = null
  clearPendingImage()
  sending.value = true

  try {
    let data
    if (imgFile) {
      const fd = new FormData()
      fd.append('image', imgFile)
      if (body) fd.append('body', body)
      if (productId) fd.append('product_id', productId)
      if (replyId) fd.append('reply_to_id', replyId)
      ;({ data } = await api.post(
        `/shop/chat/conversations/${activeConv.value.id}/messages`,
        fd
      ))
    } else {
      ;({ data } = await api.post(`/shop/chat/conversations/${activeConv.value.id}/messages`, {
        body,
        product_id: productId,
        reply_to_id: replyId,
      }))
    }
    messages.value.push(data)
    scrollBottom()
  } finally { sending.value = false }
}

async function pollMessages(convId) {
  if (!activeConv.value || activeConv.value.id !== convId) return
  try {
    const { data } = await api.get(`/shop/chat/conversations/${convId}/messages`)
    const lastLocal  = messages.value.at(-1)?.id
    const lastRemote = data.at(-1)?.id
    if (lastRemote !== lastLocal || data.length !== messages.value.length) {
      messages.value = data
      firstUnreadId.value = null
      scrollBottom()
    }
  } catch { /* ignore */ }
}

function startPolling(convId) {
  clearInterval(pollTimer)
  pollTimer = setInterval(() => pollMessages(convId), 4000)
}

function scrollBottom() {
  nextTick(() => {
    if (msgBox.value) msgBox.value.scrollTop = msgBox.value.scrollHeight
  })
}

function scrollToUnread() {
  nextTick(() => {
    const el = msgBox.value?.querySelector('[data-unread-divider]')
      || msgBox.value?.querySelector(`[data-msg-id="${firstUnreadId.value}"]`)
    if (el) {
      el.scrollIntoView({ behavior: 'instant', block: 'start' })
    } else {
      scrollBottom()
    }
  })
}

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

function startReply() {
  const msg = ctxMenu.value.msg
  closeMenu()
  if (!msg) return
  replyTo.value = { id: msg.id, body: msg.body, sender_type: msg.sender_type }
}

function cancelLongPress() {
  clearTimeout(longPressTimer)
}

function closeMenu() {
  ctxMenu.value.visible = false
}

async function deleteMsg() {
  const msg    = ctxMenu.value.msg
  const convId = activeConv.value?.id
  closeMenu()
  if (!msg || !convId) return
  try {
    await api.delete(`/shop/chat/conversations/${convId}/messages/${msg.id}`)
    messages.value = messages.value.filter(m => m.id !== msg.id)
    if (replyTo.value?.id === msg.id) replyTo.value = null
  } catch { /* ignore */ }
}

onMounted(async () => {
  document.documentElement.style.overflow = 'hidden'
  document.addEventListener('click', closeMenu)

  await loadConversations()

  // โหลดข้อมูลสินค้า ถ้ามา จาก "แชทเลย"
  if (route.query.productSlug) {
    try {
      const { data } = await api.get(`/shop/products/${route.query.productSlug}`)
      productContext.value = data.product
    } catch { /* ignore */ }
  }

  if (route.query.id) {
    const conv = conversations.value.find(c => c.id == route.query.id)
    if (conv) await openConv(conv)
    if (productContext.value) {
      newMsg.value = `สอบถามสินค้า: ${productContext.value.name}`
    }
  }
})

onUnmounted(() => {
  clearInterval(pollTimer)
  cancelLongPress()
  document.removeEventListener('click', closeMenu)
  document.documentElement.style.overflow = ''
})

watch(() => route.query.id, async id => {
  if (id) {
    const conv = conversations.value.find(c => c.id == id)
    if (conv) await openConv(conv)
  }
})
</script>
