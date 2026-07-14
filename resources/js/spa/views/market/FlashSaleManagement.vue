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

    <!-- Tabs + Date filter -->
    <div class="space-y-2">
      <div class="flex gap-2">
        <button v-for="t in tabs" :key="t.key" @click="switchTab(t.key)"
          class="flex-1 py-2 rounded-xl text-sm font-medium transition text-center"
          :class="activeTab === t.key
            ? 'bg-orange-500 text-white shadow-sm'
            : 'bg-white border border-slate-200 text-slate-600 hover:border-orange-300'">
          {{ t.label }}
        </button>
      </div>
      <!-- Date range filter (ซ่อนใน Calendar tab) -->
      <div v-if="activeTab !== 'calendar'" class="flex items-center gap-1.5">
        <input
          v-model="filterFrom"
          type="date"
          class="flex-1 min-w-0 border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-orange-400 bg-white"
        />
        <span class="text-slate-300 text-xs shrink-0">—</span>
        <input
          v-model="filterTo"
          type="date"
          class="flex-1 min-w-0 border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-orange-400 bg-white"
        />
        <button
          v-if="filterFrom || filterTo"
          @click="clearFilter"
          class="w-7 h-7 shrink-0 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition"
          title="ล้างตัวกรอง"
        >
          <i class="fi fi-rr-cross text-[10px]"></i>
        </button>
      </div>
    </div>

    <!-- ===== Tab: Active events ===== -->
    <template v-if="activeTab === 'active'">
      <div v-if="loadingEvents" class="box-card text-center py-14 text-slate-400">
        <i class="fi fi-rr-loading text-2xl animate-spin"></i>
      </div>
      <template v-else>
        <div v-if="!events.length" class="box-card text-center py-14">
          <i class="fi fi-rr-calendar text-3xl text-orange-200"></i>
          <p class="mt-3 text-sm font-medium text-slate-500">
            {{ filterFrom || filterTo ? 'ไม่พบ Flash Sale ในช่วงเวลานี้' : 'ไม่มี Flash Sale ที่กำลังดำเนินการหรือกำลังจะมาถึง' }}
          </p>
          <p v-if="isSuperAdmin && !filterFrom && !filterTo" class="text-xs text-slate-400 mt-1">กดปุ่ม "สร้าง Event" ด้านบนเพื่อเริ่มต้น</p>
        </div>

        <div v-else class="space-y-3">
          <div v-for="ev in events" :key="ev.id" class="box-card overflow-hidden p-0">

            <div class="p-4">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                  :class="{
                    'bg-green-100 text-green-600':  ev.is_currently_active,
                    'bg-blue-100 text-blue-500':    ev.is_upcoming && !ev.is_currently_active,
                    'bg-slate-100 text-slate-400':  !ev.is_active || (!ev.is_currently_active && !ev.is_upcoming),
                  }">
                  <i class="fi fi-rr-bolt text-base"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="font-semibold text-slate-800 text-sm">{{ ev.title }}</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                      :class="{
                        'bg-green-100 text-green-700':  ev.is_currently_active,
                        'bg-blue-100 text-blue-600':    ev.is_upcoming && !ev.is_currently_active,
                        'bg-slate-100 text-slate-500':  !ev.is_active || (!ev.is_currently_active && !ev.is_upcoming),
                      }">{{ ev.status_label }}</span>
                    <span class="text-[10px] text-slate-400">{{ ev.items_count }} สินค้า</span>
                  </div>
                  <p class="text-xs text-slate-500 mt-0.5">
                    <i class="fi fi-rr-calendar text-[10px] mr-1"></i>
                    {{ fmtDate(ev.starts_at) }} — {{ fmtDate(ev.ends_at) }}
                  </p>
                </div>
              </div>
            </div>

            <div class="px-4 pb-4 flex gap-2 border-t border-slate-50 pt-3">
              <button @click="toggleItems(ev)"
                :class="[
                  'flex-1 py-2 text-xs rounded-xl flex items-center justify-center gap-1.5 font-medium transition',
                  selectedEvent?.id === ev.id ? 'bg-violet-700 text-white' : 'bg-violet-600 text-white hover:bg-violet-700',
                ]">
                <i class="fi fi-rr-list"></i>
                {{ selectedEvent?.id === ev.id ? 'ซ่อนสินค้า' : 'จัดการสินค้า' }}
              </button>
              <template v-if="isSuperAdmin">
                <button @click="openEventDialog(ev)"
                  class="px-3 py-2 text-xs rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center gap-1 transition">
                  <i class="fi fi-rr-edit"></i> แก้ไข
                </button>
                <button @click="deleteEvent(ev)" :disabled="deletingEventId === ev.id"
                  class="px-3 py-2 text-xs rounded-xl border border-red-100 text-red-400 hover:bg-red-50 disabled:opacity-40 transition">
                  <i class="fi fi-rr-trash"></i>
                </button>
              </template>
            </div>

            <!-- Inline items panel -->
            <div v-if="selectedEvent?.id === ev.id" class="border-t border-violet-100 bg-slate-50/60">
              <div class="p-4 space-y-3">
                <div class="flex items-center justify-between">
                  <p class="text-xs font-semibold text-violet-700 flex items-center gap-1">
                    <i class="fi fi-rr-box-open text-violet-400"></i> สินค้าใน event
                  </p>
                  <button @click="openAddPanel"
                    class="flex items-center gap-1 px-3 py-1.5 text-xs rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition">
                    <i class="fi fi-rr-plus"></i> เพิ่มสินค้า
                  </button>
                </div>
                <template v-if="addPanelOpen">
                  <div class="rounded-xl border border-orange-200 bg-white p-3 space-y-2">
                    <div class="flex items-center justify-between mb-1">
                      <p class="text-xs font-medium text-orange-700">เลือกสินค้า + ระบุราคา Flash Sale</p>
                      <button @click="closeAddPanel" class="text-slate-400 hover:text-slate-600">
                        <i class="fi fi-rr-cross text-[10px]"></i>
                      </button>
                    </div>
                    <input v-model="addSearch" type="text" placeholder="ค้นหาสินค้า..."
                      class="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-400" />
                    <div v-if="loadingAll" class="text-center py-4 text-slate-400 text-xs">
                      <i class="fi fi-rr-loading animate-spin"></i>
                    </div>
                    <div v-else class="max-h-64 overflow-y-auto space-y-2 pr-0.5">
                      <div v-for="p in filteredAll" :key="p.id" @click="toggleEventSelect(p)"
                        :class="[
                          'rounded-xl border cursor-pointer select-none transition-all flex items-center gap-3 bg-white p-3',
                          eventAddSelected.find(s => s.product_id === p.id) ? 'border-orange-400 ring-2 ring-orange-100' : 'border-slate-100 hover:border-orange-300 hover:shadow-sm',
                          inCurrentEvent(p.id) ? 'opacity-40 pointer-events-none' : '',
                        ]">
                        <img :src="primaryImage(p)" class="w-14 h-14 rounded-xl object-cover bg-slate-100 shrink-0"
                          @error="e => e.target.src = '/storage/placeholder.png'" />
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-semibold text-slate-800 line-clamp-2 leading-snug">{{ p.name }}</p>
                          <p class="text-xs text-slate-500 mt-0.5">ราคาปกติ {{ fmt(p.price) }} บาท</p>
                          <span v-if="inCurrentEvent(p.id)" class="text-[10px] text-violet-500 font-medium">✓ อยู่ใน event แล้ว</span>
                        </div>
                        <div v-if="eventAddSelected.find(s => s.product_id === p.id)" @click.stop class="shrink-0">
                          <label class="text-[10px] text-orange-600 font-medium block mb-1 text-center">ราคา Flash Sale</label>
                          <input v-model.number="eventAddSelected.find(s => s.product_id === p.id).sale_price"
                            type="number" min="0" step="0.01" placeholder="0.00"
                            class="w-24 border-2 border-orange-400 rounded-xl px-2 py-1.5 text-sm text-center font-bold text-orange-600 focus:outline-none focus:border-orange-500" />
                        </div>
                        <div v-if="eventAddSelected.find(s => s.product_id === p.id)"
                          class="w-5 h-5 rounded-full bg-orange-500 flex items-center justify-center shrink-0 shadow-sm">
                          <i class="fi fi-rr-check text-[9px] text-white"></i>
                        </div>
                      </div>
                      <p v-if="!filteredAll.length" class="text-center text-sm text-slate-400 py-4">ไม่พบสินค้า</p>
                    </div>
                    <div v-if="eventAddSelected.length" class="flex items-center justify-between pt-2 border-t border-orange-100">
                      <p class="text-xs text-orange-700">เลือก {{ eventAddSelected.length }} รายการ</p>
                      <button @click="submitAddToEvent" :disabled="eventAddSaving"
                        class="flex items-center gap-1 px-3 py-1.5 text-xs rounded-lg bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-50">
                        <i v-if="eventAddSaving" class="fi fi-rr-loading animate-spin"></i>
                        <i v-else class="fi fi-rr-bolt"></i>
                        เพิ่มเข้า Event
                      </button>
                    </div>
                  </div>
                </template>
                <div v-if="loadingItems" class="text-center py-8 text-slate-400 text-xs">
                  <i class="fi fi-rr-loading animate-spin text-lg"></i>
                </div>
                <div v-else-if="!eventItems.length" class="text-center py-8 text-slate-400">
                  <i class="fi fi-rr-shopping-cart text-2xl text-slate-200"></i>
                  <p class="mt-2 text-xs">ยังไม่มีสินค้าใน event นี้</p>
                </div>
                <div v-else class="max-h-64 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-100 bg-white">
                  <div v-for="item in eventItems" :key="item.id" class="flex items-center gap-2 px-3 py-2">
                    <i class="fi fi-rr-bolt text-orange-400 text-xs shrink-0"></i>
                    <p class="flex-1 min-w-0 text-xs text-slate-700 line-clamp-1">{{ item.product?.name }}</p>
                    <div class="flex items-center gap-1 shrink-0">
                      <span class="text-xs font-bold text-orange-600">{{ fmt(item.sale_price) }}</span>
                      <span class="text-[10px] text-slate-300 line-through">{{ fmt(item.product?.price) }}</span>
                      <span class="text-[9px] bg-red-100 text-red-500 font-bold px-1 py-0.5 rounded-full">
                        -{{ discountPct(item.product?.price, item.sale_price) }}%
                      </span>
                    </div>
                    <button @click="removeEventItem(item)" :disabled="removingItemId === item.id"
                      class="text-red-300 hover:text-red-500 p-1 disabled:opacity-40 transition shrink-0">
                      <i class="fi fi-rr-trash text-[10px]"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </template>
    </template>

    <!-- ===== Tab: History ===== -->
    <template v-else-if="activeTab === 'history'">
      <div v-if="loadingHistory" class="box-card text-center py-14 text-slate-400">
        <i class="fi fi-rr-loading text-2xl animate-spin"></i>
      </div>

      <!-- Superadmin history -->
      <template v-else-if="isSuperAdmin">
        <div v-if="!historyEvents.length" class="box-card text-center py-14">
          <i class="fi fi-rr-history text-3xl text-slate-200"></i>
          <p class="mt-3 text-sm text-slate-400">
            {{ filterFrom || filterTo ? 'ไม่พบประวัติในช่วงเวลานี้' : 'ยังไม่มีประวัติ Flash Sale' }}
          </p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="ev in historyEvents" :key="ev.id" class="box-card p-4 space-y-3">
            <div class="flex items-start gap-3">
              <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                <i class="fi fi-rr-bolt text-base"></i>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-1.5">
                  <span class="font-semibold text-slate-700 text-sm">{{ ev.title }}</span>
                  <span class="text-[10px] bg-slate-100 text-slate-500 font-medium px-1.5 py-0.5 rounded-full">สิ้นสุดแล้ว</span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">
                  <i class="fi fi-rr-calendar text-[10px] mr-1"></i>
                  {{ fmtDate(ev.starts_at) }} — {{ fmtDate(ev.ends_at) }}
                </p>
              </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div class="rounded-xl bg-orange-50 border border-orange-100 px-3 py-2 text-center">
                <p class="text-[10px] text-orange-400 font-medium">สินค้าร่วม</p>
                <p class="text-sm font-bold text-orange-700 mt-0.5">{{ ev.items_count }}</p>
              </div>
              <div class="rounded-xl bg-violet-50 border border-violet-100 px-3 py-2 text-center">
                <p class="text-[10px] text-violet-400 font-medium">ขายได้</p>
                <p class="text-sm font-bold text-violet-700 mt-0.5">{{ ev.sold_qty }} ชิ้น</p>
              </div>
              <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-3 py-2 text-center">
                <p class="text-[10px] text-emerald-400 font-medium">รายได้รวม</p>
                <p class="text-sm font-bold text-emerald-700 mt-0.5">฿{{ fmt(ev.revenue) }}</p>
              </div>
            </div>
            <button @click="toggleHistoryItems(ev)"
              class="w-full py-2 text-xs rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center gap-1.5 font-medium transition">
              <i class="fi fi-rr-list"></i>
              {{ selectedHistoryEvent?.id === ev.id ? 'ซ่อนสินค้า' : 'ดูสินค้าใน event' }}
            </button>
            <div v-if="selectedHistoryEvent?.id === ev.id && historyItems.length"
              class="divide-y divide-slate-100 rounded-xl border border-slate-100 overflow-hidden">
              <div v-for="item in historyItems" :key="item.id" class="flex items-center gap-2 px-3 py-2 bg-white">
                <i class="fi fi-rr-bolt text-orange-300 text-xs shrink-0"></i>
                <p class="flex-1 text-xs text-slate-700 truncate">{{ item.product?.name }}</p>
                <span class="text-xs font-bold text-orange-600 shrink-0">{{ fmt(item.sale_price) }}</span>
                <span class="text-[10px] text-slate-400 line-through shrink-0">{{ fmt(item.product?.price) }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Seller history -->
      <template v-else>
        <div v-if="!sellerHistory.length" class="box-card text-center py-14">
          <i class="fi fi-rr-history text-3xl text-slate-200"></i>
          <p class="mt-3 text-sm text-slate-400">
            {{ filterFrom || filterTo ? 'ไม่พบประวัติในช่วงเวลานี้' : 'ยังไม่มีสินค้าที่เคยเข้าร่วม Flash Sale' }}
          </p>
        </div>
        <div v-else class="space-y-2.5">
          <div v-for="item in sellerHistory" :key="item.id" class="box-card p-4">
            <div class="flex items-start gap-3">
              <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-400 flex items-center justify-center shrink-0">
                <i class="fi fi-rr-bolt text-sm"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-800 text-sm truncate">{{ item.product?.name }}</p>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  <i class="fi fi-rr-calendar text-[10px] mr-0.5"></i>
                  {{ item.event?.title }} · {{ fmtDateShort(item.event?.ends_at) }}
                </p>
              </div>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-3">
              <div class="rounded-xl bg-orange-50 border border-orange-100 px-2 py-2 text-center">
                <p class="text-[10px] text-orange-400 font-medium">ราคา Flash</p>
                <p class="text-sm font-bold text-orange-600 mt-0.5">฿{{ fmt(item.sale_price) }}</p>
                <p class="text-[10px] text-slate-400 line-through">฿{{ fmt(item.product?.price) }}</p>
              </div>
              <div class="rounded-xl bg-violet-50 border border-violet-100 px-2 py-2 text-center">
                <p class="text-[10px] text-violet-400 font-medium">ขายได้</p>
                <p class="text-sm font-bold text-violet-700 mt-0.5">{{ item.sold_qty }} ชิ้น</p>
              </div>
              <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-2 py-2 text-center">
                <p class="text-[10px] text-emerald-400 font-medium">รายได้</p>
                <p class="text-sm font-bold text-emerald-700 mt-0.5">฿{{ fmt(item.revenue) }}</p>
              </div>
            </div>
          </div>
        </div>
      </template>
    </template>

    <!-- ===== Tab: Calendar ===== -->
    <template v-else-if="activeTab === 'calendar'">
      <!-- Calendar card -->
      <div class="box-card p-4 space-y-3">
        <!-- Month navigation -->
        <div class="flex items-center justify-between">
          <button @click="prevMonth"
            class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-orange-100 text-slate-600 hover:text-orange-600 flex items-center justify-center transition">
            <i class="fi fi-rr-angle-left text-sm"></i>
          </button>
          <h3 class="font-bold text-slate-700 text-sm">{{ calendarMonthLabel }}</h3>
          <button @click="nextMonth"
            class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-orange-100 text-slate-600 hover:text-orange-600 flex items-center justify-center transition">
            <i class="fi fi-rr-angle-right text-sm"></i>
          </button>
        </div>

        <div v-if="loadingCalendar" class="text-center py-10 text-slate-400">
          <i class="fi fi-rr-loading text-xl animate-spin"></i>
        </div>

        <template v-else>
          <!-- Day headers -->
          <div class="grid grid-cols-7 text-center">
            <div v-for="d in ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส']" :key="d"
              class="text-[11px] font-semibold text-slate-400 py-1">{{ d }}</div>
          </div>

          <!-- Calendar grid -->
          <div class="grid grid-cols-7 gap-1">
            <div
              v-for="cell in calendarCells"
              :key="cell.key"
              @click="selectCalendarDate(cell)"
              class="rounded-lg p-1 flex flex-col min-h-[52px] transition-all"
              :class="[
                !cell.isCurrentMonth ? 'opacity-20 pointer-events-none bg-slate-50' : 'cursor-pointer',
                cell.isCurrentMonth && !cell.isToday && selectedCalendarDate !== cell.dateStr
                  ? 'bg-white border border-slate-100 hover:border-orange-300 hover:bg-orange-50/40'
                  : '',
                cell.isToday && selectedCalendarDate !== cell.dateStr ? 'ring-2 ring-orange-400 bg-orange-50' : '',
                selectedCalendarDate === cell.dateStr ? 'ring-2 ring-orange-500 bg-orange-50' : '',
              ]"
            >
              <span class="text-[11px] font-semibold leading-none"
                :class="cell.isToday ? 'text-orange-600' : 'text-slate-700'">
                {{ cell.day }}
              </span>
              <div class="mt-1 space-y-0.5 overflow-hidden">
                <div v-for="ev in cell.events.slice(0, 2)" :key="ev.id"
                  class="h-1.5 rounded-full w-full"
                  :class="{
                    'bg-orange-500': ev.is_currently_active,
                    'bg-blue-400':   ev.is_upcoming && !ev.is_currently_active,
                    'bg-slate-300':  !ev.is_currently_active && !ev.is_upcoming,
                  }">
                </div>
                <div v-if="cell.events.length > 2" class="text-[8px] text-slate-400 leading-none">
                  +{{ cell.events.length - 2 }}
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Legend -->
      <div class="flex items-center gap-4 px-1 flex-wrap">
        <div class="flex items-center gap-1.5">
          <div class="w-4 h-1.5 rounded-full bg-orange-500"></div>
          <span class="text-xs text-slate-500">กำลังดำเนินการ</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-4 h-1.5 rounded-full bg-blue-400"></div>
          <span class="text-xs text-slate-500">กำลังจะมาถึง</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-4 h-1.5 rounded-full bg-slate-300"></div>
          <span class="text-xs text-slate-500">สิ้นสุดแล้ว</span>
        </div>
        <span v-if="!loadingCalendar" class="text-[11px] text-slate-300">กดวันในปฏิทินเพื่อดูรายละเอียด</span>
      </div>

      <!-- Selected date events -->
      <template v-if="selectedCalendarDate">
        <div v-if="!calendarDayEvents.length" class="box-card text-center py-8 text-slate-400">
          <i class="fi fi-rr-calendar text-2xl text-slate-200"></i>
          <p class="mt-2 text-sm">ไม่มี Flash Sale ในวันที่เลือก</p>
        </div>
        <div v-else class="space-y-2">
          <p class="text-xs font-semibold text-slate-500 px-1 flex items-center gap-1.5">
            <i class="fi fi-rr-calendar text-orange-400"></i>
            Flash Sale วัน{{ fmtDateThai(selectedCalendarDate) }}
          </p>

          <div v-for="ev in calendarDayEvents" :key="ev.id" class="box-card overflow-hidden p-0">
            <div class="p-4">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                  :class="{
                    'bg-green-100 text-green-600':  ev.is_currently_active,
                    'bg-blue-100 text-blue-500':    ev.is_upcoming && !ev.is_currently_active,
                    'bg-slate-100 text-slate-400':  !ev.is_currently_active && !ev.is_upcoming,
                  }">
                  <i class="fi fi-rr-bolt text-base"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="font-semibold text-slate-800 text-sm">{{ ev.title }}</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                      :class="{
                        'bg-green-100 text-green-700':  ev.is_currently_active,
                        'bg-blue-100 text-blue-600':    ev.is_upcoming && !ev.is_currently_active,
                        'bg-slate-100 text-slate-500':  !ev.is_currently_active && !ev.is_upcoming,
                      }">{{ ev.status_label }}</span>
                    <span class="text-[10px] text-slate-400">{{ ev.items_count }} สินค้า</span>
                  </div>
                  <p class="text-xs text-slate-500 mt-0.5">
                    <i class="fi fi-rr-calendar text-[10px] mr-1"></i>
                    {{ fmtDate(ev.starts_at) }} — {{ fmtDate(ev.ends_at) }}
                  </p>
                </div>
                <button v-if="isSuperAdmin" @click="openEventDialog(ev)"
                  class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 flex items-center justify-center transition shrink-0">
                  <i class="fi fi-rr-edit text-sm"></i>
                </button>
              </div>
            </div>

            <!-- Manage button (active/upcoming only) -->
            <div v-if="ev.is_currently_active || ev.is_upcoming"
              class="px-4 pb-4 border-t border-slate-50 pt-3">
              <button @click="toggleItems(ev)"
                :class="[
                  'w-full py-2 text-xs rounded-xl flex items-center justify-center gap-1.5 font-medium transition',
                  selectedEvent?.id === ev.id ? 'bg-violet-700 text-white' : 'bg-violet-600 text-white hover:bg-violet-700',
                ]">
                <i class="fi fi-rr-list"></i>
                {{ selectedEvent?.id === ev.id ? 'ซ่อนสินค้า' : 'จัดการสินค้า / เพิ่มล่วงหน้า' }}
              </button>
            </div>

            <!-- Inline items panel (calendar) -->
            <div v-if="selectedEvent?.id === ev.id" class="border-t border-violet-100 bg-slate-50/60">
              <div class="p-4 space-y-3">
                <div class="flex items-center justify-between">
                  <p class="text-xs font-semibold text-violet-700 flex items-center gap-1">
                    <i class="fi fi-rr-box-open text-violet-400"></i> สินค้าใน event
                  </p>
                  <button @click="openAddPanel"
                    class="flex items-center gap-1 px-3 py-1.5 text-xs rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition">
                    <i class="fi fi-rr-plus"></i> เพิ่มสินค้า
                  </button>
                </div>
                <template v-if="addPanelOpen">
                  <div class="rounded-xl border border-orange-200 bg-white p-3 space-y-2">
                    <div class="flex items-center justify-between mb-1">
                      <p class="text-xs font-medium text-orange-700">เลือกสินค้า + ระบุราคา Flash Sale</p>
                      <button @click="closeAddPanel" class="text-slate-400 hover:text-slate-600">
                        <i class="fi fi-rr-cross text-[10px]"></i>
                      </button>
                    </div>
                    <input v-model="addSearch" type="text" placeholder="ค้นหาสินค้า..."
                      class="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-400" />
                    <div v-if="loadingAll" class="text-center py-4 text-slate-400 text-xs">
                      <i class="fi fi-rr-loading animate-spin"></i>
                    </div>
                    <div v-else class="max-h-64 overflow-y-auto space-y-2 pr-0.5">
                      <div v-for="p in filteredAll" :key="p.id" @click="toggleEventSelect(p)"
                        :class="[
                          'rounded-xl border cursor-pointer select-none transition-all flex items-center gap-3 bg-white p-3',
                          eventAddSelected.find(s => s.product_id === p.id) ? 'border-orange-400 ring-2 ring-orange-100' : 'border-slate-100 hover:border-orange-300 hover:shadow-sm',
                          inCurrentEvent(p.id) ? 'opacity-40 pointer-events-none' : '',
                        ]">
                        <img :src="primaryImage(p)" class="w-14 h-14 rounded-xl object-cover bg-slate-100 shrink-0"
                          @error="e => e.target.src = '/storage/placeholder.png'" />
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-semibold text-slate-800 line-clamp-2 leading-snug">{{ p.name }}</p>
                          <p class="text-xs text-slate-500 mt-0.5">ราคาปกติ {{ fmt(p.price) }} บาท</p>
                          <span v-if="inCurrentEvent(p.id)" class="text-[10px] text-violet-500 font-medium">✓ อยู่ใน event แล้ว</span>
                        </div>
                        <div v-if="eventAddSelected.find(s => s.product_id === p.id)" @click.stop class="shrink-0">
                          <label class="text-[10px] text-orange-600 font-medium block mb-1 text-center">ราคา Flash Sale</label>
                          <input v-model.number="eventAddSelected.find(s => s.product_id === p.id).sale_price"
                            type="number" min="0" step="0.01" placeholder="0.00"
                            class="w-24 border-2 border-orange-400 rounded-xl px-2 py-1.5 text-sm text-center font-bold text-orange-600 focus:outline-none focus:border-orange-500" />
                        </div>
                        <div v-if="eventAddSelected.find(s => s.product_id === p.id)"
                          class="w-5 h-5 rounded-full bg-orange-500 flex items-center justify-center shrink-0 shadow-sm">
                          <i class="fi fi-rr-check text-[9px] text-white"></i>
                        </div>
                      </div>
                      <p v-if="!filteredAll.length" class="text-center text-sm text-slate-400 py-4">ไม่พบสินค้า</p>
                    </div>
                    <div v-if="eventAddSelected.length" class="flex items-center justify-between pt-2 border-t border-orange-100">
                      <p class="text-xs text-orange-700">เลือก {{ eventAddSelected.length }} รายการ</p>
                      <button @click="submitAddToEvent" :disabled="eventAddSaving"
                        class="flex items-center gap-1 px-3 py-1.5 text-xs rounded-lg bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-50">
                        <i v-if="eventAddSaving" class="fi fi-rr-loading animate-spin"></i>
                        <i v-else class="fi fi-rr-bolt"></i>
                        เพิ่มเข้า Event
                      </button>
                    </div>
                  </div>
                </template>
                <div v-if="loadingItems" class="text-center py-8 text-slate-400 text-xs">
                  <i class="fi fi-rr-loading animate-spin text-lg"></i>
                </div>
                <div v-else-if="!eventItems.length" class="text-center py-8 text-slate-400">
                  <i class="fi fi-rr-shopping-cart text-2xl text-slate-200"></i>
                  <p class="mt-2 text-xs">ยังไม่มีสินค้าใน event นี้</p>
                </div>
                <div v-else class="max-h-64 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-100 bg-white">
                  <div v-for="item in eventItems" :key="item.id" class="flex items-center gap-2 px-3 py-2">
                    <i class="fi fi-rr-bolt text-orange-400 text-xs shrink-0"></i>
                    <p class="flex-1 min-w-0 text-xs text-slate-700 line-clamp-1">{{ item.product?.name }}</p>
                    <div class="flex items-center gap-1 shrink-0">
                      <span class="text-xs font-bold text-orange-600">{{ fmt(item.sale_price) }}</span>
                      <span class="text-[10px] text-slate-300 line-through">{{ fmt(item.product?.price) }}</span>
                      <span class="text-[9px] bg-red-100 text-red-500 font-bold px-1 py-0.5 rounded-full">
                        -{{ discountPct(item.product?.price, item.sale_price) }}%
                      </span>
                    </div>
                    <button @click="removeEventItem(item)" :disabled="removingItemId === item.id"
                      class="text-red-300 hover:text-red-500 p-1 disabled:opacity-40 transition shrink-0">
                      <i class="fi fi-rr-trash text-[10px]"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Empty month (no date selected yet) -->
      <div v-else-if="!loadingCalendar && !calendarEvents.length" class="box-card text-center py-10">
        <i class="fi fi-rr-calendar text-3xl text-slate-200"></i>
        <p class="mt-3 text-sm text-slate-400">ไม่มี Flash Sale ในเดือนนี้</p>
        <p v-if="isSuperAdmin" class="text-xs text-slate-300 mt-1">กดปุ่ม "สร้าง Event" ด้านบนเพื่อเพิ่ม</p>
      </div>
    </template>

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
            <input v-model="eventForm.starts_at" type="datetime-local"
              class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-orange-400" />
          </div>
          <div>
            <label class="form-label">สิ้นสุด <span class="text-red-500">*</span></label>
            <input v-model="eventForm.ends_at" type="datetime-local"
              class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-orange-400" />
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
import { ref, computed, onMounted, watch } from 'vue'
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

