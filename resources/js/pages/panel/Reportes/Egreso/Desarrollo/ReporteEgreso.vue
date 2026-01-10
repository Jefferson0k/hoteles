<template>
    <div>
        <!-- Header compacto -->
        <div class="flex justify-between items-center mb-3">
            <div>
                <h1 class="text-2xl font-bold">Egresos</h1>
                <p class="text-sm">Análisis de gastos</p>
            </div>
            <div class="flex gap-2">
                <Calendar 
                    v-model="filtroMes" 
                    view="month" 
                    dateFormat="mm/yy" 
                    placeholder="Mes"
                    fluid
                    @date-select="cargarTodosDatos"
                />
            </div>
        </div>

        <!-- KPIs con Messages -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
            <Message severity="error" :closable="false">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <p class="text-xs font-medium mb-1">Total Egresos</p>
                        <p class="text-xl font-bold">S/ {{ formatoMoneda(egresos.total) }}</p>
                        <p class="text-xs opacity-80 mt-1">{{ resumenPeriodo.mesAnio }}</p>
                    </div>
                    <i class="pi pi-arrow-down text-2xl"></i>
                </div>
            </Message>

            <Message severity="info" :closable="false">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <p class="text-xs font-medium mb-1">Movimientos</p>
                        <p class="text-xl font-bold">{{ egresos.total_movimientos }}</p>
                        <p class="text-xs opacity-80 mt-1">Transacciones</p>
                    </div>
                    <i class="pi pi-sitemap text-2xl"></i>
                </div>
            </Message>

            <Message severity="warn" :closable="false">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <p class="text-xs font-medium mb-1">Compras</p>
                        <p class="text-xl font-bold">S/ {{ formatoMoneda(egresos.egresos_movimientos) }}</p>
                        <p class="text-xs opacity-80 mt-1">{{ egresos.total_movimientos_compras }} registros</p>
                    </div>
                    <i class="pi pi-shopping-cart text-2xl"></i>
                </div>
            </Message>

            <Message severity="secondary" :closable="false">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <p class="text-xs font-medium mb-1">Nómina</p>
                        <p class="text-xl font-bold">S/ {{ formatoMoneda(egresos.egresos_personal) }}</p>
                        <p class="text-xs opacity-80 mt-1">{{ egresos.total_pagos_personal }} pagos</p>
                    </div>
                    <i class="pi pi-users text-2xl"></i>
                </div>
            </Message>
        </div>

        <!-- Message de carga o error -->
        <Message v-if="error" severity="error" :closable="true" @close="error = ''" class="mb-3">
            {{ error }}
        </Message>

        <Message v-if="cargando" severity="info" :closable="false" class="mb-3">
            <i class="pi pi-spin pi-spinner mr-2"></i> Cargando datos...
        </Message>

        <br>
        <!-- Gráficas compactas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between text-base mb-3">
                    <span class="font-semibold">Tendencia de Egresos</span>
                    <Tag :value="`${datosGrafica.length} días`" severity="secondary" class="text-xs" />
                </div>
                <Chart type="line" :data="graficaLinea" :options="opcionesLinea" class="h-56" />
            </div>

            <div class="shadow-sm">
                <div class="text-base mb-3 font-semibold">Distribución</div>
                <Chart type="doughnut" :data="graficaDistribucion" :options="opcionesDoughnut" class="h-56" />
            </div>
        </div>

        <!-- Gráfica comparativa -->
        <div class="mb-3">
            <div class="flex items-center justify-between text-base mb-3">
                <span class="font-semibold">Comparativa Diaria</span>
                <div class="flex gap-2">
                    <Tag value="Compras" severity="warning" class="text-xs" />
                    <Tag value="Personal" severity="info" class="text-xs" />
                </div>
            </div>
            <Chart type="bar" :data="graficaComparativa" :options="opcionesComparativa" class="h-64" />
        </div>

        <br>
        <!-- Tabla de detalle -->
        <div class="">
            <div class="flex items-center justify-between text-base mb-3">
                <span class="font-semibold">Detalle de Transacciones</span>
                <Tag :value="`${egresosDetalle.total_registros} registros`" severity="info" class="text-xs" />
            </div>
                <Message v-if="cargandoDetalle" severity="info" :closable="false" class="mb-3">
                    <i class="pi pi-spin pi-spinner mr-2"></i> Cargando detalles...
                </Message>

                <DataTable 
                    v-else
                    :value="egresosDetalle.egresos" 
                    stripedRows
                    paginator 
                    :rows="10"
                    :rowsPerPageOptions="[10, 20, 50]"
                    size="small"
                    :globalFilterFields="['codigo', 'proveedor', 'concepto']"
                >
                    <template #empty>
                        <div class="text-center py-6 text-gray-500">
                            <i class="pi pi-inbox text-3xl mb-2 block"></i>
                            <p class="text-sm">No hay registros</p>
                        </div>
                    </template>

                    <Column field="fecha" header="Fecha" sortable style="min-width: 100px">
                        <template #body="{ data }">
                            <span class="text-sm font-medium">{{ data.fecha }}</span>
                        </template>
                    </Column>

                    <Column field="codigo" header="Código" sortable style="min-width: 120px">
                        <template #body="{ data }">
                            <Tag :value="data.codigo" :severity="data.tipo === 'compra_gasto' ? 'warning' : 'info'" class="text-xs" />
                        </template>
                    </Column>

                    <Column field="proveedor" header="Proveedor/Empleado" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i :class="data.tipo === 'compra_gasto' ? 'pi pi-building' : 'pi pi-user'" class="text-gray-400 text-xs"></i>
                                <span class="text-sm">{{ data.proveedor }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="concepto" header="Concepto" sortable>
                        <template #body="{ data }">
                            <span class="text-sm text-gray-600">{{ data.concepto }}</span>
                        </template>
                    </Column>

                    <Column field="comprobante" header="Comprobante" style="min-width: 100px">
                        <template #body="{ data }">
                            <Tag :value="getTipoComprobante(data.comprobante)" severity="secondary" class="text-xs" />
                        </template>
                    </Column>
                    <Column field="igv" header="IGV" sortable style="min-width: 120px">
                        <template #body="{ data }">
                            <span class="font-bold text-red-600 text-sm">S/ {{ formatoMoneda(data.igv) }}</span>
                        </template>
                    </Column>
                    <Column field="subtotal" header="Sub Total" sortable style="min-width: 120px">
                        <template #body="{ data }">
                            <span class="font-bold text-red-600 text-sm">S/ {{ formatoMoneda(data.subtotal) }}</span>
                        </template>
                    </Column>
                    <Column field="monto" header="Total" sortable style="min-width: 120px">
                        <template #body="{ data }">
                            <span class="font-bold text-red-600 text-sm">S/ {{ formatoMoneda(data.monto) }}</span>
                        </template>
                    </Column>

                    <Column header="Tipo" style="min-width: 100px">
                        <template #body="{ data }">
                            <Tag :value="data.tipo === 'compra_gasto' ? 'Compra' : 'Personal'" :severity="data.tipo === 'compra_gasto' ? 'warning' : 'info'" class="text-xs" />
                        </template>
                    </Column>

                    <Column style="min-width: 80px">
                        <template #body="{ data }">
                            <Button icon="pi pi-eye" severity="secondary" text size="small" @click="verDetalle(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Dialog de detalle -->
        <Dialog v-model:visible="mostrarDialog" modal header="Detalle de Transacción" :style="{ width: '500px' }">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <span class="font-semibold">Detalle de Transacción</span>
                </div>
            </template>
            <div v-if="egresoSeleccionado" class="space-y-3">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs">Código</p>
                        <p class="font-semibold">{{ egresoSeleccionado.codigo }}</p>
                    </div>
                    <div>
                        <p class="text-xs">Fecha</p>
                        <p class="font-semibold">{{ egresoSeleccionado.fecha }}</p>
                    </div>
                    <div>
                        <p class="text-xs">Proveedor/Empleado</p>
                        <p class="font-semibold">{{ egresoSeleccionado.proveedor }}</p>
                    </div>
                    <div>
                        <p class="text-xs">Concepto</p>
                        <p class="font-semibold">{{ egresoSeleccionado.concepto }}</p>
                    </div>
                    <div>
                        <p class="text-xs">Tipo de Pago</p>
                        <p class="font-semibold">{{ egresoSeleccionado.tipo_pago }}</p>
                    </div>
                    <div>
                        <p class="text-xs">Monto Total</p>
                        <p class="font-bold text-red-600 text-lg">S/ {{ formatoMoneda(egresoSeleccionado.monto) }}</p>
                    </div>
                </div>
            </div>
        </Dialog>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

// PrimeVue Components
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Message from 'primevue/message';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';

// Types
interface Egresos {
    total: number;
    total_movimientos: number;
    egresos_movimientos: number;
    egresos_personal: number;
    total_movimientos_compras: number;
    total_pagos_personal: number;
}

interface EgresoDetalle {
    id: string;
    tipo: string;
    codigo: string;
    fecha: string;
    proveedor: string;
    concepto: string;
    comprobante: string;
    tipo_pago: string;
    monto: number;
    detalles: any[];
}

interface EgresosDetalleResponse {
    egresos: EgresoDetalle[];
    total_registros: number;
}

interface DatosGrafica {
    dia: string;
    egresos_movimientos: number;
    egresos_personal: number;
    egresos_totales: number;
}

interface DistribucionTipo {
    tipo: string;
    total: number;
    color: string;
    icono: string;
}

// Estado
const cargando = ref(false);
const cargandoDetalle = ref(false);
const error = ref('');
const filtroMes = ref(new Date());
const mostrarDialog = ref(false);
const egresoSeleccionado = ref<EgresoDetalle | null>(null);

const egresos = ref<Egresos>({
    total: 0,
    total_movimientos: 0,
    egresos_movimientos: 0,
    egresos_personal: 0,
    total_movimientos_compras: 0,
    total_pagos_personal: 0
});

const egresosDetalle = ref<EgresosDetalleResponse>({
    egresos: [],
    total_registros: 0
});

const datosGrafica = ref<DatosGrafica[]>([]);
const distribucionTipos = ref<DistribucionTipo[]>([]);

// Computed
const resumenPeriodo = computed(() => {
    const fecha = filtroMes.value;
    const year = fecha.getFullYear();
    const month = fecha.getMonth();
    const monthName = fecha.toLocaleDateString('es-ES', { month: 'long' });
    
    return {
        mesAnio: `${monthName.charAt(0).toUpperCase() + monthName.slice(1)} ${year}`
    };
});

// Formateadores
const formatoMoneda = (valor: number) => {
    return new Intl.NumberFormat('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(valor);
};
const obtenerParametrosMes = () => {
    const fecha = filtroMes.value;
    return {
        month: fecha.getMonth() + 1,
        year: fecha.getFullYear()
    };
};

// Cargar datos
const cargarTodosDatos = async () => {
    cargando.value = true;
    error.value = '';
    
    try {
        await Promise.all([
            cargarEgresos(),
            cargarEgresosDetalle(),
            cargarEgresosGrafica(),
            cargarEgresosDistribucion()
        ]);
    } catch (err: any) {
        error.value = err.message || 'Error al cargar los datos';
        console.error('Error:', err);
    } finally {
        cargando.value = false;
    }
};

const cargarEgresos = async () => {
    const params = obtenerParametrosMes();
    const response = await axios.get('/reports/egresos', { params });
    egresos.value = response.data.data;
};

const cargarEgresosDetalle = async () => {
    cargandoDetalle.value = true;
    try {
        const params = obtenerParametrosMes();
        const response = await axios.get('/reports/egresos-detalle', { params });
        egresosDetalle.value = response.data.data;
    } finally {
        cargandoDetalle.value = false;
    }
};

const cargarEgresosGrafica = async () => {
    const params = obtenerParametrosMes();
    const response = await axios.get('/reports/egresos-grafica', { params });
    datosGrafica.value = response.data.data;
};

const cargarEgresosDistribucion = async () => {
    const params = obtenerParametrosMes();
    const response = await axios.get('/reports/egresos-distribucion', { params });
    distribucionTipos.value = response.data.data;
};

// Gráfica de línea - CORREGIDO
const graficaLinea = computed(() => {
    if (!datosGrafica.value.length) {
        return { labels: [], datasets: [] };
    }

    // Extraer día directamente del string sin usar new Date()
    const labels = datosGrafica.value.map(item => {
        return item.dia.split('-')[2]; // "2026-01-29" -> "29"
    });

    return {
        labels,
        datasets: [{
            label: 'Total Egresos',
            data: datosGrafica.value.map(item => item.egresos_totales),
            borderColor: '#EF4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5
        }]
    };
});

const opcionesLinea = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (context: any) => `S/ ${formatoMoneda(context.parsed.y)}`
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: (value: any) => `S/ ${value}`
            }
        }
    }
};

