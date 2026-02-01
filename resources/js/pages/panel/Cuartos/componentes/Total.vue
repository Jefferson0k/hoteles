<template>
    <br>
    <div class="p-6 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-300 dark:border-slate-600">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-surface-900 dark:text-surface-0 flex items-center gap-2">
                <i class="pi pi-file-edit"></i>
                Resumen de Cuenta
            </h3>
            
            <!-- Botones de Tipo de Comprobante -->
            <div class="flex gap-2">
                <Button 
                    v-for="type in voucherTypes" 
                    :key="type.value"
                    :label="voucherType === type.value ? `✓ ${type.label}` : type.label" 
                    :severity="voucherType === type.value ? 'success' : 'secondary'"
                    size="small"
                    @click="voucherType = type.value"
                    :outlined="voucherType !== type.value"
                />
            </div>
        </div>

        <!-- Indicador de tipo de comprobante y moneda -->
        <div class="mb-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="pi pi-receipt text-primary-600 dark:text-primary-400"></i>
                    <span class="font-semibold text-primary-700 dark:text-primary-300">
                        Comprobante: {{ voucherType.toUpperCase() }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="pi pi-dollar text-green-600 dark:text-green-400"></i>
                    <span class="font-semibold text-green-700 dark:text-green-300">
                        {{ currencySymbol }} {{ currencyCode }}
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <!-- Habitación -->
            <div class="flex justify-between items-center pb-2 border-b border-surface-300 dark:border-surface-600">
                <div>
                    <p class="font-medium text-surface-900 dark:text-surface-0">
                        Habitación {{ roomNumber || 'N/A' }}
                    </p>
                    <p class="text-sm text-surface-600 dark:text-surface-400">
                        {{ rateLabel }} - {{ timeAmount }} {{ timeUnit }}
                    </p>
                </div>
                <span class="font-semibold text-lg text-surface-900 dark:text-surface-0">
                    {{ currencySymbol }} {{ roomTotal.toFixed(2) }}
                </span>
            </div>

            <!-- Productos -->
            <div v-if="products.length > 0" class="pb-2 border-b border-surface-300 dark:border-surface-600">
                <p class="font-medium text-surface-900 dark:text-surface-0 mb-2">Productos</p>
                <div v-for="product in products" :key="product.id" class="flex justify-between text-sm mb-1">
                    <span class="text-surface-600 dark:text-surface-400">
                        {{ product.name || product.nombre }} x{{ getProductQuantity(product).toFixed(2) }}
                    </span>
                    <span class="text-surface-900 dark:text-surface-0">
                        {{ currencySymbol }} {{ getProductTotal(product).toFixed(2) }}
                    </span>
                </div>
            </div>

            <!-- Subtotal -->
            <div class="flex justify-between items-center text-lg">
                <span class="font-medium text-surface-700 dark:text-surface-300">Subtotal:</span>
                <span class="font-semibold text-surface-900 dark:text-surface-0">
                    {{ currencySymbol }} {{ subtotal.toFixed(2) }}
                </span>
            </div>

            <!-- Total -->
            <div class="flex justify-between items-center pt-3 border-t-2 border-surface-400 dark:border-surface-500">
                <span class="text-2xl font-bold text-surface-900 dark:text-surface-0">TOTAL:</span>
                <span class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                    {{ currencySymbol }} {{ total.toFixed(2) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Button from 'primevue/button';

interface Product {
    id: number | string;
    name?: string;
    nombre?: string;
    price?: number | string;
    precio_venta?: number | string;
    quantity?: number | string;
    cantidad?: number | string;
}

interface Props {
    roomNumber?: string | number;
    roomPrice?: number | string;
    selectedRate?: 'hour' | 'day' | 'night' | null;
    timeAmount?: number | string;
    products?: Product[];
    currencySymbol?: string;
    currencyCode?: string;
    modelValue?: 'boleta' | 'ticket' | 'factura';
}

const props = withDefaults(defineProps<Props>(), {
    roomNumber: '',
    roomPrice: 0,
    selectedRate: null,
    timeAmount: 1,
    products: () => [],
    currencySymbol: 'S/',
    currencyCode: 'PEN',
    modelValue: 'boleta'
});

const emit = defineEmits<{
    'update:modelValue': [type: 'boleta' | 'ticket' | 'factura'];
}>();

const voucherTypes = [
    { value: 'boleta', label: 'Boleta' },
    { value: 'ticket', label: 'Ticket' },
    { value: 'factura', label: 'Factura' }
];

const voucherType = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value as 'boleta' | 'ticket' | 'factura')
});

const rateLabel = computed(() => {
    const labels: Record<string, string> = {
        'hour': 'Por Hora',
        'day': 'Por Día',
        'night': 'Por Noche'
    };
    return props.selectedRate ? labels[props.selectedRate] : 'Sin tarifa';
});

const timeUnit = computed(() => {
    const units: Record<string, string> = {
        'hour': 'Hora(s)',
        'day': 'Día(s)',
        'night': 'Noche(s)'
    };
    return props.selectedRate ? units[props.selectedRate] : '';
});

// Convertir a número de forma segura
const safeNumber = (value: any, defaultValue: number = 0): number => {
    const parsed = parseFloat(value);
    return isNaN(parsed) ? defaultValue : parsed;
};

const getProductQuantity = (product: Product): number => {
    return safeNumber(product.quantity || product.cantidad);
};

const getProductTotal = (product: Product): number => {
    const quantity = safeNumber(product.quantity || product.cantidad);
    const price = safeNumber(product.price || product.precio_venta);
    return quantity * price;
};

const roomTotal = computed(() => {
    const price = safeNumber(props.roomPrice);
    const amount = safeNumber(props.timeAmount, 1);
    return price * amount;
});

const productsTotal = computed(() => {
    return props.products.reduce((sum, p) => {
        const quantity = safeNumber(p.quantity || p.cantidad);
        const price = safeNumber(p.price || p.precio_venta);
        return sum + (quantity * price);
    }, 0);
});

const subtotal = computed(() => {
    return roomTotal.value + productsTotal.value;
});

const total = computed(() => {
    return subtotal.value;
});
</script>