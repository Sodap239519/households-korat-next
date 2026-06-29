import { ref } from 'vue'

const locale = ref(localStorage.getItem('shop_locale') || 'th')

const dict = {
  // Filter sidebar
  'หมวดหมู่':      { en: 'Category' },
  'ทั้งหมด':       { en: 'All' },
  'กลุ่มผู้ขาย':   { en: 'Sellers' },
  'ทุกกลุ่ม':      { en: 'All Groups' },
  'ช่วงราคา':      { en: 'Price Range' },
  'ต่ำสุด':        { en: 'Min' },
  'สูงสุด':        { en: 'Max' },
  'กรองราคา':      { en: 'Filter' },
  // Sort
  'ใหม่ล่าสุด':    { en: 'Newest' },
  'ราคา: ต่ำ→สูง': { en: 'Price: Low→High' },
  'ราคา: สูง→ต่ำ': { en: 'Price: High→Low' },
  'ยอดนิยม':       { en: 'Popular' },
  'คะแนนรีวิว':    { en: 'Rating' },
  'เรียงโดย':      { en: 'Sort' },
  // Catalog
  'พบ':            { en: 'Found' },
  'รายการ':        { en: 'items' },
  'สำหรับ':        { en: 'for' },
  'ไม่พบสินค้าที่ตรงกับเงื่อนไข': { en: 'No products found' },
  'ตัวกรอง':       { en: 'Filters' },
}

export function useLocale() {
  function t(text) {
    if (locale.value === 'en' && dict[text]) return dict[text].en
    return text
  }

  function toggleLocale() {
    locale.value = locale.value === 'th' ? 'en' : 'th'
    localStorage.setItem('shop_locale', locale.value)
  }

  return { locale, t, toggleLocale }
}
