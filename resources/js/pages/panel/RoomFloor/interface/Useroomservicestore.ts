import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

// ==========================================
// INTERFACES Y TIPOS
// ==========================================

export interface Customer {
    id: string | number;
    name: string;
    document_number?: string;
    email?: string;
    phone?: string;
}

export interface Product {
    id: string | number;
    nombre: string;
    cantidad: number;
    precio_venta: number;
    quantity: number;
    price: number;
    status?: string;
    consumed_at?: string;
    consumption_id?: string | number;
}

export interface Currency {
    id: string | number;
    name: string;
    code: string;
    symbol: string;
}

export interface PaymentMethod {
    id: string | number;
    name: string;
    requires_reference: boolean;
}

export interface RateType {
    id: string | number;
    code: string;
    name: string;
}

export interface CashRegister {
    id: string | number;
    name: string;
}

export interface RoomData {
    id: string | number;
    room_number: string;
    full_name: string;
    status: 'available' | 'occupied' | 'maintenance' | 'cleaning';
    floor?: {
        name: string;
        floor_number: number;
    };
    room_type?: {
        name: string;
        capacity: number;
        base_price_per_hour: string;
        base_price_per_day: string;
        base_price_per_night: string;
    };
    current_booking?: any;
}

export interface StartServicePayload {
    paymentMethod: PaymentMethod;
    operationNumber: string;
}

export interface FinishServicePayload {
    paymentMethod: PaymentMethod | null;
    operationNumber: string;
    notes: string;
}

export type RateTypeKey = 'hour' | 'day' | 'night';
export type VoucherType = 'boleta' | 'ticket' | 'factura';

// ==========================================
// STORE DEFINITION
// ==========================================

