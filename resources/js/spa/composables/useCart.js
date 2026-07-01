import { ref, computed } from 'vue'
import api from '../api/index.js'

const STORAGE_KEY = 'shop_cart_v1'

function load() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    } catch {
        return []
    }
}

// shared reactive cart state (singleton)
const items = ref(load())

function persist() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items.value))
}

export function useCart() {
    /** เพิ่มสินค้าลงตะกร้า (รวม qty ถ้ามีอยู่แล้ว) */
    function add(product, qty = 1) {
        const existing = items.value.find(i => i.product_id === product.id)
        if (existing) {
            existing.qty += qty
        } else {
            items.value.push({
                product_id: product.id,
                slug:       product.slug,
                name:       product.name,
                price:          Number(product.effective_price ?? product.sale_price ?? product.price),
                original_price: Number(product.price),
                unit:       product.unit,
                image:      product.primary_image_url || null,
                group_id:   product.seller_group_id ?? product.seller_group?.id ?? product.sellerGroup?.id,
                group_name: product.seller_group?.name ?? product.sellerGroup?.name ?? '',
                group_slug: product.seller_group?.slug ?? product.sellerGroup?.slug ?? '',
                stock_qty:  product.stock_qty ?? null,
                qty,
                added_at:   new Date().toISOString(),
            })
        }
        persist()
    }

    function updateQty(productId, qty) {
        const item = items.value.find(i => i.product_id === productId)
        if (item) {
            item.qty = Math.max(1, qty)
            persist()
        }
    }

    function remove(productId) {
        items.value = items.value.filter(i => i.product_id !== productId)
        persist()
    }

    function clear() {
        items.value = []
        persist()
    }

    /** ลบเฉพาะสินค้าของกลุ่มหนึ่ง (ใช้หลัง checkout สำเร็จต่อกลุ่ม) */
    function clearGroup(groupId) {
        items.value = items.value.filter(i => i.group_id !== groupId)
        persist()
    }

    /** ซิงค์ตะกร้า localStorage → server (เรียกหลัง login) */
    async function syncToServer() {
        if (!items.value.length) return
        try {
            await api.post('/shop/cart/sync', {
                items: items.value.map(i => ({
                    slug:     i.slug,
                    qty:      i.qty,
                    added_at: i.added_at || new Date().toISOString(),
                })),
            })
        } catch { /* ignore — server sync เป็น best-effort */ }
    }

    /** แจ้ง server ว่าตะกร้าว่างแล้ว (เรียกหลัง checkout ทั้งหมด) */
    async function clearOnServer() {
        try { await api.post('/shop/cart/clear') } catch { /* ignore */ }
    }

    const count      = computed(() => items.value.length)
    const totalQty   = computed(() => items.value.reduce((s, i) => s + i.qty, 0))
    const fullPrice  = computed(() => items.value.reduce((s, i) => s + i.original_price * i.qty, 0))
    const subtotal   = computed(() => items.value.reduce((s, i) => s + i.price * i.qty, 0))
    const discount   = computed(() => fullPrice.value - subtotal.value)

    /** แยกตะกร้าตามกลุ่มผู้ขาย (multi-vendor → 1 ออเดอร์/กลุ่ม) */
    const groups = computed(() => {
        const map = {}
        for (const i of items.value) {
            const key = i.group_id ?? 'unknown'
            if (!map[key]) {
                map[key] = { group_id: i.group_id, group_name: i.group_name, group_slug: i.group_slug ?? '', items: [], subtotal: 0 }
            }
            map[key].items.push(i)
            map[key].subtotal += i.price * i.qty
        }
        return Object.values(map)
    })

    /** sync ราคาจากข้อมูล stock batch {slug: {price, original_price, stock}} */
    function syncPrices(stockData) {
        let changed = false
        items.value.forEach(item => {
            const info = stockData[item.slug]
            if (info && typeof info === 'object') {
                if (item.original_price === undefined || item.original_price === null) {
                    item.original_price = info.original_price
                    item.price          = info.price
                    changed = true
                }
            }
        })
        if (changed) persist()
    }

    return { items, add, updateQty, remove, clear, clearGroup, count, totalQty, fullPrice, subtotal, discount, groups, syncToServer, clearOnServer, syncPrices }
}
