<template>
    <div class="space-y-4">
        <!-- TÍTULO Y FILTROS -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Ingreso de Productos</h2>
                <p class="text-sm mt-1">Reporte mensual de ingresos por venta</p>
            </div>
            <div class="flex gap-2">
                <Calendar 
                    v-model="filtroMes" 
                    view="month" 
                    dateFormat="mm/yy" 
                    placeholder="Seleccionar mes"
                    @date-select="cargarDatos"
                    fluid
                />
            </div>
        </div>

        <!-- TARJETAS RESUMEN - COMPACTAS CON MESSAGE -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <Message severity="success" class="!m-0">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <div class="text-xl font-bold">S/ {{ formatoMoneda(resumen.total) }}</div>
                        <div class="text-sm opacity-80">Total Ingresos</div>
                    </div>
                    <i class="pi pi-money-bill text-2xl"></i>
                </div>
            </Message>

            <Message severity="info" class="!m-0">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <div class="text-xl font-bold">{{ resumen.total_unidades }}</div>
                        <div class="text-sm opacity-80">Unidades Vendidas</div>
                    </div>
                    <i class="pi pi-shopping-cart text-2xl"></i>
                </div>
            </Message>

            <Message severity="warn" class="!m-0">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <div class="text-xl font-bold">{{ resumen.reservas_con_consumo }}</div>
                        <div class="text-sm opacity-80">Reservas con Consumo</div>
                    </div>
                    <i class="pi pi-tag text-2xl"></i>
                </div>
            </Message>

            <Message severity="secondary" class="!m-0">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <div class="text-xl font-bold">S/ {{ formatoMoneda(resumen.reservas_con_consumo > 0 ? resumen.total / resumen.reservas_con_consumo : 0) }}</div>
                        <div class="text-sm opacity-80">Promedio por Venta</div>
                    </div>
                    <i class="pi pi-chart-line text-2xl"></i>
                </div>
            </Message>
        </div>

        <!-- GRÁFICAS - DOS EN DOS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Ingresos por Día -->
            <div class="">
                <div class="flex items-center gap-2 mb-3">
                    <i class="pi pi-chart-bar text-blue-500"></i>
                    <h3 class="text-base font-semibold">Ingresos por Día</h3>
                </div>
                <Chart 
                    type="bar" 
                    :data="graficaIngresosDia" 
                    :options="opcionesGrafica"
                    class="h-64"
                />
            </div>

            <!-- Top 5 Productos -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="pi pi-star text-yellow-500"></i>
                    <h3 class="text-base font-semibold">Top 5 Productos</h3>
                </div>
                <Chart 
                    type="doughnut" 
                    :data="graficaTopProductos" 
                    :options="opcionesDoughnut"
                    class="h-64"
                />
            </div>

            <!-- Tendencia de Ventas -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="pi pi-chart-line text-green-500"></i>
                    <h3 class="text-base font-semibold">Tendencia de Ventas</h3>
                </div>
                <Chart 
                    type="line" 
                    :data="graficaTendencia" 
                    :options="opcionesLinea"
                    class="h-64"
                />
            </div>

            <!-- Estadísticas -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="pi pi-info-circle"></i>
                    <h3 class="text-base font-semibold">Estadísticas</h3>
                </div>
                <div class="space-y-2">
                    <Message severity="info" class="!m-0">
                        <div class="flex items-center justify-between w-full gap-4">
                            <span class="text-sm">Producto Más Vendido</span>
                            <span class="font-semibold text-sm text-right">{{ estadisticas.producto_mas_vendido }}</span>
                        </div>
                    </Message>
                    <br>
                    <Message severity="success" class="!m-0">
                        <div class="flex items-center justify-between w-full gap-4">
                            <span class="text-sm">Ingreso Promedio</span>
                            <span class="font-semibold text-sm text-right">S/ {{ formatoMoneda(estadisticas.promedio_por_producto) }}</span>
                        </div>
                    </Message>
                    <br>
                    <Message severity="warn" class="!m-0">
                        <div class="flex items-center justify-between w-full gap-4">
                            <span class="text-sm">Día con Más Ventas</span>
                            <span class="font-semibold text-sm text-right">{{ estadisticas.dia_max_ventas }}</span>
                        </div>
                    </Message>
                    <br>
                    <Message severity="secondary" class="!m-0">
                        <div class="flex items-center justify-between w-full gap-4">
                            <span class="text-sm">Tasa de Conversión</span>
                            <span class="font-semibold text-sm text-right">{{ estadisticas.tasa_conversion }}%</span>
                        </div>
                    </Message>
                </div>
            </div>
        </div>

        <!-- TABLA DETALLADA DE CONSUMOS -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <i class="pi pi-table"></i>
                <h3 class="text-base font-semibold">Detalle de Consumos</h3>
            </div>
            <DataTable 
                :value="consumos" 
                :loading="cargando"
                stripedRows
                paginator 
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20, 50]"
                class="p-datatable-sm"
            >
                <Column field="product.name" header="Producto" sortable>
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-tag text-gray-400 text-xs"></i>
                            <span class="text-sm">{{ slotProps.data.product?.name || 'N/A' }}</span>
                        </div>
                    </template>
                </Column>
                <Column field="booking.booking_code" header="Reserva" sortable>
                    <template #body="slotProps">
                        <Tag 
                            :value="slotProps.data.booking?.booking_code || 'N/A'" 
                            severity="info"
                            class="text-xs"
                        />
                    </template>
                </Column>
                <Column field="quantity" header="Cantidad" sortable>
                    <template #body="slotProps">
                        <span class="font-semibold text-sm">{{ slotProps.data.quantity }}</span>
                    </template>
                </Column>
                <Column field="unit_price" header="Precio Unit." sortable>
                    <template #body="slotProps">
                        <span class="text-sm">S/ {{ formatoMoneda(slotProps.data.unit_price) }}</span>
                    </template>
                </Column>
                <Column field="total_price" header="Total" sortable>
                    <template #body="slotProps">
                        <span class="font-bold text-green-600 text-sm">
                            S/ {{ formatoMoneda(slotProps.data.total_price) }}
                        </span>
                    </template>
                </Column>
                <Column field="consumed_at" header="Fecha" sortable>
                    <template #body="slotProps">
                        <div class="flex items-center gap-1">
                            <i class="pi pi-calendar text-gray-400 text-xs"></i>
                            <span class="text-sm">{{ formatoFecha(slotProps.data.consumed_at) }}</span>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
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

