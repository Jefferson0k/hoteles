import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { rateTypeService } from '../services/rateType.service';
import type { RateType, RateTypeFormData } from '../interfaces/rateType.interface';

export const useRateTypeStore = defineStore('rateType', () => {
    const rateTypes = ref<RateType[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const activeRateTypes = computed(() => 
        rateTypes.value.filter(rt => rt.is_active)
    );

    async function fetchRateTypes() {
        isLoading.value = true;
        error.value = null;
        try {
            rateTypes.value = await rateTypeService.getAll();
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al cargar tipos de tarifa';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function createRateType(data: RateTypeFormData) {
        const response = await rateTypeService.create(data);
        // Agregar al array local sin recargar
        if (response.data) {
            rateTypes.value.push(response.data);
        }
        return response;
    }

    async function updateRateType(id: number, data: RateTypeFormData) {
        const response = await rateTypeService.update(id, data);
        // Actualizar en el array local sin recargar
        if (response.data) {
            const index = rateTypes.value.findIndex(rt => rt.id === id);
            if (index !== -1) {
                rateTypes.value[index] = response.data;
            }
        }
        return response;
    }

    async function deleteRateType(id: number) {
        const response = await rateTypeService.delete(id);
        // Eliminar del array local sin recargar
        const index = rateTypes.value.findIndex(rt => rt.id === id);
        if (index !== -1) {
            rateTypes.value.splice(index, 1);
        }
        return response;
    }

    return {
        rateTypes,
        isLoading,
        error,
        activeRateTypes,
        fetchRateTypes,
        createRateType,
        updateRateType,
        deleteRateType
    };
});