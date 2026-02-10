<template>
  <div class="">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-base font-semibold">Depósitos y Garantías</h3>
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
          <div :class="{ 'opacity-40': data.key !== 'require_deposit' && !localSettings.requires_deposit }">
            <p class="m-0 font-medium">{{ data.label }}</p>
            <small class="text-gray-400">{{ data.desc }}</small>
          </div>
        </template>
      </Column>

      <Column style="text-align: center">
        <template #body="{ data }">
          <div :class="{ 'opacity-40': data.key !== 'require_deposit' && !localSettings.requires_deposit }">

            <!-- Requiere depósito -->
            <div v-if="data.key === 'require_deposit'" class="flex justify-center">
              <Checkbox 
                v-model="localSettings.requires_deposit" 
                :binary="true"
                @update:modelValue="markChanged"
              />
            </div>

            <!-- Monto depósito -->
            <div v-else-if="data.key === 'deposit_amount'" class="flex items-center justify-center gap-2">
              <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">S/.</span>
              <InputNumber 
                v-model="localSettings.deposit_amount" 
                :min="0" 
                :step="10" 
                mode="decimal" 
                :maxFractionDigits="2" 
                :disabled="!localSettings.requires_deposit" 
                class="w-24"
                @update:modelValue="markChanged"
              />
            </div>

            <!-- Método de pago -->
            <div v-else-if="data.key === 'deposit_method'" class="flex justify-center">
              <Dropdown
                v-model="localSettings.payment_method"
                :options="paymentMethods"
                optionLabel="label"
                optionValue="value"
                :disabled="!localSettings.requires_deposit"
                class="w-48"
                @update:modelValue="markChanged"
              />
            </div>

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
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import { useSubBranchConfigurationStore } from '../../stores/subBranchConfigurationStore'
import type { DepositSettings } from '../../stores/subBranchConfigurationStore'

const store = useSubBranchConfigurationStore()
const toast = useToast()

const localSettings = ref<Partial<DepositSettings>>({
  requires_deposit: false,
  deposit_amount: 0,
  payment_method: null,
})

const hasChanges = ref(false)

const paymentMethods = [
  { label: 'Efectivo', value: 'CASH' },
  { label: 'Tarjeta', value: 'CARD' },
  { label: 'Transferencia', value: 'TRANSFER' },
  { label: 'Cualquiera', value: 'ANY' },
]

const rows = [
  { key: 'require_deposit', label: 'Requiere depósito', desc: 'Solicitar garantía al momento de reservar' },
  { key: 'deposit_amount', label: 'Monto del depósito', desc: 'Cantidad a depositar como garantía' },
  { key: 'deposit_method', label: 'Método de pago', desc: 'Forma de pago aceptada para el depósito' },
]

watch(() => store.depositSettings, (newSettings) => {
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

  const result = await store.updateDepositSettings(store.currentSubBranchId, localSettings.value)

  if (result.success) {
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: result.message || 'Configuración de depósitos actualizada',
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