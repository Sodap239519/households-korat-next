<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 py-4 sm:py-6 space-y-4">
    <Breadcrumb :items="[{ label: 'คำสั่งซื้อ', to: '/shop/account/orders' }, { label: orderNo }]" class="mb-2" />

    <div v-if="loading" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-spinner animate-spin text-3xl"></i>
    </div>

    <template v-else-if="order">
      <!-- Header -->
      <div class="box-card p-5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <div>
            <h1 class="text-base font-bold text-slate-800">{{ order.order_no }}</h1>
            <p class="text-xs text-slate-400 mt-0.5">
              {{ order.seller_group?.name }}
              <template v-if="sellerNames.length">
                <span class="mx-1">·</span>{{ sellerNames.join(', ') }}
              </template>
            </p>
          </div>
          <OrderStatusChip :status="order.status" />
        </div>
      </div>

      <!-- Items -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-3">รายการสินค้า</h3>
        <div v-for="it in order.items" :key="it.id"
          class="flex justify-between py-2 text-sm border-b border-slate-50 last:border-0 items-center gap-2">
          <RouterLink v-if="it.product?.slug"
            :to="`/shop/products/${it.product.slug}`"
            class="flex-1 min-w-0 break-words text-slate-600 hover:text-violet-600 hover:underline transition pr-1">
            {{ it.product_name }} <span class="text-slate-400">×{{ it.qty }}</span>
          </RouterLink>
          <span v-else class="flex-1 min-w-0 break-words text-slate-600 pr-1">
            {{ it.product_name }} <span class="text-slate-400">×{{ it.qty }}</span>
          </span>
          <span class="text-slate-700 shrink-0">฿{{ fmt(it.line_total) }}</span>
        </div>
        <div class="flex justify-between mt-3 pt-3 border-t border-slate-100 font-bold">
          <span class="text-slate-700">ยอดสุทธิ</span>
          <span class="text-fuchsia-700 text-lg">฿{{ fmt(order.total) }}</span>
        </div>
      </div>

      <!-- Shipping -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-2 flex items-center gap-2">
          <i class="fi fi-rr-marker text-violet-600"></i> ที่อยู่จัดส่ง
          <button v-if="canEdit" type="button"
            class="ml-auto text-xs text-violet-600 hover:text-violet-800 flex items-center gap-1 font-medium transition"
            @click="openEdit">
            <i class="fi fi-rr-edit"></i> แก้ไข
          </button>
        </h3>
        <p class="text-sm text-slate-600">{{ order.shipping_name }} · {{ order.shipping_phone }}</p>
        <p class="text-sm text-slate-500 mt-0.5 leading-relaxed">
          {{ [order.shipping_address, order.shipping_sub_district, order.shipping_district, order.shipping_province, order.shipping_zipcode].filter(Boolean).join(' ') }}
        </p>
        <!-- วิธีการจัดส่ง — แสดงเสมอถ้ามีค่า หรือถ้ายังแก้ไขได้ให้แสดงข้อความชี้นำ -->
        <p v-if="order.shipping_method || canEdit"
          class="text-xs mt-1 flex items-center gap-1.5"
          :class="order.shipping_method ? 'text-slate-400' : 'text-violet-400'">
          <i class="fi fi-rr-truck-side"></i>
          {{ order.shipping_method || 'ยังไม่ได้เลือกวิธีจัดส่ง — กดแก้ไขเพื่อเลือก' }}
        </p>
      </div>

      <!-- Shipment card (แสดงเมื่อผู้ขายกดจัดส่งแล้ว — ทั้งแบบมีเลขพัสดุและจัดส่งเอง) -->
      <div v-if="order.shipment" class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-3 flex items-center gap-2">
          <i class="fi fi-rr-truck-side text-indigo-500"></i> ข้อมูลการจัดส่ง
        </h3>
        <div class="rounded-xl bg-indigo-50 border border-indigo-100 px-4 py-3 space-y-2">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2">
              <i class="fi fi-rr-box-alt text-indigo-400 text-sm"></i>
              <span class="text-xs text-slate-500">{{ order.shipment.tracking_no ? 'ขนส่ง' : 'วิธีจัดส่ง' }}</span>
              <span class="text-sm font-semibold text-slate-700">
                {{ order.shipment.tracking_no ? (order.shipment.carrier || '—') : (order.shipment.carrier || 'จัดส่งโดยผู้ขายโดยตรง') }}
              </span>
            </div>
            <span v-if="order.shipment.shipped_at" class="text-[11px] text-slate-400">
              ส่งเมื่อ {{ formatDate(order.shipment.shipped_at) }}
            </span>
          </div>
          <!-- มีเลขพัสดุ (ขนส่งเอกชน) -->
          <div v-if="order.shipment.tracking_no" class="flex items-center gap-2">
            <i class="fi fi-rr-barcode text-indigo-400 text-sm"></i>
            <span class="text-xs text-slate-500 shrink-0">เลขพัสดุ</span>
            <span class="text-sm font-mono font-bold text-indigo-800 break-all flex-1">{{ order.shipment.tracking_no }}</span>
            <button
              @click="copyTracking(order.shipment.tracking_no)"
              class="shrink-0 text-indigo-500 hover:text-indigo-700 text-xs flex items-center gap-1 transition px-2 py-1 rounded-lg hover:bg-indigo-100"
              title="คัดลอกเลขพัสดุ">
              <i class="fi fi-rr-copy-alt text-xs"></i>
              <span>คัดลอก</span>
            </button>
          </div>
          <!-- จัดส่งเอง (ไม่มีเลขพัสดุ) -->
          <p v-else class="text-xs text-emerald-600 flex items-center gap-1.5">
            <i class="fi fi-rr-check-circle"></i> ผู้ขายจัดส่งเอง — ไม่มีเลขพัสดุติดตาม
          </p>
          <p v-if="order.shipment.note" class="text-xs text-slate-500 pt-1 border-t border-indigo-100">
            {{ order.shipment.note }}
          </p>
        </div>
      </div>

      <!-- ===== Payment section ===== -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm flex items-center gap-2"
          :class="canEditPayment ? 'mb-1' : 'mb-3'">
          <i class="fi fi-rr-receipt text-violet-600"></i> การชำระเงิน
          <span class="font-normal text-slate-400 text-xs">
            ({{ order.payment_method === 'cod' ? 'ชำระปลายทาง' : 'โอนเงิน' }})
          </span>
          <button v-if="canEditPayment" type="button"
            class="ml-auto text-xs text-violet-600 hover:text-violet-800 flex items-center gap-1 font-medium transition"
            @click="openEdit">
            <i class="fi fi-rr-edit"></i> แก้ไข
          </button>
        </h3>
        <p v-if="canEditPayment" class="text-[11px] text-slate-400 mb-3">
          วิธีชำระเงินและการจัดส่งสามารถแก้ไขได้ก่อนผู้ขายยืนยัน
        </p>

        <!-- Existing payment status -->
        <div v-if="latestPayment" class="rounded-xl p-4 mb-4 flex gap-3"
          :class="payBoxClass">
          <!-- Slip thumbnail (tappable → fullscreen) -->
          <button v-if="latestPayment.slip_url"
            @click="fullscreenSlip = latestPayment.slip_url"
            class="shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-white/60 shadow-sm">
            <img :src="latestPayment.slip_url" class="w-full h-full object-cover" />
          </button>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <i class="fi text-xl"
                :class="{
                  'fi-rr-clock text-sky-500': latestPayment.status === 'pending',
                  'fi-rr-check-circle text-emerald-500': latestPayment.status === 'confirmed',
                  'fi-rr-cross-circle text-rose-500': latestPayment.status === 'rejected',
                }"></i>
              <p class="font-semibold text-sm">{{ payStatusLabel }}</p>
            </div>
            <p class="text-sm font-bold mt-1">฿{{ fmt(latestPayment.amount) }}</p>
            <p v-if="latestPayment.status === 'rejected' && latestPayment.reject_reason"
              class="text-xs text-rose-600 mt-1 bg-rose-50 rounded-lg px-2 py-1">
              เหตุผล: {{ latestPayment.reject_reason }}
            </p>
          </div>
        </div>

        <!-- COD notice -->
        <div v-if="order.payment_method === 'cod'"
          class="rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100 p-4 flex items-start gap-3">
          <i class="fi fi-rr-money-bill-wave text-emerald-500 text-xl shrink-0 mt-0.5"></i>
          <div>
            <p class="font-semibold text-emerald-700 text-sm">ชำระเงินปลายทาง (COD)</p>
            <p class="text-xs text-emerald-600 mt-0.5">เตรียมเงินสด <span class="font-bold">฿{{ fmt(order.total) }}</span> ชำระเมื่อรับสินค้า — ไม่ต้องโอนเงินล่วงหน้า</p>
          </div>
        </div>

        <!-- Bank account info (when payable — online only) -->
        <template v-if="canPay && order.payment_method !== 'cod'">
          <div class="rounded-2xl bg-gradient-to-br from-violet-50 to-fuchsia-50 border border-violet-100 p-4 mb-4">
            <p class="text-xs font-semibold text-violet-500 uppercase tracking-wider mb-2">โอนเงินมาที่</p>
            <template v-if="order.seller_group?.bank_account_no || order.seller_group?.promptpay_id">
              <p v-if="order.seller_group?.bank_name" class="text-sm text-slate-600 font-medium">{{ order.seller_group.bank_name }}</p>
              <div v-if="order.seller_group?.bank_account_no" class="flex items-center gap-2 mt-1">
                <p class="text-base font-bold text-slate-800 font-mono tracking-wide break-all">{{ order.seller_group.bank_account_no }}</p>
                <button type="button" @click="copyText(order.seller_group.bank_account_no, 'คัดลอกเลขบัญชีแล้ว')"
                  class="shrink-0 flex items-center gap-1 text-xs text-violet-600 hover:text-violet-800 px-2 py-1 rounded-lg hover:bg-violet-100 transition"
                  title="คัดลอกเลขบัญชี">
                  <i class="fi fi-rr-copy-alt text-xs"></i> คัดลอก
                </button>
              </div>
              <p v-if="order.seller_group?.bank_account_name" class="text-sm text-slate-600 mt-0.5">{{ order.seller_group.bank_account_name }}</p>
              <div v-if="order.seller_group?.promptpay_id" class="text-sm text-violet-600 mt-1.5 flex items-center gap-1.5">
                <i class="fi fi-rr-smartphone shrink-0"></i>
                <span class="break-all">พร้อมเพย์: {{ order.seller_group.promptpay_id }}</span>
                <button type="button" @click="copyText(order.seller_group.promptpay_id, 'คัดลอกพร้อมเพย์แล้ว')"
                  class="shrink-0 flex items-center gap-1 text-xs text-violet-600 hover:text-violet-800 px-1.5 py-0.5 rounded-lg hover:bg-violet-100 transition"
                  title="คัดลอกพร้อมเพย์">
                  <i class="fi fi-rr-copy-alt text-xs"></i>
                </button>
              </div>
            </template>
            <p v-else class="text-sm text-slate-500">ติดต่อผู้ขาย: {{ order.seller_group?.contact_phone || '-' }}</p>
            <div class="mt-3 pt-3 border-t border-violet-100 flex items-center justify-between">
              <span class="text-sm text-slate-500">ยอดที่ต้องโอน</span>
              <span class="text-xl font-bold text-fuchsia-700">฿{{ fmt(order.total) }}</span>
            </div>
          </div>

          <!-- Slip uploader -->
          <div class="space-y-3">
            <p class="text-sm font-semibold text-slate-700 flex items-center gap-2">
              <i class="fi fi-rr-camera text-violet-500"></i> แนบสลิปโอนเงิน
              <span class="text-xs font-normal text-slate-400">(ระบบอ่าน QR อัตโนมัติ)</span>
            </p>
            <SlipUploader :orderTotal="order.total" @change="onSlipChange" />
            <p v-if="payError" class="text-sm text-rose-500 flex items-center gap-1">
              <i class="fi fi-rr-exclamation"></i> {{ payError }}
            </p>
            <button
              :disabled="submitting || !pay.file"
              class="w-full h-12 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition shadow-lg"
              :class="pay.file
                ? 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white hover:opacity-90 shadow-violet-500/30'
                : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
              @click="submitPayment">
              <i v-if="submitting" class="fi fi-rr-spinner animate-spin"></i>
              <i v-else class="fi fi-rr-paper-plane"></i>
              {{ submitting ? 'กำลังส่ง...' : 'แจ้งชำระเงิน' }}
            </button>
          </div>
        </template>
      </div>

      <!-- Cancel (pending_payment only) -->
      <div v-if="canCancel" class="box-card p-5">
        <button
          :disabled="cancelling"
          class="w-full h-12 rounded-2xl border-2 border-rose-300 text-rose-600 hover:bg-rose-50 font-semibold text-sm flex items-center justify-center gap-2 transition disabled:opacity-50"
          @click="cancelOrder">
          <i :class="cancelling ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-cross-circle'"></i>
          {{ cancelling ? 'กำลังยกเลิก...' : 'ยกเลิกคำสั่งซื้อ' }}
        </button>
        <p class="text-xs text-slate-400 text-center mt-1.5">ยกเลิกได้เฉพาะก่อนชำระเงิน — สต็อกจะถูกคืนอัตโนมัติ</p>
      </div>

      <!-- Actions -->
      <div v-if="canReceive || canReturn || ['completed', 'cancelled', 'refunded'].includes(order.status)" class="box-card p-5 space-y-2">
        <button v-if="canReceive"
          class="w-full h-12 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm flex items-center justify-center gap-2 transition"
          @click="receive">
          <i class="fi fi-rr-box-check"></i> ฉันได้รับสินค้าแล้ว
        </button>
        <button v-if="canReturn"
          class="w-full h-12 rounded-2xl border-2 border-rose-300 text-rose-600 hover:bg-rose-50 font-semibold text-sm flex items-center justify-center gap-2 transition"
          @click="returnOpen = true">
          <i class="fi fi-rr-undo"></i> ขอคืน / เคลมสินค้า
        </button>
        <button v-if="['completed', 'cancelled', 'refunded'].includes(order.status)"
          :disabled="reordering"
          class="w-full h-12 rounded-2xl border-2 border-violet-300 text-violet-700 hover:bg-violet-50 font-semibold text-sm flex items-center justify-center gap-2 transition disabled:opacity-50"
          @click="reorder">
          <i :class="reordering ? 'fi fi-rr-spinner animate-spin' : 'fi fi-rr-refresh'"></i>
          {{ reordering ? 'กำลังโหลด...' : 'สั่งซื้ออีกครั้ง' }}
        </button>
      </div>

      <!-- Status timeline -->
      <div class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-4 flex items-center gap-2">
          <i class="fi fi-rr-time-past text-violet-600"></i> ประวัติสถานะ
        </h3>
        <ol class="relative ml-3">
          <li v-for="(h, idx) in order.status_histories" :key="h.id"
            class="mb-4 pl-5 relative">
            <!-- Line -->
            <div v-if="idx < order.status_histories.length - 1"
              class="absolute left-0 top-3 bottom-0 w-px bg-slate-200"></div>
            <!-- Dot -->
            <div class="absolute left-0 top-1.5 -translate-x-1/2 w-3 h-3 rounded-full border-2 border-white"
              :class="idx === 0 ? 'bg-violet-500 shadow-md shadow-violet-300' : 'bg-slate-300'"></div>
            <!-- Content -->
            <div class="flex items-center justify-between flex-wrap gap-1">
              <OrderStatusChip :status="h.status" />
              <span class="text-[11px] text-slate-400">{{ formatDate(h.created_at) }}</span>
            </div>
            <p v-if="h.note" class="text-xs text-slate-500 mt-1">{{ h.note }}</p>
          </li>
          <li v-if="!order.status_histories?.length" class="text-sm text-slate-400 pl-5">ยังไม่มีประวัติ</li>
        </ol>
      </div>

      <!-- Slip status tracking -->
      <div v-if="order.payments?.length" class="box-card p-5">
        <h3 class="font-semibold text-slate-700 text-sm mb-4 flex items-center gap-2">
          <i class="fi fi-rr-receipt text-violet-600"></i> ประวัติสลิป
        </h3>
        <ol class="space-y-3">
          <li v-for="(pmt, idx) in order.payments" :key="pmt.id"
            class="flex gap-3 p-3 rounded-xl border"
            :class="{
              'border-emerald-200 bg-emerald-50': pmt.status === 'confirmed',
              'border-rose-200 bg-rose-50': pmt.status === 'rejected',
              'border-sky-200 bg-sky-50': pmt.status === 'pending',
            }">
            <!-- Thumbnail -->
            <button v-if="pmt.slip_url" @click="fullscreenSlip = pmt.slip_url"
              class="shrink-0 w-14 h-14 rounded-lg overflow-hidden border border-white/60 shadow-sm">
              <img :src="pmt.slip_url" class="w-full h-full object-cover" />
            </button>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                  :class="{
                    'bg-emerald-100 text-emerald-700': pmt.status === 'confirmed',
                    'bg-rose-100 text-rose-700': pmt.status === 'rejected',
                    'bg-sky-100 text-sky-700': pmt.status === 'pending',
                  }">
                  {{ { confirmed: 'ยืนยันแล้ว', rejected: 'ปฏิเสธ', pending: 'รอยืนยัน' }[pmt.status] || pmt.status }}
                </span>
                <span class="text-xs text-slate-500">{{ formatDate(pmt.paid_at || pmt.created_at) }}</span>
              </div>
              <p class="text-sm font-bold mt-1">฿{{ fmt(pmt.amount) }}</p>
              <p v-if="pmt.reject_reason" class="text-xs text-rose-600 mt-1">เหตุผล: {{ pmt.reject_reason }}</p>
            </div>
            <span class="text-xs text-slate-400 shrink-0">#{{ idx + 1 }}</span>
          </li>
        </ol>
      </div>
    </template>

    <!-- Edit order dialog -->
    <Dialog v-model:visible="editMode" modal header="แก้ไขคำสั่งซื้อ"
      :style="{ width: '95vw', maxWidth: '30rem' }">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="form-label">ชื่อผู้รับ *</label>
            <input v-model="editForm.shipping_name" class="inp" placeholder="ชื่อ-นามสกุล" />
          </div>
          <div>
            <label class="form-label">เบอร์โทรศัพท์ *</label>
            <input v-model="editForm.shipping_phone" class="inp" type="tel" placeholder="08x-xxx-xxxx" />
          </div>
        </div>
        <div>
          <label class="form-label">ที่อยู่ *</label>
          <input v-model="editForm.shipping_address" class="inp" placeholder="บ้านเลขที่ ถนน หมู่บ้าน" />
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="form-label">ตำบล/แขวง</label>
            <input v-model="editForm.shipping_sub_district" class="inp" placeholder="ตำบล" />
          </div>
          <div>
            <label class="form-label">อำเภอ/เขต</label>
            <input v-model="editForm.shipping_district" class="inp" placeholder="อำเภอ" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="form-label">จังหวัด</label>
            <input v-model="editForm.shipping_province" class="inp" placeholder="จังหวัด" />
          </div>
          <div>
            <label class="form-label">รหัสไปรษณีย์</label>
            <input v-model="editForm.shipping_zipcode" class="inp" placeholder="XXXXX" maxlength="5" />
          </div>
        </div>
        <!-- วิธีการจัดส่ง — style เดียวกับหน้า Checkout -->
        <div class="pt-2 border-t border-slate-100">
          <p class="text-xs font-semibold text-slate-700 flex items-center gap-1.5 mb-1">
            <i class="fi fi-rr-truck-side text-violet-600 text-[11px]"></i> วิธีการจัดส่ง
          </p>
          <div v-if="loadingShipping" class="flex items-center gap-2 text-slate-400 text-xs py-2">
            <i class="fi fi-rr-spinner animate-spin text-[11px]"></i> กำลังโหลดตัวเลือก...
          </div>
          <template v-else-if="shippingOptions.length">
            <label v-for="opt in shippingOptions" :key="opt.id"
              class="flex items-start gap-3 py-2 border-b border-slate-100 last:border-0 cursor-pointer hover:bg-violet-50/30 -mx-1 px-1 rounded-lg transition">
              <input type="radio" :value="opt.id" v-model="editForm.shipping_option_id"
                class="mt-0.5 accent-violet-600 shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800">{{ opt.name }}</p>
                <p class="text-xs text-slate-400 mt-0 flex items-center gap-1">
                  <i class="fi fi-rr-clock text-[10px]"></i>
                  {{ opt.days_min === opt.days_max ? `${opt.days_min} วัน` : `${opt.days_min}–${opt.days_max} วัน` }}
                </p>
              </div>
              <span class="shrink-0 text-sm font-semibold"
                :class="Number(opt.fee) === 0 ? 'text-emerald-600' : 'text-slate-700'">
                {{ Number(opt.fee) === 0 ? 'ฟรี' : `฿${Number(opt.fee).toLocaleString()}` }}
              </span>
            </label>
          </template>
          <p v-else class="text-xs text-slate-400 py-1 flex items-center gap-2">
            <i class="fi fi-rr-info text-slate-300"></i> ยังไม่มีบริการจัดส่ง (ติดต่อผู้ขายโดยตรง)
          </p>
        </div>

        <div>
          <label class="form-label">หมายเหตุการจัดส่ง</label>
          <input v-model="editForm.shipping_note" class="inp" placeholder="เช่น ฝากไว้ที่นิติฯ" />
        </div>

        <!-- ช่องทางชำระเงิน — style เดียวกับหน้า Checkout -->
        <template v-if="canEditPayment">
          <div class="pt-2 border-t border-slate-100">
            <p class="text-xs font-semibold text-slate-700 flex items-center gap-1.5 mb-1">
              <i class="fi fi-rr-credit-card text-violet-600 text-[11px]"></i> ช่องทางชำระเงิน
            </p>
            <label v-if="allowedPayments.online"
              class="flex items-start gap-3 py-2 border-b border-slate-100 last:border-0 cursor-pointer hover:bg-violet-50/30 -mx-1 px-1 rounded-lg transition">
              <input type="radio" name="edit-pay" value="bank_transfer" v-model="editForm.payment_method"
                class="mt-0.5 accent-violet-600 shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 flex items-center gap-1.5">
                  <i class="fi fi-rr-bank text-violet-500 text-xs"></i>
                  ชำระเงินในระบบ (โอนเงิน + สลิป)
                </p>
                <p class="text-xs text-slate-400 mt-0">โอนเงินเข้าบัญชี แล้วแนบสลิปยืนยัน</p>
              </div>
            </label>
            <label v-if="allowedPayments.cod"
              class="flex items-start gap-3 py-2 border-b border-slate-100 last:border-0 cursor-pointer hover:bg-violet-50/30 -mx-1 px-1 rounded-lg transition">
              <input type="radio" name="edit-pay" value="cod" v-model="editForm.payment_method"
                class="mt-0.5 accent-violet-600 shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 flex items-center gap-1.5">
                  <i class="fi fi-rr-money-bill-wave text-emerald-500 text-xs"></i>
                  ชำระเงินปลายทาง (COD)
                </p>
                <p class="text-xs text-slate-400 mt-0">เตรียมเงินสดชำระเมื่อรับสินค้า</p>
              </div>
            </label>
            <p v-if="!allowedPayments.online && !allowedPayments.cod" class="text-xs text-slate-400 mt-1">
              ตัวเลือกจัดส่งนี้ไม่รองรับวิธีชำระเงินที่เปิดใช้
            </p>
          </div>
        </template>
        <p v-if="editError" class="text-sm text-rose-500 flex items-center gap-1">
          <i class="fi fi-rr-exclamation"></i> {{ editError }}
        </p>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="editMode = false" />
        <Button label="บันทึก" :loading="saving" @click="saveEdit" />
      </template>
    </Dialog>

    <!-- Return dialog -->
    <Dialog v-model:visible="returnOpen" modal header="ขอคืน / เคลมสินค้า"
      :style="{ width: '95vw', maxWidth: '26rem' }">
      <div class="space-y-3">
        <div>
          <label class="form-label">ประเภท</label>
          <select v-model="ret.type" class="inp">
            <option value="return">ขอคืนสินค้า</option>
            <option value="refund">ขอคืนเงิน</option>
            <option value="claim">เคลม (สินค้าชำรุด)</option>
          </select>
        </div>
        <div>
          <label class="form-label">เหตุผล *</label>
          <input v-model="ret.reason" class="inp" placeholder="เช่น สินค้าชำรุด / ไม่ตรงปก" />
        </div>
        <div>
          <label class="form-label">รายละเอียดเพิ่มเติม</label>
          <textarea v-model="ret.description" rows="3" class="inp"></textarea>
        </div>
        <div>
          <label class="form-label">รูปประกอบ (ไม่บังคับ)</label>
          <input ref="retImgInput" type="file" accept="image/*" multiple class="block w-full text-sm" @change="onRetImgs" />
        </div>
        <p v-if="retError" class="text-sm text-rose-500">{{ retError }}</p>
      </div>
      <template #footer>
        <Button label="ยกเลิก" text @click="returnOpen = false" />
        <Button label="ส่งคำขอ" severity="danger" :loading="retBusy" @click="submitReturn" />
      </template>
    </Dialog>

    <!-- Fullscreen slip viewer -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="fullscreenSlip"
          class="fixed inset-0 z-[9999] bg-black/95 flex items-center justify-center"
          style="touch-action: pinch-zoom;"
          @click.self="fullscreenSlip = null">
          <img :src="fullscreenSlip"
            class="max-w-full max-h-full object-contain select-none"
            style="touch-action: pinch-zoom;" />
          <button @click="fullscreenSlip = null"
            class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition backdrop-blur-sm">
            <i class="fi fi-rr-cross text-lg"></i>
          </button>
          <p class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/40 text-xs">
            Pinch เพื่อ zoom · กดนอกภาพเพื่อปิด
          </p>
        </div>
      </Transition>
    </Teleport>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCart } from '../../composables/useCart.js'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import api from '../../api/index.js'
