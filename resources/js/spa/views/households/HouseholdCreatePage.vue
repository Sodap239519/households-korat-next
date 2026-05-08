<template>
  <div class="p-3 sm:p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-5">
      <div>
        <Button label="กลับไปรายการ" icon="fi fi-rr-arrow-small-left" severity="secondary" outlined @click="$router.push('/app/households')" />
      </div>
      <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
        <i class="fi fi-rr-add-document text-violet-600"></i>
        เพิ่มรายการครัวเรือน
      </h2>
    </div>

    <HouseholdFormDialog v-model="open" :householdId="null" @saved="onSaved" />
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import HouseholdFormDialog from './HouseholdFormDialog.vue'

const router = useRouter()
const toast = useToast()
const open = ref(true)

function onSaved() {
  toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'เพิ่มครัวเรือนแล้ว', life: 1800 })
  setTimeout(() => router.push('/app/households'), 600)
}

// If user closes the dialog, navigate back to list
import { watch } from 'vue'
watch(open, (v) => { if (!v) router.push('/app/households') })

onMounted(() => { open.value = true })
</script>
