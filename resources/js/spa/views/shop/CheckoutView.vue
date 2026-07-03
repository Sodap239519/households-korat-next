<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 pt-6 pb-72 lg:pb-6">
    <Breadcrumb :items="[{ label: 'ตะกร้า', to: '/shop/cart' }, { label: 'ชำระเงิน' }]" class="mb-4" />

    <div v-if="!activeItems.length" class="box-card p-12 text-center text-slate-400">
      <i class="fi fi-rr-shopping-cart text-4xl"></i>
      <p class="mt-3">ไม่มีสินค้าในตะกร้า</p>
      <RouterLink to="/shop/products" class="text-violet-600 hover:underline mt-2 inline-block">เลือกซื้อสินค้า</RouterLink>
    </div>

    <div v-else class="grid lg:grid-cols-3 gap-5">
      <!-- Left column -->
      <div class="lg:col-span-2 space-y-4">

        <!-- Saved address picker -->
        <div class="box-card p-4">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold text-slate-800 text-sm flex items-center gap-1.5">
              <i class="fi fi-rr-marker text-violet-600 text-xs"></i> ที่อยู่จัดส่ง
            </h3>
            <button @click="openPicker" class="text-xs text-violet-600 hover:text-violet-800 font-medium flex items-center gap-1">
              <i class="fi fi-rr-edit text-[10px]"></i> เปลี่ยน / จัดการ
            </button>
          </div>

          <div v-if="selectedAddr" class="bg-violet-50 border border-violet-200 rounded-lg p-2.5">
            <div class="flex items-start gap-2">
              <i class="fi fi-rr-home text-violet-500 mt-0.5 text-xs"></i>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-800 text-sm">{{ selectedAddr.name }}
                  <span class="text-slate-400 font-normal">· {{ selectedAddr.phone }}</span>
                  <span class="ml-1.5 text-[11px] text-slate-400">· {{ selectedAddr.label }}</span>
                </p>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                  {{ selectedAddr.address }}<template v-if="selectedAddr.sub_district">, {{ selectedAddr.sub_district }}</template><template v-if="selectedAddr.district">, {{ selectedAddr.district }}</template><template v-if="selectedAddr.province">, {{ selectedAddr.province }}</template><template v-if="selectedAddr.postal_code"> {{ selectedAddr.postal_code }}</template>
                </p>
              </div>
            </div>
          </div>

          <div v-else class="border-2 border-dashed border-slate-200 rounded-xl p-4 text-center text-slate-400">
            <i class="fi fi-rr-map-marker-plus text-xl mb-1"></i>
            <p class="text-sm">ยังไม่มีที่อยู่จัดส่ง</p>
            <button @click="openAddNew" class="mt-1 text-sm text-violet-600 hover:underline font-medium">+ เพิ่มที่อยู่ใหม่</button>
          </div>

          <div class="mt-2">
            <label class="form-label text-xs">หมายเหตุ (ไม่บังคับ)</label>
            <textarea v-model="form.shipping_note" rows="1" class="inp w-full text-sm" style="min-height:2rem;resize:none"></textarea>
          </div>
        </div>

        <!-- Per-group shipping options -->
        <div v-if="loadingShipping" class="box-card p-4 text-sm text-slate-400 flex items-center gap-2">
          <i class="fi fi-rr-spinner animate-spin text-[11px]"></i> โหลดบริการจัดส่ง...
        </div>
        <template v-else>
          <div v-for="g in activeGroups" :key="`ship-${g.key}`" class="box-card p-4">
            <h3 class="font-semibold text-slate-800 flex items-center gap-1.5 mb-2 text-sm flex-wrap">
              <i class="fi fi-rr-truck-side text-violet-600 text-xs"></i>
              <span>จัดส่ง —</span>
              <span class="text-violet-600">{{ g.seller_name || g.group_name }}</span>
              <template v-if="g.seller_name">
                <span class="text-slate-300 font-normal">·</span>
                <span class="text-xs text-slate-500 font-normal">{{ g.group_name }}</span>
              </template>
            </h3>

            <template v-if="(groupShipMap[g.group_id] || []).length">
              <label v-for="opt in (groupShipMap[g.group_id] || [])" :key="opt.id"
                class="flex items-start gap-3 py-2 border-b border-slate-100 last:border-0 cursor-pointer hover:bg-violet-50/30 -mx-1 px-1 rounded-lg transition">
                <input type="radio" :name="`ship-group-${g.key}`" :value="opt.id"
                  v-model="selectedGroupShips[g.key]"
                  class="mt-0.5 accent-violet-600 shrink-0" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-slate-800 flex items-center gap-1.5 flex-wrap">
                    {{ opt.name }}
                    <span v-if="opt.carrier" class="text-[10px] bg-sky-50 text-sky-600 border border-sky-100 px-1.5 py-0.5 rounded font-normal">{{ opt.carrier }}</span>
                  </p>
                  <p class="text-xs text-slate-400 mt-0 flex items-center gap-1">
                    <i class="fi fi-rr-clock text-[10px]"></i>
                    {{ opt.days_min === opt.days_max ? `${opt.days_min} วัน` : `${opt.days_min}–${opt.days_max} วัน` }}
                  </p>
                </div>
                <div class="shrink-0 text-sm font-semibold" :class="Number(opt.fee) === 0 ? 'text-emerald-600' : 'text-slate-700'">
                  {{ Number(opt.fee) === 0 ? 'ฟรี' : `฿${fmt(opt.fee)}` }}
                </div>
              </label>
            </template>
            <div v-else class="text-sm text-slate-400 py-1.5 flex items-center gap-2">
              <i class="fi fi-rr-info text-slate-300 text-sm"></i>
              ยังไม่มีบริการจัดส่ง (ติดต่อผู้ขายโดยตรง)
            </div>
          </div>
        </template>

        <!-- Payment method -->
        <div v-if="allowedPayments.length" class="box-card p-4">
          <h3 class="font-semibold text-slate-800 flex items-center gap-1.5 mb-2 text-sm">
            <i class="fi fi-rr-credit-card text-violet-600 text-xs"></i>
            วิธีชำระเงิน
          </h3>
          <div class="space-y-0">
            <label v-if="allowedPayments.includes('online')"
              class="flex items-start gap-3 py-2 border-b border-slate-100 last:border-0 cursor-pointer hover:bg-violet-50/30 -mx-1 px-1 rounded-lg transition">
              <input type="radio" name="pay-method" value="online" v-model="selectedPayment"
                class="mt-0.5 accent-violet-600 shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 flex items-center gap-1.5">
                  <i class="fi fi-rr-bank text-violet-500 text-xs"></i>
                  ชำระเงินในระบบ (โอนเงิน + สลิป)
                </p>
                <p class="text-xs text-slate-400 mt-0">โอนเงินเข้าบัญชี แล้วแนบสลิปยืนยัน</p>
              </div>
            </label>
            <label v-if="allowedPayments.includes('cod')"
              class="flex items-start gap-3 py-2 border-b border-slate-100 last:border-0 cursor-pointer hover:bg-violet-50/30 -mx-1 px-1 rounded-lg transition">
              <input type="radio" name="pay-method" value="cod" v-model="selectedPayment"
                class="mt-0.5 accent-violet-600 shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 flex items-center gap-1.5">
                  <i class="fi fi-rr-money-bill-wave text-emerald-500 text-xs"></i>
                  ชำระเงินปลายทาง (COD)
                </p>
                <p class="text-xs text-slate-400 mt-0">เตรียมเงินสดชำระเมื่อรับสินค้า</p>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Summary (desktop) -->
      <div class="lg:col-span-1 hidden lg:block">
        <div class="box-card p-5 sticky top-20">
          <h3 class="font-bold text-slate-800 mb-3">สรุปคำสั่งซื้อ</h3>
          <div v-for="g in activeGroups" :key="g.key" class="mb-2 pb-2 border-b border-slate-100 last:border-0">
            <p class="text-xs font-semibold text-violet-700 mb-1.5 flex items-center gap-1 flex-wrap">
              <i class="fi fi-rr-shop"></i> {{ g.seller_name || g.group_name }}
              <template v-if="g.seller_name">
                <span class="text-slate-300">·</span>
                <span class="font-normal text-slate-500">{{ g.group_name }}</span>
              </template>
            </p>
            <div v-for="item in g.items" :key="item.product_id + ':' + (item.option_id ?? '')" class="flex justify-between text-sm text-slate-600 gap-2">
              <span class="truncate flex-1 min-w-0">
                {{ item.name }}<span v-if="item.option_name" class="text-violet-500"> ({{ item.option_name }})</span>
                <span class="text-slate-400 font-normal"> จำนวน {{ item.qty }} {{ item.unit || 'ชิ้น' }}</span>
                <span v-if="item.original_price > item.price"
                  class="ml-1 text-[10px] font-bold px-1 py-0.5 rounded-full bg-rose-500 text-white align-middle">
                  -{{ Math.round((1 - item.price / item.original_price) * 100) }}%
                </span>
              </span>
              <span class="shrink-0 text-right">
                <span class="block font-medium">฿{{ fmt(item.price * item.qty) }}</span>
                <span v-if="item.original_price > item.price" class="block text-xs text-slate-400 line-through">฿{{ fmt(item.original_price * item.qty) }}</span>
              </span>
            </div>
            <!-- ค่าจัดส่งของกลุ่มนี้ -->
            <div v-if="getGroupShipOpt(g)" class="flex justify-between text-xs mt-1 pt-1 border-t border-slate-100">
              <span class="text-slate-400 flex items-center gap-1 flex-wrap">
                <i class="fi fi-rr-truck-side text-[9px]"></i>
                {{ getGroupShipOpt(g).name }}
                <span v-if="getGroupShipOpt(g).carrier" class="text-sky-400">({{ getGroupShipOpt(g).carrier }})</span>
              </span>
              <span :class="Number(getGroupShipOpt(g).fee) === 0 ? 'text-emerald-600 font-medium' : 'text-slate-600'">
                {{ Number(getGroupShipOpt(g).fee) === 0 ? 'ฟรี' : `+฿${fmt(getGroupShipOpt(g).fee)}` }}
              </span>
            </div>
          </div>
          <div class="space-y-1 border-t border-slate-100 pt-2 mt-1">
            <div v-if="activeDiscount > 0" class="flex justify-between text-sm">
              <span class="text-emerald-600">ส่วนลดรวม</span>
              <span class="text-emerald-600 font-semibold">-฿{{ fmt(activeDiscount) }}</span>
            </div>
            <div v-if="shippingTotal > 0" class="flex justify-between text-sm">
              <span class="text-slate-500">ค่าจัดส่งรวม</span>
              <span class="text-slate-700">+฿{{ fmt(shippingTotal) }}</span>
            </div>
          </div>
          <div class="flex justify-between items-end pt-2 border-t border-slate-100 mt-2">
            <span class="text-slate-600">ยอดสุทธิ</span>
            <span class="text-2xl font-bold text-fuchsia-700">฿{{ fmt(grandTotal) }}</span>
          </div>
          <p v-if="error" class="text-sm text-rose-500 mt-2">{{ error }}</p>
          <button :disabled="loading || !selectedAddr || !allGroupsHaveShipping" class="btn-sheen w-full mt-4 h-12 rounded-full btn-orange font-semibold disabled:opacity-60" @click="placeOrder">
            {{ loading ? 'กำลังสั่งซื้อ...' : 'ยืนยันสั่งซื้อ' }}
          </button>
          <p v-if="!allGroupsHaveShipping && !loading" class="text-xs text-amber-600 mt-1.5 text-center">กรุณาเลือกบริการจัดส่งให้ครบทุกกลุ่ม</p>
          <p class="text-xs text-slate-400 mt-2 text-center">
            <template v-if="selectedPayment === 'cod'">ชำระเงินสดเมื่อรับสินค้า — ไม่ต้องโอนเงินล่วงหน้า</template>
            <template v-else>ขั้นตอนถัดไปคือแจ้งโอนเงิน + แนบสลิป</template>
          </p>
        </div>
      </div>
    </div>

    <!-- ===== Address Picker Modal ===== -->
    <div v-if="pickerOpen" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm" @click.self="pickerOpen = false">
      <div class="w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-100 shrink-0">
          <h2 class="font-bold text-slate-800">ที่อยู่จัดส่ง</h2>
          <button @click="pickerOpen = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400">
            <i class="fi fi-rr-cross-small text-lg"></i>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <div v-for="addr in addresses" :key="addr.id"
            @click="selectAddr(addr)"
            class="relative border-2 rounded-xl p-4 cursor-pointer transition"
            :class="selectedAddr?.id === addr.id ? 'border-violet-400 bg-violet-50' : 'border-slate-200 hover:border-violet-200'">
            <div class="flex items-start gap-3">
              <div class="mt-0.5">
                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition"
                  :class="selectedAddr?.id === addr.id ? 'border-violet-500 bg-violet-500' : 'border-slate-300'">
                  <div v-if="selectedAddr?.id === addr.id" class="w-2 h-2 rounded-full bg-white"></div>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-semibold text-slate-800">{{ addr.name }}</span>
                  <span class="text-slate-400 text-sm">{{ addr.phone }}</span>
                  <span v-if="addr.is_default" class="text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded font-medium">ค่าเริ่มต้น</span>
                  <span class="text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">{{ addr.label }}</span>
                </div>
                <p class="text-sm text-slate-500 mt-1 leading-relaxed">
                  {{ addr.address }}<template v-if="addr.sub_district">, {{ addr.sub_district }}</template><template v-if="addr.district">, {{ addr.district }}</template><template v-if="addr.province">, {{ addr.province }}</template><template v-if="addr.postal_code"> {{ addr.postal_code }}</template>
                </p>
              </div>
            </div>
            <div class="flex gap-2 mt-3 justify-end">
              <button @click.stop="editAddr(addr)" class="text-xs text-violet-600 hover:underline flex items-center gap-1"><i class="fi fi-rr-edit"></i> แก้ไข</button>
              <button @click.stop="setDefault(addr)" v-if="!addr.is_default" class="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1"><i class="fi fi-rr-star"></i> ตั้งเป็นหลัก</button>
              <button @click.stop="deleteAddr(addr)" class="text-xs text-rose-400 hover:text-rose-600 flex items-center gap-1"><i class="fi fi-rr-trash"></i> ลบ</button>
            </div>
          </div>

          <button @click="openAddNew" class="w-full border-2 border-dashed border-slate-200 rounded-xl p-4 text-slate-400 hover:border-violet-300 hover:text-violet-600 transition flex items-center justify-center gap-2">
            <i class="fi fi-rr-map-marker-plus"></i> เพิ่มที่อยู่ใหม่
          </button>
        </div>
      </div>
    </div>

    <!-- ===== Add/Edit Address Modal ===== -->
    <div v-if="formOpen" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50 backdrop-blur-sm" @click.self="formOpen = false">
      <div class="w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-100 shrink-0">
          <h2 class="font-bold text-slate-800">{{ addrForm.id ? 'แก้ไขที่อยู่' : 'เพิ่มที่อยู่ใหม่' }}</h2>
          <button @click="formOpen = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400">
            <i class="fi fi-rr-cross-small text-lg"></i>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label">ชื่อที่อยู่</label>
              <input v-model="addrForm.label" class="inp w-full" placeholder="บ้าน / ที่ทำงาน" />
            </div>
            <div>
              <label class="form-label">ชื่อผู้รับ *</label>
              <input v-model="addrForm.name" class="inp w-full" />
            </div>
            <div class="col-span-2">
              <label class="form-label">เบอร์โทร *</label>
              <input v-model="addrForm.phone" class="inp w-full" type="tel" />
            </div>
            <div class="col-span-2">
              <label class="form-label">บ้านเลขที่ หมู่ ถนน *</label>
              <input v-model="addrForm.address" class="inp w-full" />
            </div>
            <ThaiAddressFields
              v-model:subDistrict="addrForm.sub_district"
              v-model:district="addrForm.district"
              v-model:province="addrForm.province"
              v-model:postalCode="addrForm.postal_code"
            />
          </div>
          <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
            <input type="checkbox" v-model="addrForm.is_default" class="accent-violet-500" />
            ตั้งเป็นที่อยู่หลัก
          </label>
          <p v-if="addrErr" class="text-sm text-rose-500">{{ addrErr }}</p>
        </div>

        <div class="p-4 border-t border-slate-100 shrink-0 flex gap-2">
          <button @click="formOpen = false" class="flex-1 h-11 rounded-full border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50">ยกเลิก</button>
          <button @click="saveAddr" :disabled="addrSaving" class="flex-1 h-11 rounded-full btn-orange btn-sheen font-semibold text-sm disabled:opacity-60">
            {{ addrSaving ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ===== Mobile sticky summary card ===== -->
    <div v-if="activeItems.length" class="lg:hidden fixed bottom-16 left-0 right-0 z-30 bg-white rounded-t-2xl shadow-[0_-8px_28px_rgba(0,0,0,.13)]">

      <!-- TOP HANDLE -->
      <div class="flex flex-col items-center gap-0.5 pt-2 pb-1"
           :class="hasManyItems ? 'cursor-pointer active:opacity-60' : ''"
           @click="hasManyItems ? summaryExpanded = !summaryExpanded : null">
        <div class="w-9 h-1 rounded-full bg-slate-200"></div>
        <div v-if="hasManyItems" class="flex items-center gap-1 mt-0.5">
          <i class="fi fi-rr-angle-up text-slate-400 text-[10px] transition-transform duration-200"
             :style="summaryExpanded ? 'transform:rotate(180deg)' : ''"></i>
          <span class="text-[10px] text-slate-400 leading-none select-none">
            {{ summaryExpanded ? 'ย่อรายละเอียดลง' : 'เลื่อนขึ้นเพื่อดูรายละเอียด' }}
          </span>
        </div>
      </div>

      <transition :name="hasManyItems ? 'sum-slide' : ''">
        <div v-show="!hasManyItems || summaryExpanded" class="overflow-hidden">
          <div class="px-4 pt-3 pb-1.5 border-b border-slate-100"
               :class="hasManyItems ? 'max-h-48 overflow-y-auto' : ''">
            <div v-for="g in activeGroups" :key="g.key" class="mb-1.5">
              <p class="text-[10px] font-semibold text-violet-600 flex items-center gap-0.5 mb-0.5 flex-wrap">
                <i class="fi fi-rr-shop text-[9px]"></i> {{ g.seller_name || g.group_name }}
                <template v-if="g.seller_name">
                  <span class="text-slate-300">·</span>
                  <span class="font-normal text-slate-500">{{ g.group_name }}</span>
                </template>
              </p>
              <div v-for="item in g.items" :key="item.product_id + ':' + (item.option_id ?? '')"
                class="flex justify-between text-xs text-slate-600 py-0.5">
                <span class="truncate flex-1 mr-2">
                  {{ item.name }}<span v-if="item.option_name" class="text-violet-500"> ({{ item.option_name }})</span>
                  <span class="text-slate-400"> จำนวน {{ item.qty }} {{ item.unit || 'ชิ้น' }}</span>
                  <span v-if="item.original_price > item.price"
                    class="ml-1 text-[9px] font-bold px-1 rounded-full bg-rose-500 text-white align-middle">
                    -{{ Math.round((1 - item.price / item.original_price) * 100) }}%
                  </span>
                </span>
                <span class="shrink-0 font-medium">฿{{ fmt(item.price * item.qty) }}</span>
              </div>
              <!-- ค่าจัดส่งกลุ่มนี้ -->
              <div v-if="getGroupShipOpt(g)" class="flex justify-between text-[11px] border-t border-slate-100 pt-1 mt-0.5">
                <span class="text-slate-400 flex items-center gap-1">
                  <i class="fi fi-rr-truck-side text-[9px]"></i> {{ getGroupShipOpt(g).name }}
                  <span v-if="getGroupShipOpt(g).carrier" class="text-sky-400">({{ getGroupShipOpt(g).carrier }})</span>
                </span>
                <span :class="Number(getGroupShipOpt(g).fee) === 0 ? 'text-emerald-500 font-medium' : 'text-slate-500'">
                  {{ Number(getGroupShipOpt(g).fee) === 0 ? 'ฟรี' : `+฿${fmt(getGroupShipOpt(g).fee)}` }}
                </span>
              </div>
            </div>
            <div v-if="activeDiscount > 0" class="flex justify-between text-[11px] border-t border-slate-100 pt-1 mt-0.5">
              <span class="text-emerald-600">ส่วนลด</span>
              <span class="text-emerald-600 font-semibold">-฿{{ fmt(activeDiscount) }}</span>
            </div>
          </div>
        </div>
      </transition>

      <!-- Bottom bar -->
      <div class="px-4 pb-3">
        <div class="flex items-center justify-between py-2">
          <span class="text-[11px] text-slate-400">สรุปคำสั่งซื้อ</span>
          <span class="text-xl font-bold text-fuchsia-700">฿{{ fmt(grandTotal) }}</span>
        </div>
        <button :disabled="loading || !selectedAddr || !allGroupsHaveShipping"
          class="btn-sheen w-full h-11 rounded-full btn-orange font-semibold text-sm disabled:opacity-60"
          @click="placeOrder">
          {{ loading ? 'กำลังสั่งซื้อ...' : 'ยืนยันสั่งซื้อ' }}
        </button>
        <p v-if="!allGroupsHaveShipping && !loading" class="text-[11px] text-amber-600 mt-1 text-center">กรุณาเลือกบริการจัดส่งให้ครบทุกกลุ่ม</p>
        <p v-if="error" class="text-xs text-rose-500 mt-1.5">{{ error }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute, onBeforeRouteLeave } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '../../api/index.js'
import { useCart } from '../../composables/useCart.js'
import { useBuyNow } from '../../composables/useBuyNow.js'
import { useAuth } from '../../composables/useAuth.js'
import Breadcrumb from './components/Breadcrumb.vue'
import ThaiAddressFields from './components/ThaiAddressFields.vue'

const router  = useRouter()
const route   = useRoute()
const toast   = useToast()
const cart    = useCart()
const buyNow  = useBuyNow()
const { user } = useAuth()

const isBuyNow    = computed(() => route.query.buynow === '1')
const activeItems  = computed(() => isBuyNow.value ? buyNow.items.value  : cart.selectedItems.value)
const activeGroups   = computed(() => isBuyNow.value ? buyNow.groups.value : cart.selectedGroups.value)
const activeDiscount = computed(() => {
  const items = activeGroups.value.flatMap(g => g.items)
  return items.reduce((s, i) => {
    const orig = Number(i.original_price ?? i.price ?? 0)
    const cur  = Number(i.price ?? 0)
    return s + Math.max(0, (orig - cur) * i.qty)
  }, 0)
})

// ========== Shipping (per-group) ==========
const loadingShipping    = ref(false)
const groupShipMap       = ref({})   // { [group_id]: [{id, name, fee, ...}] }
const selectedGroupShips = reactive({}) // { [group_id]: option_id }
const selectedPayment    = ref('online')
const summaryExpanded    = ref(true)

const hasManyItems = computed(() => {
  const totalItems = activeGroups.value.reduce((n, g) => n + g.items.length, 0)
  return totalItems > 2 || activeGroups.value.length > 1
})

// เลือกจัดส่งตาม "ร้านย่อย" (g.key) แต่ตัวเลือกโหลดตามโซน (g.group_id — ร้านในโซนเดียวกันใช้ตัวเลือกร่วมกัน)
function getGroupShipOpt(g) {
  const optId = selectedGroupShips[g.key]
  const opts  = groupShipMap.value[g.group_id] || []
  return opts.find(o => o.id === optId) || null
}

const allGroupsHaveShipping = computed(() =>
  activeGroups.value.every(g => {
    const opts = groupShipMap.value[g.group_id] || []
    return !opts.length || selectedGroupShips[g.key] != null
  })
)

const allowedPayments = computed(() => {
  const perGroup = activeGroups.value.map(g => {
    const opt = getGroupShipOpt(g)
    return opt?.allowed_payment_methods?.length ? opt.allowed_payment_methods : ['online', 'cod']
  })
  if (!perGroup.length) return ['online', 'cod']
  return perGroup.reduce((a, b) => a.filter(x => b.includes(x)))
})

watch(allowedPayments, (methods) => {
  if (!methods.includes(selectedPayment.value)) {
    selectedPayment.value = methods[0] || 'online'
  }
})

const shippingTotal = computed(() =>
  activeGroups.value.reduce((sum, g) => {
    const opt = getGroupShipOpt(g)
    return sum + (opt ? Number(opt.fee) : 0)
  }, 0)
)

const grandTotal = computed(() => {
  const base = isBuyNow.value ? buyNow.subtotal.value : cart.subtotal.value
  return base + shippingTotal.value
})

async function loadShipping() {
  const gids = [...new Set(activeGroups.value.map(g => g.group_id).filter(Boolean))]
  if (!gids.length) return
  loadingShipping.value = true
  try {
    const params = new URLSearchParams()
    gids.forEach(id => params.append('groups[]', id))
    const { data } = await api.get(`/shop/shipping/by-groups?${params}`)
    groupShipMap.value = data || {}
    // auto-select default ต่อร้านย่อย (ตาม g.key)
    for (const g of activeGroups.value) {
      const opts = groupShipMap.value[g.group_id] || []
      if (selectedGroupShips[g.key] == null && opts.length) {
        const def = opts.find(o => o.is_default) || opts[0]
        if (def) selectedGroupShips[g.key] = def.id
      }
    }
  } catch {
    groupShipMap.value = {}
  } finally {
    loadingShipping.value = false
  }
}

// ออกจากหน้าก่อนชำระเงิน
let orderPlaced = false
onBeforeRouteLeave((to, from, next) => {
  if (orderPlaced || !activeItems.value.length) { next(); return }
  const discount = activeDiscount.value
  const msg = discount > 0
    ? `ส่วนลด ฿${Number(discount).toLocaleString('th-TH', { maximumFractionDigits: 0 })} ของคุณอาจหายไป หากออกจากหน้าชำระเงิน\nต้องการออกจากหน้านี้หรือไม่?`
    : 'ออกจากหน้าชำระเงินหรือไม่? ข้อมูลที่กรอกจะหายไป'
  if (confirm(msg)) { next() } else { next(false) }
})

// ========== Addresses ==========
const addresses    = ref([])
const selectedAddr = ref(null)
const pickerOpen   = ref(false)
const formOpen     = ref(false)
const addrErr      = ref('')
const addrSaving   = ref(false)

const blankAddr = () => ({ id: null, label: 'บ้าน', name: user.value?.name || '', phone: '', address: '', sub_district: '', district: '', province: 'นครราชสีมา', postal_code: '', is_default: false })
const addrForm = reactive(blankAddr())

async function loadAddresses() {
  try {
    const { data } = await api.get('/shop/my/addresses')
    addresses.value = data
    if (!selectedAddr.value) {
      selectedAddr.value = data.find(a => a.is_default) || data[0] || null
    }
  } catch { /* not logged in or no addresses */ }
}

function selectAddr(addr) {
  selectedAddr.value = addr
  pickerOpen.value = false
}

function openPicker() { pickerOpen.value = true }

function openAddNew() {
  Object.assign(addrForm, blankAddr())
  pickerOpen.value = false
  formOpen.value = true
}

function editAddr(addr) {
  Object.assign(addrForm, { ...addr })
  pickerOpen.value = false
  formOpen.value = true
}

async function setDefault(addr) {
  await api.post(`/shop/my/addresses/${addr.id}/default`)
  await loadAddresses()
}

async function deleteAddr(addr) {
  if (!confirm(`ลบที่อยู่ "${addr.label}" ออก?`)) return
  await api.delete(`/shop/my/addresses/${addr.id}`)
  if (selectedAddr.value?.id === addr.id) selectedAddr.value = null
  await loadAddresses()
}

async function saveAddr() {
  if (!addrForm.name || !addrForm.phone || !addrForm.address) {
    addrErr.value = 'กรุณากรอกชื่อผู้รับ เบอร์โทร และที่อยู่'
    return
  }
  addrSaving.value = true
  addrErr.value = ''
  try {
    const payload = { label: addrForm.label, name: addrForm.name, phone: addrForm.phone, address: addrForm.address, sub_district: addrForm.sub_district, district: addrForm.district, province: addrForm.province, postal_code: addrForm.postal_code, is_default: addrForm.is_default }
    if (addrForm.id) {
      const { data } = await api.put(`/shop/my/addresses/${addrForm.id}`, payload)
      if (selectedAddr.value?.id === addrForm.id) selectedAddr.value = data
    } else {
      const { data } = await api.post('/shop/my/addresses', payload)
      selectedAddr.value = data
    }
    formOpen.value = false
    await loadAddresses()
  } catch (e) {
    addrErr.value = e.response?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    addrSaving.value = false
  }
}

// ========== Checkout ==========
const form    = reactive({ shipping_note: '' })
const err     = reactive({})
const error   = ref('')
const loading = ref(false)

function fmt(v) { return Number(v).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }

async function placeOrder() {
  if (!selectedAddr.value) { error.value = 'กรุณาเลือกที่อยู่จัดส่ง'; return }
  if (!allGroupsHaveShipping.value) { error.value = 'กรุณาเลือกบริการจัดส่งให้ครบทุกกลุ่ม'; return }
  loading.value = true
  error.value = ''
  try {
    const a = selectedAddr.value
    const items = activeItems.value.map(i => ({ product_id: i.product_id, option_id: i.option_id ?? null, qty: i.qty }))
    const group_shippings = activeGroups.value.map(g => ({
      group_id: g.group_id,
      seller_user_id: g.seller_id ?? null,
      shipping_option_id: selectedGroupShips[g.key] ?? null,
    }))
    const { data } = await api.post('/shop/checkout', {
      shipping_name: a.name, shipping_phone: a.phone, shipping_address: a.address,
      shipping_sub_district: a.sub_district, shipping_district: a.district,
      shipping_province: a.province, shipping_zipcode: a.postal_code,
      shipping_note: form.shipping_note, items,
      group_shippings,
      payment_method: selectedPayment.value,
    })
    orderPlaced = true
    // ลบเฉพาะรายการที่เพิ่งสั่งซื้อ (เลือกไว้) — รายการที่ไม่ได้เลือกยังอยู่ในตะกร้า
    isBuyNow.value ? buyNow.clear() : cart.clearSelected()
    toast.add({ severity: 'success', summary: 'สั่งซื้อสำเร็จ', detail: `สร้าง ${data.order_nos.length} คำสั่งซื้อ`, life: 2500 })
    router.push(`/shop/account/orders/${data.order_nos[0]}`)
  } catch (e) {
    if (e.response?.status === 422) {
      Object.entries(e.response.data.errors || {}).forEach(([k, v]) => { err[k] = v[0] })
      error.value = e.response.data.message || 'กรุณาตรวจสอบข้อมูล'
    } else {
      error.value = e.response?.data?.message || 'สั่งซื้อไม่สำเร็จ'
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadAddresses()
  await loadShipping()
})
</script>

<style scoped>
.inp { height: 2.75rem; padding: 0 0.75rem; border-radius: 0.75rem; border: 1px solid rgb(226 232 240); }
.inp:focus { outline: none; border-color: rgb(167 139 250); }
textarea.inp { height: auto; padding: 0.5rem 0.75rem; }

.sum-slide-enter-active, .sum-slide-leave-active {
  transition: max-height 0.25s ease, opacity 0.2s ease;
  max-height: 320px;
}
.sum-slide-enter-from, .sum-slide-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>