import Breadcrumb from './components/Breadcrumb.vue'
import OrderStatusChip from './components/OrderStatusChip.vue'
import SlipUploader from './components/SlipUploader.vue'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'

const route   = useRoute()
const router  = useRouter()
const toast   = useToast()
const confirm = useConfirm()
const cart    = useCart()
const orderNo = route.params.orderNo

const order      = ref(null)
const loading    = ref(true)

const sellerNames = computed(() => {
  const names = new Set()
  for (const it of order.value?.items ?? []) {
    const n = it.product?.seller_user?.shop_name || it.product?.seller_user?.name
    if (n) names.add(n)
  }
  return [...names]
})
const submitting = ref(false)
const payError   = ref('')
const fullscreenSlip = ref(null)

// pay: file + auto-detected amount (or fallback to order.total)
const pay = reactive({ file: null, amount: null })

function onSlipChange({ file, amount }) {
  pay.file   = file
  pay.amount = amount
}

const editMode       = ref(false)
const saving         = ref(false)
const editError      = ref('')
const shippingOptions   = ref([])
const loadingShipping   = ref(false)
const editForm  = reactive({
  shipping_name: '', shipping_phone: '', shipping_address: '',
  shipping_sub_district: '', shipping_district: '', shipping_province: '',
  shipping_zipcode: '', shipping_note: '', payment_method: 'bank_transfer',
  shipping_option_id: null,
})

