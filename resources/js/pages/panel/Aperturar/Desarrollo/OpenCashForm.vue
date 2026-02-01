<template>
  <div>
    <Toast position="top-right" />
    
    <div class="mb-4">
      <h2 class="text-3xl font-bold">Aperturar Caja</h2>
      <p class="mt-2">Selecciona una caja para iniciar tu turno</p>
    </div>

    <div class="grid">
      <!-- User Info Section -->
      <div class="col-12 lg:col-4">
        <div class="text-center">
          <Avatar icon="pi pi-user" size="xlarge" class="mb-3" />
          <p class="text-sm mb-2">Usuario Autenticado</p>
          <h3 class="text-xl font-bold mb-1">{{ authenticatedUser?.name }}</h3>
          <p class="text-sm text-gray-600">{{ authenticatedUser?.email }}</p>
        </div>
      </div>

      <!-- Cash Register Form Section -->
      <div class="col-12 lg:col-8">
        <div>
          <!-- Cash Register Dropdown -->
          <div class="field mb-4">
            <label for="cash-register" class="font-bold block mb-2">
              <i class="pi pi-calculator mr-2"></i>
              Cajas Disponibles
            </label>
            <Dropdown 
              id="cash-register" 
              v-model="selectedCashRegister"
              :options="closedCashRegisters"
              optionLabel="name" 
              placeholder="Selecciona una caja para aperturar..." 
              class="w-full"
              :class="{ 'p-invalid': errors?.cash_register }" 
              :loading="loadingCashRegisters"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="flex align-items-center">
                  <i class="pi pi-calculator mr-2"></i>
                  <span>{{ slotProps.value.name }}</span>
                  <Tag 
                    :value="slotProps.value.is_occupied ? 'OCUPADA' : 'DISPONIBLE'" 
                    :severity="slotProps.value.is_occupied ? 'danger' : 'success'"
                    class="ml-auto" 
                  />
                </div>
                <span v-else>{{ slotProps.placeholder }}</span>
              </template>
              <template #option="slotProps">
                <div class="flex align-items-center justify-content-between w-full">
                  <div class="flex align-items-center">
                    <i class="pi pi-calculator mr-2"></i>
                    <span>{{ slotProps.option.name }}</span>
                  </div>
                  <Tag 
                    :value="slotProps.option.is_occupied ? 'OCUPADA' : 'DISPONIBLE'" 
                    :severity="slotProps.option.is_occupied ? 'danger' : 'success'" 
                  />
                </div>
              </template>
            </Dropdown>
            <small v-if="errors?.cash_register" class="p-error block mt-2">
              {{ errors.cash_register }}
            </small>
          </div>

          <!-- Selected Cash Register Info -->
          <Message 
            v-if="selectedCashRegister" 
            severity="info" 
            :closable="false" 
            class="mb-4"
          >
            <div class="grid">
              <div class="col-6">
                <p class="font-bold mb-1">Sucursal</p>
                <p>{{ selectedCashRegister?.sub_branch?.name || 'N/A' }}</p>
              </div>
              <div class="col-6">
                <p class="font-bold mb-1">Estado Actual</p>
                <Tag 
                  :value="selectedCashRegister?.is_occupied ? 'OCUPADA' : 'DISPONIBLE'"
                  :severity="selectedCashRegister?.is_occupied ? 'danger' : 'success'" 
                />
              </div>
            </div>
          </Message>

          <!-- Opening Amount Input -->
          <div v-if="selectedCashRegister" class="field mb-4">
            <label for="opening-amount" class="font-bold block mb-2">
              <i class="pi pi-money-bill mr-2"></i>
              Monto de Apertura
            </label>
            <InputNumber 
              id="opening-amount" 
              v-model="openingAmount"
              mode="currency" 
              currency="PEN" 
              locale="es-PE"
              placeholder="Ingrese el monto inicial de caja" 
              class="w-full"
              :class="{ 'p-invalid': errors?.opening_amount }" 
              :min="0" 
              :minFractionDigits="2" 
              :maxFractionDigits="2" 
            />
            <small v-if="errors?.opening_amount" class="p-error block mt-2">
              {{ errors.opening_amount }}
            </small>
            <small v-else class="text-gray-600 block mt-2">
              Ingrese el monto con el que iniciará la caja
            </small>
          </div>

          <!-- Submit Button -->
          <Button 
            label="Aperturar Caja" 
            icon="pi pi-lock-open" 
            @click="handleOpenCashRegister" 
            :loading="isOpening"
            :disabled="!canOpenCashRegister" 
            severity="contrast" 
            class="w-full" 
          />
        </div>
      </div>
    </div>

    <!-- Info Message -->
    <Message severity="warn" :closable="false" class="mt-4">
      <template #icon>
        <i class="pi pi-info-circle text-2xl"></i>
      </template>
      <div>
        <p class="font-bold mb-2">Información Importante</p>
        <ul class="pl-4">
          <li>Solo puedes aperturar cajas que estén <strong>"disponibles"</strong></li>
          <li>Una vez aperturada, la caja quedará asignada a tu usuario</li>
          <li>Podrás registrar movimientos y transacciones en la caja</li>
        </ul>
      </div>
    </Message>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Avatar from 'primevue/avatar'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Toast from 'primevue/toast'

