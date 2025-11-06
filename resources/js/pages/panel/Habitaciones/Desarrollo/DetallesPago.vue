<template>
    <Dialog 
        v-model:visible="visible" 
        modal 
        header="Detalle del Pago" 
        :style="{ width: '70rem' }" 
        @update:visible="cerrarDialog"
        :draggable="false">
        
        <div v-if="isLoading" class="text-center p-8">
            <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
            <p class="mt-3 text-lg">Cargando detalles del pago...</p>
        </div>

        <div v-else-if="pago" class="space-y-4">
            <!-- Información Principal del Pago -->
            <Message severity="info" :closable="false">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <span class="text-sm">Código de Pago</span>
                        <p class="font-bold text-lg">{{ pago.payment_code }}</p>
                    </div>
                    <div>
                        <span class="text-sm">Estado del Pago</span>
                        <div class="mt-1">
                            <Tag :value="pago.status_label" :severity="getPaymentStatusSeverity(pago.status)" />
                        </div>
                    </div>
                    <div>
                        <span class="text-sm">Fecha de Pago</span>
                        <p class="font-semibold">{{ pago.payment_date || 'No registrado' }}</p>
                    </div>
                </div>
            </Message>

            <!-- Información de la Reserva -->
            <div v-if="pago.booking">
                <Divider align="left">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-calendar text-primary"></i>
                        <span class="font-semibold">Información de la Reserva</span>
                    </div>
                </Divider>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-lg">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="">Código Reserva:</span>
                            <span class="font-semibold">{{ pago.booking.booking_code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="">Habitación:</span>
                            <Tag :value="pago.booking.room.number" severity="info" />
                        </div>
                        <div class="flex justify-between">
                            <span class="">Sucursal:</span>
                            <span class="font-semibold">{{ pago.booking.room.sub_branch.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="">Estado Reserva:</span>
                            <Tag :value="pago.booking.status_label" :severity="getBookingStatusSeverity(pago.booking.status)" />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="">Cliente:</span>
                            <span class="font-semibold">{{ pago.booking.customer.name }}</span>
                        </div>
                        <div class="flex justify-between" v-if="pago.booking.customer.document">
                            <span class="">Documento:</span>
                            <span class="font-semibold">{{ pago.booking.customer.document }}</span>
                        </div>
                        <div class="flex justify-between" v-if="pago.booking.customer.phone">
                            <span class="">Teléfono:</span>
                            <span class="font-semibold">{{ pago.booking.customer.phone }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="">Total Horas:</span>
                            <span class="font-semibold">{{ pago.booking.total_hours }} hrs</span>
                        </div>
                    </div>
                </div>

                <!-- Horarios -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                    <Message severity="success" :closable="false">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-sign-in text-2xl"></i>
                            <div>
                                <span class="text-xs">Check-in</span>
                                <p class="font-semibold">{{ pago.booking.check_in }}</p>
                            </div>
                        </div>
                    </Message>

                    <Message severity="warn" :closable="false">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-sign-out text-2xl"></i>
                            <div>
                                <span class="text-xs">Check-out</span>
                                <p class="font-semibold">{{ pago.booking.check_out || 'Pendiente' }}</p>
                            </div>
                        </div>
                    </Message>
                </div>
            </div>

            <!-- Desglose de Costos -->
            <div>
                <Divider align="left">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-money-bill text-primary"></i>
                        <span class="font-semibold">Desglose de Costos</span>
                    </div>
                </Divider>

                <div class="p-4 rounded-lg space-y-2">
                    <div class="flex justify-between items-center py-2 border-b">
                        <span class="">Costo de Habitación</span>
                        <span class="font-semibold text-lg">S/. {{ formatCurrency(pago.booking?.room_subtotal) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b">
                        <span class="">Subtotal Productos</span>
                        <span class="font-semibold text-lg">S/. {{ formatCurrency(pago.booking?.products_subtotal) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b">
                        <span class="">Subtotal</span>
                        <span class="font-semibold text-lg">S/. {{ formatCurrency(pago.booking?.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b text-green-600">
                        <span>Impuestos (IGV)</span>
                        <span class="font-semibold">S/. {{ formatCurrency(pago.booking?.tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b text-red-600" v-if="pago.booking?.discount_amount > 0">
                        <span>Descuentos</span>
                        <span class="font-semibold">- S/. {{ formatCurrency(pago.booking?.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 px-3 rounded mt-2">
                        <span class="font-bold text-lg">Total a Pagar</span>
                        <span class="font-bold text-2xl text-primary">S/. {{ formatCurrency(pago.booking?.total_amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Consumos -->
            <div v-if="pago.booking?.consumptions && pago.booking.consumptions.length > 0">
                <Divider align="left">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-shopping-cart text-primary"></i>
                        <span class="font-semibold">Detalle de Consumos ({{ pago.booking.consumptions.length }})</span>
                    </div>
                </Divider>

                <DataTable 
                    :value="pago.booking.consumptions" 
                    class="p-datatable-sm"
                    :paginator="pago.booking.consumptions.length > 5"
                    :rows="5">
                    <Column field="product" header="Producto" style="min-width: 15rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-box text-primary"></i>
                                <span class="font-semibold">{{ data.product }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="quantity" header="Cantidad" style="min-width: 8rem">
                        <template #body="{ data }">
                            <Tag :value="data.quantity" severity="info" />
                        </template>
                    </Column>
                    <Column field="unit_price" header="Precio Unit." style="min-width: 10rem">
                        <template #body="{ data }">
                            <span class="font-semibold">S/. {{ formatCurrency(data.unit_price) }}</span>
                        </template>
                    </Column>
                    <Column field="total_price" header="Total" style="min-width: 10rem">
                        <template #body="{ data }">
                            <span class="font-bold text-primary">S/. {{ formatCurrency(data.total_price) }}</span>
                        </template>
                    </Column>
                    <Column field="consumed_at" header="Fecha Consumo" style="min-width: 12rem">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.consumed_at }}</span>
                        </template>
                    </Column>
                </DataTable>

                <div class="flex justify-end mt-3 p-3 rounded">
                    <div class="text-right">
                        <span class="mr-3">Total Consumos:</span>
                        <span class="font-bold text-xl text-orange-600">S/. {{ formatCurrency(pago.booking.total_consumos) }}</span>
                    </div>
                </div>
            </div>

            <div v-else>
                <Divider align="left">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-shopping-cart text-primary"></i>
                        <span class="font-semibold">Consumos</span>
                    </div>
                </Divider>
                <Message severity="secondary" :closable="false">
                    <div class="text-center">
                        <i class="pi pi-inbox text-3xl block mb-2"></i>
                        <p>No se registraron consumos en esta reserva</p>
                    </div>
                </Message>
            </div>

            <!-- Información del Pago -->
            <div>
                <Divider align="left">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-wallet text-primary"></i>
                        <span class="font-semibold">Información del Pago</span>
                    </div>
                </Divider>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-lg space-y-3">
                        <div class="flex justify-between">
                            <span>Método de Pago:</span>
                            <Tag :value="pago.payment_method.name" :severity="getPaymentMethodSeverity(pago.payment_method.name)" />
                        </div>
                        <div class="flex justify-between">
                            <span class="">Moneda:</span>
                            <span class="font-semibold">{{ pago.currency.name }} ({{ pago.currency.code }})</span>
                        </div>
                        <div class="flex justify-between" v-if="pago.exchange_rate && pago.exchange_rate !== 1">
                            <span class="">Tipo de Cambio:</span>
                            <span class="font-semibold">{{ pago.exchange_rate }}</span>
                        </div>
                        <div class="flex justify-between" v-if="pago.operation_number">
                            <span class="">N° Operación:</span>
                            <span class="font-semibold">{{ pago.operation_number }}</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-lg space-y-3">
                        <div class="flex justify-between">
                            <span class="">Caja:</span>
                            <span class="font-semibold">{{ pago.cash_register.name }}</span>
                        </div>
                        <div class="flex justify-between" v-if="pago.reference">
                            <span class="">Referencia:</span>
                            <span class="font-semibold">{{ pago.reference }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="">Registrado:</span>
                            <span class="font-semibold">{{ pago.created_at }}</span>
                        </div>
                        <div class="flex justify-between" v-if="pago.updated_at !== pago.created_at">
                            <span class="">Actualizado:</span>
                            <span class="font-semibold">{{ pago.updated_at }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div v-if="pago.notes" class="mt-3">
                    <Message severity="secondary" :closable="false">
                        <div>
                            <strong>Notas:</strong>
                            <p class="mt-1">{{ pago.notes }}</p>
                        </div>
                    </Message>
                </div>
            </div>

            <!-- Resumen de Pagos -->
            <div>
                <Divider align="left">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-chart-line text-primary"></i>
                        <span class="font-semibold"> Resumen de Pagos</span>
                    </div>
                </Divider>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <Message severity="success" :closable="false">
                        <div class="text-center">
                            <i class="pi pi-check-circle text-3xl block mb-2"></i>
                            <span class="text-xs"> Total Pagado</span>
                            <p class="font-bold text-xl">S/. {{ formatCurrency(pago.booking?.paid_amount) }}</p>
                        </div>
                    </Message>

                    <Message :severity="pago.booking?.balance > 0 ? 'warn' : 'success'" :closable="false">
                        <div class="text-center">
                            <i :class="pago.booking?.balance > 0 ? 'pi pi-exclamation-triangle' : 'pi pi-check-circle'" class="text-3xl block mb-2"></i>
                            <span class="text-xs">Saldo Pendiente</span>
                            <p class="font-bold text-xl">S/. {{ formatCurrency(pago.booking?.balance) }}</p>
                        </div>
                    </Message>

                    <Message severity="info" :closable="false">
                        <div class="text-center">
                            <i class="pi pi-wallet text-3xl block mb-2"></i>
                            <span class="text-xs"> Este Pago</span>
                            <p class="font-bold text-xl">S/. {{ formatCurrency(pago.amount) }}</p>
                        </div>
                    </Message>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cerrar" icon="pi pi-times" @click="cerrarDialog" severity="secondary" />
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import axios from 'axios';

const props = defineProps({
    pagoId: {
        type: String,
        default: null
    },
    modelValue: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const visible = ref(props.modelValue);
const pago = ref(null);
const isLoading = ref(false);

const formatCurrency = (value: number): string => {
    return value?.toFixed(2) || '0.00';
};

const cargarDetalle = async (id: string) => {
    if (!id) return;
    
    isLoading.value = true;
    try {
        const response = await axios.get(`/reporte-pagos/${id}`);
        
        // La respuesta viene directamente en response.data.data
        if (response.data && response.data.data) {
            pago.value = response.data.data;
        } else if (response.data) {
            // Por si acaso viene directo en response.data
            pago.value = response.data;
        }
        
        console.log('Pago cargado:', pago.value);
    } catch (error) {
        console.error('Error al cargar detalle del pago:', error);
    } finally {
        isLoading.value = false;
    }
};

const cerrarDialog = () => {
    visible.value = false;
    emit('update:modelValue', false);
    pago.value = null;
};

const getPaymentStatusSeverity = (status: string): string => {
    const statusMap: Record<string, string> = {
        'pending': 'warn',
        'completed': 'success',
        'cancelled': 'danger',
        'refunded': 'info'
    };
    return statusMap[status] || 'secondary';
};

const getBookingStatusSeverity = (status: string): string => {
    const statusMap: Record<string, string> = {
        'pending': 'warn',
        'confirmed': 'info',
        'checked_in': 'success',
        'checked_out': 'secondary',
        'cancelled': 'danger'
    };
    return statusMap[status] || 'secondary';
};

const getPaymentMethodSeverity = (method: string): string => {
    const methodLower = method?.toLowerCase() || '';
    
    if (methodLower.includes('efectivo') || methodLower.includes('cash')) return 'success';
    if (methodLower.includes('tarjeta') || methodLower.includes('card')) return 'warn';
    if (methodLower.includes('yape')) return 'secondary';
    if (methodLower.includes('plin')) return 'info';
    if (methodLower.includes('transfer')) return 'contrast';
    
    return 'secondary';
};

watch(() => props.modelValue, (newVal) => {
    visible.value = newVal;
    if (newVal && props.pagoId) {
        cargarDetalle(props.pagoId);
    }
});

watch(() => props.pagoId, (newId) => {
    if (newId && visible.value) {
        cargarDetalle(newId);
    }
});
</script>