const selectedShippingOpt = computed(() =>
  shippingOptions.value.find(o => o.id === editForm.shipping_option_id) ?? null
)
const allowedPayments = computed(() => {
  const methods = selectedShippingOpt.value?.allowed_payment_methods ?? ['online', 'cod']
  return {
    online: methods.includes('online') || methods.includes('bank_transfer'),
    cod:    methods.includes('cod'),
  }
})

watch(() => editForm.shipping_option_id, () => {
  const ap = allowedPayments.value
  if (!ap.online && editForm.payment_method === 'bank_transfer') editForm.payment_method = 'cod'
  if (!ap.cod   && editForm.payment_method === 'cod')           editForm.payment_method = 'bank_transfer'
})

async function openEdit() {
  const o = order.value
  editForm.shipping_name         = o.shipping_name         || ''
  editForm.shipping_phone        = o.shipping_phone        || ''
  editForm.shipping_address      = o.shipping_address      || ''
  editForm.shipping_sub_district = o.shipping_sub_district || ''
  editForm.shipping_district     = o.shipping_district     || ''
  editForm.shipping_province     = o.shipping_province     || ''
  editForm.shipping_zipcode      = o.shipping_zipcode      || ''
  editForm.shipping_note         = o.shipping_note         || ''
  editForm.payment_method        = o.payment_method        || 'bank_transfer'
  editForm.shipping_option_id    = o.shipping_option_id    ?? null
  editError.value = ''
  editMode.value  = true

  // โหลดตัวเลือกจัดส่งของกลุ่มผู้ขาย
  const gid = o.seller_group_id || o.seller_group?.id
  if (gid) {
    loadingShipping.value = true
    try {
      const { data } = await api.get(`/shop/shipping/by-groups?groups[]=${gid}`)
      shippingOptions.value = data[gid] ?? []
    } catch { shippingOptions.value = [] }
    finally { loadingShipping.value = false }
  }
}

