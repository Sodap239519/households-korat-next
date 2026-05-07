// Thai date helpers — convert ISO/Date input to Buddhist-era Thai strings.
// e.g. fmtThaiDate('2026-05-07') → "7 พฤษภาคม 2569"

const MONTHS_FULL = [
  'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
  'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
]

const MONTHS_SHORT = [
  'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
  'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.',
]

const WEEKDAYS_FULL = [
  'อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์',
]

function toDate(input) {
  if (input == null || input === '') return null
  if (input instanceof Date) return isNaN(input) ? null : input
  const d = new Date(input)
  return isNaN(d) ? null : d
}

export function fmtThaiDate(input, opts = {}) {
  const d = toDate(input)
  if (!d) return '-'
  const day   = d.getDate()
  const month = opts.short ? MONTHS_SHORT[d.getMonth()] : MONTHS_FULL[d.getMonth()]
  const year  = d.getFullYear() + 543
  return `${day} ${month} ${year}`
}

export function fmtThaiDateTime(input, opts = {}) {
  const d = toDate(input)
  if (!d) return '-'
  const date = fmtThaiDate(d, opts)
  const time = d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
  return `${date} ${time} น.`
}

export function fmtThaiDateLong(input) {
  const d = toDate(input)
  if (!d) return '-'
  return `วัน${WEEKDAYS_FULL[d.getDay()]}ที่ ${fmtThaiDate(d)}`
}

// "5 นาทีที่แล้ว", "3 ชั่วโมงที่แล้ว"
export function relativeTime(input) {
  const d = toDate(input)
  if (!d) return ''
  const diff = Date.now() - d.getTime()
  const m = Math.round(diff / 60000)
  if (m < 1)  return 'เมื่อสักครู่'
  if (m < 60) return `${m} นาทีที่แล้ว`
  const h = Math.round(m / 60)
  if (h < 24) return `${h} ชั่วโมงที่แล้ว`
  const dy = Math.round(h / 24)
  if (dy < 30) return `${dy} วันที่แล้ว`
  return fmtThaiDate(d, { short: true })
}
