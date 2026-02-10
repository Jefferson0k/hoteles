<template>
  <div class="">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-base font-semibold">Impuestos</h3>
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
          <!-- IGV -->
          <div v-if="data.key === 'igv'" class="flex items-center justify-center gap-2">
            <InputNumber 
              v-model="localSettings.tax_percentage" 
              :min="0" 
              :max="100" 
              class="w-24"
              @update:modelValue="markChanged"
            />
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">%</span>
          </div>

          <!-- Incluido en precio -->
          <div v-else-if="data.key === 'tax_included'" class="flex justify-center">
            <Checkbox 
              v-model="localSettings.tax_included" 
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
import type { TaxSettings } from '../../stores/subBranchConfigurationStore'

const store = useSubBranchConfigurationStore()
const toast = useToast()

const localSettings = ref<Partial<TaxSettings>>({
  tax_percentage: 18,
  tax_included: true,
})

const hasChanges = ref(false)

const rows = [
  { key: 'igv', label: 'IGV / Impuesto', desc: 'Porcentaje de impuesto aplicable' },
  { key: 'tax_included', label: 'Impuesto incluido', desc: 'Si el precio mostrado ya incluye impuestos' },
]

watch(() => store.taxSettings, (newSettings) => {
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

  const result = await store.updateTaxSettings(store.currentSubBranchId, localSettings.value)

  if (result.success) {
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: result.message || 'Configuración de impuestos actualizada',
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