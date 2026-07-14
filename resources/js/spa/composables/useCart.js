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
    /** จับคู่รายการในตะกร้า ตาม product + option (ตัวเลือกต่างกัน = คนละรายการ) */
    function sameItem(i, productId, optionId) {
        return i.product_id === productId && (i.option_id ?? null) === (optionId ?? null)
    }

    /** เพิ่มสินค้าลงตะกร้า (รวม qty ถ้ามีอยู่แล้ว) — รองรับตัวเลือก; คืน false ถ้าสินค้าหมด */
    function add(product, qty = 1, option = null) {
        const optId = option?.id ?? null
        const stock = option ? (option.stock_qty ?? null) : (product.stock_qty ?? null)
        if (stock !== null && Number(stock) <= 0) return false   // หมด — เพิ่มไม่ได้
        const cap = stock !== null ? Number(stock) : Infinity
        const existing = items.value.find(i => sameItem(i, product.id, optId))
        if (existing) {
            existing.qty = Math.min(existing.qty + qty, cap)
        } else {
            const unitPrice = Number(option ? option.price : (product.effective_price ?? product.sale_price ?? product.price))
            items.value.push({
                product_id:  product.id,
                option_id:   optId,
                option_name: option?.name ?? null,
                slug:        product.slug,
                name:        product.name,
                price:          unitPrice,
                original_price: Number(option ? option.price : product.price),
                unit:        product.unit,
                image:       product.primary_image_url || null,
                group_id:    product.seller_group_id ?? product.seller_group?.id ?? product.sellerGroup?.id,
                group_name:  product.seller_group?.name ?? product.sellerGroup?.name ?? '',
                group_slug:  product.seller_group?.slug ?? product.sellerGroup?.slug ?? '',
                seller_id:   product.seller_user?.id ?? product.seller_user_id ?? null,
                seller_name: product.seller_user?.shop_name || product.seller_user?.name || null,
                stock_qty:   stock,
                qty:         Math.min(qty, cap),
                added_at:    new Date().toISOString(),
            })
        }
        persist()
        return true
    }

    /** reorder: ตั้งค่า qty เป็น qty เสมอ + อัปเดตราคาปัจจุบัน (ไม่สะสมถ้ากดซ้ำ) */
    function setItem(product, qty = 1) {
        const existing = items.value.find(i => i.product_id === product.id)
        if (existing) {
            existing.qty            = qty
            existing.price          = Number(product.effective_price ?? product.sale_price ?? product.price)
            existing.original_price = Number(product.price)
            existing.stock_qty      = product.stock_qty ?? null
        } else {
            items.value.push({
                product_id:     product.id,
                slug:           product.slug,
                name:           product.name,
                price:          Number(product.effective_price ?? product.sale_price ?? product.price),
                original_price: Number(product.price),
                unit:           product.unit,
                image:          product.primary_image_url || null,
                group_id:       product.seller_group_id ?? product.seller_group?.id ?? product.sellerGroup?.id,
                group_name:     product.seller_group?.name ?? product.sellerGroup?.name ?? '',
                group_slug:     product.seller_group?.slug ?? product.sellerGroup?.slug ?? '',
                seller_id:      product.seller_user?.id ?? product.seller_user_id ?? null,
                seller_name:    product.seller_user?.shop_name || product.seller_user?.name || null,
                stock_qty:      product.stock_qty ?? null,
                qty,
                added_at:       new Date().toISOString(),
            })
        }
        persist()
    }

    function updateQty(productId, qty, optionId = null) {
        const item = items.value.find(i => sameItem(i, productId, optionId))
        if (item) {
            item.qty = Math.max(1, qty)
            persist()
        }
    }

    function remove(productId, optionId = null) {
        items.value = items.value.filter(i => !sameItem(i, productId, optionId))
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

    /** ลบเฉพาะรายการที่เลือกไว้ (ใช้หลัง checkout สำเร็จ — เหลือรายการที่ไม่ได้เลือกไว้ในตะกร้า) */
    function clearSelected() {
        items.value = items.value.filter(i => i.selected === false)
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

    // === การเลือกรายการ (checkbox) — ค่าเริ่มต้น = เลือกทั้งหมด ===
    function isSel(i) { return i.selected !== false }
    function itemGroupKey(i) { return (i.group_id ?? 'x') + ':' + (i.seller_id ?? '') }

    function toggleSelected(productId, optionId = null) {
        const item = items.value.find(i => sameItem(i, productId, optionId))
        if (item) { item.selected = !isSel(item); persist() }
    }
    function setGroupSelected(groupKey, value) {
        items.value.forEach(i => { if (itemGroupKey(i) === groupKey) i.selected = value })
        persist()
    }
    function toggleAll(value) { items.value.forEach(i => { i.selected = value }); persist() }

    const selectedItems = computed(() => items.value.filter(isSel))
    const allSelected   = computed(() => items.value.length > 0 && items.value.every(isSel))

    const count         = computed(() => items.value.length)          // badge = ทั้งหมดในตะกร้า
    const selectedCount = computed(() => selectedItems.value.length)
    const totalQty   = computed(() => selectedItems.value.reduce((s, i) => s + i.qty, 0))
    const fullPrice  = computed(() => selectedItems.value.reduce((s, i) => s + i.original_price * i.qty, 0))
    const subtotal   = computed(() => selectedItems.value.reduce((s, i) => s + i.price * i.qty, 0))
    const discount   = computed(() => fullPrice.value - subtotal.value)

    /** จัดกลุ่มตาม "ร้านย่อย" (โซน + seller_user) → 1 ออเดอร์/ร้านย่อย จ่ายแยกกัน */
    function buildGroups(list) {
        const map = {}
        for (const i of list) {
            const key = itemGroupKey(i)
            if (!map[key]) {
                map[key] = {
                    key, group_id: i.group_id, group_name: i.group_name, group_slug: i.group_slug ?? '',
                    seller_id: i.seller_id ?? null, seller_name: i.seller_name ?? null,
                    items: [], subtotal: 0,
                }
            }
            map[key].items.push(i)
            map[key].subtotal += i.price * i.qty
        }
        return Object.values(map)
    }
    const groups         = computed(() => buildGroups(items.value))          // แสดงในตะกร้า (ทั้งหมด)
    const selectedGroups = computed(() => buildGroups(selectedItems.value))  // สำหรับ checkout (เฉพาะที่เลือก)

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

    return {
        items, add, setItem, updateQty, remove, clear, clearGroup, clearSelected,
        count, selectedCount, totalQty, fullPrice, subtotal, discount,
        groups, selectedGroups, selectedItems, allSelected,
        toggleSelected, setGroupSelected, toggleAll, isSel, itemGroupKey,
        syncToServer, clearOnServer, syncPrices,
    }
}
