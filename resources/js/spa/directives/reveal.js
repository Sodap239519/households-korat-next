// v-reveal — ค่อยๆ fade-up เมื่อ element เลื่อนเข้ามาในจอ
// ใช้: <div v-reveal />  หรือ  <div v-reveal="index" /> (ใส่ค่าเป็น index เพื่อหน่วงเวลาแบบ stagger)
let observer = null

function ensureObserver() {
    if (observer) return observer
    if (typeof IntersectionObserver === 'undefined') return null
    observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible')
                    observer.unobserve(entry.target)
                }
            }
        },
        { threshold: 0.08, rootMargin: '0px 0px -40px 0px' }
    )
    return observer
}

export const reveal = {
    mounted(el, binding) {
        const obs = ensureObserver()
        // หน่วงเวลาแบบขั้นบันได ถ้าส่ง index มา
        const idx = Number(binding.value)
        if (!Number.isNaN(idx) && idx > 0) {
            el.style.animationDelay = `${Math.min(idx, 12) * 70}ms`
        }
        el.classList.add('reveal')
        if (!obs) { el.classList.add('is-visible'); return } // fallback: แสดงเลย
        obs.observe(el)
    },
    unmounted(el) {
        observer?.unobserve(el)
    },
}
