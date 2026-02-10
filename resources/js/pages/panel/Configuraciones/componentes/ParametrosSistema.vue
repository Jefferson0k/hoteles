<template>
  <div class="w-full flex flex-col gap-4">
    <!-- HEADER -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="m-0 text-xl font-semibold">Parámetros del Sistema</h2>
        <small class="text-gray-400">Configuración por sucursal</small>
      </div>
      <Dropdown
        v-model="selectedBranchId"
        :options="store.subBranches"
        optionLabel="name"
        optionValue="id"
        placeholder="Seleccionar sucursal"
        class="w-64"
        :loading="store.loadingSubBranches"
        @change="onBranchChange"
      >
        <template #value="slotProps">
          <div v-if="slotProps.value" class="flex align-items-center">
            <div>{{ getSelectedBranchName(slotProps.value) }}</div>
          </div>
          <span v-else>
            {{ slotProps.placeholder }}
          </span>
        </template>
        <template #option="slotProps">
          <div class="flex align-items-center">
            <div>
              <div class="font-semibold">{{ slotProps.option.name }}</div>
              <small class="text-gray-500">{{ slotProps.option.code }} - {{ slotProps.option.address }}</small>
            </div>
          </div>
        </template>
      </Dropdown>
    </div>

    <!-- Sin sucursal -->
    <div v-if="!selectedBranchId" class="card flex flex-col items-center justify-center text-center py-16">
      <i class="pi pi-map-marker text-gray-400 mb-3" style="font-size: 2rem"></i>
      <p class="m-0 text-gray-400">Selecciona una sucursal para ver su configuración.</p>
    </div>

    <!-- Loading -->
    <div v-else-if="store.loading" class="card flex flex-col items-center justify-center text-center py-16">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
      <p class="m-0 text-gray-400 mt-3">Cargando configuración...</p>
    </div>

    <!-- Error -->
    <Message v-else-if="store.error" severity="error" :closable="true" @close="store.error = null">
      {{ store.error }}
    </Message>

    <!-- Contenido -->
    <template v-else-if="store.hasConfiguration">
      <!-- Información de la sucursal -->
      <Card v-if="store.configuration?.sub_branch" class="mb-3">
        <template #content>
          <div class="flex items-center gap-4">
            <i class="pi pi-building text-3xl text-primary"></i>
            <div>
              <h3 class="m-0 font-semibold">{{ store.configuration.sub_branch.name }}</h3>
              <p class="m-0 text-sm text-gray-500">Código: {{ store.configuration.sub_branch.code }}</p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Tiempo -->
      <TimeSettingsSection v-if="store.timeSettings" />

      <!-- Check-in/Check-out -->
      <CheckinSettingsSection v-if="store.checkinSettings" />

      <!-- Penalización -->
      <PenaltySettingsSection v-if="store.penaltySettings" />

      <!-- Cancelación -->
      <CancellationPolicySection v-if="store.cancellationPolicy" />

      <!-- Depósitos -->
      <DepositSettingsSection v-if="store.depositSettings" />

      <!-- Impuestos -->
      <TaxSettingsSection v-if="store.taxSettings" />

      <!-- Reservas -->
      <ReservationSettingsSection v-if="store.reservationSettings" />

      <!-- Notificaciones -->
      <NotificationSettingsSection v-if="store.notificationSettings" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dropdown from 'primevue/dropdown'
import Message from 'primevue/message'
import Card from 'primevue/card'
import ProgressSpinner from 'primevue/progressspinner'
import { useSubBranchConfigurationStore } from '../stores/subBranchConfigurationStore'

// Componentes de secciones
import TimeSettingsSection from './sections/TimeSettingsSection.vue'
import CheckinSettingsSection from './sections/CheckinSettingsSection.vue'
import PenaltySettingsSection from './sections/PenaltySettingsSection.vue'
import CancellationPolicySection from './sections/CancellationPolicySection.vue'
import DepositSettingsSection from './sections/DepositSettingsSection.vue'
import TaxSettingsSection from './sections/TaxSettingsSection.vue'
import ReservationSettingsSection from './sections/ReservationSettingsSection.vue'
import NotificationSettingsSection from './sections/NotificationSettingsSection.vue'

const store = useSubBranchConfigurationStore()
const toast = useToast()

const selectedBranchId = ref<string | null>(null)

onMounted(async () => {
  const result = await store.loadSubBranches()
  
  if (!result.success) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las sucursales',
      life: 5000
    })
  }
})

function getSelectedBranchName(branchId: string) {
  const branch = store.subBranches.find(b => b.id === branchId)
  return branch ? branch.name : ''
}

async function onBranchChange() {
  if (selectedBranchId.value) {
    const result = await store.loadConfiguration(selectedBranchId.value)
    
    if (!result.success) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: result.error || 'No se pudo cargar la configuración',
        life: 5000
      })
    }
  }
}
</script>