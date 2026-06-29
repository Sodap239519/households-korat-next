import { computed } from 'vue'

const KEY = 'shop_buy_now_v1'

function read() {
    try { return JSON.parse(sessionStorage.getItem(KEY)) } catch { return null }
}

export function useBuyNow() {
    function set(item) { sessionStorage.setItem(KEY, JSON.stringify(item)) }
    function clear() { sessionStorage.removeItem(KEY) }
    function has() { return !!read() }

    // computed ที่ mimic โครงสร้าง useCart
    const items = computed(() => {
        const d = read()
        return d ? [d] : []
    })

    const groups = computed(() => {
        const d = read()
        if (!d) return []
        return [{
            group_id:   d.group_id,
            group_name: d.group_name,
            items:      [d],
            subtotal:   d.price * d.qty,
        }]
    })

    const subtotal = computed(() => {
        const d = read()
        return d ? d.price * d.qty : 0
    })

    return { set, clear, has, items, groups, subtotal }
}
