<template>
  <apexchart
    v-if="hasData"
    :type="type"
    :series="apexSeries"
    :options="apexOptions"
    :height="height"
  />
  <div v-else class="text-center text-slate-400 py-12 text-sm">
    <i class="fi fi-rr-info text-2xl"></i>
    <p class="mt-2">{{ emptyText || 'ยังไม่มีข้อมูล' }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // 'bar' | 'line' | 'area' | 'donut' | 'pie' | 'stacked-bar'
  type:       { type: String, default: 'bar' },
  // labels along the x-axis (for bar/line/area)
  labels:     { type: Array, default: () => [] },
  // [{ name, data: [...], color? }, ...] for cartesian
  series:     { type: Array, default: () => [] },
  // For donut/pie use plain numbers in `data` and `labels` array
  data:       { type: Array, default: () => [] },
  // Color override (per-bar/donut slice)
  colors:     { type: Array, default: () => [] },
  height:     { type: [Number, String], default: 280 },
  emptyText:  { type: String, default: '' },
  // For stacked bar
  stacked:    { type: Boolean, default: false },
  // Smooth area / line
  smooth:     { type: Boolean, default: true },
  yFormatter: { type: Function, default: null },
  legend:     { type: Boolean, default: true },
})

const PALETTE = [
  '#7c3aed', '#a855f7', '#d946ef', '#ec4899', '#8b5cf6',
  '#6366f1', '#3b82f6', '#06b6d4', '#10b981', '#f59e0b',
]

const isCircular = computed(() => props.type === 'donut' || props.type === 'pie')

const hasData = computed(() => {
  if (isCircular.value) return (props.data || []).length > 0
  return (props.series || []).some(s => (s.data || []).length > 0)
})

const apexType = computed(() => {
  if (props.type === 'stacked-bar') return 'bar'
  return props.type
})

const apexSeries = computed(() => {
  if (isCircular.value) return props.data
  return props.series.map(s => ({ name: s.name, data: s.data || [] }))
})

const apexOptions = computed(() => {
  const colors = props.colors.length ? props.colors : PALETTE

  const base = {
    chart: {
      type: apexType.value,
      stacked: props.type === 'stacked-bar' || props.stacked,
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: { speed: 350 },
      fontFamily: 'Prompt, ui-sans-serif, sans-serif',
    },
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

  // bar / line / area / stacked-bar
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
        formatter: (val) =>
          val == null ? '' : Number(val).toLocaleString(),
      },
    },
    plotOptions: {
      bar: {
        borderRadius: 6,
        columnWidth: '60%',
      },
    },
    stroke: {
      width: props.type === 'line' ? 2 : props.type === 'area' ? 2 : 0,
      curve: props.smooth ? 'smooth' : 'straight',
    },
    fill: props.type === 'area' ? {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] },
    } : undefined,
  }
})
</script>
