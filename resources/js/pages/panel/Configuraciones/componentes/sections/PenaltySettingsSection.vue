<template>
  <div class="">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-base font-semibold">Penalización</h3>
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
          <div :class="{ 'opacity-40': data.key !== 'penalty_active' && !localSettings.penalty_active }">
            <p class="m-0 font-medium">{{ data.label }}</p>
            <small class="text-gray-400">{{ data.desc }}</small>
          </div>
        </template>
      </Column>

      <Column style="text-align: center">
        <template #body="{ data }">
          <div :class="{ 'opacity-40': data.key !== 'penalty_active' && !localSettings.penalty_active }">

            <!-- Activa toggle -->
            <div v-if="data.key === 'penalty_active'" class="flex justify-center">
              <Checkbox 
                v-model="localSettings.penalty_active" 
                :binary="true"
                @update:modelValue="markChanged"
              />
            </div>

            <!-- Cobro cada X min -->
            <div v-else-if="data.key === 'interval'" class="flex items-center justify-center gap-2">
              <InputNumber 
                v-model="localSettings.charge_interval_minutes" 
                :min="1" 
                :max="120" 
                :disabled="!localSettings.penalty_active" 
                class="w-24"
                @update:modelValue="markChanged"
              />
              <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">min</span>
            </div>

            <!-- Monto -->
            <div v-else-if="data.key === 'amount'" class="flex items-center justify-center gap-2">
              <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">S/.</span>
              <InputNumber 
                v-model="localSettings.amount_per_interval" 
                :min="0" 
                :step="0.5" 
                mode="decimal" 
                :maxFractionDigits="2" 
                :disabled="!localSettings.penalty_active" 
                class="w-24"
                @update:modelValue="markChanged"
              />
            </div>

            <!-- Tipo -->
            <div v-else-if="data.key === 'type'" class="flex justify-center">
              <SelectButton
                v-model="localSettings.penalty_type"
                :options="penaltyTypes"
                optionLabel="label"
                optionValue="value"
                :disabled="!localSettings.penalty_active"
                @update:modelValue="markChanged"
              />
            </div>

          </div>
        </template>
      </Column>

      <Column />
    </DataTable>

    <!-- Ejemplo dinámico -->
    <Message v-if="localSettings.penalty_active && store.timeSettings" severity="warn" class="mt-3">
      <p class="m-0 text-sm">
        Si el cliente usa <strong>{{ exampleMinutes }} min</strong>
        (límite {{ limiteTotalMinutos }} min), hay <strong>{{ excessMinutes }} min</strong> de exceso →
        <strong>{{ exampleIntervals }} intervalo{{ exampleIntervals !== 1 ? 's' : '' }}</strong>
        de {{ localSettings.charge_interval_minutes }} min →
        <strong>S/. {{ exampleCharge.toFixed(2) }}</strong>
      </p>
    </Message>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ColumnGroup from 'primevue/columngroup'
import Row from 'primevue/row'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'
import SelectButton from 'primevue/selectbutton'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { useSubBranchConfigurationStore } from '../../stores/subBranchConfigurationStore'
import type { PenaltySettings } from '../../stores/subBranchConfigurationStore'

const store = useSubBranchConfigurationStore()
const toast = useToast()

const localSettings = ref<Partial<PenaltySettings>>({
  penalty_active: false,
  charge_interval_minutes: 15,
  amount_per_interval: 0,
  penalty_type: 'fixed',
})

const hasChanges = ref(false)

const penaltyTypes = [
  { label: 'Monto fijo', value: 'fixed' },
  { label: 'Progresivo', value: 'progressive' },
]

const rows = [
  { key: 'penalty_active', label: 'Penalización activa', desc: 'Cobrar por cada intervalo de exceso de tiempo' },
  { key: 'interval', label: 'Cobro adicional cada', desc: 'Se suma un cargo por cada bloque de tiempo que excede' },
  { key: 'amount', label: 'Monto por intervalo', desc: 'Precio que se cobra en cada bloque de exceso' },
  { key: 'type', label: 'Tipo de penalidad', desc: 'Cómo se calcula el cobro adicional' },
]

// Cálculos para el ejemplo dinámico
const limiteTotalMinutos = computed(() => {
  if (!store.timeSettings) return 60
  return store.timeSettings.max_allowed_time + 
    (store.timeSettings.apply_tolerance ? store.timeSettings.extra_tolerance : 0)
})

const exampleMinutes = computed(() => limiteTotalMinutos.value + 45)
const excessMinutes = computed(() => exampleMinutes.value - limiteTotalMinutos.value)
const exampleIntervals = computed(() => 
  Math.ceil(excessMinutes.value / (localSettings.value.charge_interval_minutes || 15))
)
const exampleCharge = computed(() => 
  exampleIntervals.value * (localSettings.value.amount_per_interval || 0)
)

watch(() => store.penaltySettings, (newSettings) => {
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

  const result = await store.updatePenaltySettings(store.currentSubBranchId, localSettings.value)

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