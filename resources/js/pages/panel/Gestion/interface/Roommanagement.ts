import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

// ============================================
// INTERFACES Y TIPOS
// ============================================

export interface Room {
    id: string;  // ← Este es el ID de la habitación
    room_number: string;
    room_type: string;
    status: RoomStatus;
    is_active: boolean;
    customer?: string;
    check_in?: string;
    check_out?: string;
    booking_id?: string;  // ← Este es el ID de la reserva
    booking_code?: string;
    elapsed_time?: string;
    elapsed_minutes?: number;
    remaining_time?: string;
    total_hours_contracted?: number;
    rate_type?: string;
}

export interface Floor {
    id: number;
    name: string;
    floor_number: number;
    total_rooms: number;
    available_rooms: number;
    rooms: Room[];
}

export type RoomStatus = 'available' | 'occupied' | 'maintenance' | 'cleaning';

export type LayoutType = 'list' | 'grid';

export interface StatusConfig {
    label: string;
    severity: 'success' | 'danger' | 'warn' | 'info' | null;
}

// ============================================
// CONSTANTES
// ============================================

export const STATUS_LABELS: Record<RoomStatus, string> = {
    available: 'Disponible',
    occupied: 'Ocupada',
    maintenance: 'Mantenimiento',
    cleaning: 'Limpieza'
};

export const STATUS_SEVERITIES: Record<RoomStatus, 'success' | 'danger' | 'warn' | 'info'> = {
    available: 'success',
    occupied: 'danger',
    maintenance: 'warn',
    cleaning: 'info'
};

// ============================================
// UTILIDADES DE TIEMPO
// ============================================

