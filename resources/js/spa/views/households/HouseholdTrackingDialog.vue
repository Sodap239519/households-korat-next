<template>
  <Dialog
    v-model:visible="visible"
    modal
    :draggable="false"
    :style="{ width: '900px' }"
    :breakpoints="{ '1024px': '95vw', '767px': '100vw' }"
    :pt="{ root: { class: 'rounded-2xl overflow-hidden' } }"
  >
    <template #header>
      <div class="flex items-center gap-3 w-full">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-600 text-white flex items-center justify-center shadow-md">
          <i class="fi fi-rr-house-blank"></i>
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="text-base sm:text-lg font-bold text-slate-800 truncate">
            {{ household?.prefix }} {{ household?.first_name }} {{ household?.last_name }}
          </h3>
          <div class="flex items-center gap-2 flex-wrap mt-0.5">
            <span class="font-mono text-xs text-violet-700">{{ household?.household_code }}</span>
            <StatusBadge v-if="household?.priority" :status="household.priority" :label="`Priority ${household.priority}`" />
            <StatusBadge :status="household?.passed ? 'success' : 'failed'" :label="household?.passed ? 'ผ่านเกณฑ์' : 'ไม่ผ่าน'" />
          </div>
        </div>
      </div>
    </template>

    <div v-if="loading" class="text-center py-12 text-violet-400">
      <i class="fi fi-rr-loading text-3xl animate-spin"></i>
    </div>

    <div v-else class="space-y-4">
      <!-- Mushroom summary cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <MetricCard
          icon="fi fi-rr-shopping-bag"
          gradient="from-violet-500 to-purple-600"
          label="ก้อนเห็ดที่ได้รับ"
          :value="fmt(totals?.total_bags_received)"
          unit="ก้อน"
        />
        <MetricCard
          icon="fi fi-rr-mushroom"
          gradient="from-emerald-500 to-teal-600"
          label="ผลผลิต"
          :value="fmt(totals?.total_harvest_kg, 2)"
          unit="กก."
          :highlight="hasHarvest"
        />
        <MetricCard
          icon="fi fi-rr-shop"
          gradient="from-orange-500 to-rose-500"
          label="ขายแล้ว"
          :value="fmt(totals?.total_sold_kg, 2)"
          unit="กก."
          :highlight="hasSold"
        />
        <MetricCard
          icon="fi fi-rr-money-bill-wave"
          gradient="from-amber-500 to-yellow-600"
          label="รายได้รวม"
          :value="fmt(totals?.total_revenue, 2)"
          unit="บาท"
          :highlight="hasRevenue"
        />
      </div>

      <!-- Status hints -->
      <div class="flex items-center gap-2 flex-wrap">
        <StatusBadge v-if="!hasHarvest" status="pending" label="ยังไม่มีผลผลิต" />
        <StatusBadge v-else                status="active"  :label="`เก็บผลผลิตแล้ว ${followupCount} ครั้ง`" />
        <StatusBadge v-if="hasSold"        status="success" label="ขายแล้ว" />
        <StatusBadge v-if="enterprises?.length" status="enterprise" :label="`สมาชิกวิสาหกิจ ${enterprises.length} แห่ง`" />
        <StatusBadge v-if="totals?.allocation_count" status="info" :label="`ได้รับโควต้า ${totals.allocation_count} ครั้ง`" />
      </div>

      <!-- Enterprises (if any) -->
      <FormSection v-if="enterprises?.length" title="วิสาหกิจที่เข้าร่วม" icon="fi fi-rr-handshake" tone="fuchsia">
        <div class="flex flex-wrap gap-2">
          <span v-for="e in enterprises" :key="e" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-fuchsia-50 border border-fuchsia-200 text-fuchsia-700 text-xs font-medium">
            <i class="fi fi-rr-shop"></i> {{ e }}
          </span>
        </div>
      </FormSection>

      <!-- Allocations summary — one row per ครั้งที่ -->
      <FormSection title="การจัดสรรที่ได้รับ" icon="fi fi-rr-seedling" tone="emerald">
        <div v-if="!household?.allocations?.length" class="text-center py-6 text-slate-400 text-sm">
          <i class="fi fi-rr-info text-2xl"></i>
          <p class="mt-2">ยังไม่ได้รับการจัดสรรโควต้าเห็ด</p>
        </div>
        <div v-else class="rounded-xl border-2 border-emerald-200 bg-white/60 overflow-hidden">
          <div
            v-for="(a, idx) in household.allocations"
            :key="a.id"
            class="px-4 py-2.5 flex items-center justify-between flex-wrap gap-2 border-b border-emerald-100 last:border-b-0 hover:bg-emerald-50/40"
          >
            <div class="flex items-center gap-3 min-w-0">
              <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white font-bold text-sm shadow">
                {{ idx + 1 }}
              </span>
              <div class="min-w-0">
                <p class="font-medium text-slate-700 text-sm truncate">
                  <span class="text-violet-700 font-semibold">ครั้งที่ {{ idx + 1 }}</span>
                  · {{ a.quota?.district || '-' }} · ปี {{ a.quota?.year }}
                  <span class="text-[11px] font-normal text-slate-500">(โควต้ารอบ {{ a.quota?.round }})</span>
                </p>
                <p class="text-[11px] text-slate-500">
                  จัดสรรเมื่อ: {{ fmtThaiDate(a.allocated_date) }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold whitespace-nowrap">
                {{ fmt(a.bags) }} ก้อน
              </span>
              <StatusBadge
                :status="a.status === 'completed' ? 'completed' : a.status === 'active' ? 'active' : 'pending'"
                :label="STATUS_LABEL[a.status] || a.status"
              />
            </div>
          </div>
          <div class="px-4 py-2.5 bg-gradient-to-r from-emerald-50 to-teal-50 flex items-center justify-between text-sm font-semibold">
            <span class="text-slate-700">
              <i class="fi fi-rr-shopping-bag text-emerald-600"></i>
              รวมทั้งหมด {{ household.allocations.length }} ครั้ง
            </span>
            <span class="text-emerald-700">{{ fmt(totalBagsAll) }} ก้อน</span>
          </div>
        </div>
      </FormSection>

      <!-- Followups — consolidated across all allocations -->
      <FormSection title="ติดตามผลผลิตและการขาย" icon="fi fi-rr-list-check" tone="fuchsia">
        <div v-if="!consolidatedFollowups.length" class="text-center py-6 text-slate-400 text-sm">
          <i class="fi fi-rr-info text-2xl"></i>
          <p class="mt-2">ยังไม่มีการติดตามผล</p>
        </div>
        <div v-else>
          <div class="overflow-x-auto rounded-xl border border-fuchsia-100">
            <table class="w-full text-xs">
              <thead class="bg-fuchsia-50/60">
                <tr class="text-left text-slate-500">
                  <th class="px-3 py-2 font-medium">รอบ</th>
                  <th class="px-3 py-2 font-medium">วันที่</th>
                  <th class="px-3 py-2 font-medium text-right">ผลผลิต (กก.)</th>
                  <th class="px-3 py-2 font-medium text-right">ขาย (กก.)</th>
                  <th class="px-3 py-2 font-medium text-right">ราคา/กก.</th>
                  <th class="px-3 py-2 font-medium text-right">รายได้ (บาท)</th>
                  <th class="px-3 py-2 font-medium">ช่องทาง</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="f in consolidatedFollowups" :key="f.id" class="border-t border-fuchsia-50 hover:bg-fuchsia-50/30">
                  <td class="px-3 py-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-fuchsia-100 text-fuchsia-700 font-semibold">
                      {{ f.followup_round }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ fmtThaiDate(f.followup_date, { short: true }) }}</td>
                  <td class="px-3 py-2 text-right">{{ fmt(f.harvest_kg, 2) }}</td>
                  <td class="px-3 py-2 text-right">{{ fmt(f.sold_kg, 2) }}</td>
                  <td class="px-3 py-2 text-right text-slate-500">{{ f.price_per_kg ? fmt(f.price_per_kg, 2) : '-' }}</td>
                  <td class="px-3 py-2 text-right font-semibold text-emerald-700">{{ fmt(f.revenue, 2) }}</td>
                  <td class="px-3 py-2">
                    <StatusBadge v-if="f.sale_channel" :status="f.sale_channel" :label="CHANNEL_LABEL[f.sale_channel] || f.sale_channel" />
                    <span v-else class="text-slate-300">-</span>
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-fuchsia-50/60">
                <tr class="font-semibold">
                  <td class="px-3 py-2" colspan="2">รวม {{ consolidatedFollowups.length }} รอบ</td>
                  <td class="px-3 py-2 text-right">{{ fmt(totalHarvestAll, 2) }}</td>
                  <td class="px-3 py-2 text-right">{{ fmt(totalSoldAll, 2) }}</td>
                  <td class="px-3 py-2 text-right text-slate-400"></td>
                  <td class="px-3 py-2 text-right text-emerald-700">{{ fmt(totalRevenueAll, 2) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </FormSection>

      <!-- Household economic info -->
      <FormSection title="เศรษฐกิจครัวเรือน" icon="fi fi-rr-money-bill-wave" tone="amber">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
          <Field label="ที่อยู่" :value="`${household?.district || '-'} · ${household?.sub_district || '-'} · ${household?.village || '-'}`" />
          <Field label="อาชีพหลัก" :value="household?.main_occupation" />
          <Field label="อาชีพเสริม" :value="household?.secondary_occupation" />
          <Field label="รายได้/เดือน" :value="fmtMoney(household?.income_month)" tone="emerald" />
          <Field label="รายจ่าย/เดือน" :value="fmtMoney(household?.expense_month)" tone="amber" />
          <Field label="หนี้สิน" :value="fmtMoney(household?.debt_amount)" tone="rose" />
        </div>
      </FormSection>
    </div>

    <template #footer>
      <Button label="ปิด" icon="fi fi-rr-cross-small" severity="secondary" outlined @click="visible = false" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch, h, defineComponent } from 'vue'
import api from '../../api/index.js'
import StatusBadge from '../../components/StatusBadge.vue'
import FormSection from '../../components/FormSection.vue'
import { fmtThaiDate } from '../../utils/date.js'

import Dialog from 'primevue/dialog'
import Button from 'primevue/button'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  householdId: { type: [Number, String, null], default: null },
})
const emit = defineEmits(['update:modelValue'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const loading = ref(false)
const household = ref(null)
const totals = ref(null)
const enterprises = ref([])

const STATUS_LABEL = {
  pending:   'รอดำเนินการ',
  active:    'กำลังดำเนินการ',
  completed: 'เสร็จสิ้น',
}
const CHANNEL_LABEL = {
  direct: 'ขายตรง', online: 'ออนไลน์', enterprise: 'วิสาหกิจ', market: 'ตลาด',
}

const followupCount = computed(() => Number(totals.value?.followup_count || 0))
const hasHarvest    = computed(() => Number(totals.value?.total_harvest_kg || 0) > 0)
const hasSold       = computed(() => Number(totals.value?.total_sold_kg    || 0) > 0)
const hasRevenue    = computed(() => Number(totals.value?.total_revenue    || 0) > 0)

const totalBagsAll = computed(() =>
  (household.value?.allocations || []).reduce((acc, a) => acc + Number(a.bags || 0), 0)
)

// All followups across the household's allocations, sorted by round then date
const consolidatedFollowups = computed(() => {
  const out = []
  for (const a of (household.value?.allocations || [])) {
    for (const f of (a.followups || [])) {
      out.push(f)
    }
  }
  return out.sort((a, b) => {
    const rA = Number(a.followup_round) || 0
    const rB = Number(b.followup_round) || 0
    if (rA !== rB) return rA - rB
    return (a.followup_date || '').localeCompare(b.followup_date || '')
  })
})

const totalHarvestAll = computed(() =>
  consolidatedFollowups.value.reduce((acc, f) => acc + Number(f.harvest_kg || 0), 0)
)
const totalSoldAll = computed(() =>
  consolidatedFollowups.value.reduce((acc, f) => acc + Number(f.sold_kg || 0), 0)
)
const totalRevenueAll = computed(() =>
  consolidatedFollowups.value.reduce((acc, f) => acc + Number(f.revenue || 0), 0)
)

function fmt(v, dec = 0) {
  if (v == null) return '-'
  return Number(v).toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}
function fmtMoney(v) {
  return v == null ? '-' : Number(v).toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' บ.'
}

async function load(id) {
  loading.value = true
  try {
    const { data } = await api.get(`/households/${id}/tracking`)
    household.value   = data.household
    totals.value      = data.totals
    enterprises.value = data.enterprises || []
  } finally {
    loading.value = false
  }
}

watch(() => [props.modelValue, props.householdId], ([open, id]) => {
  if (open && id) load(id)
})

// Inline helper components
const MetricCard = defineComponent({
  props: ['icon', 'gradient', 'label', 'value', 'unit', 'highlight'],
  setup(p) {
    return () => h('div', {
      class: `relative overflow-hidden rounded-xl p-3 sm:p-4 text-white shadow ${p.highlight ? '' : 'opacity-90'} bg-gradient-to-br ${p.gradient}`,
    }, [
      h('div', { class: 'absolute -top-6 -right-6 w-20 h-20 rounded-full bg-white/15 blur-2xl pointer-events-none' }),
      h('div', { class: 'relative flex items-start justify-between gap-2' }, [
        h('div', { class: 'min-w-0' }, [
          h('p', { class: 'text-[10px] sm:text-xs text-white/85' }, p.label),
          h('p', { class: 'text-lg sm:text-2xl font-bold mt-0.5 truncate' }, p.value ?? '-'),
          h('p', { class: 'text-[10px] text-white/70' }, p.unit),
        ]),
        h('div', { class: 'flex-shrink-0 w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center' }, [
          h('i', { class: `${p.icon}` }),
        ]),
      ]),
    ])
  },
})

const Field = defineComponent({
  props: ['label', 'value', 'tone'],
  setup(p) {
    const colorMap = { emerald: 'text-emerald-700', amber: 'text-amber-700', rose: 'text-rose-700' }
    return () => h('div', {}, [
      h('p', { class: 'text-[11px] uppercase tracking-wide text-slate-400 mb-0.5' }, p.label),
      h('p', { class: `font-medium truncate ${colorMap[p.tone] || 'text-slate-700'}` }, p.value ?? '-'),
    ])
  },
})
</script>