// ===== Tabs =====
const activeTab = ref('active')
const tabs = computed(() => [
  { key: 'active',   label: 'กำลังดำเนินการ', icon: 'fi fi-rr-bolt' },
  { key: 'history',  label: isSuperAdmin.value ? 'ประวัติแคมเปญ' : 'ประวัติสินค้า', icon: 'fi fi-rr-history' },
  { key: 'calendar', label: 'ปฏิทิน Flash Sale', icon: 'fi fi-rr-calendar' },
])

function switchTab(key) {
  activeTab.value = key
  selectedEvent.value    = null
  addPanelOpen.value     = false
  selectedCalendarDate.value = null
  if (key === 'history')  loadHistory()
  if (key === 'calendar') loadCalendarEvents()
}

// ===== Date filter =====
const filterFrom = ref('')
const filterTo   = ref('')

function clearFilter() {
  filterFrom.value = ''
  filterTo.value   = ''
}

watch([filterFrom, filterTo], () => {
  if (activeTab.value === 'active')  loadEvents()
  if (activeTab.value === 'history') loadHistory()
})

// ===== Events =====
const events          = ref([])
const loadingEvents   = ref(false)
const deletingEventId = ref(null)

// ===== History =====
const loadingHistory       = ref(false)
const historyEvents        = ref([])
const sellerHistory        = ref([])
const selectedHistoryEvent = ref(null)
const historyItems         = ref([])

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

