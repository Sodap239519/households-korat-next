<template>
  <div class="min-h-screen bg-slate-50 pb-20 lg:pb-0">
    <!-- Loading skeleton -->
    <template v-if="loading">
      <div class="h-60 bg-slate-200 animate-pulse"></div>
      <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <div v-for="i in 8" :key="i" class="box-card aspect-[3/4] skeleton"></div>
        </div>
      </div>
    </template>

    <template v-else-if="group">
      <!-- ===== Banner ===== -->
      <div class="relative bg-gradient-to-br from-violet-600 via-violet-700 to-fuchsia-700 text-white overflow-hidden">
        <!-- Decorative blobs -->
        <div class="absolute -top-10 -right-10 w-64 h-64 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-10 -left-10 w-48 h-48 rounded-full bg-fuchsia-400/20 blur-2xl"></div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
          <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
            <!-- Profile picture / Logo -->
            <div class="shrink-0 w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-4xl border-2 border-white/40 shadow-xl overflow-hidden">
              <img v-if="group.logo_path" :src="`/storage/${group.logo_path}`" :alt="group.name" class="w-full h-full object-cover" />
              <i v-else class="fi fi-rr-shop text-white text-3xl"></i>
            </div>

            <!-- Info -->
            <div class="flex-1 text-center sm:text-left">
              <h1 class="text-2xl sm:text-3xl font-bold drop-shadow">{{ group.name }}</h1>
              <p v-if="group.description" class="mt-1.5 text-white/80 text-sm leading-relaxed max-w-lg">{{ group.description }}</p>

              <!-- Stats row -->
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-3 justify-center sm:justify-start text-sm">
                <!-- Avg rating -->
                <span v-if="group.avg_rating > 0" class="flex items-center gap-1 text-amber-300 font-semibold">
                  <i class="fi fi-sr-star text-xs"></i>
                  {{ group.avg_rating }}
                  <span class="text-white/60 font-normal text-xs">({{ group.total_reviews }} รีวิว)</span>
                </span>
                <!-- Product count -->
                <span class="flex items-center gap-1 text-white/80">
                  <i class="fi fi-rr-box-open text-xs"></i> {{ products?.total || 0 }} สินค้า
                </span>
                <!-- Districts -->
                <span v-if="group.districts?.length" class="flex items-center gap-1 text-white/80">
                  <i class="fi fi-rr-map-marker text-xs"></i>
                  {{ group.districts.slice(0,3).join(', ') }}{{ group.districts.length > 3 ? ` +${group.districts.length-3}` : '' }}
                </span>
                <span v-if="distance != null" class="flex items-center gap-1 text-amber-300 font-semibold">
                  <i class="fi fi-rr-route text-xs"></i> ห่าง {{ fmtDist(distance) }}
                </span>
              </div>

              <!-- Contact row -->
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-2 justify-center sm:justify-start text-sm text-white/70">
                <span v-if="group.contact_phone" class="flex items-center gap-1">
                  <i class="fi fi-rr-phone-call text-xs"></i> {{ group.contact_phone }}
                </span>
                <span v-if="group.contact_address" class="flex items-center gap-1">
                  <i class="fi fi-rr-marker text-xs"></i> {{ group.contact_address }}
                </span>
              </div>
            </div>

            <!-- Chat button + Share -->
            <div class="shrink-0 flex items-center gap-2">
              <button v-if="user" @click="startChat"
                class="flex items-center gap-2 bg-white text-violet-700 font-semibold px-5 py-2.5 rounded-full shadow-lg hover:bg-violet-50 active:scale-95 transition text-sm">
                <i class="fi fi-rr-comment-alt"></i> ส่งข้อความ
              </button>
              <RouterLink v-else to="/shop/login"
                class="flex items-center gap-2 bg-white text-violet-700 font-semibold px-5 py-2.5 rounded-full shadow-lg hover:bg-violet-50 transition text-sm">
                <i class="fi fi-rr-comment-alt"></i> ส่งข้อความ
              </RouterLink>
              <ShareButton
                :title="group.name"
                :text="group.description || group.name"
                :url="`/shop/sellers/${group.slug}`"
                theme="light"
                variant="icon"
                align="right"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- ===== Store Info Cards ===== -->
      <div class="max-w-5xl mx-auto px-4 sm:px-6 mt-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <!-- Rating card → สินค้าเรียงตามคะแนน -->
          <RouterLink :to="`/shop/products?group=${group.slug}&sort=rating`"
            class="box-card p-4 flex flex-col items-center gap-1 text-center group hover:border-amber-300 hover:bg-amber-50/30 transition cursor-pointer">
            <div class="text-2xl font-bold text-amber-500 group-hover:scale-110 transition-transform">{{ group.avg_rating > 0 ? group.avg_rating : '—' }}</div>
            <div class="flex text-amber-400 text-sm gap-0.5">
              <i v-for="n in 5" :key="n" :class="n <= Math.round(group.avg_rating) ? 'fi fi-sr-star' : 'fi fi-rr-star'"></i>
            </div>
            <div class="text-xs text-slate-400 group-hover:text-amber-600 transition">คะแนนรวม</div>
            <div class="text-[10px] text-amber-400 opacity-0 group-hover:opacity-100 transition">ดูสินค้ายอดนิยม →</div>
          </RouterLink>
          <!-- Product count → เลื่อนไปส่วนสินค้า -->
          <button @click="scrollToSection('store-products')"
            class="box-card p-4 flex flex-col items-center gap-1 text-center group hover:border-violet-300 hover:bg-violet-50/30 transition cursor-pointer w-full">
            <div class="text-2xl font-bold text-violet-600 group-hover:scale-110 transition-transform">{{ products?.total || 0 }}</div>
            <div class="text-violet-400 text-lg"><i class="fi fi-rr-box-open"></i></div>
            <div class="text-xs text-slate-400 group-hover:text-violet-600 transition">สินค้าทั้งหมด</div>
            <div class="text-[10px] text-violet-400 opacity-0 group-hover:opacity-100 transition">ดูสินค้าทั้งหมด →</div>
          </button>
          <!-- Reviews → สินค้าเรียงตามรีวิว -->
          <RouterLink :to="`/shop/products?group=${group.slug}&sort=rating`"
            class="box-card p-4 flex flex-col items-center gap-1 text-center group hover:border-fuchsia-300 hover:bg-fuchsia-50/30 transition cursor-pointer">
            <div class="text-2xl font-bold text-fuchsia-600 group-hover:scale-110 transition-transform">{{ group.total_reviews || 0 }}</div>
            <div class="text-fuchsia-400 text-lg"><i class="fi fi-rr-comment-alt"></i></div>
            <div class="text-xs text-slate-400 group-hover:text-fuchsia-600 transition">รีวิวสินค้า</div>
            <div class="text-[10px] text-fuchsia-400 opacity-0 group-hover:opacity-100 transition">ดูสินค้ามีรีวิว →</div>
          </RouterLink>
          <!-- Districts → toggle รายชื่ออำเภอ -->
          <button @click="showDistricts = !showDistricts"
            class="box-card p-4 flex flex-col items-center gap-1 text-center group hover:border-emerald-300 hover:bg-emerald-50/30 transition cursor-pointer w-full"
            :class="showDistricts ? 'border-emerald-300 bg-emerald-50/30' : ''">
            <div class="text-2xl font-bold text-emerald-600 group-hover:scale-110 transition-transform">{{ group.districts?.length || 0 }}</div>
            <div class="text-emerald-400 text-lg"><i class="fi fi-rr-map-marker"></i></div>
            <div class="text-xs text-slate-400 group-hover:text-emerald-600 transition">อำเภอที่บริการ</div>
            <div class="text-[10px] text-emerald-400 opacity-0 group-hover:opacity-100 transition">{{ showDistricts ? 'ซ่อน ↑' : 'ดูรายชื่อ →' }}</div>
          </button>
        </div>

        <!-- Districts panel -->
        <Transition name="slide-down">
          <div v-if="showDistricts && group.districts?.length" class="mt-3 box-card p-4 border-emerald-100 bg-emerald-50/20">
            <h4 class="text-sm font-semibold text-emerald-700 flex items-center gap-1.5 mb-2.5">
              <i class="fi fi-rr-map-marker"></i> อำเภอที่ร้านนี้ให้บริการ
            </h4>
            <div class="flex flex-wrap gap-2">
              <span v-for="d in group.districts" :key="d"
                class="px-3 py-1 bg-white rounded-full text-sm text-emerald-700 border border-emerald-200 shadow-sm">
                {{ d }}
              </span>
            </div>
          </div>
        </Transition>
      </div>

      <!-- ===== Location ===== -->
      <div v-if="group.lat && group.lng" class="max-w-5xl mx-auto px-4 sm:px-6 mt-3">
        <div class="box-card overflow-hidden">
          <div class="p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center text-violet-600 shrink-0">
              <i class="fi fi-rr-map-marker"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-slate-700 text-sm">ตำแหน่งร้าน</h3>
              <p class="text-xs text-slate-400 mt-0.5 truncate">
                {{ group.map_label || group.contact_address || 'ปักพิกัดแล้ว' }}
              </p>
            </div>
            <div class="shrink-0 flex items-center gap-3">
              <div v-if="distance != null" class="text-right">
                <p class="text-sm font-bold text-violet-700">{{ fmtDist(distance) }}</p>
                <p class="text-[10px] text-slate-400">จากคุณ</p>
              </div>
              <button @click="toggleMap"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition"
                :class="mapOpen ? 'bg-violet-600 text-white' : 'bg-violet-100 text-violet-700 hover:bg-violet-200'">
                <i :class="mapOpen ? 'fi fi-rr-cross-small' : 'fi fi-rr-map-marker'"></i>
                {{ mapOpen ? 'ซ่อนแผนที่' : 'ดูแผนที่' }}
              </button>
            </div>
          </div>
          <!-- Mini map (Leaflet) -->
          <div v-if="mapOpen">
            <div ref="mapEl" class="w-full border-t border-slate-100" style="height: 280px;"></div>
            <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
              <span>© OpenStreetMap contributors</span>
              <RouterLink to="/shop/map" class="text-violet-600 hover:underline font-medium">
                ดูแผนที่ทุกร้าน →
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== About / Recommendations ===== -->
      <div v-if="group.description" id="store-about" class="max-w-5xl mx-auto px-4 sm:px-6 mt-4">
        <div class="box-card p-4 sm:p-5">
          <h3 class="font-semibold text-slate-700 flex items-center gap-2 mb-2">
            <i class="fi fi-rr-info text-violet-500"></i> เกี่ยวกับร้าน
          </h3>
          <p class="text-slate-600 text-sm leading-relaxed">{{ group.description }}</p>
          <div v-if="group.contact_address || group.contact_phone" class="mt-3 pt-3 border-t border-slate-100 flex flex-col gap-1.5 text-sm text-slate-500">
            <span v-if="group.contact_address" class="flex items-start gap-2">
              <i class="fi fi-rr-marker text-violet-400 mt-0.5 shrink-0"></i>
              <span>{{ group.contact_address }}</span>
            </span>
            <span v-if="group.contact_phone" class="flex items-center gap-2">
              <i class="fi fi-rr-phone-call text-violet-400"></i>
              <a :href="`tel:${group.contact_phone}`" class="hover:text-violet-600 hover:underline">{{ group.contact_phone }}</a>
            </span>
          </div>
        </div>
      </div>

      <!-- ===== Products ===== -->
      <div id="store-products" class="max-w-5xl mx-auto px-4 sm:px-6 py-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-slate-800 flex items-center gap-2">
            <i class="fi fi-rr-grid text-violet-500"></i> สินค้าในร้าน
            <span class="text-slate-400 font-normal text-sm">({{ products?.total || 0 }} รายการ)</span>
          </h2>
        </div>

        <div v-if="products?.data?.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <ProductCard v-for="p in products.data" :key="p.id" :product="p" />
        </div>
        <div v-else class="box-card p-16 text-center text-slate-400">
          <i class="fi fi-rr-box-open text-4xl mb-3 block"></i>
          <p>ยังไม่มีสินค้าในร้านนี้</p>
        </div>

        <!-- Pagination -->
        <div v-if="products?.last_page > 1" class="flex justify-center gap-2 mt-8">
          <button v-for="p in products.last_page" :key="p" @click="loadPage(p)"
            class="w-9 h-9 rounded-full text-sm font-medium transition"
            :class="p === products.current_page ? 'bg-violet-600 text-white' : 'bg-white text-slate-600 hover:bg-violet-50 border border-slate-200'">
            {{ p }}
          </button>
        </div>
      </div>
    </template>

    <div v-else class="flex items-center justify-center min-h-[50vh] text-slate-400">
      <div class="text-center">
        <i class="fi fi-rr-shop text-5xl mb-3 block"></i>
        <p>ไม่พบร้านค้านี้</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth.js'
