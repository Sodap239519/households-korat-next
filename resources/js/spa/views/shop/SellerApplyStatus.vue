<template>
  <div class="max-w-lg mx-auto px-4 sm:px-6 pb-24">

    <!-- ===== Header Banner ===== -->
    <div class="-mx-4 sm:-mx-6 -mt-0 mb-6 overflow-hidden"
      style="background:linear-gradient(135deg,#4c1d95 0%,#7c3aed 50%,#f97316 100%)">
      <div class="px-5 pt-5 pb-3 flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-white/20 border border-white/25 flex items-center justify-center text-white text-2xl shrink-0 shadow-lg backdrop-blur-sm">
          <i class="fi fi-rr-shop"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-white font-extrabold text-xl tracking-wide leading-none">ติดตามสถานะ</p>
          <p class="text-white/70 text-xs mt-1.5 leading-relaxed truncate">{{ info?.business_name || 'คำขอสมัครร้านค้า' }}</p>
        </div>
        <i class="fi fi-rr-badge-check text-white/15 text-6xl shrink-0 leading-none select-none hidden sm:block"></i>
      </div>

      <!-- Step indicator -->
      <div class="px-5 pb-4 flex items-center gap-2 text-xs">
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/20 text-white/60">
          <i class="fi fi-rr-check"></i> กรอกข้อมูล
        </div>
        <i class="fi fi-rr-angle-right text-white/40 text-[10px]"></i>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/20 text-white/60">
          <i class="fi fi-rr-check"></i> ส่งคำขอ
        </div>
        <i class="fi fi-rr-angle-right text-white/40 text-[10px]"></i>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition"
          :class="info?.status === 'approved'
            ? 'bg-emerald-400/40 text-white font-semibold'
            : info?.status === 'rejected'
              ? 'bg-rose-400/40 text-white font-semibold'
              : info?.status === 'revision_requested'
                ? 'bg-amber-400/50 text-white font-semibold'
                : 'bg-white/25 text-white font-semibold'">
          <i :class="info?.status === 'approved' ? 'fi fi-rr-badge-check'
                     : info?.status === 'rejected' ? 'fi fi-rr-cross-circle'
                     : info?.status === 'revision_requested' ? 'fi fi-rr-undo'
                     : 'fi fi-rr-time-half-past'"></i>
          {{ info?.status === 'approved' ? 'อนุมัติแล้ว!'
           : info?.status === 'rejected' ? 'ไม่ผ่าน'
           : info?.status === 'revision_requested' ? 'ต้องแก้ไข'
           : 'รอผล' }}
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-spinner animate-spin text-2xl"></i>
      <p class="mt-2 text-sm">กำลังโหลด...</p>
    </div>

    <!-- Not found -->
    <div v-else-if="notFound" class="box-card p-10 text-center">
      <i class="fi fi-rr-search text-4xl text-slate-300 mb-3"></i>
      <p class="font-semibold text-slate-700">ไม่พบคำขอนี้</p>
      <p class="text-sm text-slate-400 mt-1 mb-4">กรุณาตรวจสอบหมายเลขติดตามอีกครั้ง</p>
      <RouterLink to="/shop/seller-apply/my"
        class="inline-flex items-center gap-2 px-5 h-10 rounded-full bg-violet-600 text-white text-sm font-semibold">
        <i class="fi fi-rr-list"></i> ดูรายการสมัครของฉัน
      </RouterLink>
    </div>

    <template v-else-if="info">

      <!-- Status summary card -->
      <div class="box-card p-5 mb-4"
        :class="info.status === 'approved' ? 'border border-emerald-200 bg-emerald-50/30'
              : info.status === 'rejected' ? 'border border-rose-200 bg-rose-50/20' : ''">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl shrink-0"
            :class="statusStyle.bg">
            <i :class="[statusStyle.icon, statusStyle.text]"></i>
          </div>
          <div>
            <p class="text-xs text-slate-400 mb-0.5">สถานะล่าสุด</p>
            <p class="font-bold text-base" :class="statusStyle.text">{{ info.status_label }}</p>
          </div>
        </div>
        <div class="space-y-2 text-sm divide-y divide-slate-100">
          <div class="flex justify-between py-1.5">
            <span class="text-slate-400">ชื่อผู้สมัคร</span>
            <span class="font-medium text-slate-800">{{ info.applicant_name }}</span>
          </div>
          <div class="flex justify-between py-1.5">
            <span class="text-slate-400">ชื่อกิจการ</span>
            <span class="font-medium text-slate-800">{{ info.business_name }}</span>
          </div>
          <div class="flex justify-between py-1.5">
            <span class="text-slate-400">วันที่สมัคร</span>
            <span class="text-slate-600">{{ fmtDate(info.submitted_at) }}</span>
          </div>
        </div>

        <!-- CTA by status -->
        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col gap-2">
          <!-- Approved -->
          <template v-if="info.status === 'approved'">
            <p class="text-sm text-emerald-700 font-medium text-center mb-2">
              <i class="fi fi-rr-party-horn mr-1"></i> ยินดีด้วย! ร้านค้าของคุณได้รับการอนุมัติแล้ว
            </p>
            <!-- Login credentials info -->
            <div class="bg-violet-50 border border-violet-200 rounded-xl p-3 mb-2 space-y-2 text-sm">
              <p class="font-semibold text-violet-700 flex items-center gap-1.5">
                <i class="fi fi-rr-key"></i> ข้อมูลเข้าสู่ระบบผู้ขาย
              </p>
              <div class="flex items-center justify-between gap-2">
                <span class="text-slate-500 shrink-0">อีเมล</span>
                <span class="font-medium text-slate-800 font-mono text-xs break-all text-right">{{ info.created_user_email || info.applicant_email || '—' }}</span>
              </div>
              <!-- Temp password row -->
              <div class="pt-1 border-t border-violet-100">
                <p class="text-slate-500 text-xs mb-2">{{ newSavedPassword ? 'รหัสผ่านของคุณ' : 'รหัสผ่านชั่วคราว' }}</p>

                <!-- ตั้งรหัสใหม่แล้ว — แสดงรหัสใหม่ (ปิด/เปิดตา) แทนข้อความหมดอายุ -->
                <div v-if="newSavedPassword" class="space-y-1.5">
                  <div class="flex items-center gap-2">
                    <span class="flex-1 font-mono text-base font-bold text-slate-800 tracking-widest px-3 py-2 bg-white rounded-xl border border-emerald-200 select-none">
                      {{ showNewPassword ? maskMiddle(newSavedPassword) : '●●●●●●●●●●' }}
                    </span>
                    <!-- คัดลอกรหัสเต็ม -->
                    <button type="button" @click="copySavedPassword"
                      class="w-10 h-10 rounded-xl bg-white border flex items-center justify-center transition shrink-0"
                      :class="copiedPw ? 'text-emerald-600 border-emerald-300' : 'text-emerald-600 border-emerald-200 hover:bg-emerald-50'"
                      :title="copiedPw ? 'คัดลอกแล้ว' : 'คัดลอกรหัสผ่านเต็ม'">
                      <i :class="copiedPw ? 'fi fi-rr-check' : 'fi fi-rr-copy-alt'" class="leading-none"></i>
                    </button>
                    <button type="button" @click="showNewPassword = !showNewPassword"
                      class="w-10 h-10 rounded-xl bg-white border border-emerald-200 flex items-center justify-center text-emerald-600 hover:bg-emerald-50 transition shrink-0"
                      :title="showNewPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'">
                      <i :class="showNewPassword ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye'" class="leading-none"></i>
                    </button>
                  </div>
                  <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] text-emerald-600 flex items-center gap-1">
                      <i class="fi fi-rr-check-circle text-[10px]"></i> รหัสผ่านใหม่ที่คุณตั้งไว้ (บันทึกในเครื่องนี้)
                    </p>
                    <button type="button" @click="clearSavedPassword"
                      class="text-[11px] text-slate-400 hover:text-rose-500 transition flex items-center gap-0.5 shrink-0" title="ล้างรหัสที่บันทึกในเครื่องนี้">
                      <i class="fi fi-rr-trash text-[10px]"></i> ล้างออก
                    </button>
                  </div>
                </div>

                <!-- ยังไม่ได้กดเปิด -->
                <button v-else-if="info.password_available && !revealedPassword"
                  :disabled="revealLoading"
                  class="w-full flex items-center justify-center gap-2 h-10 rounded-xl bg-violet-100 hover:bg-violet-200 text-violet-700 text-sm font-semibold transition disabled:opacity-60"
                  @click="revealPassword">
                  <i :class="revealLoading ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-eye'" class="leading-none"></i>
                  {{ revealLoading ? 'กำลังโหลด...' : 'กดเพื่อดูรหัสผ่าน (นับ 30 นาทีหลังกด)' }}
                </button>

                <!-- เปิดแล้ว — แสดงพร้อม countdown -->
                <div v-else-if="revealedPassword" class="space-y-2">
                  <div class="flex items-center gap-2">
                    <span class="flex-1 font-mono text-base font-bold text-slate-800 tracking-widest px-3 py-2 bg-white rounded-xl border border-violet-200 select-none">
                      {{ showPassword ? maskLast3(revealedPassword) : '●●●●●●●●●●' }}
                    </span>
                    <!-- ปุ่มคัดลอกรหัสเต็ม (เอาไปวางล็อกอินได้ โดยไม่ต้องโชว์ครบบนจอ) -->
                    <button type="button" @click="copyPassword"
                      class="w-10 h-10 rounded-xl bg-white border border-violet-200 flex items-center justify-center transition shrink-0"
                      :class="copiedPw ? 'text-emerald-500 border-emerald-200' : 'text-violet-500 hover:bg-violet-100'"
                      :title="copiedPw ? 'คัดลอกแล้ว' : 'คัดลอกรหัสผ่าน'">
                      <i :class="copiedPw ? 'fi fi-rr-check' : 'fi fi-rr-copy-alt'" class="leading-none"></i>
                    </button>
                    <button type="button" @click="showPassword = !showPassword"
                      class="w-10 h-10 rounded-xl bg-white border border-violet-200 flex items-center justify-center text-violet-500 hover:bg-violet-100 transition shrink-0"
                      :title="showPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'">
                      <i :class="showPassword ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye'" class="leading-none"></i>
                    </button>
                  </div>
                  <p v-if="showPassword" class="text-[11px] text-slate-400 flex items-center gap-1">
                    <i class="fi fi-rr-lock text-[10px]"></i> ปิด 3 ตัวท้ายเพื่อความปลอดภัย — กดปุ่มคัดลอกเพื่อใช้รหัสเต็ม
                  </p>
                  <!-- Countdown -->
                  <div class="flex items-center gap-1.5 text-xs"
                    :class="remainingSeconds < 300 ? 'text-rose-500' : 'text-amber-600'">
                    <i class="fi fi-rr-clock shrink-0"></i>
                    <span v-if="remainingSeconds > 0">
                      รหัสหมดอายุใน <strong>{{ countdownLabel }}</strong> — เข้าสู่ระบบและเปลี่ยนรหัสผ่านโดยเร็ว
                    </span>
                    <span v-else class="text-rose-500 font-semibold">รหัสผ่านหมดอายุแล้ว</span>
                  </div>
                </div>

                <!-- หมดอายุถาวร — แต่มี hint 3 ตัวท้าย -->
                <div v-else-if="info.temp_password_hint" class="space-y-1.5">
                  <p class="text-[11px] text-slate-400">เปลี่ยนรหัสผ่านแล้ว — แสดงได้เฉพาะส่วนท้าย</p>
                  <div class="flex items-center gap-2">
                    <span class="flex-1 font-mono text-base font-bold text-slate-700 tracking-widest px-3 py-2 bg-white rounded-xl border border-violet-200">
                      {{ showHintPassword ? '***' + info.temp_password_hint : '●●●●●●●●' }}
                    </span>
                    <button type="button" @click="showHintPassword = !showHintPassword"
                      class="w-10 h-10 rounded-xl bg-white border border-violet-200 flex items-center justify-center text-violet-500 hover:bg-violet-100 transition shrink-0">
                      <i :class="showHintPassword ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye'" class="leading-none"></i>
                    </button>
                  </div>
                </div>

                <!-- หมดอายุถาวร ไม่มี hint -->
                <p v-else class="text-xs text-slate-400 text-center py-1">
                  รหัสผ่านหมดอายุแล้ว — ตั้งรหัสใหม่ได้ด้านล่าง
                </p>
              </div>

              <!-- ลืมรหัสผ่าน / ตั้งรหัสใหม่ด้วยตัวเอง -->
              <div class="pt-2 border-t border-violet-100">
                <button v-if="!showResetForm && !resetDone" @click="showResetForm = true"
                  class="text-xs text-violet-600 hover:text-violet-800 font-medium flex items-center gap-1.5">
                  <i class="fi fi-rr-lock"></i> ลืมรหัสผ่าน? ตั้งรหัสใหม่ด้วยตัวเอง
                </button>

                <div v-else-if="resetDone" class="text-xs text-emerald-600 flex items-center gap-1.5 py-1">
                  <i class="fi fi-rr-check-circle"></i> ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว — เข้าสู่ระบบด้วยรหัสใหม่ได้เลย
                </div>

                <div v-else class="space-y-2">
                  <p class="text-xs font-semibold text-violet-700 flex items-center gap-1.5"><i class="fi fi-rr-lock"></i> ตั้งรหัสผ่านใหม่</p>
                  <div class="relative">
                    <input v-model="resetPw" :type="showResetPw ? 'text' : 'password'"
                      placeholder="รหัสผ่านใหม่ (อย่างน้อย 6 ตัว)" class="inp w-full pr-9 text-sm" />
                    <button type="button" @click="showResetPw = !showResetPw"
                      class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-violet-500">
                      <i :class="showResetPw ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye'"></i>
                    </button>
                  </div>
                  <input v-model="resetPwConfirm" :type="showResetPw ? 'text' : 'password'"
                    placeholder="ยืนยันรหัสผ่านใหม่" @keyup.enter="submitReset" class="inp w-full text-sm" />
                  <p v-if="resetError" class="text-xs text-rose-500">{{ resetError }}</p>
                  <div class="flex gap-2">
                    <button @click="submitReset" :disabled="resetLoading"
                      class="flex-1 h-9 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold transition disabled:opacity-60 flex items-center justify-center gap-1.5">
                      <i :class="resetLoading ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-disk'"></i>
                      {{ resetLoading ? 'กำลังบันทึก...' : 'บันทึกรหัสใหม่' }}
                    </button>
                    <button @click="showResetForm = false; resetError = ''"
                      class="px-3 h-9 rounded-lg border border-slate-200 text-slate-500 text-xs hover:bg-slate-50 transition">ยกเลิก</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="flex gap-2">
              <button @click="handleSellerLogin"
                class="flex-1 flex items-center justify-center gap-1.5 h-11 rounded-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition">
                <i class="fi fi-rr-sign-in-alt"></i>
                {{ isMarketStaff ? 'ไปหน้าจัดการร้านค้า' : 'เข้าสู่ระบบร้านค้า' }}
              </button>
              <RouterLink to="/shop/account"
                class="flex-1 flex items-center justify-center gap-1.5 h-11 rounded-full border border-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">
                <i class="fi fi-rr-arrow-small-left"></i> กลับ
              </RouterLink>
            </div>
          </template>
          <!-- Rejected -->
          <template v-else-if="info.status === 'rejected'">
            <p v-if="info.admin_note || info.superadmin_note" class="text-sm text-rose-600 bg-rose-50 rounded-xl p-3 leading-relaxed">
              <i class="fi fi-rr-comment mr-1"></i> {{ info.superadmin_note || info.admin_note }}
            </p>
            <RouterLink to="/shop/seller-apply"
              class="flex items-center justify-center gap-2 h-11 rounded-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition">
              <i class="fi fi-rr-refresh"></i> สมัครใหม่
            </RouterLink>
          </template>
          <!-- Revision requested -->
          <template v-else-if="info.status === 'revision_requested'">
            <div class="bg-amber-50 rounded-xl p-3 mb-2">
              <p class="text-xs font-semibold text-amber-700 mb-1 flex items-center gap-1">
                <i class="fi fi-rr-undo"></i> ข้อมูลที่ต้องแก้ไข
              </p>
              <p class="text-sm text-amber-800 leading-relaxed">{{ info.revision_note }}</p>
            </div>
            <RouterLink :to="`/shop/seller-apply?edit=${info.token || tokenInput}`"
              class="flex items-center justify-center gap-2 h-11 rounded-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold transition">
              <i class="fi fi-rr-edit"></i> แก้ไขและส่งใหม่
            </RouterLink>
          </template>
          <!-- Pending/In review -->
          <template v-else>
            <p class="text-xs text-slate-400 text-center">
              <i class="fi fi-rr-clock mr-1"></i> ทีมงานจะตรวจสอบและติดต่อกลับภายใน 1–3 วันทำการ
            </p>
            <RouterLink to="/shop/seller-apply/my"
              class="flex items-center justify-center gap-2 h-10 rounded-full border border-violet-200 text-violet-600 text-sm font-medium hover:bg-violet-50 transition">
              <i class="fi fi-rr-list"></i> ดูรายการสมัครทั้งหมด
            </RouterLink>
          </template>
        </div>
      </div>

      <!-- Timeline -->
      <div class="box-card p-5 mb-4">
        <h3 class="font-semibold text-slate-700 mb-4 text-sm flex items-center gap-2">
          <i class="fi fi-rr-time-past text-violet-500"></i> ขั้นตอนการพิจารณา
        </h3>
        <div class="space-y-4">
          <TimelineStep
            :done="true"
            icon="fi-rr-paper-plane"
            label="ส่งคำขอสมัครแล้ว"
            :sub="fmtDate(info.submitted_at)" />
          <TimelineStep
            :done="['admin_approved','escalated','approved','rejected'].includes(info.status)"
            :current="info.status === 'pending'"
            icon="fi-rr-user-check"
            label="Admin พิจารณาขั้นแรก"
            :sub="info.status === 'escalated' ? 'เลยกำหนด 1 ชั่วโมง — ส่งตรงถึง superadmin' : undefined"
            :warn="info.status === 'escalated'" />
          <TimelineStep
            :done="['approved','rejected'].includes(info.status)"
            :current="['admin_approved','escalated'].includes(info.status)"
            icon="fi-rr-badge-check"
            label="Superadmin พิจารณาขั้นสุดท้าย" />
          <TimelineStep
            :done="info.status === 'approved'"
            :rejected="info.status === 'rejected'"
            :icon="info.status === 'approved' ? 'fi-rr-shop' : 'fi-rr-cross-circle'"
            :label="info.status === 'approved' ? 'อนุมัติ — เริ่มขายได้เลย!' : info.status === 'rejected' ? 'ไม่ผ่านการพิจารณา' : 'ผลการอนุมัติ'" />
        </div>
      </div>

      <!-- Notes (for non-rejected, shown inline in CTA for rejected) -->
      <div v-if="(info.superadmin_note || info.admin_note) && info.status !== 'rejected'" class="box-card p-5 mb-4">
        <h3 class="font-semibold text-slate-700 mb-3 text-sm flex items-center gap-2">
          <i class="fi fi-rr-comment text-violet-500"></i> หมายเหตุจากทีมงาน
        </h3>
        <p class="text-sm text-slate-600 leading-relaxed">{{ info.superadmin_note || info.admin_note }}</p>
      </div>

    </template>

    <!-- ประวัติในอุปกรณ์นี้ -->
    <div v-if="history.length" class="box-card p-5 mt-4">
      <p class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
        <i class="fi fi-rr-time-past text-violet-500"></i> ประวัติการสมัครในอุปกรณ์นี้
      </p>
      <div class="space-y-2">
        <button v-for="h in history" :key="h.token"
          @click="fetchStatus(h.token); tokenInput = h.token"
          class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-violet-50 transition text-left"
          :class="h.token === tokenInput ? 'ring-1 ring-violet-300 bg-violet-50' : ''">
          <div>
            <p class="text-sm font-medium text-slate-700">{{ h.business_name }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ fmtDate(h.submitted_at) }}</p>
          </div>
          <i class="fi fi-rr-angle-right text-slate-400 text-xs shrink-0"></i>
        </button>
      </div>
    </div>

    <!-- ===== Modal เข้าสู่ระบบร้านค้า ===== -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showLoginModal"
          class="fixed inset-0 z-[300] bg-black/60 flex items-end sm:items-center justify-center p-4"
          @click.self="showLoginModal = false">
          <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-violet-600 to-fuchsia-600 px-5 py-4">
              <h3 class="font-bold text-white text-base flex items-center gap-2">
                <i class="fi fi-rr-shop"></i> เข้าสู่ระบบร้านค้า
              </h3>
              <p class="text-white/70 text-xs mt-0.5">กรอกรหัสผ่านเพื่อเข้าสู่ระบบผู้ขาย</p>
            </div>
            <div class="p-5 space-y-3">
              <!-- Email (readonly) -->
              <div>
                <label class="text-xs text-slate-400 mb-1 block">อีเมลร้านค้า</label>
                <div class="h-11 px-3 flex items-center rounded-xl bg-slate-50 border border-slate-200 text-slate-600 font-mono text-sm break-all">
                  {{ sellerEmail }}
                </div>
              </div>
              <!-- Password -->
              <div>
                <label class="text-xs text-slate-400 mb-1 block">รหัสผ่าน</label>
                <input ref="loginPasswordInput"
                  v-model="loginPassword" type="password"
                  class="inp w-full" placeholder="กรอกรหัสผ่าน"
                  @keyup.enter="doSellerLogin" />
              </div>
              <!-- Error -->
              <p v-if="loginError" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-3 py-2 flex items-center gap-2">
                <i class="fi fi-rr-exclamation shrink-0"></i> {{ loginError }}
              </p>
              <!-- Buttons -->
              <div class="flex gap-2 pt-1">
                <button @click="doSellerLogin"
                  :disabled="loginLoading || !loginPassword.trim()"
                  class="flex-1 h-11 rounded-full bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700 transition disabled:opacity-50 flex items-center justify-center gap-1.5">
                  <i :class="loginLoading ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-sign-in-alt'"></i>
                  {{ loginLoading ? 'กำลังเข้าสู่ระบบ...' : 'เข้าสู่ระบบ' }}
                </button>
                <button @click="showLoginModal = false"
                  class="flex-1 h-11 rounded-full border border-slate-200 text-slate-600 text-sm hover:bg-slate-50 transition">
                  ยกเลิก
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ค้นหาด้วย token (สำรอง) -->
    <div class="box-card p-5 mt-4">
      <p class="text-sm text-slate-500 mb-2 flex items-center gap-1.5">
        <i class="fi fi-rr-search text-slate-400 text-xs"></i> ค้นหาด้วยหมายเลขติดตาม
      </p>
      <div class="flex gap-2">
        <input v-model="tokenInput" class="inp flex-1" placeholder="วางหมายเลขติดตามที่นี่" @keyup.enter="search" />
        <button @click="search" class="px-4 h-11 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700 transition">
          ค้นหา
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, defineComponent, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/index.js'
import { useAuth } from '../../composables/useAuth.js'