// Types
interface Resumen {
    total: number;
    total_unidades: number;
    reservas_con_consumo: number;
    promedio_venta: number;
}

interface Consumo {
    id: string;
    quantity: number;
    unit_price: number;
    total_price: number;
    consumed_at: string;
    product?: {
        id: string;
        name: string;
    };
    booking?: {
        id: string;
        booking_code: string;
    };
}

interface DatosGrafica {
    dia: string;
    ingresos: string;
}

interface Estadisticas {
    producto_mas_vendido: string;
    promedio_por_producto: number;
    dia_max_ventas: string;
    tasa_conversion: number;
}

// Estado reactivo
const cargando = ref(false);
const filtroMes = ref(new Date());
const resumen = ref<Resumen>({
    total: 0,
    total_unidades: 0,
    reservas_con_consumo: 0,
    promedio_venta: 0
});
const consumos = ref<Consumo[]>([]);
const datosGrafica = ref<DatosGrafica[]>([]);
const topProductos = ref<any[]>([]);

// Estadísticas calculadas
const estadisticas = computed<Estadisticas>(() => {
    if (!consumos.value.length) {
        return {
            producto_mas_vendido: 'N/A',
            promedio_por_producto: 0,
            dia_max_ventas: 'N/A',
            tasa_conversion: 0
        };
    }

    // Producto más vendido
    const productosCount = consumos.value.reduce((acc: any, consumo) => {
        const nombre = consumo.product?.name || 'Desconocido';
        if (!acc[nombre]) {
            acc[nombre] = { cantidad: 0, ingresos: 0 };
        }
        acc[nombre].cantidad += consumo.quantity;
        acc[nombre].ingresos += consumo.total_price;
        return acc;
    }, {});

    const productoMasVendido = Object.keys(productosCount).reduce((a, b) => 
        productosCount[a].cantidad > productosCount[b].cantidad ? a : b
    );

    // Día con más ventas
    const ventasPorDia = consumos.value.reduce((acc: any, consumo) => {
        const dia = consumo.consumed_at.split('T')[0];
        if (!acc[dia]) acc[dia] = 0;
        acc[dia] += consumo.total_price;
        return acc;
    }, {});

    const diaMaxVentas = Object.keys(ventasPorDia).reduce((a, b) => 
        ventasPorDia[a] > ventasPorDia[b] ? a : b
    );

    return {
        producto_mas_vendido: productoMasVendido,
        promedio_por_producto: resumen.value.total / Object.keys(productosCount).length,
        dia_max_ventas: new Date(diaMaxVentas).toLocaleDateString('es-PE'),
        tasa_conversion: (resumen.value.reservas_con_consumo / (resumen.value.reservas_con_consumo + 10)) * 100
    };
});

