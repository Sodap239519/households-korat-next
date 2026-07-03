<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-4">
    <div class="flex items-center gap-3">
      <RouterLink to="/shop" class="p-2 rounded-full hover:bg-slate-100 text-slate-500 -ml-1">
        <i class="fi fi-rr-arrow-small-left text-lg"></i>
      </RouterLink>
      <div>
        <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <i class="fi fi-rr-map-marker text-violet-600"></i> แผนที่กลุ่มผู้ขาย
        </h1>
        <p class="text-sm text-slate-400">ดูระยะทางระหว่างคุณกับแต่ละกลุ่มผู้ขาย</p>
      </div>
    </div>

    <!-- Location status -->
    <div class="box-card p-3 flex items-center gap-3 text-sm">
      <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
        :class="locationStatus === 'found' ? 'bg-emerald-100 text-emerald-600'
              : locationStatus === 'denied' ? 'bg-rose-100 text-rose-500'
              : 'bg-violet-100 text-violet-600'">
        <i :class="locationStatus === 'found' ? 'fi fi-rr-check'
                  : locationStatus === 'denied' ? 'fi fi-rr-cross-small'
                  : 'fi fi-rr-spinner animate-spin'"></i>
      </div>
      <div class="flex-1">
        <p v-if="locationStatus === 'found'" class="text-emerald-700 font-medium">ระบุตำแหน่งของคุณได้แล้ว</p>
        <p v-else-if="locationStatus === 'denied'" class="text-rose-600">ไม่ได้รับสิทธิ์เข้าถึงตำแหน่ง — แสดงแผนที่โดยไม่มีระยะทาง</p>
        <p v-else class="text-slate-500">กำลังขอตำแหน่งของคุณ...</p>
      </div>
      <button v-if="locationStatus === 'denied'" @click="requestLocation"
        class="shrink-0 px-3 py-1.5 rounded-full bg-violet-100 text-violet-700 text-xs font-semibold hover:bg-violet-200 transition">
        ลองใหม่
      </button>
    </div>

    <!-- Map -->
    <div class="box-card overflow-hidden">
      <div ref="mapEl" class="w-full h-[220px] sm:h-[350px]"></div>
    </div>

    <!-- Distance cards -->
    <div v-if="groups.length" class="space-y-2">
      <h2 class="font-semibold text-slate-700 text-sm flex items-center gap-2">
        <i class="fi fi-rr-shop text-violet-500"></i> กลุ่มผู้ขายทั้งหมด
        <span v-if="locationStatus === 'found'" class="text-slate-400 font-normal">(เรียงตามระยะทาง)</span>
      </h2>
      <div class="grid gap-3 sm:grid-cols-2">
        <div v-for="g in sortedGroups" :key="g.id"
          class="box-card p-3.5 flex items-center gap-3 hover:border-violet-300 hover-lift transition group relative">
          <!-- Clickable overlay (ครอบทั้งการ์ด ยกเว้นปุ่มแชร์) -->
          <RouterLink :to="`/shop/sellers/${g.slug}`" class="absolute inset-0 rounded-2xl" aria-label="ดูร้านค้า"></RouterLink>
          <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center shadow">
            <img v-if="g.logo_path" :src="`/storage/${g.logo_path}`" class="w-full h-full object-cover" />
            <i v-else class="fi fi-rr-shop"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-800 truncate group-hover:text-violet-700 transition text-sm">{{ g.name }}</p>
            <p v-if="g.districts?.length" class="text-xs text-slate-400 truncate mt-0.5">
              <i class="fi fi-rr-marker text-[10px]"></i> {{ g.districts.slice(0, 3).join(' · ') }}
            </p>
          </div>
          <div class="shrink-0 text-right">
            <p v-if="g.distance != null" class="text-sm font-bold text-violet-700">{{ fmtDist(g.distance) }}</p>
            <p v-else-if="!g.lat" class="text-xs text-slate-300">ไม่มีพิกัด</p>
            <p v-else class="text-xs text-slate-400">ไม่ทราบ</p>
            <p v-if="g.distance != null" class="text-[10px] text-slate-400">จากคุณ</p>
          </div>
          <!-- แชร์ (z-10 ลอยอยู่เหนือ overlay link) -->
          <div class="relative z-10" @click.stop>
            <ShareButton
              :title="g.name"
              :text="(g.districts||[]).slice(0,3).join(' · ')"
              :url="`/shop/sellers/${g.slug}`"
              variant="icon-sm"
              theme="default"
              align="right"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '../../api/index.js'
