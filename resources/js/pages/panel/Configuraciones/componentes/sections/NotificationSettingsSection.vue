<template>
  <div class="">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-base font-semibold">Notificaciones</h3>
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
          <!-- Recordatorio pre-checkin -->
          <div v-if="data.key === 'reminder_hours'" class="flex items-center justify-center gap-2">
            <InputNumber 
              v-model="localSettings.reminder_hours_before" 
              :min="0" 
              :max="48" 
              class="w-24"
              @update:modelValue="markChanged"
            />
            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded">hrs antes</span>
          </div>

          <!-- Recordatorio activo -->
          <div v-else-if="data.key === 'reminder_active'" class="flex justify-center">
            <Checkbox 
              v-model="localSettings.reservation_reminder_active" 
              :binary="true"
              @update:modelValue="markChanged"
            />
          </div>

          <!-- Alerta exceso tiempo -->
          <div v-else-if="data.key === 'alert_overtime'" class="flex justify-center">
            <Checkbox 
              v-model="localSettings.excess_alert_active" 
              :binary="true"
              @update:modelValue="markChanged"
            />
          </div>

          <!-- Email confirmación -->
          <div v-else-if="data.key === 'email_confirmation'" class="flex justify-center">
            <Checkbox 
              v-model="localSettings.confirmation_email_active" 
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
import type { NotificationSettings } from '../../stores/subBranchConfigurationStore'

const store = useSubBranchConfigurationStore()
const toast = useToast()

const localSettings = ref<Partial<NotificationSettings>>({
  reservation_reminder_active: false,
  reminder_hours_before: 24,
  excess_alert_active: false,
  confirmation_email_active: false,
})

const hasChanges = ref(false)

const rows = [
  { key: 'reminder_active', label: 'Recordatorio activo', desc: 'Activar recordatorios de reserva' },
  { key: 'reminder_hours', label: 'Recordatorio de reserva', desc: 'Enviar recordatorio antes del check-in' },
  { key: 'alert_overtime', label: 'Alerta de exceso', desc: 'Notificar cuando se exceda el tiempo permitido' },
  { key: 'email_confirmation', label: 'Email de confirmación', desc: 'Enviar correo al confirmar la reserva' },
]

watch(() => store.notificationSettings, (newSettings) => {
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

  const result = await store.updateNotificationSettings(store.currentSubBranchId, localSettings.value)

  if (result.success) {
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: result.message || 'Configuración de notificaciones actualizada',
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