const route        = useRoute()
const router       = useRouter()
const { user, logout, isMarketStaff, fetchUser } = useAuth()
const loading      = ref(false)
const notFound     = ref(false)
const info         = ref(null)
const tokenInput   = ref('')
const history      = ref([])
const showPassword      = ref(false)
const showHintPassword  = ref(false)
const revealLoading     = ref(false)
const revealedPassword  = ref(null)
const remainingSeconds  = ref(0)
const copiedPw          = ref(false)
// ลืมรหัสผ่าน / ตั้งรหัสใหม่
const showResetForm     = ref(false)
const resetPw           = ref('')
const resetPwConfirm    = ref('')
const showResetPw       = ref(false)
const resetLoading      = ref(false)
const resetError        = ref('')
const resetDone         = ref(false)
const newSavedPassword  = ref(null)   // รหัสใหม่ที่เพิ่งตั้ง (แสดงในเซสชันนี้เท่านั้น)
const showNewPassword   = ref(false)
let   countdownInterval = null

async function copySavedPassword() {
  if (!newSavedPassword.value) return
  try {
    await navigator.clipboard.writeText(newSavedPassword.value)
    copiedPw.value = true
    setTimeout(() => { copiedPw.value = false }, 2000)
  } catch { /* ignore */ }
}

