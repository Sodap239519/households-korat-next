import { ref, computed } from 'vue'
import api from '../api/index.js'

// singleton state
const likedIds = ref(new Set())
let fetched = false

export function useWishlist() {
    async function fetchIds(user) {
        if (!user || fetched) return
        try {
            const { data } = await api.get('/shop/my/wishlist-ids')
            likedIds.value = new Set(data)
            fetched = true
        } catch { /* ignore — อาจยังไม่ได้ login */ }
    }

    function isLiked(productId) {
        return likedIds.value.has(Number(productId))
    }

    async function toggle(productId, user) {
        if (!user) return false
        const id = Number(productId)
        const wasLiked = likedIds.value.has(id)

        // Optimistic update
        const next = new Set(likedIds.value)
        wasLiked ? next.delete(id) : next.add(id)
        likedIds.value = next

        try {
            await api.post(`/shop/wishlist/${id}/toggle`)
        } catch {
            // Revert on error
            const revert = new Set(likedIds.value)
            wasLiked ? revert.add(id) : revert.delete(id)
            likedIds.value = revert
        }

        return !wasLiked
    }

    function reset() {
        likedIds.value = new Set()
        fetched = false
    }

    const count = computed(() => likedIds.value.size)

    return { isLiked, toggle, fetchIds, count, reset, likedIds }
}