// ===== Event Dialog =====
const eventDialogOpen = ref(false)
const eventSaving     = ref(false)
const eventError      = ref('')
const blankEvent      = () => ({ id: null, title: '', description: '', starts_at: '', ends_at: '', is_active: true })
const eventForm       = ref(blankEvent())

// ===== Calendar =====
const calendarYear         = ref(new Date().getFullYear())
const calendarMonth        = ref(new Date().getMonth())
const calendarEvents       = ref([])
const loadingCalendar      = ref(false)
const selectedCalendarDate = ref(null)

const THAI_MONTHS_FULL = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม']

const calendarMonthLabel = computed(() =>
  `${THAI_MONTHS_FULL[calendarMonth.value]} ${calendarYear.value + 543}`
)

function pad2(n) { return String(n).padStart(2, '0') }

const calendarCells = computed(() => {
  const y = calendarYear.value
  const m = calendarMonth.value
  const today = new Date()
  const todayStr = `${today.getFullYear()}-${pad2(today.getMonth()+1)}-${pad2(today.getDate())}`
  const firstDow   = new Date(y, m, 1).getDay()
  const lastDayNum = new Date(y, m + 1, 0).getDate()

  const cells = []

  // Previous month filler
  const prevY    = m === 0 ? y - 1 : y
  const prevM    = m === 0 ? 11 : m - 1
  const prevLast = new Date(prevY, prevM + 1, 0).getDate()
  for (let i = firstDow - 1; i >= 0; i--) {
    const d = prevLast - i
    const s = `${prevY}-${pad2(prevM+1)}-${pad2(d)}`
    cells.push({ key: 'p' + s, dateStr: s, day: d, isCurrentMonth: false, isToday: false, events: [] })
  }

  // Current month
  for (let d = 1; d <= lastDayNum; d++) {
    const s = `${y}-${pad2(m+1)}-${pad2(d)}`
    const dayEvents = calendarEvents.value.filter(ev => {
      const start = ev.starts_at.slice(0, 10)
      const end   = ev.ends_at.slice(0, 10)
      return s >= start && s <= end
    })
    cells.push({ key: s, dateStr: s, day: d, isCurrentMonth: true, isToday: s === todayStr, events: dayEvents })
  }

  // Next month filler to complete the last row
  const remainder = cells.length % 7
  const nextFill  = remainder === 0 ? 0 : 7 - remainder
  const nextY = m === 11 ? y + 1 : y
  const nextM = m === 11 ? 0 : m + 1
  for (let d = 1; d <= nextFill; d++) {
    const s = `${nextY}-${pad2(nextM+1)}-${pad2(d)}`
    cells.push({ key: 'n' + s, dateStr: s, day: d, isCurrentMonth: false, isToday: false, events: [] })
  }

  return cells
})

