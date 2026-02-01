import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

// Interfaces (se mantienen igual)
interface SubBranch {
  id: string;
  name: string;
}

interface CashRegister {
  id: string;
  name: string;
  is_active: boolean;
  is_occupied: boolean;
  occupied_by: string | null;
  sub_branch: SubBranch;
  created_at: string;
}

interface ApiResponse {
  success: boolean;
  message: string;
  data: CashRegister[];
}

interface OpenCashRegisterPayload {
  opening_amount: number;
}

interface ValidationErrors {
  cash_register?: string;
  opening_amount?: string;
}

export const useCashRegisterStore = defineStore('cashRegister', () => {
  // State - Inicializar errors con las propiedades definidas
  const availableCashRegisters = ref<CashRegister[]>([]);
  const selectedCashRegister = ref<CashRegister | null>(null);
  const openingAmount = ref<number | null>(null);
  const loadingCashRegisters = ref(false);
  const isOpening = ref(false);
  const errors = ref<ValidationErrors>({
    cash_register: undefined,
    opening_amount: undefined
  });

  // Getters
  const hasSelectedCashRegister = computed(() => selectedCashRegister.value !== null);
  const canOpenCashRegister = computed(() => 
    hasSelectedCashRegister.value && 
    openingAmount.value !== null && 
    openingAmount.value >= 0
  );

  const closedCashRegisters = computed(() => 
    availableCashRegisters.value.filter(register => !register.is_occupied)
  );

  // Helper para mostrar notificaciones
  const showNotification = (severity: 'success' | 'error' | 'info' | 'warn', summary: string, detail: string, life: number = 3000) => {
    // Disparar un evento personalizado que el componente puede escuchar
    const event = new CustomEvent('show-toast', {
      detail: { severity, summary, detail, life }
    });
    window.dispatchEvent(event);
    
    // También mostrar en consola para debug
    console.log(`[${severity.toUpperCase()}] ${summary}: ${detail}`);
  };

  // Actions
  const loadCashRegisters = async () => {
    loadingCashRegisters.value = true;
    errors.value = {
      cash_register: undefined,
      opening_amount: undefined
    };

    try {
      const response = await axios.get<ApiResponse>(route('cash.cash-registers.index'), {
        params: { 
          is_active: true, 
          per_page: 100
        }
      });

      if (response.data.success) {
        availableCashRegisters.value = response.data.data;
      }
    } catch (error: any) {
      console.error('Error loading cash registers:', error);
      showNotification(
        'error',
        'Error', 
        error.response?.data?.message || 'Error al cargar las cajas disponibles'
      );
    } finally {
      loadingCashRegisters.value = false;
    }
  };

  const validateOpenCashRegister = (): boolean => {
    errors.value = {
      cash_register: undefined,
      opening_amount: undefined
    };

    if (!selectedCashRegister.value) {
      errors.value.cash_register = 'Debes seleccionar una caja';
      return false;
    }

    if (openingAmount.value === null || openingAmount.value < 0) {
      errors.value.opening_amount = 'Debes ingresar un monto de apertura válido';
      return false;
    }

    return true;
  };

  const openCashRegister = async () => {
    if (!validateOpenCashRegister()) {
      return;
    }

    isOpening.value = true;

    try {
      const payload: OpenCashRegisterPayload = {
        opening_amount: openingAmount.value!
      };

      const response = await axios.post(
        route('cash.cash-registers.open', selectedCashRegister.value!.id),
        payload
      );

      if (response.data.success) {
        showNotification(
          'success',
          'Éxito',
          'Caja aperturada correctamente. Redirigiendo a habitaciones...',
          2000
        );

        // Reset state
        resetForm();

        // Redirect
        setTimeout(() => {
          router.visit(route('aperturar.view'));
        }, 1500);
      }
    } catch (error: any) {
      console.error('Error opening cash register:', error);
      
      if (error.response?.data?.errors) {
        errors.value = {
          cash_register: error.response.data.errors.cash_register,
          opening_amount: error.response.data.errors.opening_amount
        };
      }
      
      const errorMessage = error.response?.data?.message || 'Error al aperturar la caja';
      showNotification('error', 'Error', errorMessage);
    } finally {
      isOpening.value = false;
    }
  };

  const resetForm = () => {
    selectedCashRegister.value = null;
    openingAmount.value = null;
    errors.value = {
      cash_register: undefined,
      opening_amount: undefined
    };
  };

  const selectCashRegister = (cashRegister: CashRegister | null) => {
    selectedCashRegister.value = cashRegister;
    errors.value.cash_register = undefined;
  };

  const setOpeningAmount = (amount: number | null) => {
    openingAmount.value = amount;
    errors.value.opening_amount = undefined;
  };

  return {
    // State
    availableCashRegisters,
    selectedCashRegister,
    openingAmount,
    loadingCashRegisters,
    isOpening,
    errors,

    // Getters
    hasSelectedCashRegister,
    canOpenCashRegister,
    closedCashRegisters,

    // Actions
    loadCashRegisters,
    openCashRegister,
    resetForm,
    selectCashRegister,
    setOpeningAmount
  };
});