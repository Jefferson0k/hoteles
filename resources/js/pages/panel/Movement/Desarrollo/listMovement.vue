<template>
    <!-- DataTable -->
    <DataTable :value="movements" :paginator="true" :rows="perPage" :totalRecords="totalRecords" :lazy="true"
        @page="onPage" :loading="loading" stripedRows  responsiveLayout="scroll" :scrollable="true"
        scrollHeight="600px" class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Movimientos</h4>
                <div class="flex gap-2 items-center">
                    <!-- Filtro por tipo de movimiento -->
                    <SelectButton 
                        v-model="selectedMovementType" 
                        :options="movementTypeOptions" 
                        optionLabel="label"
                        optionValue="value"
                        @change="onMovementTypeChange"
                    />
                    
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="searchQuery" placeholder="Buscar movimientos..." @input="onSearch" />
                        <Button icon="pi pi-refresh" @click="loadMovements" :loading="loading" severity="contrast" rounded variant="outlined"
                            v-tooltip="'Actualizar'" />
                    </IconField>
                </div>
            </div>
        </template>
        <template #loading>
            <div class="flex align-items-center justify-center">
                <ProgressSpinner style="width:50px;height:50px" strokeWidth="8" />
            </div>
        </template>
    
    <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        
        <!-- NUEVA COLUMNA: Tipo de Movimiento -->
        <Column field="movement_type" header="Tipo" sortable style="min-width: 6rem">
            <template #body="slotProps">
                <Tag 
                    :value="getMovementTypeLabel(slotProps.data.movement_type)" 
                    :severity="getMovementTypeSeverity(slotProps.data.movement_type)" 
                    class="text-sm" 
                />
            </template>
        </Column>
        
        <Column field="code" header="Código" sortable style="min-width: 8rem">
            <template #body="slotProps">
                <span class="font-semibold text-primary">
                    {{ slotProps.data.code || 'Sin código' }}
                </span>
            </template>
        </Column>
        
        <Column field="voucher_type" header="Comprobante" sortable style="min-width: 5rem">
            <template #body="slotProps">
                <Tag 
                    :value="getVoucherTypeLabel(slotProps.data.voucher_type)" 
                    :severity="slotProps.data.voucher_type ? 'secondary' : 'danger'" 
                    class="text-sm" 
                />
            </template>
        </Column>

        <Column field="provider.razon_social" header="Proveedor" sortable style="min-width: 15rem">
            <template #body="slotProps">
                <div class="flex flex-col">
                    <span class="font-semibold">
                        {{ slotProps.data.provider?.razon_social || 'Sin proveedor' }}
                    </span>
                    <small class="text-gray-500">
                        RUC: {{ slotProps.data.provider?.ruc || 'No disponible' }}
                    </small>
                </div>
            </template>
        </Column>

        <Column field="payment_type" header="Tipo Pago" sortable style="min-width: 8rem">
            <template #body="slotProps">
                <Tag 
                    :value="getPaymentTypeLabel(slotProps.data.payment_type)"
                    :severity="getPaymentTypeSeverity(slotProps.data.payment_type)" 
                    class="text-sm" 
                />
            </template>
        </Column>
        
        <Column field="credit_date" header="Fecha de Crédito" sortable style="min-width: 11rem">
            <template #body="slotProps">
                <span :class="{ 'text-gray-400 italic': !slotProps.data.credit_date }">
                    {{ slotProps.data.credit_date || 'No aplica' }}
                </span>
            </template>
        </Column>
        
        <Column field="date" header="Fecha Emisión" sortable style="min-width: 9rem">
            <template #body="slotProps">
                <span :class="{ 'text-red-500': !slotProps.data.date }">
                    {{ slotProps.data.date || 'Sin fecha' }}
                </span>
            </template>
        </Column>
        
        <Column field="created_at" header="Fecha Ejecución" sortable style="min-width: 12rem">
            <template #body="slotProps">
                <span>
                    {{ slotProps.data.created_at || 'No disponible' }}
                </span>
            </template>
        </Column>
        
        <Column field="includes_igv" header="IGV" sortable style="min-width: 5rem">
            <template #body="slotProps">
                <Tag 
                    :value="getIgvLabel(slotProps.data.includes_igv)"
                    :severity="getIgvSeverity(slotProps.data.includes_igv)" 
                    class="text-sm" 
                />
            </template>
        </Column>
        
        <Column field="subtotal" header="Sub Total" sortable style="min-width: 8rem">
            <template #body="slotProps">
                <span :class="{ 'text-red-500': !slotProps.data.subtotal }">
                    {{ formatCurrency(slotProps.data.subtotal) || 'S/ 0.00' }}
                </span>
            </template>
        </Column>
        
        <Column field="igv" header="IGV" sortable style="min-width: 8rem">
            <template #body="slotProps">
                <span>
                    {{ formatCurrency(slotProps.data.igv) || 'S/ 0.00' }}
                </span>
            </template>
        </Column>
        
        <Column field="total" header="Total" sortable style="min-width: 8rem">
            <template #body="slotProps">
                <span class="font-semibold" :class="{ 'text-red-500': !slotProps.data.total }">
                    {{ formatCurrency(slotProps.data.total) || 'S/ 0.00' }}
                </span>
            </template>
        </Column>

        <Column header="">
            <template #body="slotProps">
                <Button 
                    icon="pi pi-ellipsis-v" 
                    size="small" 
                    severity="secondary"
                    text
                    @click="toggleActionsMenu($event, slotProps.data)"
                    v-tooltip="'Acciones'" 
                />
            </template>
        </Column>

        <template #empty>
            <div class="text-center py-4">
                <i class="pi pi-inbox text-4xl text-gray-400 mb-2"></i>
                <p class="text-gray-500">No se encontraron movimientos</p>
            </div>
        </template>
    </DataTable>

    <!-- Menú contextual de acciones -->
    <Menu ref="actionsMenu" id="actionsMenu" :model="actionMenuItems" :popup="true" />

    <!-- Dialog de confirmación para eliminar -->
    <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Confirmar Eliminación" :modal="true">
        <div class="flex align-items-center justify-center">
            <i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
            <span v-if="movementToDelete">
                ¿Está seguro de que desea eliminar el movimiento <b>{{ movementToDelete.code || 'sin código' }}</b>?
            </span>
        </div>
        <template #footer>
            <Button label="No" icon="pi pi-times" severity="secondary" text @click="deleteDialog = false" />
            <Button label="Sí" icon="pi pi-check" severity="danger" :loading="deleteLoading" @click="deleteMovement" />
        </template>
    </Dialog>
    <UpdateMovement ref="updateMovementRef" @actualizado="onMovementUpdated" />
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Menu from 'primevue/menu';
import ProgressSpinner from 'primevue/progressspinner';
import SelectButton from 'primevue/selectbutton';
import { useToast } from 'primevue/usetoast';
import { defineProps, defineEmits } from 'vue';
import IconField from 'primevue/iconfield';
import UpdateMovement from './updateMovement.vue';

