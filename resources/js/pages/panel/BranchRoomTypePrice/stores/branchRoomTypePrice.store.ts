import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { branchRoomTypePriceService } from '../services/branchRoomTypePrice.service';
import type {
  IBranchRoomTypePrice,
  IBranchRoomTypePriceForm,
  IBranchRoomTypePriceFilters,
  IPricingOptions,
  ICalculatePriceRequest,
  ICalculatePriceResponse,
} from '../interfaces/branchRoomTypePrice.interface';

export const useBranchRoomTypePriceStore = defineStore('branchRoomTypePrice', () => {
  // State
  const branchRoomTypePrices = ref<IBranchRoomTypePrice[]>([]);
  const currentBranchRoomTypePrice = ref<IBranchRoomTypePrice | null>(null);
  const pricingOptions = ref<IPricingOptions | null>(null);
  const calculatedPrice = ref<ICalculatePriceResponse | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const activePrices = computed(() =>
    branchRoomTypePrices.value.filter((price) => price.is_active)
  );

  const currentEffectivePrices = computed(() =>
    branchRoomTypePrices.value.filter((price) => price.is_currently_effective)
  );

  const expiredPrices = computed(() =>
    branchRoomTypePrices.value.filter((price) => price.has_expired)
  );

  const getPricesBySubBranch = computed(() => {
    return (subBranchId: string) =>
      branchRoomTypePrices.value.filter(
        (price) => price.sub_branch_id === subBranchId
      );
  });

  const getPricesByRoomType = computed(() => {
    return (roomTypeId: string) =>
      branchRoomTypePrices.value.filter(
        (price) => price.room_type_id === roomTypeId
      );
  });

  const getPricesByRateType = computed(() => {
    return (rateTypeId: string) =>
      branchRoomTypePrices.value.filter(
        (price) => price.rate_type_id === rateTypeId
      );
  });

  // Actions
  const fetchBranchRoomTypePrices = async (
    filters?: IBranchRoomTypePriceFilters
  ) => {
    loading.value = true;
    error.value = null;
    try {
      branchRoomTypePrices.value = await branchRoomTypePriceService.getAll(
        filters
      );
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al obtener los precios';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchBranchRoomTypePriceById = async (id: string) => {
    loading.value = true;
    error.value = null;
    try {
      currentBranchRoomTypePrice.value =
        await branchRoomTypePriceService.getById(id);
      return currentBranchRoomTypePrice.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al obtener el precio';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const createBranchRoomTypePrice = async (data: IBranchRoomTypePriceForm) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await branchRoomTypePriceService.create(data);
      branchRoomTypePrices.value.unshift(response.data);
      return response;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || 'Error al crear la configuración de precio';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateBranchRoomTypePrice = async (
    id: string,
    data: IBranchRoomTypePriceForm
  ) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await branchRoomTypePriceService.update(id, data);
      const index = branchRoomTypePrices.value.findIndex(
        (price) => price.id === id
      );
      if (index !== -1) {
        branchRoomTypePrices.value[index] = response.data;
      }
      return response;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || 'Error al actualizar la configuración de precio';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const deleteBranchRoomTypePrice = async (id: string) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await branchRoomTypePriceService.delete(id);
      branchRoomTypePrices.value = branchRoomTypePrices.value.filter(
        (price) => price.id !== id
      );
      return response;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || 'Error al eliminar la configuración de precio';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchPricingOptions = async (params: {
    sub_branch_id: string;
    room_type_id: string;
    rate_type_id: string;
    date?: string;
  }) => {
    loading.value = true;
    error.value = null;
    try {
      pricingOptions.value = await branchRoomTypePriceService.getPricingOptions(
        params
      );
      return pricingOptions.value;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || 'Error al obtener opciones de precio';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const calculatePrice = async (data: ICalculatePriceRequest) => {
    loading.value = true;
    error.value = null;
    try {
      calculatedPrice.value = await branchRoomTypePriceService.calculatePrice(
        data
      );
      return calculatedPrice.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al calcular el precio';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const clearCurrentBranchRoomTypePrice = () => {
    currentBranchRoomTypePrice.value = null;
  };

  const clearError = () => {
    error.value = null;
  };

  const clearPricingOptions = () => {
    pricingOptions.value = null;
  };

  const clearCalculatedPrice = () => {
    calculatedPrice.value = null;
  };

  return {
    // State
    branchRoomTypePrices,
    currentBranchRoomTypePrice,
    pricingOptions,
    calculatedPrice,
    loading,
    error,

    // Getters
    activePrices,
    currentEffectivePrices,
    expiredPrices,
    getPricesBySubBranch,
    getPricesByRoomType,
    getPricesByRateType,

    // Actions
    fetchBranchRoomTypePrices,
    fetchBranchRoomTypePriceById,
    createBranchRoomTypePrice,
    updateBranchRoomTypePrice,
    deleteBranchRoomTypePrice,
    fetchPricingOptions,
    calculatePrice,
    clearCurrentBranchRoomTypePrice,
    clearError,
    clearPricingOptions,
    clearCalculatedPrice,
  };
});