const page = usePage()
const toast = useToast()
const authenticatedUser = computed(() => page.props.auth?.user)

// State
const availableCashRegisters = ref([])
const selectedCashRegister = ref(null)
const openingAmount = ref(null)
const loadingCashRegisters = ref(false)
const isOpening = ref(false)
const errors = reactive({
  cash_register: null,
  opening_amount: null
})

// Computed
const hasSelectedCashRegister = computed(() => selectedCashRegister.value !== null)
const canOpenCashRegister = computed(() => 
  hasSelectedCashRegister.value && 
  openingAmount.value !== null && 
  openingAmount.value >= 0
)

const closedCashRegisters = computed(() => 
  availableCashRegisters.value.filter(register => !register.is_occupied)
)

// Methods
const loadCashRegisters = async () => {
  loadingCashRegisters.value = true
  errors.cash_register = null
  errors.opening_amount = null

  try {
    const response = await axios.get(route('cash.cash-registers.index'), {
      params: { 
        is_active: true, 
        per_page: 100
      }
    })

    if (response.data.success) {
      availableCashRegisters.value = response.data.data
    }
  } catch (error) {
    console.error('Error loading cash registers:', error)
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: error.response?.data?.message || 'Error al cargar las cajas disponibles', 
      life: 3000 
    })
  } finally {
    loadingCashRegisters.value = false
  }
}

const validateOpenCashRegister = () => {
  errors.cash_register = null
  errors.opening_amount = null

  if (!selectedCashRegister.value) {
    errors.cash_register = 'Debes seleccionar una caja'
    return false
  }

  if (openingAmount.value === null || openingAmount.value < 0) {
    errors.opening_amount = 'Debes ingresar un monto de apertura válido'
    return false
  }

  return true
}

const openCashRegister = async () => {
  if (!validateOpenCashRegister()) {
    return
  }

  isOpening.value = true

  try {
    const payload = {
      opening_amount: openingAmount.value
    }

    const response = await axios.post(
      route('cash.cash-registers.open', selectedCashRegister.value.id),
      payload
    )

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: 'Caja aperturada correctamente. Redirigiendo...',
        life: 2000
      })

      // Reset form
      selectedCashRegister.value = null
      openingAmount.value = null
      errors.cash_register = null
      errors.opening_amount = null

      // Redirect después de 1.5 segundos
      setTimeout(() => {
        router.visit(route('aperturar.view'))
      }, 1500)
    }
  } catch (error) {
    console.error('Error opening cash register:', error)
    
    // Mostrar errores de validación del backend
    if (error.response?.data?.errors) {
      if (error.response.data.errors.cash_register) {
        errors.cash_register = error.response.data.errors.cash_register[0]
      }
      if (error.response.data.errors.opening_amount) {
        errors.opening_amount = error.response.data.errors.opening_amount[0]
      }
    }
    
    const errorMessage = error.response?.data?.message || 'Error al aperturar la caja'
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: errorMessage, 
      life: 3000 
    })
  } finally {
    isOpening.value = false
  }
}

const handleOpenCashRegister = async () => {
  await openCashRegister()
}

// Lifecycle
onMounted(async () => {
  await loadCashRegisters()
})
</script>

<style scoped>
.field {
  margin-bottom: 1.5rem;
}

.p-invalid {
  border-color: #e24c4c;
}

.p-error {
  color: #e24c4c;
  font-size: 0.875rem;
}
</style>