const calendarDayEvents = computed(() => {
  if (!selectedCalendarDate.value) return []
  return calendarEvents.value.filter(ev => {
    const start = ev.starts_at.slice(0, 10)
    const end   = ev.ends_at.slice(0, 10)
    return selectedCalendarDate.value >= start && selectedCalendarDate.value <= end
  })
})

function selectCalendarDate(cell) {
  if (!cell.isCurrentMonth) return
  if (selectedCalendarDate.value === cell.dateStr) {
    selectedCalendarDate.value = null
    return
  }
  selectedCalendarDate.value = cell.dateStr
  selectedEvent.value = null
  addPanelOpen.value  = false
}

async function loadCalendarEvents() {
  loadingCalendar.value = true
  selectedEvent.value   = null
  const y = calendarYear.value
  const m = calendarMonth.value
  const dateFrom = `${y}-${pad2(m+1)}-01`
  const dateTo   = `${y}-${pad2(m+1)}-${pad2(new Date(y, m+1, 0).getDate())}`
  try {
    const { data } = await api.get('/market/flash-sale-events', {
      params: { tab: 'calendar', date_from: dateFrom, date_to: dateTo },
    })
    calendarEvents.value = data
  } catch {
    toast.add({ severity: 'error', summary: 'โหลดปฏิทินล้มเหลว', life: 3000 })
  } finally {
    loadingCalendar.value = false
  }
}

