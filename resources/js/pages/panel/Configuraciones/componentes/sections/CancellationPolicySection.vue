<template>
  <div class="">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-base font-semibold">Políticas de Cancelación</h3>
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
          <!-- Tiempo límite -->
          <div v-if="data.key === 'cancel_hours'" class="flex items-center justify-center gap-2">
            <InputNumber 
              v-model="localSettings.time_limit_hours" 
              :min="0" 
              :max="168" 
              class="w-24"
              @update:modelValue="markChanged"
            />
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">hrs</span>
          </div>

          <!-- Reembolso -->
          <div v-else-if="data.key === 'refund_percent'" class="flex items-center justify-center gap-2">
            <InputNumber 
              v-model="localSettings.refund_percentage" 
              :min="0" 
              :max="100" 
              class="w-24"
              @update:modelValue="markChanged"
            />
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">%</span>
          </div>

          <!-- No-show -->
          <div v-else-if="data.key === 'noshow_charge'" class="flex items-center justify-center gap-2">
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">S/.</span>
            <InputNumber 
              v-model="localSettings.no_show_charge" 
              :min="0" 
              :step="5" 
              mode="decimal" 
              :maxFractionDigits="2" 
              class="w-24"
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
import Button from 'primevue/button'
import { useSubBranchConfigurationStore } from '../../stores/subBranchConfigurationStore'
import type { CancellationPolicy } from '../../stores/subBranchConfigurationStore'

const store = useSubBranchConfigurationStore()
const toast = useToast()

const localSettings = ref<Partial<CancellationPolicy>>({
  time_limit_hours: 24,
  refund_percentage: 80,
  no_show_charge: 0,
})

const hasChanges = ref(false)

const rows = [
  { key: 'cancel_hours', label: 'Tiempo límite para cancelar', desc: 'Horas antes del check-in para cancelar sin penalidad' },
  { key: 'refund_percent', label: 'Porcentaje de reembolso', desc: 'Cantidad a devolver en cancelaciones' },
  { key: 'noshow_charge', label: 'Cargo por no-show', desc: 'Penalidad si el cliente no se presenta' },
]

watch(() => store.cancellationPolicy, (newSettings) => {
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

  const result = await store.updateCancellationPolicy(store.currentSubBranchId, localSettings.value)

  if (result.success) {
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: result.message || 'Política de cancelación actualizada',
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