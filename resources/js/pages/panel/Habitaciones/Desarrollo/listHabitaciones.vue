<template>
    <!-- Sección de Totales con Messages de PrimeVue -->
    <div class="mb-4">
        <!-- Primera fila: 3 tarjetas principales -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
            <!-- Total General -->
            <Message severity="info">
                <template #icon>
                    <i class="pi pi-wallet text-3xl"></i>
                </template>
                <div class="ml-2">
                    <div class="text-sm font-medium mb-1">Total General</div>
                    <div class="text-2xl font-bold">S/. {{ formatCurrency(reporteData?.TOTAL_GENERAL || 0) }}</div>
                    <div class="text-xs mt-1">{{ totalRegistros }} pagos totales</div>
                </div>
            </Message>

            <!-- Total por Habitaciones -->
            <Message severity="success">
                <template #icon>
                    <i class="pi pi-building text-3xl"></i>
                </template>
                <div class="ml-2">
                    <div class="text-sm font-medium mb-1">Total Habitaciones</div>
                    <div class="text-2xl font-bold">S/. {{ formatCurrency(totalHabitaciones) }}</div>
                    <div class="text-xs mt-1">Ingresos por alquiler</div>
                </div>
            </Message>

            <!-- Total por Consumos -->
            <Message severity="warn">
                <template #icon>
                    <i class="pi pi-shopping-cart text-3xl"></i>
                </template>
                <div class="ml-2">
                    <div class="text-sm font-medium mb-1">Total Consumos</div>
                    <div class="text-2xl font-bold">S/. {{ formatCurrency(totalConsumos) }}</div>
                    <div class="text-xs mt-1">Ingresos adicionales</div>
                </div>
            </Message>
        </div>

        <!-- Segunda fila: Métodos de pago -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <Message 
                v-for="metodo in totalesPorMetodo" 
                :key="metodo.metodo_pago"
                @click="toggleMethodFilter(metodo.metodo_pago)"
                :severity="selectedMethod === metodo.metodo_pago ? getMethodSeverity(metodo.metodo_pago) : 'secondary'"
                class="cursor-pointer transition-all hover:scale-105">
                <template #icon>
                    <i :class="getMethodIcon(metodo.metodo_pago) + ' text-2xl'"></i>
                </template>
                <div class="ml-2">
                    <div class="text-sm font-semibold">{{ metodo.metodo_pago }}</div>
                    <div class="text-xl font-bold">S/. {{ formatCurrency(metodo.total_cobrado) }}</div>
                    <div class="text-xs">{{ metodo.cantidad_pagos }} pagos</div>
                </div>
            </Message>
        </div>
    </div>

    <!-- Tabla de Pagos -->
    <DataTable 
        ref="dt" 
        v-model:selection="selectedPagos" 
        :value="pagos" 
        dataKey="codigo_pago" 
        :loading="isLoading"
        :lazy="true"
        :paginator="true"
        :rows="pagination.per_page"
        :totalRecords="pagination.total"
        @page="onPage"
        @sort="onSort"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25, 50]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} pagos" 
        class="p-datatable-sm"
        stripedRows>
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <div class="flex items-center gap-2">
                    <h4 class="m-0">Listado de Pagos</h4>
                    
                    <Tag v-if="selectedMethod" 
                         :severity="getMethodSeverity(selectedMethod)">
                        <div class="flex items-center gap-2">
                            <i :class="getMethodIcon(selectedMethod)"></i>
                            <span>{{ selectedMethod }}</span>
                            <i @click.stop="clearMethodFilter()" class="pi pi-times cursor-pointer hover:text-red-500"></i>
                        </div>
                    </Tag>
                    <Tag v-if="filters.codigo_pago" severity="info">
                        <div class="flex items-center gap-2">
                            <span>Código: {{ filters.codigo_pago }}</span>
                            <i @click.stop="filters.codigo_pago = ''; aplicarFiltros()" class="pi pi-times cursor-pointer hover:text-red-500"></i>
                        </div>
                    </Tag>
                    <Tag v-if="filters.habitacion" severity="info">
                        <div class="flex items-center gap-2">
                            <span>Habitación: {{ filters.habitacion }}</span>
                            <i @click.stop="filters.habitacion = ''; aplicarFiltros()" class="pi pi-times cursor-pointer hover:text-red-500"></i>
                        </div>
                    </Tag>
                    <Tag v-if="filters.cliente" severity="info">
                        <div class="flex items-center gap-2">
                            <span>Cliente: {{ filters.cliente }}</span>
                            <i @click.stop="filters.cliente = ''; aplicarFiltros()" class="pi pi-times cursor-pointer hover:text-red-500"></i>
                        </div>
                    </Tag>
                </div>
                <div class="flex gap-2">
                    <Button 
                        icon="pi pi-filter-slash" 
                        severity="contrast" 
                        outlined 
                        @click="clearAllFilters" 
                        v-if="hasActiveFilters"
                    />
                    <Button 
                        icon="pi pi-refresh" 
                        severity="contrast" 
                        rounded 
                        outlined 
                        @click="cargarReporte" 
                        :loading="isLoading" 
                    />
                </div>
            </div>
        </template>
        
        <template #empty>
            <div class="text-center p-4">
                <i class="pi pi-inbox text-4xl mb-2 block text-gray-400"></i>
                <p class="text-gray-500">No hay pagos registrados</p>
            </div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        
        <Column field="code" header="Código" sortable style="min-width: 14rem">
            <template #filter>
                <InputText 
                    v-model="filters.codigo_pago" 
                    placeholder="Buscar por código"
                    class="p-column-filter w-full"
                    @keyup.enter="aplicarFiltros"
                />
            </template>
        </Column>
        
        <Column field="fecha_pago" header="Fecha Pago" sortable style="min-width: 12rem"></Column>
        
        <Column field="habitacion" header="Habitación" sortable style="min-width: 8rem">
            <template #filter>
                <InputText 
                    v-model="filters.habitacion" 
                    placeholder="Buscar habitación"
                    class="p-column-filter w-full"
                    @keyup.enter="aplicarFiltros"
                />
            </template>
            <template #body="{ data }">
                <Tag :value="data.habitacion" severity="info" />
            </template>
        </Column>
        
        <Column field="cliente" header="Cliente" sortable style="min-width: 20rem">
            <template #filter>
                <InputText 
                    v-model="filters.cliente" 
                    placeholder="Buscar cliente"
                    class="p-column-filter w-full"
                    @keyup.enter="aplicarFiltros"
                />
            </template>
        </Column>
        
        <Column field="hora_inicio" header="Hora Inicio" sortable style="min-width: 12rem"></Column>
        
        <Column field="hora_fin" header="Hora Fin" sortable style="min-width: 12rem"></Column>
        
        <Column field="rate_per_unit" header="Costo Hab." sortable style="min-width: 8rem">
            <template #body="{ data }">
                <span class="font-semibold">S/ {{ formatCurrency(data.rate_per_unit) }}</span>
            </template>
        </Column>
        <Column field="cantidad_texto" header="Tiempo" sortable style="min-width: 6rem"></Column>
        <Column field="total_a_pagar" header="Total" sortable style="min-width: 8rem">
            <template #body="{ data }">
                <span class="font-bold">S/. {{ formatCurrency(data.total_a_pagar) }}</span>
            </template>
        </Column>
        
        <Column field="tuvo_consumo" header="Consumo" sortable style="min-width: 5rem">
            <template #body="{ data }">
                <Tag :value="data.tuvo_consumo" :severity="data.tuvo_consumo === 'SI' ? 'success' : 'secondary'" />
            </template>
        </Column>
        
        <Column field="total_consumos" header="Total Cons." sortable style="min-width: 8rem">
            <template #body="{ data }">
                <span class="font-semibold">S/. {{ formatCurrency(data.total_consumos) }}</span>
            </template>
        </Column>
        
        
        <Column field="metodo_pago" header="Método Pago" sortable style="min-width: 12rem">
            <template #body="{ data }">
                <Tag :severity="getMethodSeverity(data.metodo_pago)">
                    <i :class="getMethodIcon(data.metodo_pago) + ' mr-2'"></i>
                    {{ data.metodo_pago }}
                </Tag>
            </template>
        </Column>
        
        <Column field="monto_pagado" header="Monto Pagado" sortable style="min-width: 10rem">
            <template #body="{ data }">
                <span class="font-bold">S/. {{ formatCurrency(data.monto_pagado) }}</span>
            </template>
        </Column>
        
        <Column header="">
            <template #body="{ data }">
                <Button icon="pi pi-ellipsis-v" text rounded @click="toggleMenu($event, data)" />
            </template>
        </Column>
    </DataTable>

    <!-- Componente de Detalle -->
    <DetallesPago 
        v-model="dialogDetalleVisible" 
        :pagoId="pagoSeleccionadoId" 
    />

    <!-- Componente de Impresión -->
    <ImprimirPago 
        v-model="dialogImprimirVisible" 
        :pagoId="pagoSeleccionadoId" 
    />

    <!-- Menu contextual para acciones -->
    <Menu ref="menu" :model="menuItems" :popup="true" />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import axios from 'axios';