import api from '../../api/index.js'
import ProductCard from './components/ProductCard.vue'
import ShareButton from './components/ShareButton.vue'

const route  = useRoute()
const router = useRouter()
const { user } = useAuth()

const group         = ref(null)
const products      = ref(null)
const loading       = ref(true)
const showDistricts = ref(false)
const mapEl         = ref(null)
const mapOpen       = ref(false)
const userLat       = ref(null)
const userLng       = ref(null)
let mapInstance     = null
let L               = null

function haversine(lat1, lng1, lat2, lng2) {
  const R = 6371
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLng = (lng2 - lng1) * Math.PI / 180
  const a = Math.sin(dLat/2)**2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2)**2
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
}

function fmtDist(km) {
  return km < 1 ? `${Math.round(km * 1000)} ม.` : `${km.toFixed(1)} กม.`
}

const distance = computed(() => {
  if (!group.value?.lat || !group.value?.lng || userLat.value == null) return null
  return haversine(userLat.value, userLng.value, Number(group.value.lat), Number(group.value.lng))
})

function requestLocation() {
  if (!navigator.geolocation) return
  navigator.geolocation.getCurrentPosition(
    pos => { userLat.value = pos.coords.latitude; userLng.value = pos.coords.longitude },
    () => { /* denied — show map without distance */ },
  )
}

