<template>
  <!-- จังหวัด -->
  <div>
    <label class="form-label">จังหวัด</label>
    <AddressDropdown
      v-model="provinceVal"
      :options="provinces"
      :loading="loadingProvince"
      placeholder="เลือกจังหวัด"
      display-key="province"
      @search="searchProvince"
      @select="onSelectProvince"
      @clear="onClearProvince"
    />
  </div>

  <!-- อำเภอ/เขต -->
  <div>
    <label class="form-label">อำเภอ/เขต</label>
    <AddressDropdown
      v-model="districtVal"
      :options="districts"
      :loading="loadingDistrict"
      :disabled="!provinceVal"
      placeholder="เลือกอำเภอ/เขต"
      display-key="district"
      @search="searchDistrict"
      @select="onSelectDistrict"
      @clear="onClearDistrict"
    />
  </div>

  <!-- ตำบล/แขวง -->
  <div>
    <label class="form-label">ตำบล/แขวง</label>
    <AddressDropdown
      v-model="subdistrictVal"
      :options="subdistricts"
      :loading="loadingSubdistrict"
      :disabled="!districtVal"
      placeholder="เลือกตำบล/แขวง"
      display-key="subdistrict"
      @search="searchSubdistrict"
      @select="onSelectSubdistrict"
      @clear="onClearSubdistrict"
    />
  </div>

  <!-- รหัสไปรษณีย์ -->
  <div>
    <label class="form-label">รหัสไปรษณีย์</label>
    <input
      v-model="postalVal"
      @input="$emit('update:postalCode', $event.target.value)"
      class="inp w-full"
      placeholder="รหัสไปรษณีย์"
      maxlength="5"
      inputmode="numeric"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '../../../api/index.js'
import AddressDropdown from './AddressDropdown.vue'

const props = defineProps({
  subDistrict: { type: String, default: '' },
  district:    { type: String, default: '' },
  province:    { type: String, default: '' },
  postalCode:  { type: String, default: '' },
})
const emit = defineEmits(['update:subDistrict','update:district','update:province','update:postalCode'])

const provinceVal     = ref(props.province     || '')
const districtVal     = ref(props.district     || '')
const subdistrictVal  = ref(props.subDistrict  || '')
const postalVal       = ref(props.postalCode   || '')

const provinces      = ref([])
const districts      = ref([])
const subdistricts   = ref([])
const loadingProvince    = ref(false)
const loadingDistrict    = ref(false)
const loadingSubdistrict = ref(false)

// sync props → local when parent sets initial values (edit mode)
watch(() => props.province,    v => { if (v !== provinceVal.value)    provinceVal.value    = v || '' })
watch(() => props.district,    v => { if (v !== districtVal.value)    districtVal.value    = v || '' })
watch(() => props.subDistrict, v => { if (v !== subdistrictVal.value) subdistrictVal.value = v || '' })
watch(() => props.postalCode,  v => { if (v !== postalVal.value)      postalVal.value      = v || '' })

async function searchProvince(q) {
  loadingProvince.value = true
  try {
    const { data } = await api.get('/address/search', { params: { q, type: 'province' } })
    provinces.value = data
  } finally { loadingProvince.value = false }
}

async function searchDistrict(q) {
  if (!provinceVal.value) return
  loadingDistrict.value = true
  try {
    const { data } = await api.get('/address/search', { params: { q, type: 'district', province: provinceVal.value } })
    districts.value = data
  } finally { loadingDistrict.value = false }
}

async function searchSubdistrict(q) {
  if (!districtVal.value) return
  loadingSubdistrict.value = true
  try {
    const { data } = await api.get('/address/search', { params: { q, type: 'subdistrict', district: districtVal.value, province: provinceVal.value } })
    subdistricts.value = data
  } finally { loadingSubdistrict.value = false }
}

function onSelectProvince(item) {
  provinceVal.value   = item.province
  districtVal.value   = ''
  subdistrictVal.value = ''
  postalVal.value     = ''
  districts.value     = []
  subdistricts.value  = []
  emit('update:province',    item.province)
  emit('update:district',    '')
  emit('update:subDistrict', '')
  emit('update:postalCode',  '')
}
function onClearProvince() {
  provinceVal.value = ''; districtVal.value = ''; subdistrictVal.value = ''; postalVal.value = ''
  emit('update:province', ''); emit('update:district', ''); emit('update:subDistrict', ''); emit('update:postalCode', '')
}

function onSelectDistrict(item) {
  districtVal.value    = item.district
  subdistrictVal.value = ''
  postalVal.value      = ''
  subdistricts.value   = []
  emit('update:district',    item.district)
  emit('update:subDistrict', '')
  emit('update:postalCode',  '')
}
function onClearDistrict() {
  districtVal.value = ''; subdistrictVal.value = ''; postalVal.value = ''
  emit('update:district', ''); emit('update:subDistrict', ''); emit('update:postalCode', '')
}

function onSelectSubdistrict(item) {
  subdistrictVal.value = item.subdistrict
  postalVal.value      = item.zipcode || ''
  emit('update:subDistrict', item.subdistrict)
  emit('update:postalCode',  item.zipcode || '')
}
function onClearSubdistrict() {
  subdistrictVal.value = ''; postalVal.value = ''
  emit('update:subDistrict', ''); emit('update:postalCode', '')
}
</script>

<style scoped>
.inp {
  height: 2.75rem;
  padding: 0 0.75rem;
  border-radius: 0.75rem;
  border: 1px solid rgb(226 232 240);
  background: white;
  width: 100%;
  font-size: 0.875rem;
  color: rgb(30 41 59);
  transition: border-color 0.15s;
}
.inp:focus { outline: none; border-color: rgb(167 139 250); }
</style>
