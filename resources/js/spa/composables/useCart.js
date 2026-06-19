import { ref, computed } from 'vue'

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
                price:      Number(product.effective_price ?? product.sale_price ?? product.price),
                unit:       product.unit,
                image:      product.primary_image_url || null,
                group_id:   product.seller_group_id ?? product.seller_group?.id ?? product.sellerGroup?.id,
                group_name: product.seller_group?.name ?? product.sellerGroup?.name ?? '',
                stock_qty:  product.stock_qty ?? null,
                qty,
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

    const count = computed(() => items.value.reduce((s, i) => s + i.qty, 0))

    const subtotal = computed(() =>
        items.value.reduce((s, i) => s + i.price * i.qty, 0)
    )

    /** แยกตะกร้าตามกลุ่มผู้ขาย (multi-vendor → 1 ออเดอร์/กลุ่ม) */
    const groups = computed(() => {
        const map = {}
        for (const i of items.value) {
            const key = i.group_id ?? 'unknown'
            if (!map[key]) {
                map[key] = { group_id: i.group_id, group_name: i.group_name, items: [], subtotal: 0 }
            }
            map[key].items.push(i)
            map[key].subtotal += i.price * i.qty
        }
        return Object.values(map)
    })

    return { items, add, updateQty, remove, clear, clearGroup, count, subtotal, groups }
}
