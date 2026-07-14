<template>
  <div class="space-y-3">
    <!-- ฟอร์มถาม -->
    <div class="box-card p-4">
      <p class="text-sm font-semibold text-slate-700 mb-2"><i class="fi fi-rr-comment mr-1 text-violet-600"></i> ถาม-ตอบ / ความคิดเห็น</p>
      <div v-if="user" class="flex gap-2">
        <UserAvatar :avatar-path="user.avatar_path" :name="user.name || ''" size="sm" class="mt-1" />
        <textarea v-model="newBody" rows="2" placeholder="ถามหรือแสดงความคิดเห็น..." class="inp flex-1 resize-none"></textarea>
        <button class="btn-orange self-end px-4 py-2 rounded-full text-sm font-semibold shrink-0" :disabled="postBusy || !newBody.trim()" @click="postComment(null, newBody)">
          ส่ง
        </button>
      </div>
      <p v-else class="text-sm text-slate-400">
        <RouterLink to="/shop/login" class="text-violet-600 hover:underline">เข้าสู่ระบบ</RouterLink> เพื่อแสดงความคิดเห็น
      </p>
    </div>

    <!-- รายการคอมเมนต์ -->
    <div v-if="!comments.length && !loading" class="text-sm text-slate-400 text-center py-3">ยังไม่มีความคิดเห็น</div>
    <div v-for="c in comments" :key="c.id" class="box-card p-4 space-y-2">
      <div class="flex items-start gap-2.5">
        <UserAvatar :avatar-path="c.user?.avatar_path" :name="c.user?.name || ''" size="sm" />
        <div class="flex-1 min-w-0">
          <div class="flex items-baseline gap-2">
            <span class="text-sm font-semibold text-slate-700">{{ c.user?.name }}</span>
            <span class="text-xs text-slate-400">{{ fmtDate(c.created_at) }}</span>
          </div>
          <p class="text-sm text-slate-600 mt-0.5">{{ c.body }}</p>
        </div>
      </div>

      <!-- replies -->
      <div v-if="c.replies?.length" class="pl-3 border-l-2 border-violet-200 space-y-2 mt-1 ml-10">
        <div v-for="rep in c.replies" :key="rep.id" class="flex items-start gap-2">
          <UserAvatar :avatar-path="rep.user?.avatar_path" :name="rep.user?.name || ''" size="xs" />
          <div class="flex-1 min-w-0">
            <div class="flex items-baseline gap-2">
              <span class="text-xs font-semibold text-violet-700">{{ rep.user?.name }}</span>
              <span class="text-xs text-slate-400">{{ fmtDate(rep.created_at) }}</span>
            </div>
            <p class="text-sm text-slate-600">{{ rep.body }}</p>
          </div>
        </div>
      </div>

      <!-- ตอบกลับ -->
      <div v-if="user" class="ml-10">
        <button v-if="replyTo !== c.id" class="text-xs text-violet-600 hover:underline" @click="replyTo = c.id; replyBody = ''">
          <i class="fi fi-rr-reply mr-0.5"></i> ตอบกลับ
        </button>
        <div v-else class="flex gap-2 mt-1">
          <UserAvatar :avatar-path="user.avatar_path" :name="user.name || ''" size="xs" class="mt-1" />
          <textarea v-model="replyBody" rows="2" placeholder="ตอบกลับ..." class="inp flex-1 resize-none text-xs"></textarea>
          <div class="flex flex-col gap-1 self-end">
            <button class="btn-orange px-3 py-1.5 rounded-full text-xs font-semibold" :disabled="postBusy || !replyBody.trim()" @click="postComment(c.id, replyBody)">ส่ง</button>
            <button class="text-xs text-slate-400 hover:underline" @click="replyTo = null">ยกเลิก</button>
          </div>
        </div>
      </div>
    </div>

    <button v-if="hasMore" class="w-full py-2 text-sm text-violet-600 hover:underline" @click="loadMore">
      {{ loading ? 'กำลังโหลด...' : 'ดูเพิ่มเติม' }}
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuth } from '../../../composables/useAuth.js'
import api from '../../../api/index.js'
import UserAvatar from './UserAvatar.vue'

const props = defineProps({
  slug: { type: String, required: true },
  initialComments: { type: Array, default: () => [] },
})

const { user } = useAuth()
const comments = ref([...props.initialComments])
const newBody = ref('')
const replyTo = ref(null)
const replyBody = ref('')
const postBusy = ref(false)
const loading = ref(false)
const page = ref(1)
const hasMore = ref(false)

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('th-TH') : '' }

async function postComment(parentId, body) {
  if (!body.trim()) return
  postBusy.value = true
  try {
    await api.post(`/shop/products/${props.slug}/comments`, { body, parent_id: parentId })
    const { data } = await api.get(`/shop/products/${props.slug}/comments`, { params: { per_page: 10, page: 1 } })
    comments.value = data.data || []
    hasMore.value = !!data.next_page_url
    if (!parentId) newBody.value = ''
    else { replyTo.value = null; replyBody.value = '' }
  } catch { /* toast handled by interceptor */ }
  finally { postBusy.value = false }
}

async function loadMore() {
  loading.value = true
  try {
    page.value++
    const { data } = await api.get(`/shop/products/${props.slug}/comments`, { params: { per_page: 10, page: page.value } })
    comments.value.push(...(data.data || []))
    hasMore.value = !!data.next_page_url
  } finally { loading.value = false }
}

onMounted(() => { hasMore.value = false })
</script>

<style scoped>
.inp { width:100%; border:1px solid rgb(226 232 240); border-radius:.6rem; padding:.4rem .65rem; font-size:.875rem; }
.inp:focus { outline:none; border-color:rgb(167 139 250); }
</style>
