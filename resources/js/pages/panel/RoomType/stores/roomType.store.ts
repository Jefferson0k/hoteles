import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { RoomTypeService } from '../services';
import type {
    IRoomType,
    IRoomTypeForm,
    IRoomTypeFilters,
} from '../interfaces';

export const useRoomTypeStore = defineStore('roomType', () => {
    // State
    const roomTypes = ref<IRoomType[]>([]);
    const currentRoomType = ref<IRoomType | null>(null);
    const loading = ref<boolean>(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
    });

    // Getters
    const activeRoomTypes = computed(() =>
        roomTypes.value.filter(rt => rt.is_active)
    );

    const roomTypesByCategory = computed(() => {
        const grouped: Record<string, IRoomType[]> = {};
        roomTypes.value.forEach(rt => {
            const category = rt.category || 'Sin categoría';
            if (!grouped[category]) {
                grouped[category] = [];
            }
            grouped[category].push(rt);
        });
        return grouped;
    });

    const totalRoomTypes = computed(() => pagination.value.total);

    const hasRoomTypes = computed(() => roomTypes.value.length > 0);

    // Actions
    async function fetchRoomTypes(filters?: IRoomTypeFilters): Promise<void> {
        loading.value = true;
        error.value = null;
        try {
            const response = await RoomTypeService.getAll(filters);
            roomTypes.value = response.data;
            if (response.meta) {
                pagination.value = {
                    current_page: response.meta.current_page,
                    last_page: response.meta.last_page,
                    per_page: response.meta.per_page,
                    total: response.meta.total
                };
            }
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Error al cargar tipos de habitación';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchRoomTypeById(id: string): Promise<IRoomType> {
        loading.value = true;
        error.value = null;
        try {
            const data = await RoomTypeService.getById(id);
            currentRoomType.value = data;
            return data;
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Error al cargar el tipo de habitación';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function createRoomType(data: IRoomTypeForm): Promise<{ message: string; data: IRoomType }> {
        loading.value = true;
        error.value = null;
        try {
            const response = await RoomTypeService.create(data);
            roomTypes.value.unshift(response.data);
            return response;
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Error al crear el tipo de habitación';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function updateRoomType(id: string, data: IRoomTypeForm): Promise<{ message: string; data: IRoomType }> {
        loading.value = true;
        error.value = null;
        try {
            const response = await RoomTypeService.update(id, data);
            const index = roomTypes.value.findIndex(rt => rt.id === id);
            if (index !== -1) {
                roomTypes.value[index] = response.data;
            }
            if (currentRoomType.value?.id === id) {
                currentRoomType.value = response.data;
            }
            return response;
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Error al actualizar el tipo de habitación';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function deleteRoomType(id: string): Promise<{ message: string }> {
        loading.value = true;
        error.value = null;
        try {
            const response = await RoomTypeService.delete(id);
            roomTypes.value = roomTypes.value.filter(rt => rt.id !== id);
            if (currentRoomType.value?.id === id) {
                currentRoomType.value = null;
            }
            return response;
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Error al eliminar el tipo de habitación';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    function resetCurrentRoomType(): void {
        currentRoomType.value = null;
    }

    function clearError(): void {
        error.value = null;
    }

    function resetStore(): void {
        roomTypes.value = [];
        currentRoomType.value = null;
        loading.value = false;
        error.value = null;
        pagination.value = {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0
        };
    }

    return {
        // State
        roomTypes,
        currentRoomType,
        loading,
        error,
        pagination,

        // Getters
        activeRoomTypes,
        roomTypesByCategory,
        totalRoomTypes,
        hasRoomTypes,

        // Actions
        fetchRoomTypes,
        fetchRoomTypeById,
        createRoomType,
        updateRoomType,
        deleteRoomType,
        resetCurrentRoomType,
        clearError,
        resetStore
    };
});