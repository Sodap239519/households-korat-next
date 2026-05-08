<template>
  <div :style="{ minHeight: heightPx, width: '100%' }">
    <div v-if="!hasData" class="text-center text-slate-400 py-12 text-sm">
      <i class="fi fi-rr-info text-2xl"></i>
      <p class="mt-2">{{ emptyText || 'ยังไม่มีข้อมูล' }}</p>
    </div>
    <div
      v-else
      ref="chartEl"
      class="w-full"
      :style="{ minHeight: heightPx }"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import ApexCharts from 'apexcharts'

const props = defineProps({
  type:        { type: String, default: 'bar' },
  labels:      { type: Array,  default: () => [] },
  series:      { type: Array,  default: () => [] },
  data:        { type: Array,  default: () => [] },
  colors:      { type: Array,  default: () => [] },
  height:      { type: [Number, String], default: 280 },
  emptyText:   { type: String, default: '' },
  stacked:     { type: Boolean, default: false },
  smooth:      { type: Boolean, default: true },
  legend:      { type: Boolean, default: true },
  // Dual y-axis (left for non-line series, right for line series). Useful for
  // mixed bar+line charts where bars and lines have different units (e.g. กก. vs บาท).
  dualAxis:    { type: Boolean, default: false },
  yLeftLabel:  { type: String, default: '' },
  yRightLabel: { type: String, default: '' },
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

function buildOptions() {
  const colors = props.colors.length ? props.colors : PALETTE
  const heightNum = typeof props.height === 'number' ? props.height : (Number(props.height) || 280)

  // Detect mixed-mode (any series carries its own type)
  const isMixed = !isCircular.value && (props.series || []).some(s => !!s.type)

  const series = isCircular.value
    ? [...props.data]
    : props.series.map(s => isMixed
        ? { name: s.name, type: s.type || 'column', data: [...(s.data || [])] }
        : { name: s.name, data: [...(s.data || [])] }
      )

  // Per-series stroke widths for mixed charts (line=3, column/bar=0, area=2)
  const mixedStroke = isMixed
    ? series.map(s => s.type === 'line' ? 3 : s.type === 'area' ? 2 : 0)
    : null

  const base = {
    chart: {
      type: isMixed ? 'line' : apexType.value,
      height: heightNum,
      width: '100%',
      stacked: props.type === 'stacked-bar' || props.stacked,
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: { speed: 350 },
      fontFamily: 'Prompt, ui-sans-serif, sans-serif',
      redrawOnParentResize: true,
      redrawOnWindowResize: true,
    },
    series,
    colors,
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
    tooltip: { style: { fontFamily: 'Prompt, ui-sans-serif, sans-serif' } },
  }

  if (isCircular.value) {
    return {
      ...base,
      labels: props.labels,
      stroke: { width: 2, colors: ['#fff'] },
      plotOptions: {
        pie: {
          donut: { size: '60%', labels: { show: true, total: { show: true, label: 'รวม', fontFamily: 'Prompt' } } },
        },
      },
    }
  }

  // Build yaxis — single (default) or dual (mixed + dualAxis prop) array
  const fontStyle = { fontSize: '11px', fontFamily: 'Prompt, ui-sans-serif, sans-serif' }
  const titleStyle = { fontFamily: 'Prompt, ui-sans-serif, sans-serif', fontSize: '11px' }
  let yaxisCfg
  if (isMixed && props.dualAxis) {
    let leftFirst = true
    let rightFirst = true
    yaxisCfg = series.map(s => {
      const isLine = s.type === 'line'
      const entry = {
        seriesName: s.name,
        labels: { style: fontStyle, formatter: (v) => v == null ? '' : Math.round(Number(v)).toLocaleString() },
      }
      if (isLine) {
        entry.opposite = true
        if (rightFirst) {
          if (props.yRightLabel) entry.title = { text: props.yRightLabel, style: titleStyle }
          rightFirst = false
        } else {
          entry.show = false
        }
      } else {
        if (leftFirst) {
          if (props.yLeftLabel) entry.title = { text: props.yLeftLabel, style: titleStyle }
          leftFirst = false
        } else {
          entry.show = false
        }
      }
      return entry
    })
  } else {
    yaxisCfg = {
      labels: { style: fontStyle, formatter: (v) => v == null ? '' : Number(v).toLocaleString() },
    }
  }

  return {
    ...base,
    xaxis: {
      categories: props.labels,
      labels: { rotate: -35, style: fontStyle },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: yaxisCfg,
    markers: isMixed ? { size: 4, strokeWidth: 2, hover: { sizeOffset: 2 } } : { size: 0 },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '60%' } },
    stroke: isMixed ? {
      width: mixedStroke,
      curve: props.smooth ? 'smooth' : 'straight',
    } : {
      width: props.type === 'line' ? 3 : props.type === 'area' ? 2 : 0,
      curve: props.smooth ? 'smooth' : 'straight',
    },
    fill: isMixed ? {
      type: series.map(s => s.type === 'area' ? 'gradient' : 'solid'),
      gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 100] },
    } : (props.type === 'area' ? {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 100] },
    } : { type: 'solid' }),
  }
}