const props = defineProps({
    refreshTrigger: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['movement-updated', 'movement-deleted']);
const toast = useToast();

// Estado del componente
const movements = ref([]);
const loading = ref(false);
const deleteLoading = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const perPage = ref(15);
const totalRecords = ref(0);
const deleteDialog = ref(false);
const movementToDelete = ref(null);
const actionsMenu = ref();
const selectedMovement = ref(null);
const updateMovementRef = ref();
// NUEVO: Filtro por tipo de movimiento
const selectedMovementType = ref('all'); // Por defecto muestra ingresos

// OPCIONES PARA TIPO DE MOVIMIENTO
const movementTypeOptions = [
    { label: 'Ingresos', value: 'ingreso' },
    { label: 'Egresos', value: 'egreso' },
    { label: 'Todos', value: 'all' }
];

// Elementos del menú de acciones
const actionMenuItems = ref([
    {
        label: 'Ver detalle',
        icon: 'pi pi-eye',
        command: () => viewMovement(selectedMovement.value)
    },
    {
        label: 'Editar',
        icon: 'pi pi-pencil',
        command: () => editMovement(selectedMovement.value)
    },
    {
        separator: true
    },
    {
        label: 'Eliminar',
        icon: 'pi pi-trash',
        command: () => confirmDeleteMovement(selectedMovement.value),
        class: 'text-red-500'
    }
]);

// Debounce para búsqueda
let searchTimeout = null;

// Funciones de utilidad para validación y formato

function getMovementTypeLabel(type) {
    const types = {
        ingreso: 'Ingreso',
        egreso: 'Egreso'
    };
    return types[type] || type;
}

// NUEVA: Obtener severity del tipo de movimiento
function getMovementTypeSeverity(type) {
    const map = {
        ingreso: 'success',
        egreso: 'danger' // egresos en rojo 🔴
    };
    return map[type] || 'secondary';
}

// Formatear moneda
function formatCurrency(value) {
    if (value === null || value === undefined || value === '') return null;
    const numValue = parseFloat(value);
    if (isNaN(numValue)) return null;
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        minimumFractionDigits: 2
    }).format(numValue);
}

// Obtener etiqueta del tipo de comprobante
function getVoucherTypeLabel(type) {
    if (!type) return 'Sin tipo';
    const types = {
        'factura': 'Factura',
        'boleta': 'Boleta',
        'guia': 'Guia'
    };
    return types[type] || type;
}

// Obtener etiqueta del tipo de pago
function getPaymentTypeLabel(type) {
    if (!type) return 'Sin definir';
    return type === 'contado' ? 'Contado' : 'Crédito';
}