function clearSavedPassword() {
  const token = info.value?.token || tokenInput.value
  try { localStorage.removeItem('seller_new_pw_' + token) } catch { /* ignore */ }
  newSavedPassword.value = null
  resetDone.value = false
}

async function submitReset() {
  resetError.value = ''
  if (resetPw.value.length < 6) { resetError.value = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัว'; return }
  if (resetPw.value !== resetPwConfirm.value) { resetError.value = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน'; return }
  resetLoading.value = true
  try {
    const token = info.value?.token || tokenInput.value
    await api.post(`/seller/apply/${token}/reset-password`, {
      password: resetPw.value,
      password_confirmation: resetPwConfirm.value,
    })
    resetDone.value = true
    showResetForm.value = false
    // เก็บรหัสใหม่ไว้แสดง (แบบปิด/เปิดตา) แทนข้อความ "หมดอายุ" + บันทึกในเครื่องเพื่อให้แสดงหลังรีเฟรช
    newSavedPassword.value = resetPw.value
    showNewPassword.value = false
    try { localStorage.setItem('seller_new_pw_' + token, resetPw.value) } catch { /* ignore */ }
    resetPw.value = ''; resetPwConfirm.value = ''
    // รหัสชั่วคราวถูกยกเลิกแล้ว
    revealedPassword.value = null
    info.value = { ...info.value, password_available: false, temp_password_hint: null }
  } catch (e) {
    resetError.value = e.response?.data?.errors?.password?.[0] || e.response?.data?.message || 'ตั้งรหัสใหม่ไม่สำเร็จ'
  } finally {
    resetLoading.value = false
  }
}

// แสดงรหัสผ่านโดยปิด 3 ตัวท้ายด้วย *** (กันคนข้างๆ แอบเห็นครบ)
function maskLast3(pw) {
  if (!pw) return ''
  return pw.length <= 3 ? '*'.repeat(pw.length) : pw.slice(0, -3) + '***'
}

// แสดงแบบโชว์ 3 ตัวหน้า + ปิดกลาง + โชว์ 3 ตัวท้าย เช่น "212***236"
function maskMiddle(pw) {
  if (!pw) return ''
  if (pw.length <= 6) return pw.slice(0, 1) + '***'   // สั้นเกินไป โชว์ตัวแรกพอ
  return pw.slice(0, 3) + '***' + pw.slice(-3)
}

async function copyPassword() {
  if (!revealedPassword.value) return
  try {
    await navigator.clipboard.writeText(revealedPassword.value)
    copiedPw.value = true
    setTimeout(() => { copiedPw.value = false }, 2000)
  } catch { /* ignore */ }
}

// Modal เข้าสู่ระบบร้านค้า
const showLoginModal    = ref(false)
const loginPassword     = ref('')
const loginError        = ref(null)
const loginLoading      = ref(false)
const loginPasswordInput = ref(null)
const sellerEmail = computed(() => info.value?.created_user_email || info.value?.applicant_email || '')

const countdownLabel = computed(() => {
  const m = Math.floor(remainingSeconds.value / 60)
  const s = remainingSeconds.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

function startCountdown(expiresAt) {
  clearInterval(countdownInterval)
  const tick = () => {
    const diff = Math.max(0, Math.floor((new Date(expiresAt) - Date.now()) / 1000))
    remainingSeconds.value = diff
    if (diff === 0) clearInterval(countdownInterval)
  }
  tick()
  countdownInterval = setInterval(tick, 1000)
}

async function revealPassword() {
  if (revealLoading.value) return
  revealLoading.value = true
  try {
    const token = info.value?.token || tokenInput.value
    const { data } = await api.post(`/seller/apply/${token}/reveal-password`)
    revealedPassword.value = data.temp_password
    showPassword.value = true
    startCountdown(data.password_expires_at)
  } catch (e) {
    if (e.response?.status === 410) {
      info.value = { ...info.value, password_available: false }
    }
  } finally {
    revealLoading.value = false
  }
}

const APPLY_HISTORY_KEY = 'seller_apply_history'

function loadHistory() {
  try {
    history.value = JSON.parse(localStorage.getItem(APPLY_HISTORY_KEY) || '[]')
  } catch { history.value = [] }
}

const STATUS_STYLE = {
  pending:            { bg: 'bg-amber-100',  text: 'text-amber-600',   icon: 'fi fi-rr-time-half-past' },
  admin_approved:     { bg: 'bg-blue-100',   text: 'text-blue-600',    icon: 'fi fi-rr-user-check' },
  escalated:          { bg: 'bg-orange-100', text: 'text-orange-600',  icon: 'fi fi-rr-exclamation' },
  approved:           { bg: 'bg-emerald-100',text: 'text-emerald-600', icon: 'fi fi-rr-badge-check' },
  rejected:           { bg: 'bg-rose-100',   text: 'text-rose-600',    icon: 'fi fi-rr-cross-circle' },
  revision_requested: { bg: 'bg-amber-100',  text: 'text-amber-600',   icon: 'fi fi-rr-undo' },
}
const statusStyle = computed(() => STATUS_STYLE[info.value?.status] ?? STATUS_STYLE.pending)

function fmtDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' })
}

async function fetchStatus(token) {
  if (!token) return
  loading.value = true; notFound.value = false; info.value = null
  revealedPassword.value = null; showHintPassword.value = false; newSavedPassword.value = null; clearInterval(countdownInterval)
  try {
    const { data } = await api.get(`/seller/apply/status/${token}`)
    info.value = data
    // ถ้าเคยตั้งรหัสใหม่ในเครื่องนี้ → แสดงต่อ (แทนข้อความรหัสชั่วคราว)
    if (data.status === 'approved') {
      const savedPw = localStorage.getItem('seller_new_pw_' + token)
      if (savedPw) { newSavedPassword.value = savedPw; showNewPassword.value = false }
    }
    // ถ้าโหลดหน้ามาแล้ว 30 นาทียังไม่หมด → เริ่ม countdown ต่อทันที
    if (data.temp_password && data.password_expires_at) {
      revealedPassword.value = data.temp_password
      showPassword.value = false
      startCountdown(data.password_expires_at)
    }
  } catch (e) {
    notFound.value = true
  } finally {
    loading.value = false
  }
}

function search() {
  if (tokenInput.value.trim()) fetchStatus(tokenInput.value.trim())
}

// TimelineStep inline component
const TimelineStep = defineComponent({
  props: { done: Boolean, current: Boolean, warn: Boolean, rejected: Boolean, icon: String, label: String, sub: String },
  setup(props) {
    return () => h('div', { class: 'flex items-start gap-3' }, [
      h('div', {
        class: [
          'w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0 mt-0.5 transition',
          props.rejected ? 'bg-rose-500 text-white' :
          props.done    ? 'bg-emerald-500 text-white' :
          props.current ? (props.warn ? 'bg-orange-400 text-white' : 'bg-violet-500 text-white') :
          'bg-slate-100 text-slate-300'
        ]
      }, [h('i', { class: `fi ${props.rejected ? 'fi-rr-cross-small' : props.done ? 'fi-rr-check' : props.icon}` })]),
      h('div', {}, [
        h('p', {
          class: ['text-sm font-medium', props.rejected ? 'text-rose-600' : props.done ? 'text-slate-800' : props.current ? 'text-violet-700' : 'text-slate-400']
        }, props.label),
        props.sub ? h('p', { class: 'text-xs text-orange-500 mt-0.5' }, props.sub) : null,
      ])
    ])
  }
})

async function handleSellerLogin() {
  if (isMarketStaff.value) {
    router.push('/app/market')
    return
  }
  // เปิด modal — ยังไม่ logout บัญชีปัจจุบัน
  loginPassword.value = ''
  loginError.value    = null
  showLoginModal.value = true
  await nextTick()
  loginPasswordInput.value?.focus()
}

async function doSellerLogin() {
  if (!loginPassword.value.trim() || loginLoading.value) return
  loginLoading.value = true
  loginError.value   = null
  try {
    await api.post('/login', { email: sellerEmail.value, password: loginPassword.value })
    // login สำเร็จ — refresh user ให้ useAuth รู้จัก seller account ก่อน navigate
    await fetchUser()
    showLoginModal.value = false
    router.push('/app/market')
  } catch (e) {
    loginError.value = e.response?.status === 422
      ? 'รหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง'
      : 'ไม่สามารถเข้าสู่ระบบได้ กรุณาลองใหม่'
  } finally {
    loginLoading.value = false
  }
}

onMounted(() => {
  loadHistory()
  const token = route.params.token
  if (token) { tokenInput.value = token; fetchStatus(token) }
  else if (history.value.length) { fetchStatus(history.value[0].token); tokenInput.value = history.value[0].token }
})

onUnmounted(() => {
  clearInterval(countdownInterval)
})
</script>

<style scoped>
.inp { height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); background: white; width: 100%; }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
