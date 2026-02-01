<template>
    <Dialog 
        v-model:visible="store.finalizarDialog" 
        modal 
        :style="{ width: '50rem' }"
        :closable="true"
        :draggable="false"
        class="finalizar-dialog"
    >
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-check-circle text-xl text-green-600"></i>
                <div>
                    <div class="font-bold text-xl">Finalizar Servicio</div>
                    <div class="text-sm ">
                        {{ bookingData?.booking?.booking_code }} - Habitación {{ bookingData?.room?.room_number }}
                    </div>
                </div>
            </div>
        </template>

        <div v-if="loading" class="flex justify-center items-center py-8">
            <ProgressSpinner style="width: 50px; height: 50px" />
        </div>

        <div v-else-if="bookingData">
            <!-- Alertas importantes -->
            <div v-for="alert in bookingData.alerts" :key="alert.message" class="mb-4">
                <Message 
                    :severity="alert.type === 'danger' ? 'error' : alert.type === 'warning' ? 'warn' : 'info'"
                    :closable="false"
                >
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">{{ alert.icon }}</span>
                        <div class="flex-1">
                            <div class="font-bold">{{ alert.title }}</div>
                            <div class="text-sm mt-1">{{ alert.message }}</div>
                            <div v-if="alert.detail" class="text-sm font-semibold mt-1">{{ alert.detail }}</div>
                        </div>
                    </div>
                </Message>
            </div>

            <!-- Resumen de Tiempo -->
            <Panel header="📅 Resumen de Tiempo" class="mb-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="">Check-in:</div>
                        <div class="font-semibold">{{ bookingData.time_info.check_in_formatted }}</div>
                    </div>
                    <div>
                        <div class="">Checkout programado:</div>
                        <div class="font-semibold">{{ bookingData.time_info.check_out_programado_formatted }}</div>
                    </div>
                    <div>
                        <div class="">Horas contratadas:</div>
                        <div class="font-semibold">{{ bookingData.time_info.horas_contratadas }}h</div>
                    </div>
                    <div>
                        <div class="">Horas usadas:</div>
                        <div 
                            class="font-semibold"
                            :class="bookingData.time_info.ya_se_paso_del_tiempo ? 'text-red-600' : ''"
                        >
                            {{ bookingData.time_info.horas_usadas }}h
                            <span v-if="bookingData.time_info.ya_se_paso_del_tiempo" class="text-red-600">
                                (+{{ bookingData.time_info.horas_extra }}h extras)
                            </span>
                        </div>
                    </div>
                </div>
            </Panel>

            <!-- Resumen Financiero -->
             <Panel header="💰 Resumen Financiero" class="mb-4">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="">Habitación:</span>
                        <span class="font-medium">S/ {{ bookingData.financial_summary.room_subtotal?.toFixed(2) }}</span>
                    </div>
                    
                    <div 
                        v-if="bookingData.financial_summary.products_subtotal > 0" 
                        class="flex justify-between"
                    >
                        <span class="">Consumos:</span>
                        <span class="font-medium">S/ {{ bookingData.financial_summary.products_subtotal?.toFixed(2) }}</span>
                    </div>
                    
                    <div 
                        v-if="bookingData.financial_summary.tiene_tiempo_extra" 
                        class="flex justify-between text-red-600"
                    >
                        <span class="font-semibold">Tiempo extra ({{ bookingData.time_info.horas_extra }}h):</span>
                        <span class="font-semibold">S/ {{ bookingData.financial_summary.costo_tiempo_extra?.toFixed(2) }}</span>
                    </div>
                    
                    <div 
                        v-if="bookingData.financial_summary.tax_amount > 0" 
                        class="flex justify-between"
                    >
                        <span class="text-gray-700">Impuestos:</span>
                        <span class="font-medium">S/ {{ bookingData.financial_summary.tax_amount?.toFixed(2) }}</span>
                    </div>
                    
                    <div 
                        v-if="bookingData.financial_summary.discount_amount > 0" 
                        class="flex justify-between text-green-600"
                    >
                        <span class="text-gray-700">Descuentos:</span>
                        <span class="font-medium">-S/ {{ bookingData.financial_summary.discount_amount?.toFixed(2) }}</span>
                    </div>
                    
                    <Divider />
                    
                    <!-- TOTAL con Message -->
                    <Message severity="info" :closable="false" class="mb-2">
                        <div class="flex justify-between items-center w-full">
                            <span class="font-bold">TOTAL: </span>
                            <span class="text-xl font-bold">
                                S/ {{ bookingData.financial_summary.total_con_extra?.toFixed(2) }}
                            </span>
                        </div>
                    </Message>
                    
                    <div class="flex justify-between">
                        <span class="">Pagado:</span>
                        <span class="font-medium text-green-600">S/ {{ bookingData.financial_summary.paid_amount?.toFixed(2) }}</span>
                    </div>
                    
                    <!-- SALDO PENDIENTE con Message -->
                    <Message 
                        :severity="bookingData.financial_summary.saldo_con_extra > 0 ? 'error' : 'success'" 
                        :closable="false"
                    >
                        <div class="flex justify-between items-center w-full">
                            <span class="font-bold">SALDO PENDIENTE:</span>
                            <span class="text-xl font-bold">
                                S/ {{ bookingData.financial_summary.saldo_con_extra?.toFixed(2) }}
                            </span>
                        </div>
                    </Message>
                </div>
            </Panel>
            <!-- Consumos -->
            <Panel 
                v-if="bookingData.consumptions && bookingData.consumptions.length > 0" 
                header="🛒 Consumos" 
                class="mb-4"
            >
                <div class="max-h-40 overflow-y-auto">
                    <div 
                        v-for="consumption in bookingData.consumptions" 
                        :key="consumption.id"
                        class="flex justify-between py-2 border-b last:border-b-0 text-sm"
                    >
                        <div>
                            <div class="font-medium">{{ consumption.product_name }}</div>
                            <div class="">{{ consumption.quantity }} × S/ {{ consumption.unit_price }}</div>
                        </div>
                        <div class="font-semibold">S/ {{ consumption.total_price }}</div>
                    </div>
                </div>
            </Panel>

            <!-- Formulario de Pago (si hay saldo pendiente) -->
            <Panel 
                v-if="bookingData.financial_summary.saldo_con_extra > 0" 
                header="💳 Registrar Pago" 
                class="mb-4 border-2 border-yellow-400"
            >
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Método de pago *</label>
                        <Dropdown
                            v-model="payment.method_id"
                            :options="paymentMethods"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Seleccione método de pago"
                            class="w-full"
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Monto a pagar *</label>
                        <InputNumber
                            v-model="payment.amount"
                            :max="bookingData.financial_summary.saldo_con_extra"
                            :minFractionDigits="2"
                            :maxFractionDigits="2"
                            prefix="S/ "
                            placeholder="0.00"
                            class="w-full"
                            inputClass="w-full"
                        />
                        <small class="">
                            Saldo pendiente: S/ {{ bookingData.financial_summary.saldo_con_extra?.toFixed(2) }}
                        </small>
                    </div>
                    
                    <div v-if="requiresOperationNumber">
                        <label class="block text-sm font-medium mb-1">N° de Operación *</label>
                        <InputText
                            v-model="payment.operation_number"
                            placeholder="Ej: 123456789"
                            class="w-full"
                        />
                    </div>
                </div>
            </Panel>

            <!-- Checkbox para forzar finalización -->
            <div v-if="bookingData.financial_summary.saldo_con_extra > 0" class="mb-4">
                <div class="flex items-center">
                    <Checkbox 
                        v-model="forceCheckout" 
                        inputId="force" 
                        :binary="true" 
                    />
                    <label for="force" class="ml-2 text-sm cursor-pointer">
                        Finalizar servicio aunque quede saldo pendiente
                    </label>
                </div>
            </div>

            <!-- Notas adicionales -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Notas adicionales (opcional)</label>
                <Textarea
                    v-model="notasFinalizacion"
                    rows="2"
                    placeholder="Ej: Cliente dejó propina, etc."
                    class="w-full"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex gap-2 justify-end">
                <Button 
                    label="Cancelar" 
                    severity="secondary" 
                    outlined 
                    @click="cerrarModal"
                    :disabled="submitting"
                />
                <Button 
                    label="Finalizar Servicio" 
                    icon="pi pi-check" 
                    severity="success"
                    @click="confirmarFinalizacion"
                    :disabled="!canFinalize || submitting"
                    :loading="submitting"
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
import Panel from 'primevue/panel';
import Message from 'primevue/message';
import Divider from 'primevue/divider';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const store = useRoomManagementStore();
const toast = useToast();

