<template>
  <div>
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-base font-semibold">Check-in / Check-out</h3>

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
          <Column style="width: 10%" />
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
          <!-- CHECK-IN -->
          <div v-if="data.key === 'checkin_time'" class="flex justify-center">
            <Calendar
              v-model="checkinTime"
              timeOnly
              hourFormat="24"
              class="w-32"
              @update:modelValue="onCheckinTimeChange"
            />
          </div>

          <!-- CHECK-OUT -->
          <div v-else-if="data.key === 'checkout_time'" class="flex justify-center">
            <Calendar
              v-model="checkoutTime"
              timeOnly
              hourFormat="24"
              class="w-32"
              @update:modelValue="onCheckoutTimeChange"
            />
          </div>

          <!-- EARLY CHECK-IN COST -->
          <div v-else-if="data.key === 'early_checkin'" class="flex items-center justify-center gap-2">
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">
              S/.
            </span>
            <InputNumber
              v-model="localSettings.early_checkin_cost"
              :min="0"
              :step="5"
              mode="decimal"
              :maxFractionDigits="2"
              class="w-24"
              @update:modelValue="markChanged"
            />
          </div>

          <!-- LATE CHECK-OUT COST -->
          <div v-else-if="data.key === 'late_checkout'" class="flex items-center justify-center gap-2">
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">
              S/.
            </span>
            <InputNumber
              v-model="localSettings.late_checkout_cost"
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
import Calendar from 'primevue/calendar'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import { useSubBranchConfigurationStore } from '../../stores/subBranchConfigurationStore'
import type { CheckinSettings } from '../../stores/subBranchConfigurationStore'

const store = useSubBranchConfigurationStore()
const toast = useToast()

/**
 * 🔹 Estado local (API → HH:MM)
 */
const localSettings = ref<Partial<CheckinSettings>>({
  checkin_time: '14:00',
  checkout_time: '12:00',
  early_checkin_cost: 0,
  late_checkout_cost: 0,
})

/**
 * 🔹 Estado UI (Calendar → Date)
 */
const checkinTime = ref(parseTimeString('14:00'))
const checkoutTime = ref(parseTimeString('12:00'))

const hasChanges = ref(false)

/**
 * 🔹 Filas del DataTable
 */
const rows = [
  { key: 'checkin_time', label: 'Hora de check-in', desc: 'Hora estándar de entrada' },
  { key: 'checkout_time', label: 'Hora de check-out', desc: 'Hora estándar de salida' },
  { key: 'early_checkin', label: 'Costo early check-in', desc: 'Cargo por entrada anticipada' },
  { key: 'late_checkout', label: 'Costo late check-out', desc: 'Cargo por salida tardía' },
]

/**
 * 🔹 Convierte "HH:MM" o "HH:MM:SS" → Date
 */
function parseTimeString(timeStr: string): Date {
  const [h, m] = timeStr.split(':')
  const date = new Date()
  date.setHours(Number(h), Number(m), 0, 0)
  return date
}

/**
 * 🔹 Convierte Date → "HH:MM" (API SAFE)
 */
function formatTimeToString(date: Date): string {
  const h = date.getHours().toString().padStart(2, '0')
  const m = date.getMinutes().toString().padStart(2, '0')
  return `${h}:${m}`
}

/**
 * 🔹 Sync desde store
 */
watch(() => store.checkinSettings, (settings) => {
  if (!settings) return

  localSettings.value = { ...settings }
  checkinTime.value = parseTimeString(settings.checkin_time)
  checkoutTime.value = parseTimeString(settings.checkout_time)
  hasChanges.value = false
}, { immediate: true })

function onCheckinTimeChange(date: Date | null) {
  if (!date) return
  localSettings.value.checkin_time = formatTimeToString(date)
  markChanged()
}

function onCheckoutTimeChange(date: Date | null) {
  if (!date) return
  localSettings.value.checkout_time = formatTimeToString(date)
  markChanged()
}

function markChanged() {
  hasChanges.value = true
}

async function save() {
  if (!store.currentSubBranchId) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'No hay una sucursal seleccionada',
      life: 3000,
    })
    return
  }

  const result = await store.updateCheckinSettings(
    store.currentSubBranchId,
    localSettings.value
  )

  if (result.success) {
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: result.message || 'Configuración actualizada',
      life: 3000,
    })
    hasChanges.value = false
  } else {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: result.error || 'No se pudo guardar',
      life: 5000,
    })
  }
}
</script>
