import { computed } from 'vue'

const KEY = 'shop_buy_now_v1'

function read() {
    try { return JSON.parse(sessionStorage.getItem(KEY)) } catch { return null }
}

export function useBuyNow() {
    /** สำหรับซื้อทันที (1 รายการ) */
    function set(item) {
        sessionStorage.setItem(KEY, JSON.stringify([item]))
    }

    /** สำหรับ reorder (หลายรายการ) */
    function setAll(itemsArray) {
        sessionStorage.setItem(KEY, JSON.stringify(itemsArray))
    }

    function clear() { sessionStorage.removeItem(KEY) }
    function has()   { const d = read(); return !!(d && (Array.isArray(d) ? d.length : true)) }

    const items = computed(() => {
        const d = read()
        if (!d) return []
        return Array.isArray(d) ? d : [d]
    })

    const groups = computed(() => {
        const list = items.value
        if (!list.length) return []
        const map = {}
        for (const i of list) {
            const key = (i.group_id ?? 'x') + ':' + (i.seller_id ?? '')
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
    })

    const subtotal = computed(() =>
        items.value.reduce((s, i) => s + i.price * i.qty, 0)
    )

    return { set, setAll, clear, has, items, groups, subtotal }
}