const loading = ref<boolean>(false);
const submitting = ref<boolean>(false);
const bookingData = ref<any>(null);
const paymentMethods = ref<any[]>([]);

const payment = ref({
    method_id: null,
    amount: 0,
    operation_number: ''
});

const forceCheckout = ref<boolean>(false);
const notasFinalizacion = ref<string>('');

// Cargar datos cuando se abre el modal
watch(() => store.finalizarDialog, async (isOpen) => {
    if (isOpen && store.selectedBookingId) {
        await Promise.all([
            cargarDatosBooking(),
            cargarMetodosPago()
        ]);
    } else {
        resetForm();
    }
});

const requiresOperationNumber = computed(() => {
    if (!payment.value.method_id || !paymentMethods.value.length) return false;
    const method = paymentMethods.value.find(m => m.id === payment.value.method_id);
    return method?.requires_reference || false;
});

const canFinalize = computed(() => {
    if (!bookingData.value) return false;
    
    const noDebt = bookingData.value.financial_summary.saldo_con_extra <= 0;
    const hasValidPayment = payment.value.amount > 0 && payment.value.method_id;
    
    return noDebt || forceCheckout.value || hasValidPayment;
});

const cargarDatosBooking = async () => {
    try {
        loading.value = true;
        const response = await fetch(`/detalles/bookings/${store.selectedBookingId}/habitacion`);
        const data = await response.json();
        
        if (data.success) {
            bookingData.value = data.data;
            // Pre-llenar monto con saldo pendiente
            payment.value.amount = data.data.financial_summary.saldo_con_extra;
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

const cargarMetodosPago = async () => {
    try {
        const response = await fetch('/payments/methods');
        const data = await response.json();
        paymentMethods.value = data.data || [];
    } catch (error) {
        console.error('Error al cargar métodos de pago:', error);
    }
};

const confirmarFinalizacion = async () => {
    try {
        submitting.value = true;
        
        const payload: any = {
            notes: notasFinalizacion.value || null,
            force_checkout: forceCheckout.value,
            payments: []
        };
        
        // Si hay un pago, agregarlo
        if (payment.value.amount > 0 && payment.value.method_id) {
            payload.payments.push({
                payment_method_id: payment.value.method_id,
                amount: payment.value.amount,
                operation_number: payment.value.operation_number || null
            });
        }
        
        const response = await axios.post(`/bookings/${store.selectedBookingId}/finish`, payload);
        
        const data = response.data;
        
        if (data.success) {
            toast.add({
                severity: 'success',
                summary: '✅ Éxito',
                detail: data.message,
                life: 3000
            });
            
            cerrarModal();
            await store.fetchFloors(); // Recargar habitaciones
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: data.message,
                life: 3000
            });
        }
    } catch (error) {
        console.error('Error al finalizar servicio:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al finalizar el servicio',
            life: 3000
        });
    } finally {
        submitting.value = false;
    }
};

const cerrarModal = () => {
    store.finalizarDialog = false;
    resetForm();
};

const resetForm = () => {
    bookingData.value = null;
    payment.value = {
        method_id: null,
        amount: 0,
        operation_number: ''
    };
    forceCheckout.value = false;
    notasFinalizacion.value = '';
};
</script>