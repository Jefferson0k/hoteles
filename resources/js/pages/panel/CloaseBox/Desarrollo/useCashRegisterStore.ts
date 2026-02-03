import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

interface PaymentMethod {
  id: string;
  name: string;
  code: string;
  requires_reference: boolean;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

interface PaymentMethodsResponse {
  success: boolean;
  data: PaymentMethod[];
}

export const useCashRegisterStore = defineStore('cashRegister', () => {
  // State
  const paymentMethods = ref<PaymentMethod[]>([]);
  const paymentAmounts = ref<Record<string, number>>({});
  const notes = ref<string>('');
  const loadingPaymentMethods = ref(false);
  const isClosing = ref(false);
  const errors = ref<Record<string, string>>({});

  // Computed
  const totalClosingAmount = computed(() => {
    return Object.values(paymentAmounts.value).reduce((sum, amount) => sum + (amount || 0), 0);
  });

  const canCloseCashRegister = computed(() => {
    return totalClosingAmount.value > 0 && !isClosing.value;
  });

  // Actions
  const loadPaymentMethods = async () => {
    loadingPaymentMethods.value = true;
    errors.value = {};
    try {
      const response = await axios.get<PaymentMethodsResponse>('/payments/methods');
      if (response.data.success) {
        paymentMethods.value = response.data.data
          .filter(method => method.is_active)
          .sort((a, b) => a.sort_order - b.sort_order);
        
        // Inicializar montos en 0
        paymentMethods.value.forEach(method => {
          if (!(method.id in paymentAmounts.value)) {
            paymentAmounts.value[method.id] = 0;
          }
        });
      }
    } catch (error: any) {
      console.error('Error loading payment methods:', error);
      errors.value.general = error.response?.data?.message || 'Error al cargar métodos de pago';
    } finally {
      loadingPaymentMethods.value = false;
    }
  };

  const setPaymentAmount = (methodId: string, amount: number | null) => {
    paymentAmounts.value[methodId] = amount || 0;
  };

  const setNotes = (value: string) => {
    notes.value = value;
  };

  const closeCashRegister = async () => {
    if (!canCloseCashRegister.value) return;

    isClosing.value = true;
    errors.value = {};

    try {
      // Preparar datos en el formato correcto
      const payload = {
        counted_amounts: Object.entries(paymentAmounts.value)
          .filter(([_, amount]) => amount > 0)
          .map(([payment_method_id, counted_amount]) => ({
            payment_method_id,
            counted_amount
          })),
        notes: notes.value || null
      };

      const response = await axios.post('/cash-register-sessions/close', payload);

      if (response.data.success) {
        // Limpiar formulario
        paymentAmounts.value = {};
        notes.value = '';
        
        // Mostrar mensaje de éxito
        console.log('✅ Caja cerrada exitosamente', response.data);

        // Redirigir a la página de apertura
        router.visit('/panel/aperturar');
        
        return response.data;
      }
    } catch (error: any) {
      console.error('Error closing cash register:', error);
      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors;
      } else {
        errors.value.general = error.response?.data?.message || 'Error al cerrar la caja';
      }
      throw error;
    } finally {
      isClosing.value = false;
    }
  };

  const resetStore = () => {
    paymentMethods.value = [];
    paymentAmounts.value = {};
    notes.value = '';
    loadingPaymentMethods.value = false;
    isClosing.value = false;
    errors.value = {};
  };

  return {
    // State
    paymentMethods,
    paymentAmounts,
    notes,
    loadingPaymentMethods,
    isClosing,
    errors,
    // Computed
    totalClosingAmount,
    canCloseCashRegister,
    // Actions
    loadPaymentMethods,
    setPaymentAmount,
    setNotes,
    closeCashRegister,
    resetStore
  };
});