<template>
    <Dialog 
        v-model:visible="store.extenderDialog" 
        modal 
        :style="{ width: '32rem' }"
        :closable="true"
        :draggable="false"
    >
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-clock text-xl"></i>
                <span class="font-bold text-xl">Extender Tiempo</span>
            </div>
        </template>

        <!-- Información actual -->
        <div class="mb-4">
            <h4 class="text-sm font-semibold mb-3">Estado actual:</h4>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span >Habitación:</span>
                    <span class="font-semibold">{{ bookingData?.room?.room_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span >Cliente:</span>
                    <span class="font-semibold">{{ bookingData?.customer?.name }}</span>
                </div>
                <div class="flex justify-between">
                    <span >Horas contratadas:</span>
                    <span class="font-semibold">{{ bookingData?.time_info?.horas_contratadas }}h</span>
                </div>
                <div class="flex justify-between">
                    <span >Horas usadas:</span>
                    <span class="font-semibold">{{ bookingData?.time_info?.horas_usadas }}h</span>
                </div>
                <div class="flex justify-between">
                    <span >Checkout programado:</span>
                    <span class="font-semibold">{{ bookingData?.time_info?.check_out_programado_formatted }}</span>
                </div>
            </div>

            <!-- Alerta si ya se pasó -->
            <Message 
                v-if="bookingData?.time_info?.ya_se_paso_del_tiempo" 
                severity="error" 
                class="mt-3"
            >
                <div class="flex flex-col">
                    <span class="font-semibold">⚠️ Se excedió {{ bookingData?.time_info?.horas_extra }}h</span>
                    <span class="text-xs mt-1">
                        Costo adicional: S/ {{ bookingData?.time_info?.costo_horas_extra?.toFixed(2) }}
                    </span>
                </div>
            </Message>
        </div>

        <!-- Input para horas adicionales -->
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">
                ¿Cuántas horas desea extender? <span class="text-red-500">*</span>
            </label>
            <InputNumber
                v-model="horasExtender"
                :min="0.5"
                :step="0.5"
                :maxFractionDigits="1"
                placeholder="Ej: 2"
                class="w-full"
                inputClass="w-full"
                @update:modelValue="calcularNuevoTotal"
            />
        </div>

        <!-- Preview del nuevo estado -->
        <div v-if="horasExtender && horasExtender > 0" class="mb-4">
            <h4 class="text-sm font-semibold text-blue-700 mb-3">Después de extender:</h4>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="">Nuevo checkout:</span>
                    <span class="font-semibold text-blue-700">{{ nuevoCheckout }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="">Total horas:</span>
                    <span class="font-semibold text-blue-700">
                        {{ (bookingData?.time_info?.horas_contratadas || 0) + (horasExtender || 0) }}h
                    </span>
                </div>
                
                <Divider />
                
                <div class="flex justify-between items-center">
                    <span class="font-medium">Costo adicional:</span>
                    <span class="text-lg font-bold text-blue-700">
                        S/ {{ costoAdicional.toFixed(2) }}
                    </span>
                </div>

                <!-- Si había tiempo extra -->
                <Message 
                    v-if="bookingData?.time_info?.ya_se_paso_del_tiempo" 
                    severity="warn" 
                    class="mt-2"
                >
                    <div class="text-xs">
                        <strong>Nota:</strong> Se regularizarán las {{ bookingData?.time_info?.horas_extra }}h extras 
                        (S/ {{ bookingData?.time_info?.costo_horas_extra?.toFixed(2) }}) + extensión solicitada
                        <div class="font-semibold mt-1">
                            Total a agregar: S/ {{ ((bookingData?.time_info?.costo_horas_extra || 0) + costoAdicional).toFixed(2) }}
                        </div>
                    </div>
                </Message>
            </div>
        </div>

        <template #footer>
            <div class="flex gap-2 justify-end">
                <Button 
                    label="Cancelar" 
                    severity="secondary" 
                    outlined 
                    @click="cerrarModal"
                    :disabled="loading"
                />
                <Button 
                    label="Confirmar extensión" 
                    icon="pi pi-check" 
                    @click="confirmarExtension"
                    :disabled="!horasExtender || horasExtender <= 0 || loading"
                    :loading="loading"
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useRoomManagementStore } from '../interface/Roommanagement';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';
import Divider from 'primevue/divider';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const store = useRoomManagementStore();
const toast = useToast();

const horasExtender = ref<number | null>(null);
const nuevoCheckout = ref<string>('');
const costoAdicional = ref<number>(0);
const loading = ref<boolean>(false);
const bookingData = ref<any>(null);

// Cargar datos cuando se abre el modal
watch(() => store.extenderDialog, async (isOpen) => {
    if (isOpen && store.selectedBookingId) {
        await cargarDatosBooking();
    } else {
        resetForm();
    }
});

// También observar cambios en horasExtender
watch(horasExtender, () => {
    calcularNuevoTotal();
});

const cargarDatosBooking = async () => {
    try {
        loading.value = true;
        const { data } = await axios.get(`/detalles/bookings/${store.selectedBookingId}/habitacion`);
        
        if (data.success) {
            bookingData.value = data.data;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo cargar la información de la reserva',
                life: 3000
            });
        }
    } catch (error) {
        console.error('Error al cargar booking:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar la información',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const calcularNuevoTotal = () => {
    if (!horasExtender.value || horasExtender.value <= 0 || !bookingData.value) {
        costoAdicional.value = 0;
        nuevoCheckout.value = '';
        return;
    }
    
    // Calcular costo
    costoAdicional.value = horasExtender.value * (bookingData.value.booking.rate_per_hour || 0);
    
    // Calcular nuevo checkout
    const checkoutActual = new Date(bookingData.value.booking.check_out);
    const ahora = new Date();
    
    let base = ahora > checkoutActual ? ahora : checkoutActual;
    base.setHours(base.getHours() + horasExtender.value);
    
    nuevoCheckout.value = base.toLocaleString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

const confirmarExtension = async () => {
    if (!horasExtender.value || horasExtender.value <= 0) return;
    
    try {
        loading.value = true;
        
        const { data } = await axios.post(
            `/bookings/${store.selectedBookingId}/extend-time`,
            {
                horas_adicionales: horasExtender.value
            }
        );
        
        if (data.success) {
            toast.add({
                severity: 'success',
                summary: '✅ Éxito',
                detail: data.message,
                life: 3000
            });
            
            cerrarModal();
            await store.fetchFloors(); // Recargar la lista de habitaciones
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: data.message,
                life: 3000
            });
        }
    } catch (error: any) {
        console.error('Error al extender tiempo:', error);
        
        const errorMessage = error.response?.data?.message || 'Error al extender el tiempo';
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const cerrarModal = () => {
    store.extenderDialog = false;
    resetForm();
};

const resetForm = () => {
    horasExtender.value = null;
    nuevoCheckout.value = '';
    costoAdicional.value = 0;
    bookingData.value = null;
};
</script>