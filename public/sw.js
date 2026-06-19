// Service worker สำหรับ PWA ตลาดชุมชนโคราช
// v2 — ไม่ cache build assets (Vite จัดการด้วย filename-hash แล้ว)
//      SW ทำหน้าที่เฉพาะ offline-fallback ให้หน้า /shop เท่านั้น
const CACHE = 'shop-shell-v2'

self.addEventListener('install', (event) => {
  // pre-cache เฉพาะ shell page สำหรับ offline
  event.waitUntil(
    caches.open(CACHE)
      .then((c) => c.add('/shop'))
      .catch(() => {})
  )
  self.skipWaiting()
})

self.addEventListener('activate', (event) => {
  // ลบ cache เก่าทุกเวอร์ชัน
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  )
  self.clients.claim()
})

self.addEventListener('fetch', (event) => {
  const req = event.request
  if (req.method !== 'GET') return

  const url = new URL(req.url)

  // ปล่อย API / sanctum ผ่านไปเครือข่ายโดยตรง
  if (url.pathname.startsWith('/api') || url.pathname.startsWith('/sanctum')) return

  // ปล่อย build assets ผ่านไปเครือข่ายโดยตรง (Vite ใช้ hash-based filenames)
  if (url.pathname.startsWith('/build/')) return

  // Navigation requests → network-first, fallback เป็น /shop เมื่อ offline
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() =>
        caches.match(req).then((hit) => hit || caches.match('/shop'))
      )
    )
  }
})