const chartEl = ref(null)
let chart = null
let resizeObs = null
let mountTimer = null

function destroy() {
  if (mountTimer) { clearTimeout(mountTimer); mountTimer = null }
  if (resizeObs) {
    try { resizeObs.disconnect() } catch (e) { /* swallow */ }
    resizeObs = null
  }
  if (chart) {
    try { chart.destroy() } catch (e) { /* swallow */ }
    chart = null
  }
}

async function tryRender(el) {
  if (!el || !el.isConnected) return false
  // Need a real width — when inside hidden tab/dialog, width is 0
  if (el.clientWidth < 10) return false

  destroy()
  try {
    chart = new ApexCharts(el, buildOptions())
    await chart.render()
    return true
  } catch (err) {
    console.error('[AppChart] render failed:', err)
    return false
  }
}

async function mountChart(el) {
  destroy()
  await nextTick()
  if (!el || !el.isConnected) return

  // Try to render right now if width is available
  if (await tryRender(el)) return

  // Otherwise wait for the element to gain a width via ResizeObserver
  if (typeof ResizeObserver !== 'undefined') {
    resizeObs = new ResizeObserver(async (entries) => {
      const entry = entries[0]
      if (!entry) return
      const w = entry.contentRect?.width || 0
      if (w >= 10 && !chart) {
        if (await tryRender(el)) {
          // Once rendered, stop watching
          try { resizeObs.disconnect() } catch (e) { /* swallow */ }
          resizeObs = null
        }
      }
    })
    resizeObs.observe(el)
  }

  // As a final fallback, retry after a short delay (covers cases where
  // tab content becomes visible without firing a resize event)
  mountTimer = setTimeout(() => {
    if (!chart) tryRender(el)
  }, 250)
}

// Mount chart whenever the chartEl ref becomes available (v-if creates it
// fresh when hasData flips from false → true).
watch(chartEl, (el) => {
  if (el) mountChart(el)
  else destroy()
}, { flush: 'post' })

// React to data / option changes once the chart is alive
watch(
  () => [props.type, props.labels, props.series, props.data, props.colors, props.height, props.stacked, props.legend],
  () => {
    if (!chart || !hasData.value) {
      // No chart yet (or no data) — let the chartEl watcher handle it
      if (hasData.value && chartEl.value) mountChart(chartEl.value)
      return
    }
    try {
      chart.updateOptions(buildOptions(), false, true)
    } catch (err) {
      console.error('[AppChart] updateOptions failed, rebuilding:', err)
      mountChart(chartEl.value)
    }
  },
  { deep: true },
)

onBeforeUnmount(destroy)
</script>