// Importar componentes nuevos
import DetallesPago from './DetallesPago.vue';
import ImprimirPago from './ImprimirPago.vue';

// Recibir fechas y sucursal como props
const props = defineProps({
    dates: null,
    sucursal: null
});

const filters = ref({
    codigo_pago: '',
    habitacion: '',
    cliente: '',
});
const selectedPagos = ref();
const pagos = ref([]);
const reporteData = ref(null);
const isLoading = ref(false);
const selectedMethod = ref(null);
const selectedMethodId = ref(null); // Guardar el ID del método seleccionado
const dialogDetalleVisible = ref(false);
const dialogImprimirVisible = ref(false);
const pagoSeleccionadoId = ref(null);
const menu = ref();
const dt = ref();
const paymentMethods = ref([]); // Almacenar métodos de pago

const pagination = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    from: 0,
    to: 0
});

const menuItems = ref([
    {
        label: 'Ver Detalle',
        icon: 'pi pi-eye',
        command: () => abrirDetalle()
    },
    {
        label: 'Imprimir',
        icon: 'pi pi-print',
        command: () => abrirImprimir()
    }
]);

// Computed para verificar si hay filtros activos
const hasActiveFilters = computed(() => {
    return filters.value.codigo_pago || 
           filters.value.habitacion || 
           filters.value.cliente || 
           selectedMethod.value;
});