import ShareButton from './components/ShareButton.vue'

const mapEl  = ref(null)
const groups = ref([])
const userLat = ref(null)
const userLng = ref(null)
const locationStatus = ref('loading')
let mapInstance = null
let L = null

// Default center: Nakhon Ratchasima city
const DEFAULT_LAT = 14.9736
const DEFAULT_LNG = 102.1015

// Haversine formula (km)
function haversine(lat1, lng1, lat2, lng2) {
  const R = 6371
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLng = (lng2 - lng1) * Math.PI / 180
  const a = Math.sin(dLat/2)**2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2)**2
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
}

function fmtDist(km) {
  if (km < 1) return `${Math.round(km * 1000)} ม.`
  return `${km.toFixed(1)} กม.`
}

const sortedGroups = computed(() => {
  const gs = groups.value.map(g => ({
    ...g,
    distance: (g.lat && g.lng && userLat.value != null)
      ? haversine(userLat.value, userLng.value, Number(g.lat), Number(g.lng))
      : null,
  }))
  return gs.sort((a, b) => {
    if (a.distance == null && b.distance == null) return 0
    if (a.distance == null) return 1
    if (b.distance == null) return -1
    return a.distance - b.distance
  })
})

async function requestLocation() {
  locationStatus.value = 'loading'
  navigator.geolocation.getCurrentPosition(
    pos => {
      userLat.value = pos.coords.latitude
      userLng.value = pos.coords.longitude
      locationStatus.value = 'found'
      updateMap()
    },
    () => { locationStatus.value = 'denied' }
  )
}

function updateMap() {
  if (!mapInstance || !L) return

  // Add/move user marker
  if (userLat.value != null) {
    const userIcon = L.divIcon({
      className: '',
      html: `<div style="width:20px;height:20px;background:#7c3aed;border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(124,58,237,.5)"></div>`,
      iconSize: [20, 20],
      iconAnchor: [10, 10],
    })
    L.marker([userLat.value, userLng.value], { icon: userIcon })
      .addTo(mapInstance)
      .bindPopup('<b style="color:#7c3aed">ตำแหน่งของคุณ</b>')
    mapInstance.setView([userLat.value, userLng.value], 11)
  }

  // Draw circles + markers for groups
  sortedGroups.value.forEach(g => {
    if (!g.lat || !g.lng) return
    const lat = Number(g.lat), lng = Number(g.lng)
    const groupIcon = L.divIcon({
      className: '',
      html: `<div style="background:#f97316;color:white;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:bold;box-shadow:0 3px 10px rgba(249,115,22,.4);border:2px solid white;">
        ${g.name.charAt(0)}
      </div>`,
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    })

    const distText = g.distance != null ? `<br><span style="color:#7c3aed;font-weight:600">ห่าง ${fmtDist(g.distance)}</span>` : ''
    L.marker([lat, lng], { icon: groupIcon })
      .addTo(mapInstance)
      .bindPopup(`<b style="color:#1e293b">${g.name}</b>${distText}<br><small style="color:#94a3b8">${(g.districts||[]).slice(0,3).join(' · ')}</small>`)

    L.circle([lat, lng], {
      radius: 8000,
      color: '#7c3aed',
      fillColor: '#ede9fe',
      fillOpacity: 0.2,
      weight: 1.5,
    }).addTo(mapInstance)
  })
}

onMounted(async () => {
  // โหลด groups
  try {
    const { data } = await api.get('/shop/groups')
    groups.value = data || []
  } catch { /* ignore */ }

  // โหลด Leaflet
  L = await import('leaflet')
  L = L.default || L

  // fix default icon paths
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
    shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
  })

  await import('leaflet/dist/leaflet.css')

  mapInstance = L.map(mapEl.value, { zoomControl: true }).setView([DEFAULT_LAT, DEFAULT_LNG], 10)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 18,
  }).addTo(mapInstance)

  // วาง markers ที่มีพิกัด
  updateMap()

  // ขอตำแหน่ง
  if (navigator.geolocation) {
    requestLocation()
  } else {
    locationStatus.value = 'denied'
  }
})

onUnmounted(() => {
  if (mapInstance) { mapInstance.remove(); mapInstance = null }
})
</script>
