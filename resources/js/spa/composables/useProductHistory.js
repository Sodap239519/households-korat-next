import { ref } from 'vue'

const STORAGE_KEY = 'ph_history'
const MAX_ITEMS   = 30

const history = ref(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'))

function persist() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(history.value))
}

function addToHistory(product) {
  if (!product?.id) return
  const item = {
    id:             product.id,
    slug:           product.slug,
    name:           product.name,
    price:          Number(product.effective_price ?? product.sale_price ?? product.price ?? 0),
    original_price: Number(product.price ?? 0),
    image:          product.primary_image_url ?? null,
    unit:           product.unit ?? '',
    seller_name:    product.seller_group?.name ?? '',
    rating_avg:     product.rating_avg ?? 0,
    viewed_at:      Date.now(),
  }
  history.value = [item, ...history.value.filter(h => h.id !== product.id)].slice(0, MAX_ITEMS)
  persist()
}

function clearHistory() {
  history.value = []
  persist()
}

export function useProductHistory() {
  return { history, addToHistory, clearHistory }
}
