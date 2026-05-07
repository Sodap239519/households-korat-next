<template>
  <div :style="{ minHeight: heightPx, width: '100%' }">
    <div v-show="hasData" ref="chartEl" class="w-full"></div>
    <div v-if="!hasData" class="text-center text-slate-400 py-12 text-sm">
      <i class="fi fi-rr-info text-2xl"></i>
      <p class="mt-2">{{ emptyText || 'ยังไม่มีข้อมูล' }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import ApexCharts from 'apexcharts'

const props = defineProps({
  // 'bar' | 'line' | 'area' | 'donut' | 'pie' | 'stacked-bar'
  type:       { type: String, default: 'bar' },
  labels:     { type: Array, default: () => [] },
  series:     { type: Array, default: () => [] },
  data:       { type: Array, default: () => [] },
  colors:     { type: Array, default: () => [] },
  height:     { type: [Number, String], default: 280 },
  emptyText:  { type: String, default: '' },
  stacked:    { type: Boolean, default: false },
  smooth:     { type: Boolean, default: true },
  yFormatter: { type: Function, default: null },
  legend:     { type: Boolean, default: true },
})

const PALETTE = [
  '#7c3aed', '#a855f7', '#d946ef', '#ec4899', '#8b5cf6',
  '#6366f1', '#3b82f6', '#06b6d4', '#10b981', '#f59e0b',
]

const isCircular = computed(() => props.type === 'donut' || props.type === 'pie')

const heightPx = computed(() =>
  typeof props.height === 'number' ? `${props.height}px` : props.height,
)

const hasData = computed(() => {
  if (isCircular.value) return (props.data || []).length > 0
  return (props.series || []).some(s => Array.isArray(s.data) && s.data.length > 0)
})

const apexType = computed(() => props.type === 'stacked-bar' ? 'bar' : props.type)

const apexSeries = computed(() => {
  if (isCircular.value) return [...props.data]
  return props.series.map(s => ({ name: s.name, data: [...(s.data || [])] }))
})

function buildOptions() {
  const colors = props.colors.length ? props.colors : PALETTE

  const base = {
    chart: {
      type: apexType.value,
      height: typeof props.height === 'number' ? props.height : Number(props.height) || 280,
      stacked: props.type === 'stacked-bar' || props.stacked,
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: { speed: 350 },
      fontFamily: 'Prompt, ui-sans-serif, sans-serif',
    },
    colors,
    series: apexSeries.value,
    dataLabels: { enabled: false },
    legend: {
      show: props.legend,
      position: 'bottom',
      fontFamily: 'Prompt, ui-sans-serif, sans-serif',
      markers: { width: 12, height: 12, radius: 12 },
    },
    grid: {
      borderColor: '#e2e8f0',
      strokeDashArray: 3,
      xaxis: { lines: { show: false } },
    },
    tooltip: {
      style: { fontFamily: 'Prompt, ui-sans-serif, sans-serif' },
      y: props.yFormatter ? { formatter: props.yFormatter } : undefined,
    },
  }

  if (isCircular.value) {
    return {
      ...base,
      labels: props.labels,
      stroke: { width: 2, colors: ['#fff'] },
      plotOptions: {
        pie: {
          donut: {
            size: '60%',
            labels: {
              show: true,
              total: { show: true, label: 'รวม', fontFamily: 'Prompt' },
            },
          },
        },
      },
    }
  }

  return {
    ...base,
    xaxis: {
      categories: props.labels,
      labels: {
        rotate: -35,
        style: { fontSize: '11px', fontFamily: 'Prompt, ui-sans-serif, sans-serif' },
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        style: { fontSize: '11px', fontFamily: 'Prompt, ui-sans-serif, sans-serif' },
        formatter: (v) => v == null ? '' : Number(v).toLocaleString(),
      },
    },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '60%' } },
    stroke: {
      width: props.type === 'line' ? 3 : props.type === 'area' ? 2 : 0,
      curve: props.smooth ? 'smooth' : 'straight',
    },
    fill: props.type === 'area' ? {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 100] },
    } : { type: 'solid' },
  }
}

const chartEl = ref(null)
let chart = null

function destroy() {
  if (chart) {
    try { chart.destroy() } catch {}
    chart = null
  }
}

async function build() {
  destroy()
  if (!hasData.value) return
  await nextTick()
  if (!chartEl.value) return
  chart = new ApexCharts(chartEl.value, buildOptions())
  await chart.render()
}

async function update() {
  if (!hasData.value) {
    destroy()
    return
  }
  if (!chart) {
    await build()
    return
  }
  try {
    chart.updateOptions(buildOptions(), false, true)
  } catch (e) {
    // Fallback to full rebuild on any update error
    await build()
  }
}

onMounted(build)
onBeforeUnmount(destroy)

// Watch reactive inputs and re-render
watch(
  () => [props.type, props.labels, props.series, props.data, props.colors, props.height, props.stacked, props.legend],
  () => update(),
  { deep: true },
)
</script>
