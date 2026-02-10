<template>
  <div class="">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-base font-semibold">Configuración de Reservas</h3>
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
          <!-- Anticipación mínima -->
          <div v-if="data.key === 'min_advance'" class="flex items-center justify-center gap-2">
            <InputNumber 
              v-model="localSettings.min_advance_hours" 
              :min="0" 
              :max="720" 
              class="w-24"
              @update:modelValue="markChanged"
            />
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">hrs</span>
          </div>

          <!-- Anticipación máxima -->
          <div v-else-if="data.key === 'max_advance'" class="flex items-center justify-center gap-2">
            <InputNumber 
              v-model="localSettings.max_advance_days" 
              :min="1" 
              :max="365" 
              class="w-24"
              @update:modelValue="markChanged"
            />
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">días</span>
          </div>

          <!-- Last minute -->
          <div v-else-if="data.key === 'lastminute_charge'" class="flex items-center justify-center gap-2">
            <InputNumber 
              v-model="localSettings.last_minute_surcharge_percentage" 
              :min="0" 
              :max="100" 
              class="w-24"
              @update:modelValue="markChanged"
            />
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">%</span>
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
import Button from 'primevue/button'
import { useSubBranchConfigurationStore } from '../../stores/subBranchConfigurationStore'
import type { ReservationSettings } from '../../stores/subBranchConfigurationStore'

const store = useSubBranchConfigurationStore()
const toast = useToast()

const localSettings = ref<Partial<ReservationSettings>>({
  min_advance_hours: 0,
  max_advance_days: 90,
  last_minute_surcharge_percentage: 0,
})

const hasChanges = ref(false)

const rows = [
  { key: 'min_advance', label: 'Anticipación mínima', desc: 'Tiempo mínimo requerido para reservar' },
  { key: 'max_advance', label: 'Anticipación máxima', desc: 'Cuánto tiempo adelante se puede reservar' },
  { key: 'lastminute_charge', label: 'Recargo last-minute', desc: 'Porcentaje extra por reservas de último momento' },
]

watch(() => store.reservationSettings, (newSettings) => {
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

  const result = await store.updateReservationSettings(store.currentSubBranchId, localSettings.value)

  if (result.success) {
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: result.message || 'Configuración de reservas actualizada',
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