function prevMonth() {
  if (calendarMonth.value === 0) { calendarYear.value--; calendarMonth.value = 11 }
  else calendarMonth.value--
  selectedCalendarDate.value = null
  loadCalendarEvents()
}

function nextMonth() {
  if (calendarMonth.value === 11) { calendarYear.value++; calendarMonth.value = 0 }
  else calendarMonth.value++
  selectedCalendarDate.value = null
  loadCalendarEvents()
}

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
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })
}

function fmtDateShort(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' })
}

function fmtDateThai(dateStr) {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('-').map(Number)
  const M = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.']
  return `${d} ${M[m-1]} ${y + 543}`
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
  return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}T${pad2(d.getHours())}:${pad2(d.getMinutes())}`
}

function inCurrentEvent(productId) {
  return eventItems.value.some(i => i.product_id === productId)
}

// ===== Events API =====
async function loadEvents() {
  loadingEvents.value = true
  try {
    const { data } = await api.get('/market/flash-sale-events', {
      params: {
        date_from: filterFrom.value || undefined,
        date_to:   filterTo.value   || undefined,
      },
    })
    events.value = data
  } catch {
    toast.add({ severity: 'error', summary: 'โหลด events ล้มเหลว', life: 3000 })
  } finally {
    loadingEvents.value = false
  }
}

async function toggleItems(ev) {
  if (selectedEvent.value?.id === ev.id) {
    selectedEvent.value    = null
    addPanelOpen.value     = false
    eventAddSelected.value = []
    return
  }
  selectedEvent.value    = ev
  addPanelOpen.value     = false
  eventAddSelected.value = []
  addSearch.value        = ''
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
    if (activeTab.value === 'active')   await loadEvents()
    if (activeTab.value === 'calendar') await loadCalendarEvents()
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
  addPanelOpen.value     = true
  eventAddSelected.value = []
  addSearch.value        = ''
}

function closeAddPanel() {
  addPanelOpen.value     = false
  eventAddSelected.value = []
}

function toggleEventSelect(p) {
  if (inCurrentEvent(p.id)) return
  const idx = eventAddSelected.value.findIndex(s => s.product_id === p.id)
  if (idx === -1) eventAddSelected.value.push({ product_id: p.id, sale_price: null })
  else eventAddSelected.value.splice(idx, 1)
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
    if (activeTab.value === 'active')   await loadEvents()
    if (activeTab.value === 'calendar') await loadCalendarEvents()
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message || 'เกิดข้อผิดพลาด', life: 3000 })
  } finally {
    eventAddSaving.value = false
  }
}

// ===== Event Dialog =====
function openEventDialog(ev = null) {
  eventError.value = ''
  eventForm.value  = ev
    ? { id: ev.id, title: ev.title, description: ev.description || '', starts_at: toDatetimeLocal(ev.starts_at), ends_at: toDatetimeLocal(ev.ends_at), is_active: ev.is_active }
    : blankEvent()
  eventDialogOpen.value = true
}

async function saveEvent() {
  eventError.value  = ''
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
    if (activeTab.value === 'active')   await loadEvents()
    if (activeTab.value === 'calendar') await loadCalendarEvents()
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

// ===== History API =====
async function loadHistory() {
  loadingHistory.value = true
  try {
    if (isSuperAdmin.value) {
      const { data } = await api.get('/market/flash-sale-events', {
        params: { tab: 'history', date_from: filterFrom.value || undefined, date_to: filterTo.value || undefined },
      })
      historyEvents.value = data
    } else {
      const { data } = await api.get('/market/flash-sale-events/seller-history', {
        params: { date_from: filterFrom.value || undefined, date_to: filterTo.value || undefined },
      })
      sellerHistory.value = data
    }
  } catch {
    toast.add({ severity: 'error', summary: 'โหลดประวัติล้มเหลว', life: 3000 })
  } finally {
    loadingHistory.value = false
  }
}

async function toggleHistoryItems(ev) {
  if (selectedHistoryEvent.value?.id === ev.id) {
    selectedHistoryEvent.value = null
    historyItems.value = []
    return
  }
  selectedHistoryEvent.value = ev
  try {
    const { data } = await api.get(`/market/flash-sale-events/${ev.id}/items`)
    historyItems.value = data
  } catch {
    historyItems.value = []
  }
}

onMounted(loadEvents)
</script>
