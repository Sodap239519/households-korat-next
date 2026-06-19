import { ref } from 'vue'

// ตั้งค่าการแสดงผลสำหรับผู้ใช้ (เข้าถึงง่าย): ขนาดตัวอักษร + โหมดสีจอ
// เก็บลง localStorage และใช้กับทั้งเว็บผ่าน <html>

const STORAGE_KEY = 'shop_display_v1'
const FONT_STEPS = [100, 112, 125, 140] // %  (ปกติ → ใหญ่สุด)
const MODES = ['light', 'dark']

const fontIndex = ref(0)
const mode = ref('light')
let initialized = false

function persist() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ fontIndex: fontIndex.value, mode: mode.value }))
}

function apply() {
    const html = document.documentElement
    html.style.fontSize = FONT_STEPS[fontIndex.value] + '%'
    html.classList.toggle('shop-dark', mode.value === 'dark')
    // PrimeVue dark theme ใช้ .app-dark — เปิดพร้อมกันให้ component หลังบ้านมืดด้วย
    html.classList.toggle('app-dark', mode.value === 'dark')
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
