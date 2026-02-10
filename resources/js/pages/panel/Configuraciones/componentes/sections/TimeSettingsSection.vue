<template>
  <div class="">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-base font-semibold">Tiempo</h3>
      <Button 
        v-if="hasChanges"
        label="Guardar" 
        icon="pi pi-save" 
        size="small"
        :loading="store.saving"
        @click="save" 
      />
    </div>

    <DataTable :value="rows" class="p-datatable-sm">
      <ColumnGroup type="header">
        <Row>
          <Column header="Configuración" style="width: 55%" />
          <Column header="Valor" style="width: 35%; text-align: center" />
          <Column header="" style="width: 10%" />
        </Row>
      </ColumnGroup>

      <Column>
        <template #body="{ data }">
          <p class="m-0 font-medium">{{ data.label }}</p>
          <small class="text-gray-400">{{ data.desc }}</small>
        </template>
      </Column>

      <Column style="text-align: center">
        <template #body="{ data }">
          <!-- Tiempo máximo -->
          <div v-if="data.key === 'max_time'" class="flex items-center justify-center gap-2">
            <InputNumber 
              v-model="localSettings.max_allowed_time" 
              :min="1" 
              :max="1440" 
              class="w-24"
              @update:modelValue="markChanged"
            />
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">min</span>
          </div>

          <!-- Tolerancia -->
          <div v-else-if="data.key === 'tolerance'" class="flex items-center justify-center gap-2">
            <InputNumber 
              v-model="localSettings.extra_tolerance" 
              :min="0" 
              :max="120" 
              :disabled="!localSettings.apply_tolerance" 
              class="w-24"
              @update:modelValue="markChanged"
            />
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">min</span>
          </div>

          <!-- Aplicar tolerancia -->
          <div v-else-if="data.key === 'apply_tolerance'" class="flex justify-center">
            <Checkbox 
              v-model="localSettings.apply_tolerance" 
              :binary="true"
              @update:modelValue="markChanged"
            />
          </div>
        </template>
      </Column>

      <Column />
    </DataTable>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ColumnGroup from 'primevue/columngroup'
import Row from 'primevue/row'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import { useSubBranchConfigurationStore } from '../../stores/subBranchConfigurationStore'
import type { TimeSettings } from '../../stores/subBranchConfigurationStore'

const store = useSubBranchConfigurationStore()
const toast = useToast()

const localSettings = ref<Partial<TimeSettings>>({
  max_allowed_time: 60,
  extra_tolerance: 0,
  apply_tolerance: false,
})

const hasChanges = ref(false)

const rows = [
  { key: 'max_time', label: 'Tiempo máximo permitido', desc: 'Duración base que puede usar el cliente' },
  { key: 'tolerance', label: 'Tolerancia extra', desc: 'Minutos de gracia antes de penalizar' },
  { key: 'apply_tolerance', label: 'Aplicar tolerancia', desc: 'Activa o desactiva el tiempo de gracia' },
]

// Watch para sincronizar con el store
watch(() => store.timeSettings, (newSettings) => {
  if (newSettings) {
    localSettings.value = { ...newSettings }
    hasChanges.value = false
  }
}, { immediate: true, deep: true })

function markChanged() {
  hasChanges.value = true
}

async function save() {
  if (!store.currentSubBranchId) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'No hay una sucursal seleccionada',
      life: 3000
    })
    return
  }

  const result = await store.updateTimeSettings(store.currentSubBranchId, localSettings.value)

  if (result.success) {
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: result.message || 'Configuración actualizada',
      life: 3000
    })
    hasChanges.value = false
  } else {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: result.error || 'No se pudo guardar',
      life: 5000
    })
  }
}
</script>