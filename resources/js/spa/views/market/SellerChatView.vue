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
          <div class="w-11 h-11 rounded-full bg-gradient-to-br from-violet-400 to-fuchsia-500 text-white flex items-center justify-center shrink-0 text-sm font-bold shadow-sm">
            {{ (conv.customer?.name || '?')[0].toUpperCase() }}
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
          <div class="w-9 h-9 rounded-full bg-gradient-to-br from-violet-400 to-fuchsia-500 text-white flex items-center justify-center font-bold text-sm shrink-0">
            {{ (activeConv.customer?.name || '?')[0].toUpperCase() }}
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
            <div v-for="msg in messages" :key="msg.id"
              class="flex gap-2"
              :class="msg.sender_type === 'staff' ? 'justify-end' : 'justify-start'">
              <div v-if="msg.sender_type !== 'staff'"
                class="w-7 h-7 rounded-full bg-gradient-to-br from-violet-300 to-fuchsia-400 text-white flex items-center justify-center text-xs font-bold shrink-0 mt-1">
                {{ (msg.sender?.name || '?')[0].toUpperCase() }}
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
                <!-- Text bubble -->
                <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm"
                  :class="msg.sender_type === 'staff'
                    ? 'bg-gradient-to-br from-violet-600 to-fuchsia-600 text-white rounded-br-sm'
                    : 'bg-white text-slate-700 border border-slate-100 rounded-bl-sm'">
                  {{ msg.body }}
                </div>
                <p class="text-[10px] text-slate-400 mt-0.5 px-1"
                  :class="msg.sender_type === 'staff' ? 'text-right' : ''">
                  {{ fmtTime(msg.created_at) }}
                </p>
              </div>
            </div>
          </template>
        </div>

        <!-- Input bar -->
        <div class="p-3 border-t border-slate-100 bg-white shrink-0 flex items-end gap-2">
          <textarea v-model="newMsg" @keydown.enter.exact.prevent="send" rows="1"
            placeholder="พิมพ์ข้อความตอบลูกค้า..."
            class="flex-1 px-3.5 py-2.5 rounded-2xl border border-slate-200 text-sm resize-none focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-400/20 max-h-28 transition"
            style="min-height: 42px;"></textarea>
          <button @click="send" :disabled="!newMsg.trim() || sending"
            class="w-11 h-11 rounded-2xl bg-gradient-to-br from-violet-600 to-fuchsia-600 text-white flex items-center justify-center hover:opacity-90 transition disabled:opacity-40 shrink-0 shadow-md shadow-violet-500/30">
            <i class="fi fi-rr-paper-plane"></i>
          </button>
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
let pollTimer = null

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
  loadingMsg.value = true
  try {
    const { data } = await api.get(`/market/chat/conversations/${conv.id}/messages`)
    messages.value = data
    conv.is_read_by_seller = true
    unread.value = Math.max(0, unread.value - 1)
    scrollBottom()
  } finally { loadingMsg.value = false }
  startPolling(conv.id)
}

async function send() {
  if (!newMsg.value.trim() || sending.value) return
  const body = newMsg.value.trim()
  newMsg.value = ''
  sending.value = true
  try {
    const { data } = await api.post(`/market/chat/conversations/${activeConv.value.id}/messages`, { body })
    messages.value.push(data)
    scrollBottom()
  } finally { sending.value = false }
}

async function pollMessages(convId) {
  if (!activeConv.value || activeConv.value.id !== convId) return
  try {
    const { data } = await api.get(`/market/chat/conversations/${convId}/messages`)
    if (data.length !== messages.value.length) { messages.value = data; scrollBottom() }
  } catch { /* ignore */ }
}

function startPolling(convId) {
  clearInterval(pollTimer)
  pollTimer = setInterval(() => pollMessages(convId), 4000)
}

function scrollBottom() {
  nextTick(() => { if (msgBox.value) msgBox.value.scrollTop = msgBox.value.scrollHeight })
}

onMounted(loadConversations)
onUnmounted(() => clearInterval(pollTimer))
</script>
