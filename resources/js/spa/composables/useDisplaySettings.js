import { ref } from 'vue'

// ตั้งค่าการแสดงผลสำหรับผู้ใช้ (เข้าถึงง่าย): ขนาดตัวอักษร + โหมดสีจอ
// เก็บลง localStorage และใช้กับทั้งเว็บผ่าน <html>

const STORAGE_KEY = 'shop_display_v1'
const FONT_STEPS = [100, 112, 125, 140] // %  (ปกติ → ใหญ่สุด)
const MODES = ['light', 'dark']

const fontIndex = ref(0)
const mode = ref('dark')
let initialized = false

function persist() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ fontIndex: fontIndex.value, mode: mode.value }))
}

function apply() {
    const html = document.documentElement
    // บนมือถือ (จอ < 640px) ลด base ลง ~8% ให้ทุกอย่างกะทัดรัดพอดีตา
    const mobileScale = (typeof window !== 'undefined' && window.innerWidth < 640) ? 0.92 : 1
    html.style.fontSize = (FONT_STEPS[fontIndex.value] * mobileScale) + '%'
    html.classList.toggle('shop-dark', mode.value === 'dark')
    // Soft Purple mode มีพื้นหลังอ่อน — คง PrimeVue ไว้ในโหมด light ตลอด
    html.classList.remove('app-dark')
}

function load() {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}')
        if (typeof saved.fontIndex === 'number') fontIndex.value = Math.min(Math.max(saved.fontIndex, 0), FONT_STEPS.length - 1)
        if (MODES.includes(saved.mode)) mode.value = saved.mode
    } catch { /* ignore */ }
}

export function useDisplaySettings() {
    if (!initialized) {
        initialized = true
        load()
        apply()
        // re-apply เมื่อหมุนจอ/เปลี่ยนขนาด (สลับ scale มือถือ↔เดสก์ท็อป)
        if (typeof window !== 'undefined') {
            let t = null
            window.addEventListener('resize', () => {
                clearTimeout(t)
                t = setTimeout(apply, 200)
            })
        }
    }

    function increaseFont() { if (fontIndex.value < FONT_STEPS.length - 1) { fontIndex.value++; apply(); persist() } }
    function decreaseFont() { if (fontIndex.value > 0) { fontIndex.value--; apply(); persist() } }
    function resetFont()    { fontIndex.value = 0; apply(); persist() }
    function setMode(m)     { if (MODES.includes(m)) { mode.value = m; apply(); persist() } }

    return {
        fontIndex, mode,
        fontSteps: FONT_STEPS,
        increaseFont, decreaseFont, resetFont, setMode,
    }
}