// Computed para totales (basado en los datos completos del reporte)
const totalRegistros = computed(() => {
    return reporteData.value?.resumen?.total_pagos_registrados || 0;
});

const totalHabitaciones = computed(() => {
    return pagos.value.reduce((sum, pago) => sum + (pago.costo_habitacion || 0), 0);
});

const totalConsumos = computed(() => {
    return pagos.value.reduce((sum, pago) => sum + (pago.total_consumos || 0), 0);
});

// Computed para totales por método
const totalesPorMetodo = computed(() => {
    return reporteData.value?.totales_por_medio_pago || [];
});

// Función para cargar métodos de pago
const cargarMetodosPago = async () => {
    try {
        const response = await axios.get('/payments/methods');
        if (response.data.success) {
            paymentMethods.value = response.data.data;
            console.log('Métodos de pago cargados:', paymentMethods.value);
        }
    } catch (error) {
        console.error('Error al cargar métodos de pago:', error);
    }
};

// Función para obtener el ID del método de pago por nombre
const getPaymentMethodIdByName = (methodName: string): string | null => {
    if (!methodName || !paymentMethods.value.length) return null;
    
    const method = paymentMethods.value.find(m => 
        m.name.toLowerCase() === methodName.toLowerCase()
    );
    
    return method ? method.id : null;
};

// Función para cargar el reporte
const cargarReporte = async (page = 1, perPage = pagination.value.per_page) => {
    isLoading.value = true;
    
    try {
        const params = new URLSearchParams();
        
        // Agregar fechas si existen y son válidas
        if (props.dates && Array.isArray(props.dates) && props.dates.length === 2) {
            const [inicio, fin] = props.dates;
            if (inicio && fin) {
                params.append('fecha_inicio', formatDate(inicio));
                params.append('fecha_fin', formatDate(fin));
            }
        }
        
        // Agregar sucursal si existe
        if (props.sucursal) {
            params.append('sucursal_id', props.sucursal);
        }
        
        // Agregar método de pago si está seleccionado
        if (selectedMethodId.value) {
            params.append('payment_method_id', selectedMethodId.value);
            console.log('Filtrando por método de pago ID:', selectedMethodId.value);
        }
        
        // Agregar filtros de búsqueda
        if (filters.value.codigo_pago) {
            params.append('codigo_pago', filters.value.codigo_pago);
        }
        if (filters.value.habitacion) {
            params.append('habitacion', filters.value.habitacion);
        }
        if (filters.value.cliente) {
            params.append('cliente', filters.value.cliente);
        }
        
        // Agregar paginación
        params.append('page', page.toString());
        params.append('per_page', perPage.toString());
        
        const url = '/reporte-pagos?' + params.toString();
        console.log('URL de consulta:', url);
        
        const response = await axios.get(url);
        
        if (response.data.success) {
            reporteData.value = response.data.data;
            pagos.value = response.data.data.pagos || [];
            
            // Actualizar información de paginación
            if (response.data.data.pagination) {
                pagination.value = response.data.data.pagination;
            }
            
            console.log(`Cargados ${pagos.value.length} pagos de ${pagination.value.total} totales`);
        }
    } catch (error) {
        console.error('Error al cargar reporte:', error);
    } finally {
        isLoading.value = false;
    }
};