// Obtener severity del tipo de pago
function getPaymentTypeSeverity(type) {
    if (!type) return 'danger';
    return type === 'contado' ? 'success' : 'warn';
}

// Obtener etiqueta de IGV
function getIgvLabel(includesIgv) {
    if (includesIgv === null || includesIgv === undefined) return 'No definido';
    return includesIgv ? 'Sí' : 'No';
}

// Obtener severity de IGV
function getIgvSeverity(includesIgv) {
    if (includesIgv === null || includesIgv === undefined) return 'danger';
    return includesIgv ? 'info' : 'secondary';
}

// Mostrar menú de acciones
function toggleActionsMenu(event, movement) {
    selectedMovement.value = movement;
    actionsMenu.value.toggle(event);
}

// Ver movimiento - Navegar a la página de detalle
function viewMovement(movement) {
    if (!movement || !movement.id) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se puede ver el movimiento: ID no disponible',
            life: 3000
        });
        return;
    }

    // Usar Inertia para navegar a la página de detalle
    router.visit(`/panel/movimientos/${movement.id}`);
}

// Editar movimiento
function editMovement(movement) {
    if (!movement || !movement.id) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se puede editar el movimiento: ID no disponible',
            life: 3000
        });
        return;
    }
    updateMovementRef.value.open(movement.id);
}

function onMovementUpdated() {
    loadMovements(currentPage.value, searchQuery.value);
    emit('movement-updated');
}

// NUEVA: Manejar cambio de tipo de movimiento
function onMovementTypeChange() {
    currentPage.value = 1;
    loadMovements(1, searchQuery.value);
}

// Cargar movimientos con validación
async function loadMovements(page = 1, search = '') {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (page > 1) params.append('page', page);
        
        // NUEVO: Agregar filtro por tipo de movimiento
        if (selectedMovementType.value !== 'todos') {
            params.append('movement_type', selectedMovementType.value);
        }

        const url = `/movements${params.toString() ? '?' + params.toString() : ''}`;
        const response = await axios.get(url);

        // Validar y limpiar datos recibidos
        movements.value = (response.data.data || []).map(movement => ({
            ...movement,
            // Asegurar que los campos críticos tengan valores por defecto
            movement_type: movement.movement_type || 'ingreso', // NUEVO: valor por defecto
            code: movement.code || null,
            voucher_type: movement.voucher_type || null,
            payment_type: movement.payment_type || null,
            provider: movement.provider || {},
            sub_total: movement.sub_total || 0,
            igv: movement.igv || 0,
            total: movement.total || movement.totoal || 0, // Corregir typo si existe
            includes_igv: movement.includes_igv ?? null,
            date: movement.date || null,
            credit_date: movement.credit_date || null,
            created_at: movement.created_at || null
        }));

        totalRecords.value = response.data.meta?.total || 0;
        perPage.value = response.data.meta?.per_page || 15;
        currentPage.value = response.data.meta?.current_page || 1;

        // Mostrar advertencia si hay registros con datos faltantes
        const incompleteRecords = movements.value.filter(m => 
            !m.code || !m.voucher_type || !m.provider?.razon_social || !m.date
        );
        
        if (incompleteRecords.length > 0) {
            toast.add({
                severity: 'warn',
                summary: 'Advertencia',
                detail: `${incompleteRecords.length} movimientos tienen datos incompletos`,
                life: 4000
            });
        }

    } catch (error) {
        console.error('Error cargando movimientos:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar los movimientos',
            life: 3000
        });
        movements.value = [];
        totalRecords.value = 0;
    } finally {
        loading.value = false;
    }
}

function onPage(event) {
    const page = event.page + 1;
    currentPage.value = page;
    loadMovements(page, searchQuery.value);
}

function onSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        loadMovements(1, searchQuery.value);
    }, 300);
}

function confirmDeleteMovement(movement) {
    movementToDelete.value = movement;
    deleteDialog.value = true;
}

async function deleteMovement() {
    if (!movementToDelete.value) return;

    deleteLoading.value = true;
    try {
        await axios.delete(`/movements/${movementToDelete.value.id}`);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Movimiento eliminado correctamente',
            life: 3000
        });

        deleteDialog.value = false;
        movementToDelete.value = null;

        await loadMovements(currentPage.value, searchQuery.value);

        emit('movement-deleted');
    } catch (error) {
        console.error('Error eliminando movimiento:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo eliminar el movimiento',
            life: 3000
        });
    } finally {
        deleteLoading.value = false;
    }
}

watch(() => props.refreshTrigger, () => {
    loadMovements(currentPage.value, searchQuery.value);
});

onMounted(() => {
    loadMovements();
});

defineExpose({
    refresh: () => loadMovements(currentPage.value, searchQuery.value)
});
</script>