async function initMap() {
  L = await import('leaflet')
  L = L.default || L
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
    shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
  })
  await import('leaflet/dist/leaflet.css')

  const lat = Number(group.value.lat)
  const lng = Number(group.value.lng)
  mapInstance = L.map(mapEl.value, { zoomControl: true }).setView([lat, lng], 14)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 18,
  }).addTo(mapInstance)

  const storeIcon = L.divIcon({
    className: '',
    html: `<div style="background:#7c3aed;color:white;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:bold;box-shadow:0 3px 12px rgba(124,58,237,.5);border:3px solid white;">${group.value.name.charAt(0)}</div>`,
    iconSize: [34, 34],
    iconAnchor: [17, 17],
  })
  L.marker([lat, lng], { icon: storeIcon })
    .addTo(mapInstance)
    .bindPopup(`<b style="color:#7c3aed">${group.value.name}</b>${group.value.contact_address ? `<br><small style="color:#94a3b8">${group.value.contact_address}</small>` : ''}`)
    .openPopup()

  if (userLat.value != null) {
    const userIcon = L.divIcon({
      className: '',
      html: `<div style="width:14px;height:14px;background:#2563eb;border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(37,99,235,.5)"></div>`,
      iconSize: [14, 14],
      iconAnchor: [7, 7],
    })
    L.marker([userLat.value, userLng.value], { icon: userIcon })
      .addTo(mapInstance)
      .bindPopup('<b style="color:#2563eb">ตำแหน่งของคุณ</b>')
    mapInstance.fitBounds(
      L.latLngBounds([[lat, lng], [userLat.value, userLng.value]]),
      { padding: [40, 40] },
    )
  }
}

async function toggleMap() {
  if (mapOpen.value) {
    if (mapInstance) { mapInstance.remove(); mapInstance = null }
    mapOpen.value = false
    return
  }
  mapOpen.value = true
  await nextTick()
  await initMap()
}

function scrollToSection(id) {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function load(page = 1) {
  if (page === 1) loading.value = true
  try {
    const { data } = await api.get(`/shop/sellers/${route.params.slug}`, { params: { page } })
    group.value    = data.group
    products.value = data.products
  } catch { group.value = null } finally { loading.value = false }
}

function loadPage(page) { load(page); window.scrollTo({ top: 0, behavior: 'smooth' }) }

async function startChat() {
  try {
    const { data } = await api.post(`/shop/chat/start/${group.value.slug}`)
    router.push({ path: '/shop/chat', query: { id: data.id } })
  } catch { /* ignore */ }
}

onMounted(() => { load(); requestLocation() })

onUnmounted(() => {
  if (mapInstance) { mapInstance.remove(); mapInstance = null }
})
</script>
