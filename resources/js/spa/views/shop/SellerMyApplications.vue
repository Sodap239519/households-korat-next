<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 pb-8">

    <!-- Header banner -->
    <div class="-mx-4 sm:-mx-6 -mt-0 mb-6 overflow-hidden"
      style="background:linear-gradient(135deg,#4c1d95 0%,#7c3aed 50%,#f97316 100%)">
      <div class="px-5 pt-5 pb-4 flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-white/20 border border-white/25 flex items-center justify-center text-white text-xl shrink-0 shadow-lg">
          <i class="fi fi-rr-file-check"></i>
        </div>
        <div>
          <p class="text-white font-extrabold text-lg leading-none">คำขอสมัครร้านค้าของฉัน</p>
          <p class="text-white/70 text-xs mt-1.5">ติดตามสถานะการสมัคร — ทีมงานจะติดต่อกลับใน 1-3 วันทำการ</p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-spinner animate-spin text-2xl"></i>
      <p class="mt-2 text-sm">กำลังโหลด...</p>
    </div>

    <!-- ไม่มีรายการ -->
    <template v-else-if="!apps.length">
      <div class="box-card p-10 text-center">
        <i class="fi fi-rr-file-upload text-4xl text-slate-300 mb-3"></i>
        <p class="font-semibold text-slate-700">ยังไม่มีคำขอสมัคร</p>
        <p class="text-sm text-slate-400 mt-1 mb-5">เริ่มสมัครเพื่อร่วมขายสินค้าชุมชน</p>
        <RouterLink to="/shop/seller-apply"
          class="inline-flex items-center gap-2 px-6 h-11 rounded-full btn-orange font-semibold text-sm shadow-md">
          <i class="fi fi-rr-shop"></i> สมัครร้านค้า
        </RouterLink>
      </div>
    </template>

    <!-- รายการ -->
    <template v-else>
      <!-- Search -->
      <div class="mb-4 relative">
        <i class="fi fi-rr-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
        <input v-model="search" class="w-full h-11 pl-9 pr-4 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:border-violet-400"
          placeholder="ค้นหาชื่อกิจการ หรืออำเภอ..." />
      </div>

      <div class="space-y-3">
        <div v-for="app in filtered" :key="app.id"
          class="box-card p-4 flex items-start gap-4 hover:shadow-md transition cursor-pointer"
          @click="$router.push(`/shop/seller-apply/status/${app.token}`)">
          <!-- Status icon -->
          <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 text-base"
            :class="statusStyle(app.status).bg">
            <i :class="['fi', statusStyle(app.status).icon, statusStyle(app.status).text]"></i>
          </div>
          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-800 truncate">{{ app.business_name }}</p>
            <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5 flex-wrap">
              <span v-if="app.district">
                <i class="fi fi-rr-marker text-[10px]"></i> {{ app.district }}
              </span>
              <span v-if="app.requested_group">
                <i class="fi fi-rr-users-alt text-[10px]"></i> {{ app.requested_group.name }}
              </span>
              <span>
                <i class="fi fi-rr-calendar text-[10px]"></i> {{ fmtDate(app.submitted_at) }}
              </span>
            </p>
          </div>
          <!-- Status badge + arrow -->
          <div class="shrink-0 flex items-center gap-2">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
              :class="[statusStyle(app.status).text, statusStyle(app.status).bg]">
              {{ app.status_label }}
            </span>
            <i class="fi fi-rr-angle-small-right text-slate-300"></i>
          </div>
        </div>

        <!-- No results from search -->
        <div v-if="!filtered.length" class="box-card p-8 text-center text-slate-400">
          <i class="fi fi-rr-search text-2xl mb-2"></i>
          <p class="text-sm">ไม่พบรายการที่ค้นหา</p>
        </div>
      </div>

      <!-- สมัครใหม่ (กรณีถูกปฏิเสธทั้งหมด) -->
      <div v-if="allRejected" class="mt-5 box-card p-5 text-center bg-violet-50 border border-violet-100">
        <p class="text-sm text-slate-600 mb-3">ต้องการลองสมัครใหม่อีกครั้ง?</p>
        <RouterLink to="/shop/seller-apply"
          class="inline-flex items-center gap-2 px-5 h-10 rounded-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition">
          <i class="fi fi-rr-shop"></i> สมัครใหม่
        </RouterLink>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api/index.js'

const apps    = ref([])
const loading = ref(true)
const search  = ref('')

const STATUS_STYLE = {
  pending:        { bg: 'bg-amber-100',   text: 'text-amber-600',   icon: 'fi-rr-time-half-past' },
  admin_approved: { bg: 'bg-blue-100',    text: 'text-blue-600',    icon: 'fi-rr-user-check' },
  escalated:      { bg: 'bg-orange-100',  text: 'text-orange-600',  icon: 'fi-rr-exclamation' },
  approved:       { bg: 'bg-emerald-100', text: 'text-emerald-600', icon: 'fi-rr-badge-check' },
  rejected:       { bg: 'bg-rose-100',    text: 'text-rose-600',    icon: 'fi-rr-cross-circle' },
}
function statusStyle(s) { return STATUS_STYLE[s] ?? STATUS_STYLE.pending }

function fmtDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' })
}

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return apps.value
  return apps.value.filter(a =>
    a.business_name?.toLowerCase().includes(q) || a.district?.toLowerCase().includes(q)
  )
})

const allRejected = computed(() =>
  apps.value.length > 0 && apps.value.every(a => a.status === 'rejected')
)

onMounted(async () => {
  try {
    const { data } = await api.get('/seller/my-applications')
    apps.value = data
  } catch { /* ignore */ }
  finally { loading.value = false }
})
</script>