// Gráfica de distribución
const graficaDistribucion = computed(() => {
    if (!distribucionTipos.value.length) {
        return { labels: [], datasets: [] };
    }

    return {
        labels: distribucionTipos.value.map(item => item.tipo),
        datasets: [{
            data: distribucionTipos.value.map(item => item.total),
            backgroundColor: distribucionTipos.value.map(item => item.color),
            borderWidth: 0
        }]
    };
});

const opcionesDoughnut = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 10,
                usePointStyle: true,
                font: { size: 11 }
            }
        },
        tooltip: {
            callbacks: {
                label: (context: any) => {
                    const label = context.label || '';
                    const value = context.parsed || 0;
                    return `${label}: S/ ${formatoMoneda(value)}`;
                }
            }
        }
    }
};

// Gráfica comparativa - CORREGIDO
const graficaComparativa = computed(() => {
    if (!datosGrafica.value.length) {
        return { labels: [], datasets: [] };
    }

    // Extraer día y mes directamente del string sin usar new Date()
    const labels = datosGrafica.value.map(item => {
        const [year, month, day] = item.dia.split('-');
        return `${day}/${month}`; // "2026-01-29" -> "29/01"
    });

    return {
        labels,
        datasets: [
            {
                label: 'Compras/Gastos',
                data: datosGrafica.value.map(item => item.egresos_movimientos),
                backgroundColor: '#F97316',
                borderRadius: 4
            },
            {
                label: 'Personal',
                data: datosGrafica.value.map(item => item.egresos_personal),
                backgroundColor: '#8B5CF6',
                borderRadius: 4
            }
        ]
    };
});

const opcionesComparativa = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (context: any) => `${context.dataset.label}: S/ ${formatoMoneda(context.parsed.y)}`
            }
        }
    },
    scales: {
        x: { stacked: false },
        y: {
            beginAtZero: true,
            ticks: {
                callback: (value: any) => `S/ ${value}`
            }
        }
    }
};

// Helpers
const getTipoComprobante = (tipo: string) => {
    const tipos: { [key: string]: string } = {
        'factura': 'Factura',
        'boleta': 'Boleta',
        'guia': 'Guía'
    };
    return tipos[tipo] || tipo;
};

const verDetalle = (egreso: EgresoDetalle) => {
    egresoSeleccionado.value = egreso;
    mostrarDialog.value = true;
};

// Montar
onMounted(() => {
    cargarTodosDatos();
});
</script>