async function saveEdit() {
  saving.value = true
  editError.value = ''
  try {
    await api.patch(`/shop/orders/${orderNo}`, { ...editForm })
    toast.add({ severity: 'success', summary: 'บันทึกแล้ว', detail: 'อัปเดตคำสั่งซื้อเรียบร้อย', life: 2500 })
    editMode.value = false
    await load()
  } catch (e) {
    editError.value = e.response?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally { saving.value = false }
}

const returnOpen   = ref(false)
const retBusy      = ref(false)
const retError     = ref('')
const retImgInput  = ref(null)
const ret = reactive({ type: 'return', reason: '', description: '', images: [] })

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
function formatDate(d) { return d ? new Date(d).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' }) : '' }

const latestPayment = computed(() => order.value?.payments?.[0] || null)
const canPay = computed(() => {
  if (!order.value) return false
  if (['pending_payment'].includes(order.value.status)) return true
  if (latestPayment.value?.status === 'rejected') return true
  return false
})
const canReceive     = computed(() => ['shipped', 'delivered'].includes(order.value?.status))
const canReturn      = computed(() => ['shipped', 'delivered'].includes(order.value?.status))
const canCancel      = computed(() => order.value?.status === 'pending_payment')
const canEdit        = computed(() => ['pending_payment', 'awaiting_confirm'].includes(order.value?.status))
const canEditPayment = computed(() => order.value?.status === 'pending_payment')
const reordering  = ref(false)
const cancelling  = ref(false)

const payStatusLabel = computed(() => ({
  pending:   'แจ้งชำระแล้ว — รอผู้ขายยืนยัน',
  confirmed: 'ยืนยันการชำระเงินแล้ว ✓',
  rejected:  'สลิปไม่ผ่าน — กรุณาแจ้งชำระใหม่',
}[latestPayment.value?.status] || ''))

const payBoxClass = computed(() => ({
  pending:   'bg-sky-50 border border-sky-200 text-sky-800',
  confirmed: 'bg-emerald-50 border border-emerald-200 text-emerald-800',
  rejected:  'bg-rose-50 border border-rose-200 text-rose-800',
}[latestPayment.value?.status] || 'bg-slate-50 border border-slate-200'))

async function load() {
  loading.value = true
  try { order.value = (await api.get(`/shop/my/orders/${orderNo}`)).data }
  finally { loading.value = false }
}

async function submitPayment() {
  if (!pay.file) { payError.value = 'กรุณาแนบสลิป'; return }
  submitting.value = true
  payError.value = ''
  try {
    const fd = new FormData()
    fd.append('amount', pay.amount ?? order.value.total)
    fd.append('slip', pay.file)
    await api.post(`/shop/orders/${orderNo}/payment`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'แจ้งชำระเงินแล้ว', detail: 'รอผู้ขายยืนยัน', life: 3000 })
    pay.file = null; pay.amount = null
    await load()
  } catch (e) {
    payError.value = e.response?.data?.message || e.response?.data?.errors?.slip?.[0] || 'แจ้งชำระไม่สำเร็จ'
  } finally { submitting.value = false }
}

async function copyText(text, label = 'คัดลอกแล้ว') {
  try {
    await navigator.clipboard.writeText(String(text))
    toast.add({ severity: 'success', summary: label, detail: text, life: 2000 })
  } catch {
    toast.add({ severity: 'info', summary: label, detail: text, life: 3000 })
  }
}

async function copyTracking(no) {
  try {
    await navigator.clipboard.writeText(no)
    toast.add({ severity: 'success', summary: 'คัดลอกแล้ว', detail: no, life: 2000 })
  } catch {
    toast.add({ severity: 'info', summary: 'เลขพัสดุ', detail: no, life: 3000 })
  }
}

function receive() {
  confirm.require({
    message: 'ยืนยันว่าได้รับสินค้าครบถ้วนแล้ว?',
    header: 'ยืนยันรับสินค้า', icon: 'fi fi-rr-box-check',
    acceptLabel: 'ได้รับแล้ว', rejectLabel: 'ยกเลิก',
    accept: async () => {
      try { await api.post(`/shop/orders/${orderNo}/receive`); router.push(`/shop/account/orders/${orderNo}/review`) }
      catch (e) { toast.add({ severity: 'error', summary: 'ไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 }) }
    },
  })
}

function onRetImgs(e) { ret.images = Array.from(e.target.files || []) }

async function submitReturn() {
  if (!ret.reason.trim()) { retError.value = 'กรุณาระบุเหตุผล'; return }
  retBusy.value = true; retError.value = ''
  try {
    const fd = new FormData()
    fd.append('type', ret.type); fd.append('reason', ret.reason)
    if (ret.description) fd.append('description', ret.description)
    ret.images.forEach(f => fd.append('images[]', f))
    await api.post(`/shop/orders/${orderNo}/returns`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'ส่งคำขอแล้ว', detail: 'รอผู้ขายตรวจสอบ', life: 2500 })
    returnOpen.value = false; ret.reason = ''; ret.description = ''; ret.images = []
    await load()
  } catch (e) { retError.value = e.response?.data?.message || 'ส่งคำขอไม่สำเร็จ' }
  finally { retBusy.value = false }
}

async function reorder() {
  if (!order.value?.items?.length) return
  reordering.value = true
  let added = 0
  try {
    for (const item of order.value.items) {
      if (!item.product?.slug) continue
      try {
        const { data } = await api.get(`/shop/products/${item.product.slug}`)
        const p = data.product ?? data
        cart.add(p, item.qty)
        added++
      } catch { /* สินค้าอาจถูกลบหรือหมด */ }
    }
    if (added) {
      router.push('/shop/checkout')
    } else {
      toast.add({ severity: 'warn', summary: 'ไม่สามารถสั่งซื้อได้', detail: 'สินค้าอาจหมดหรือถูกลบแล้ว', life: 3000 })
    }
  } finally { reordering.value = false }
}

function cancelOrder() {
  confirm.require({
    message: `ยืนยันยกเลิกคำสั่งซื้อ ${orderNo}?`,
    header: 'ยกเลิกคำสั่งซื้อ', icon: 'fi fi-rr-cross-circle',
    acceptLabel: 'ยกเลิกคำสั่งซื้อ', rejectLabel: 'ไม่ยกเลิก',
    acceptClass: 'p-button-danger',
    accept: async () => {
      cancelling.value = true
      try {
        await api.post(`/shop/orders/${orderNo}/cancel`)
        toast.add({ severity: 'info', summary: 'ยกเลิกแล้ว', detail: 'ยกเลิกคำสั่งซื้อเรียบร้อย', life: 2500 })
        await load()
      } catch (e) {
        toast.add({ severity: 'error', summary: 'ยกเลิกไม่สำเร็จ', detail: e.response?.data?.message || '', life: 3000 })
      } finally { cancelling.value = false }
    },
  })
}

onMounted(load)
</script>

<style scoped>
.inp { width: 100%; height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); font-size: 0.875rem; }
.inp:focus { outline: none; border-color: rgb(167 139 250); box-shadow: 0 0 0 3px rgb(167 139 250 / 0.15); }
textarea.inp { height: auto; padding: 0.6rem 0.75rem; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