export const calculateRemainingTime = (
    checkInTime: string | null,
    checkOutTime: string | null,
    currentTime: Date
): string => {
    if (!checkOutTime) {
        return '00:00:00';
    }
    
    const checkOut = new Date(checkOutTime);
    const diff = checkOut.getTime() - currentTime.getTime();
    
    const isExpired = diff < 0;
    const absDiff = Math.abs(diff);
    
    const hours = Math.floor(absDiff / (1000 * 60 * 60));
    const minutes = Math.floor((absDiff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((absDiff % (1000 * 60)) / 1000);
    
    const sign = isExpired ? '-' : '';
    return `${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

export const formatCheckIn = (checkInTime: string | null): string => {
    if (!checkInTime) {
        return '-';
    }
    
    const date = new Date(checkInTime);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `Entrada: ${hours}:${minutes}`;
};

export const formatCheckOut = (checkOutTime: string | null): string => {
    if (!checkOutTime) {
        return '-';
    }
    
    const date = new Date(checkOutTime);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `Salida: ${hours}:${minutes}`;
};

export const isNearCheckout = (checkOutTime: string | null, currentTime: Date): boolean => {
    if (!checkOutTime) {
        return false;
    }
    
    const checkOut = new Date(checkOutTime);
    
    if (isNaN(checkOut.getTime())) {
        return false;
    }
    
    const diff = checkOut.getTime() - currentTime.getTime();
    const minutes = Math.floor(diff / (1000 * 60));
    
    return minutes <= 5 && minutes > 0;
};

export const isCheckoutExpired = (checkOutTime: string | null, currentTime: Date): boolean => {
    if (!checkOutTime) {
        return false;
    }
    
    const checkOut = new Date(checkOutTime);
    
    if (isNaN(checkOut.getTime())) {
        return false;
    }
    
    const diff = checkOut.getTime() - currentTime.getTime();
    
    return diff <= 0;
};

export const isSuspiciousCheckout = (checkOutTime: string | null, currentTime: Date): boolean => {
    if (!checkOutTime) {
        return false;
    }
    
    const checkOut = new Date(checkOutTime);
    const diff = checkOut.getTime() - currentTime.getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    
    return hours > 48;
};

// ============================================
// PINIA STORE
// ============================================

export const useRoomManagementStore = defineStore('roomManagement', () => {
    // Estado
    const floors = ref<Floor[]>([]);
    const layout = ref<LayoutType>('grid');
    const loading = ref<boolean>(true);
    const currentTime = ref<Date>(new Date());
    
    // Diálogos
    const liberarDialog = ref<boolean>(false);
    const extenderDialog = ref<boolean>(false);
    const showCobrarDialog = ref<boolean>(false);
    const finalizarDialog = ref<boolean>(false);
    
    // Selección
    const selectedRoomId = ref<string | null>(null);
    const selectedBookingId = ref<string | null>(null);
    const selectedRoomNumber = ref<string | null>(null);
    
    // Variable para el intervalo
    let timeInterval: ReturnType<typeof setInterval> | null = null;

    // Computed
    const layoutOptions = computed(() => ['list', 'grid'] as const);

    // ============================================
    // ACCIONES - FETCH
    // ============================================

    const fetchFloors = async (): Promise<void> => {
        try {
            loading.value = true;
            const response = await axios.get('/floors-rooms');
            floors.value = response.data.data;
        } catch (error) {
            console.error('Error al cargar pisos y habitaciones:', error);
            floors.value = [];
        } finally {
            loading.value = false;
        }
    };

    // ============================================
    // ACCIONES - TIEMPO
    // ============================================

    const updateCurrentTime = (): void => {
        currentTime.value = new Date();
    };

    const startTimeInterval = (): void => {
        if (timeInterval) {
            clearInterval(timeInterval);
        }
        
        currentTime.value = new Date();
        
        timeInterval = setInterval(() => {
            currentTime.value = new Date();
        }, 1000);
    };

    const stopTimeInterval = (): void => {
        if (timeInterval) {
            clearInterval(timeInterval);
            timeInterval = null;
        }
    };

    // ============================================
    // ACCIONES - ABRIR DIÁLOGOS
    // ============================================

    const openLiberarDialog = (roomId: string, roomNumber: string): void => {
        selectedRoomId.value = roomId;
        selectedRoomNumber.value = roomNumber;
        liberarDialog.value = true;
    };

    const openExtenderDialog = (bookingId: string, roomNumber: string): void => {
        selectedBookingId.value = bookingId;
        selectedRoomNumber.value = roomNumber;
        extenderDialog.value = true;
    };

    const openCobrarDialog = (bookingId: string, roomNumber: string): void => {
        selectedBookingId.value = bookingId;
        selectedRoomNumber.value = roomNumber;
        showCobrarDialog.value = true;
    };

    const openFinalizarDialog = (bookingId: string, roomNumber: string): void => {
        selectedBookingId.value = bookingId;
        selectedRoomNumber.value = roomNumber;
        finalizarDialog.value = true;
    };

    const closeAllDialogs = (): void => {
        liberarDialog.value = false;
        extenderDialog.value = false;
        showCobrarDialog.value = false;
        finalizarDialog.value = false;
        selectedRoomId.value = null;
        selectedBookingId.value = null;
        selectedRoomNumber.value = null;
    };

    // ============================================
    // ACCIONES - API CALLS
    // ============================================

    const liberarHabitacion = async (roomId: string): Promise<void> => {
        try {
            const { data } = await axios.post(`/cuarto/${roomId}/liberar`);
            
            // Actualiza la habitación en el estado local
            floors.value.forEach(floor => {
                const room = floor.rooms.find(r => r.id === roomId);
                if (room) {
                    room.status = 'available';
                }
            });

            return data;
        } catch (error) {
            console.error('Error al liberar habitación:', error);
            throw error;
        }
    };

    // ============================================
    // ACCIONES - HANDLERS DE DIÁLOGOS
    // ============================================

    const handleRoomLiberated = async (): Promise<void> => {
        await fetchFloors();
        liberarDialog.value = false;
        selectedRoomId.value = null;
        selectedRoomNumber.value = null;
    };

    const handleTimeExtended = async (): Promise<void> => {
        await fetchFloors();
        extenderDialog.value = false;
        selectedBookingId.value = null;
        selectedRoomNumber.value = null;
    };

    const handleExtraTimeCharged = async (): Promise<void> => {
        await fetchFloors();
        showCobrarDialog.value = false;
        selectedBookingId.value = null;
        selectedRoomNumber.value = null;
    };

    const handleBookingFinished = async (): Promise<void> => {
        await fetchFloors();
        finalizarDialog.value = false;
        selectedBookingId.value = null;
        selectedRoomNumber.value = null;
    };

    return {
        // Estado
        floors,
        layout,
        loading,
        currentTime,
        liberarDialog,
        extenderDialog,
        showCobrarDialog,
        finalizarDialog,
        selectedRoomId,
        selectedBookingId,
        selectedRoomNumber,
        
        // Computed
        layoutOptions,
        
        // Acciones - Fetch
        fetchFloors,
        
        // Acciones - Tiempo
        updateCurrentTime,
        startTimeInterval,
        stopTimeInterval,
        
        // Acciones - Diálogos
        openLiberarDialog,
        openExtenderDialog,
        openCobrarDialog,
        openFinalizarDialog,
        closeAllDialogs,
        
        // Acciones - API
        liberarHabitacion,
        
        // Handlers
        handleRoomLiberated,
        handleTimeExtended,
        handleExtraTimeCharged,
        handleBookingFinished
    };
});

// ============================================
// COMPOSABLES
// ============================================

export const useStatusLabel = () => {
    const getStatusLabel = (status: RoomStatus): string => {
        return STATUS_LABELS[status] || status;
    };

    const getStatusSeverity = (status: RoomStatus): 'success' | 'danger' | 'warn' | 'info' => {
        return STATUS_SEVERITIES[status];
    };

    return {
        getStatusLabel,
        getStatusSeverity
    };
};

export const useRoomTimer = () => {
    const store = useRoomManagementStore();
    
    const getRemainingTime = (checkIn: string | null, checkOut: string | null) => {
        return calculateRemainingTime(checkIn, checkOut, store.currentTime);
    };

    const isNear = (checkOut: string | null) => {
        return isNearCheckout(checkOut, store.currentTime);
    };

    const isExpired = (checkOut: string | null) => {
        return isCheckoutExpired(checkOut, store.currentTime);
    };

    const isSuspicious = (checkOut: string | null) => {
        return isSuspiciousCheckout(checkOut, store.currentTime);
    };

    return {
        getRemainingTime,
        isNear,
        isExpired,
        isSuspicious,
        formatCheckIn,
        formatCheckOut
    };
};