// Formateadores
const formatoMoneda = (valor: number) => {
    return new Intl.NumberFormat('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(valor);
};

const formatoFecha = (fecha: string) => {
    return new Date(fecha).toLocaleDateString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Obtener mes y año para la API
const obtenerParametrosMes = () => {
    const fecha = filtroMes.value;
    return {
        month: fecha.getMonth() + 1,
        year: fecha.getFullYear()
    };
};

// Cargar datos del reporte
const cargarDatos = async () => {
    cargando.value = true;
    try {
        const params = obtenerParametrosMes();
        
        // Cargar datos principales
        const response = await axios.get('/reports/ingreso-productos', { params });
        resumen.value = response.data;
        
        // Cargar consumos detallados
        await cargarConsumosDetallados();
        
        // Cargar datos para gráficas
        await cargarDatosGrafica();
        await cargarTopProductos();
        
    } catch (error) {
        console.error('Error cargando datos:', error);
    } finally {
        cargando.value = false;
    }
};

// Cargar consumos detallados para la tabla
const cargarConsumosDetallados = async () => {
    try {
        const params = obtenerParametrosMes();
        const response = await axios.get('/reports/booking-consumptions', { 
            params: {
                ...params,
                with_product: true,
                with_booking: true,
                per_page: 100
            }
        });
        consumos.value = response.data.data || [];
    } catch (error) {
        console.error('Error cargando consumos:', error);
        consumos.value = [];
    }
};

// Cargar datos para gráficas
const cargarDatosGrafica = async () => {
    try {
        const params = obtenerParametrosMes();
        const response = await axios.get('/reports/ingreso-productos-grafica', { params });
        datosGrafica.value = response.data;
    } catch (error) {
        console.error('Error cargando gráfica:', error);
        datosGrafica.value = [];
    }
};

// Cargar top productos
const cargarTopProductos = async () => {
    try {
        const params = obtenerParametrosMes();
        const response = await axios.get('/reports/productos-mas-vendidos', { params });
        topProductos.value = response.data.slice(0, 5);
    } catch (error) {
        console.error('Error cargando top productos:', error);
        topProductos.value = [];
    }
};

// Configuración de gráficas
const graficaIngresosDia = computed(() => {
    if (!datosGrafica.value.length) {
        return {
            labels: ['No hay datos'],
            datasets: [
                {
                    label: 'Ingresos por Día',
                    data: [0],
                    backgroundColor: '#3B82F6',
                    borderColor: '#1D4ED8',
                    borderWidth: 2
                }
            ]
        };
    }

    const labels = datosGrafica.value.map(item => item.dia);
    const data = datosGrafica.value.map(item => parseFloat(item.ingresos));

    return {
        labels: labels,
        datasets: [
            {
                label: 'Ingresos por Día',
                data: data,
                backgroundColor: '#3B82F6',
                borderColor: '#1D4ED8',
                borderWidth: 2,
                borderRadius: 4
            }
        ]
    };
});

const graficaTopProductos = computed(() => {
    if (!topProductos.value.length) {
        return {
            labels: ['No hay datos'],
            datasets: [
                {
                    data: [1],
                    backgroundColor: ['#e0e0e0']
                }
            ]
        };
    }

    const labels = topProductos.value.map(item => item.name);
    const data = topProductos.value.map(item => item.ingreso_generado);

    return {
        labels: labels,
        datasets: [
            {
                data: data,
                backgroundColor: [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                    '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }
        ]
    };
});

const graficaTendencia = computed(() => {
    if (!datosGrafica.value.length) {
        return {
            labels: ['No hay datos'],
            datasets: [
                {
                    label: 'Tendencia de Ventas',
                    data: [0],
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }
            ]
        };
    }

    const labels = datosGrafica.value.map(item => item.dia);
    const data = datosGrafica.value.map(item => parseFloat(item.ingresos));

    return {
        labels: labels,
        datasets: [
            {
                label: 'Tendencia de Ventas',
                data: data,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }
        ]
    };
});

// Opciones de gráficas
const opcionesGrafica = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: '#374151',
                font: {
                    size: 12
                }
            }
        }
    },
    scales: {
        x: {
            ticks: {
                color: '#6B7280'
            },
            grid: {
                color: '#F3F4F6'
            }
        },
        y: {
            ticks: {
                color: '#6B7280',
                callback: function(value: any) {
                    return 'S/ ' + value;
                }
            },
            grid: {
                color: '#F3F4F6'
            }
        }
    }
};

const opcionesDoughnut = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: '#374151',
                font: {
                    size: 11
                }
            }
        }
    }
};

const opcionesLinea = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: '#374151'
            }
        }
    },
    scales: {
        x: {
            ticks: {
                color: '#6B7280'
            },
            grid: {
                color: '#F3F4F6'
            }
        },
        y: {
            ticks: {
                color: '#6B7280',
                callback: function(value: any) {
                    return 'S/ ' + value;
                }
            },
            grid: {
                color: '#F3F4F6'
            }
        }
    }
};

// Ver detalle de consumo
const verDetalle = (consumo: Consumo) => {
    console.log('Ver detalle:', consumo);
};

// Cargar datos al montar el componente
onMounted(() => {
    cargarDatos();
});
</script>