export const useRoomServiceStore = defineStore('roomService', () => {
    const toast = useToast();

    // ==========================================
    // STATE
    // ==========================================

    // Room & Service State
    const roomData = ref<RoomData | null>(null);
    const selectedRate = ref<RateTypeKey | null>(null);
    const timeAmount = ref<number>(1);
    const voucherType = ref<VoucherType>('boleta');

    // Customer & Products
    const selectedClient = ref<Customer | null>(null);
    const products = ref<Product[]>([]);

    // Timer State
    const isTimerRunning = ref<boolean>(false);
    const remainingSeconds = ref<number>(0);
    const totalSeconds = ref<number>(0);
    const timerInterval = ref<NodeJS.Timeout | null>(null);
    const syncInterval = ref<NodeJS.Timeout | null>(null);

    // Booking State
    const currentBookingId = ref<string | null>(null);

    // Data Collections
    const currencies = ref<Currency[]>([]);
    const selectedCurrency = ref<Currency | null>(null);
    const rateTypes = ref<RateType[]>([]);
    const paymentMethods = ref<PaymentMethod[]>([]);
    const userCashRegister = ref<CashRegister | null>(null);

    // UI State
    const showStartDialog = ref<boolean>(false);
    const processingPayment = ref<boolean>(false);
    const showFinishDialog = ref<boolean>(false);
    const processingFinish = ref<boolean>(false);

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    const formattedTime = computed(() => {
        const totalSecs = Math.abs(remainingSeconds.value);
        const hours = Math.floor(totalSecs / 3600);
        const minutes = Math.floor((totalSecs % 3600) / 60);
        const seconds = totalSecs % 60;
        
        const sign = remainingSeconds.value < 0 ? '-' : '';
        return `${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    });

    const progressPercentage = computed(() => {
        if (totalSeconds.value === 0) return 0;
        const percentage = (remainingSeconds.value / totalSeconds.value) * 100;
        return Math.max(0, Math.min(100, percentage));
    });

    const currentRoomPrice = computed(() => {
        if (!selectedRate.value || !roomData.value?.room_type) return 0;
        
        const rates: Record<RateTypeKey, string> = {
            hour: roomData.value.room_type.base_price_per_hour,
            day: roomData.value.room_type.base_price_per_day,
            night: roomData.value.room_type.base_price_per_night
        };
        
        return parseFloat(rates[selectedRate.value] || '0');
    });

    const productsTotal = computed(() => {
        return products.value.reduce((sum, p) => {
            const quantity = parseFloat(String(p.quantity || p.cantidad || 0));
            const price = parseFloat(String(p.precio_venta || p.price || 0));
            return sum + (quantity * price);
        }, 0);
    });

    const roomTotal = computed(() => {
        return currentRoomPrice.value * timeAmount.value;
    });

    const totalAmount = computed(() => {
        return (roomTotal.value + productsTotal.value).toFixed(2);
    });

    const hasExtraTime = computed(() => {
        return remainingSeconds.value < 0;
    });

    const extraTimeFormatted = computed(() => {
        if (!hasExtraTime.value) return '0h 0m';
        const extraSeconds = Math.abs(remainingSeconds.value);
        const hours = Math.floor(extraSeconds / 3600);
        const minutes = Math.floor((extraSeconds % 3600) / 60);
        return `${hours}h ${minutes}m`;
    });

    const canStartService = computed(() => {
        return (
            roomData.value?.status === 'available' &&
            selectedClient.value !== null &&
            selectedRate.value !== null &&
            selectedCurrency.value !== null
        );
    });

    // ==========================================
    // HELPER METHODS
    // ==========================================

    const getRateLabel = (rate: RateTypeKey | null): string => {
        const labels: Record<RateTypeKey, string> = {
            hour: 'Por Hora',
            day: 'Por Día',
            night: 'Por Noche'
        };
        return rate ? labels[rate] : '';
    };

    const getTimeUnit = (rate: RateTypeKey | null): string => {
        const units: Record<RateTypeKey, string> = {
            hour: 'Hora(s)',
            day: 'Día(s)',
            night: 'Noche(s)'
        };
        return rate ? units[rate] : '';
    };

    const getStatusLabel = (status: string): string => {
        const labels: Record<string, string> = {
            available: 'Disponible',
            occupied: 'Ocupada',
            maintenance: 'Mantenimiento',
            cleaning: 'Limpieza'
        };
        return labels[status] || status;
    };

    const getStatusSeverity = (status: string): string => {
        const severities: Record<string, string> = {
            available: 'success',
            occupied: 'danger',
            maintenance: 'warn',
            cleaning: 'info'
        };
        return severities[status] || 'secondary';
    };

    const calculateTotalSeconds = (): number => {
        if (!selectedRate.value) return 0;
        
        const multipliers: Record<RateTypeKey, number> = {
            hour: 3600,
            day: 86400,
            night: 28800
        };
        
        return timeAmount.value * multipliers[selectedRate.value];
    };

    const getRateTypeId = (): string | number => {
        if (!selectedRate.value) {
            throw new Error('No se ha seleccionado una tarifa');
        }

        const rateTypeMap: Record<RateTypeKey, string> = {
            hour: 'HOUR',
            day: 'DAY',
            night: 'NIGHT'
        };
        
        const rateCode = rateTypeMap[selectedRate.value];
        const rateType = rateTypes.value.find(rt => rt.code === rateCode);
        
        if (!rateType) {
            throw new Error(`No se encontró el rate type para: ${selectedRate.value}`);
        }
        
        return rateType.id;
    };

    // ==========================================
    // API METHODS
    // ==========================================

    const loadNecessaryData = async (): Promise<void> => {
        try {
            const [currenciesRes, paymentMethodsRes, cashRegisterRes, rateTypesRes] = await Promise.all([
                axios.get('/currencies'),
                axios.get('/payments/methods'),
                axios.get('/payments/user-cash-register'),
                axios.get('/rate-types')
            ]);

            currencies.value = currenciesRes.data.data || currenciesRes.data;
            paymentMethods.value = paymentMethodsRes.data.data || paymentMethodsRes.data;
            userCashRegister.value = cashRegisterRes.data.data;
            rateTypes.value = rateTypesRes.data.data || rateTypesRes.data;

            if (currencies.value.length > 0 && !selectedCurrency.value) {
                selectedCurrency.value = currencies.value[0];
            }
        } catch (error: any) {
            console.error('Error al cargar datos:', error);
            
            if (error.response?.status === 404) {
                throw new Error('No tienes una caja abierta. Debes aperturar una caja primero.');
            } else {
                throw new Error('No se pudieron cargar los datos necesarios');
            }
        }
    };

    const syncWithBackend = async (): Promise<void> => {
        if (!roomData.value?.id) return;
        
        try {
            const response = await axios.get(`/rooms/${roomData.value.id}`);
            const roomInfo = response.data.data;
            
            // Si hay booking activo, sincronizar datos
            if (roomInfo.current_booking) {
                const booking = roomInfo.current_booking;
                
                // Actualizar estado del cronómetro
                if (booking.remaining_seconds !== undefined) {
                    remainingSeconds.value = booking.remaining_seconds;
                    isTimerRunning.value = true;
                    currentBookingId.value = booking.booking_id;
                    
                    // Recuperar datos del cliente
                    if (booking.guest_name && booking.guest_client_id) {
                        selectedClient.value = {
                            id: booking.guest_client_id,
                            name: booking.guest_name,
                            document_number: booking.guest_document
                        };
                    }
                    
                    // Recuperar tarifa
                    const rateTypeMap: Record<string, RateTypeKey> = {
                        'Por Hora': 'hour',
                        'Por Día': 'day',
                        'Por Noche': 'night'
                    };
                    selectedRate.value = rateTypeMap[booking.rate_type] || null;
                    
                    // Recuperar cantidad de tiempo
                    if (booking.total_hours) {
                        if (selectedRate.value === 'hour') {
                            timeAmount.value = booking.total_hours;
                        } else if (selectedRate.value === 'day') {
                            timeAmount.value = Math.floor(booking.total_hours / 24);
                        } else if (selectedRate.value === 'night') {
                            timeAmount.value = Math.floor(booking.total_hours / 8);
                        }
                    }
                    
                    // Calcular total de segundos basado en el tiempo contratado
                    totalSeconds.value = booking.total_hours * 3600;
                    
                    // Recuperar tipo de comprobante
                    voucherType.value = booking.voucher_type || 'boleta';
                    
                    // Recuperar productos/consumos
                    if (booking.consumptions && booking.consumptions.length > 0) {
                        products.value = booking.consumptions.map((c: any) => ({
                            id: c.product_id,
                            nombre: c.product_name,
                            cantidad: c.quantity,
                            precio_venta: c.unit_price,
                            quantity: c.quantity,
                            price: c.unit_price,
                            status: c.status,
                            consumed_at: c.consumed_at,
                            consumption_id: c.id
                        }));
                    }
                    
                    // Iniciar cronómetro local si no está corriendo
                    if (!timerInterval.value) {
                        startLocalTimer();
                    }
                }
                
                // Actualizar estado de la habitación
                if (roomData.value) {
                    roomData.value.status = roomInfo.status;
                }
            } else {
                // No hay booking activo, detener cronómetro
                stopLocalTimer();
                isTimerRunning.value = false;
                currentBookingId.value = null;
            }
        } catch (error: any) {
            console.error('Error al sincronizar con backend:', error);
        }
    };

    // ==========================================
    // TIMER METHODS
    // ==========================================

    const startLocalTimer = (): void => {
        if (timerInterval.value) return;

        timerInterval.value = setInterval(() => {
            remainingSeconds.value--;
            
            if (remainingSeconds.value === 0) {
                toast.add({
                    severity: 'warn',
                    summary: '⚠️ Tiempo Contratado Agotado',
                    detail: 'A partir de ahora se cobrará tiempo extra.',
                    life: 8000
                });
            }
        }, 1000);
    };

    const stopLocalTimer = (): void => {
        if (timerInterval.value) {
            clearInterval(timerInterval.value);
            timerInterval.value = null;
        }
    };

    const startSyncInterval = (): void => {
        if (syncInterval.value) return;

        syncInterval.value = setInterval(() => {
            syncWithBackend();
        }, 30000); // Cada 30 segundos
    };

    const stopSyncInterval = (): void => {
        if (syncInterval.value) {
            clearInterval(syncInterval.value);
            syncInterval.value = null;
        }
    };

    // ==========================================
    // SERVICE ACTIONS
    // ==========================================

    const selectRate = (rate: RateTypeKey): void => {
        if (!isTimerRunning.value) {
            selectedRate.value = rate;
            remainingSeconds.value = calculateTotalSeconds();
        }
    };

    const updateTimeAmount = (amount: number): void => {
        if (!isTimerRunning.value) {
            timeAmount.value = amount;
            remainingSeconds.value = calculateTotalSeconds();
        }
    };

    const setCustomer = (customer: Customer): void => {
        console.log('✅ Cliente guardado en store:', customer);
        selectedClient.value = customer;
    };

    const updateProducts = (newProducts: Product[]): void => {
        products.value = newProducts;
    };

    const confirmStartService = async (): Promise<void> => {
        if (!selectedClient.value) {
            toast.add({
                severity: 'warn',
                summary: 'Cliente Requerido',
                detail: 'Debe registrar un cliente primero',
                life: 3000
            });
            return;
        }
        
        if (!selectedClient.value.id) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'El cliente no tiene un ID válido',
                life: 4000
            });
            return;
        }
        
        if (!selectedRate.value) {
            toast.add({
                severity: 'warn',
                summary: 'Tarifa Requerida',
                detail: 'Debe seleccionar una tarifa',
                life: 3000
            });
            return;
        }
        
        if (!selectedCurrency.value) {
            toast.add({
                severity: 'warn',
                summary: 'Moneda Requerida',
                detail: 'Debe seleccionar una moneda',
                life: 3000
            });
            return;
        }

        try {
            await loadNecessaryData();
            showStartDialog.value = true;
        } catch (error: any) {
            console.error('Error al cargar datos:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'No se pudieron cargar los datos necesarios',
                life: 4000
            });
        }
    };

    const startService = async (payload: StartServicePayload): Promise<void> => {
        if (!roomData.value || !selectedClient.value || !selectedCurrency.value) {
            throw new Error('Faltan datos requeridos');
        }

        processingPayment.value = true;

        try {
            const bookingData = {
                room_id: roomData.value.id,
                customers_id: selectedClient.value.id,
                rate_type_id: getRateTypeId(),
                currency_id: selectedCurrency.value.id,
                quantity: timeAmount.value,
                rate_per_hour: currentRoomPrice.value,
                voucher_type: voucherType.value,
                
                payments: [
                    {
                        payment_method_id: payload.paymentMethod.id,
                        amount: parseFloat(totalAmount.value),
                        cash_register_id: userCashRegister.value?.id,
                        operation_number: payload.paymentMethod.requires_reference 
                            ? payload.operationNumber 
                            : null
                    }
                ],
                
                consumptions: products.value.length > 0 
                    ? products.value.map(p => ({
                        product_id: p.id,
                        quantity: parseFloat(String(p.quantity || p.cantidad || 0)),
                        unit_price: parseFloat(String(p.precio_venta || p.price || 0))
                    }))
                    : []
            };

            console.log('📤 Enviando booking:', bookingData);

            const response = await axios.post('/bookings', bookingData);

            console.log('✅ Respuesta del servidor:', response.data);

            currentBookingId.value = response.data.data?.booking?.id || null;

            toast.add({
                severity: 'success',
                summary: '✅ Servicio Iniciado',
                detail: response.data.message || 'Habitación ocupada correctamente',
                life: 4000
            });

            showStartDialog.value = false;
            
            totalSeconds.value = calculateTotalSeconds();
            remainingSeconds.value = totalSeconds.value;
            isTimerRunning.value = true;
            
            startLocalTimer();

            if (roomData.value) {
                roomData.value.status = 'occupied';
            }

        } catch (error: any) {
            console.error('❌ Error al crear booking:', error);
            
            if (error.response?.data?.errors) {
                const errors = error.response.data.errors;
                Object.keys(errors).forEach(key => {
                    toast.add({
                        severity: 'error',
                        summary: 'Error de Validación',
                        detail: `${key}: ${Array.isArray(errors[key]) ? errors[key][0] : errors[key]}`,
                        life: 5000
                    });
                });
            } else if (error.response?.data?.message) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.response.data.message,
                    life: 5000
                });
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.message || 'Error al crear el booking',
                    life: 5000
                });
            }
            throw error;
        } finally {
            processingPayment.value = false;
        }
    };

    const confirmFinishService = (): void => {
        if (!currentBookingId.value) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se encontró el ID del booking activo',
                life: 4000
            });
            return;
        }

        showFinishDialog.value = true;
    };

    const finishService = async (payload: FinishServicePayload): Promise<void> => {
        if (!currentBookingId.value) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se encontró el ID del booking activo',
                life: 4000
            });
            return;
        }

        processingFinish.value = true;

        try {
            const finishData: any = {
                notes: payload.notes || undefined,
            };

            if (payload.paymentMethod) {
                finishData.payments = [
                    {
                        payment_method_id: payload.paymentMethod.id,
                        amount: 0,
                        cash_register_id: userCashRegister.value?.id,
                        operation_number: payload.paymentMethod.requires_reference 
                            ? payload.operationNumber 
                            : null
                    }
                ];
            }

            console.log('📤 Finalizando booking:', currentBookingId.value);

            const response = await axios.post(`/bookings/${currentBookingId.value}/finish`, finishData);

            console.log('✅ Respuesta del servidor:', response.data);

            stopLocalTimer();
            isTimerRunning.value = false;

            toast.add({
                severity: 'success',
                summary: '✅ Servicio Finalizado',
                detail: response.data.message || 'Habitación pasa a limpieza',
                life: 4000
            });

            showFinishDialog.value = false;

            if (response.data.data?.time_summary?.extra_hours > 0) {
                toast.add({
                    severity: 'info',
                    summary: '⏱️ Tiempo Extra Cobrado',
                    detail: `Se cobraron ${response.data.data.time_summary.extra_hours} hora(s) adicionales`,
                    life: 6000
                });
            }

            setTimeout(() => {
                window.location.reload();
            }, 2000);

        } catch (error: any) {
            console.error('❌ Error al finalizar booking:', error);
            
            if (error.response?.data?.message) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.response.data.message,
                    life: 5000
                });
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.message || 'Error al finalizar el servicio',
                    life: 5000
                });
            }
            throw error;
        } finally {
            processingFinish.value = false;
        }
    };

    // ==========================================
    // INITIALIZATION & CLEANUP
    // ==========================================

    const initialize = async (room: RoomData): Promise<void> => {
        console.log('🚀 Inicializando habitación:', room.room_number);
        
        roomData.value = room;
        
        try {
            await loadNecessaryData();
            await syncWithBackend();
            startSyncInterval();
            
            if (selectedRate.value && !isTimerRunning.value) {
                remainingSeconds.value = calculateTotalSeconds();
            }
            
            console.log('✅ Inicialización completa');
        } catch (error: any) {
            console.error('❌ Error en inicialización:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message,
                life: 5000
            });
        }
    };

    /**
     * 🔥 MÉTODO CRÍTICO: Limpia TODOS los timers y resetea el estado
     * Llámalo antes de cambiar de habitación o al desmontar el componente
     */
    const cleanup = (): void => {
        console.log('🧹 Limpiando store...');
        
        // 1. Detener timers primero
        stopLocalTimer();
        stopSyncInterval();
        
        // 2. Resetear todo el estado
        resetState();
        
        console.log('✅ Store completamente limpio');
    };

    /**
     * Resetea todo el estado a valores iniciales
     */
    const resetState = (): void => {
        // Room Data
        roomData.value = null;
        
        // Service State
        selectedRate.value = null;
        timeAmount.value = 1;
        voucherType.value = 'boleta';
        
        // Customer & Products
        selectedClient.value = null;
        products.value = [];
        
        // Timer State
        isTimerRunning.value = false;
        remainingSeconds.value = 0;
        totalSeconds.value = 0;
        
        // Booking State
        currentBookingId.value = null;
        
        // UI State
        showStartDialog.value = false;
        showFinishDialog.value = false;
        processingPayment.value = false;
        processingFinish.value = false;
        
        // NOTA: NO reseteamos estos porque queremos mantenerlos entre habitaciones
        // para no tener que recargarlos cada vez:
        // - currencies
        // - selectedCurrency
        // - rateTypes
        // - paymentMethods
        // - userCashRegister
        
        console.log('✅ Estado reseteado a valores iniciales');
    };

    // ==========================================
    // RETURN (PUBLIC API)
    // ==========================================

    return {
        // State
        roomData,
        selectedRate,
        timeAmount,
        voucherType,
        selectedClient,
        products,
        isTimerRunning,
        remainingSeconds,
        totalSeconds,
        currentBookingId,
        currencies,
        selectedCurrency,
        rateTypes,
        paymentMethods,
        userCashRegister,
        showStartDialog,
        processingPayment,
        showFinishDialog,
        processingFinish,

        // Computed
        formattedTime,
        progressPercentage,
        currentRoomPrice,
        productsTotal,
        roomTotal,
        totalAmount,
        hasExtraTime,
        extraTimeFormatted,
        canStartService,

        // Helpers
        getRateLabel,
        getTimeUnit,
        getStatusLabel,
        getStatusSeverity,

        // Actions
        selectRate,
        updateTimeAmount,
        setCustomer,
        updateProducts,
        confirmStartService,
        startService,
        confirmFinishService,
        finishService,
        loadNecessaryData,
        syncWithBackend,
        initialize,
        cleanup,
        resetState
    };
});