const aplicarFiltros = () => {
    pagination.value.current_page = 1;
    cargarReporte(1, pagination.value.per_page);
};

const clearAllFilters = () => {
    filters.value.codigo_pago = '';
    filters.value.habitacion = '';
    filters.value.cliente = '';
    selectedMethod.value = null;
    selectedMethodId.value = null;
    cargarReporte(1, pagination.value.per_page);
};

const clearMethodFilter = () => {
    selectedMethod.value = null;
    selectedMethodId.value = null;
    cargarReporte(1, pagination.value.per_page);
};

const onPage = (event: any) => {
    cargarReporte(event.page + 1, event.rows);
};

const onSort = (event: any) => {
    // Implementar ordenamiento si es necesario
    console.log('Sort:', event);
};

const formatDate = (date: Date | null): string => {
    if (!date) return '';
    
    try {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    } catch (error) {
        console.error('Error al formatear fecha:', error);
        return '';
    }
};

const formatCurrency = (value: number): string => {
    return value.toFixed(2);
};

const toggleMethodFilter = (metodo: string) => {
    if (selectedMethod.value === metodo) {
        // Deseleccionar
        selectedMethod.value = null;
        selectedMethodId.value = null;
    } else {
        // Seleccionar
        selectedMethod.value = metodo;
        // Buscar el ID del método de pago
        selectedMethodId.value = getPaymentMethodIdByName(metodo);
        console.log(`Método seleccionado: ${metodo}, ID: ${selectedMethodId.value}`);
    }
    cargarReporte(1, pagination.value.per_page);
};

const getMethodIcon = (metodo: string): string => {
    const metodosLower = metodo?.toLowerCase() || '';
    
    if (metodosLower.includes('efectivo') || metodosLower.includes('cash')) return 'pi pi-money-bill';
    if (metodosLower.includes('tarjeta') || metodosLower.includes('card') || metodosLower.includes('débito') || metodosLower.includes('crédito')) return 'pi pi-credit-card';
    if (metodosLower.includes('yape')) return 'pi pi-mobile';
    if (metodosLower.includes('plin')) return 'pi pi-mobile';
    if (metodosLower.includes('transfer')) return 'pi pi-send';
    
    return 'pi pi-wallet';
};

const getMethodSeverity = (metodo: string): string => {
    const metodosLower = metodo?.toLowerCase() || '';
    
    if (metodosLower.includes('efectivo') || metodosLower.includes('cash')) return 'success';
    if (metodosLower.includes('tarjeta') || metodosLower.includes('card') || metodosLower.includes('débito') || metodosLower.includes('crédito')) return 'warn';
    if (metodosLower.includes('yape')) return 'secondary';
    if (metodosLower.includes('plin')) return 'info';
    if (metodosLower.includes('transfer')) return 'contrast';
    
    return 'secondary';
};

const abrirDetalle = () => {
    dialogDetalleVisible.value = true;
};

const abrirImprimir = () => {
    dialogImprimirVisible.value = true;
};

const toggleMenu = (event: any, pago: any) => {
    pagoSeleccionadoId.value = pago.id;
    menu.value.toggle(event);
};

watch(() => [props.dates, props.sucursal], ([newDates]) => {
    if (newDates && Array.isArray(newDates) && newDates.length === 2 && newDates[0] && newDates[1]) {
        console.log('Cargando reporte con fechas:', newDates);
        cargarReporte(1, pagination.value.per_page);
    }
}, { deep: true });

onMounted(async () => {
    console.log('Componente montado - Cargando métodos de pago y datos iniciales');
    await cargarMetodosPago(); // Cargar métodos de pago primero
    cargarReporte();
});

defineExpose({
    cargarReporte
});
</script>