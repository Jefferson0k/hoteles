<template>
    <div class="">
        <!-- TÍTULO Y FILTROS -->
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-xl font-bold m-0">Ingresos de Habitaciones</h2>
                <p class="text-sm mt-1">Reporte mensual de ingresos generados</p>
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

        <!-- TARJETAS RESUMEN - 4 EN FILA -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
            <Message severity="info" class="m-0">
                <template #icon>
                    <i class="pi pi-wallet text-xl"></i>
                </template>
                <div class="ml-2">
                    <div class="text-gray-500 font-medium text-xs">Total Ingresos</div>
                    <div class="text-lg font-bold">S/ {{ formatoMoneda(resumen.total) }}</div>
                </div>
            </Message>

            <Message severity="success" class="m-0">
                <template #icon>
                    <i class="pi pi-home text-xl"></i>
                </template>
                <div class="ml-2">
                    <div class="text-gray-500 font-medium text-xs">Total Reservas</div>
                    <div class="text-lg font-bold">{{ resumen.total_reservas }}</div>
                </div>
            </Message>

            <Message severity="warn" class="m-0">
                <template #icon>
                    <i class="pi pi-clock text-xl"></i>
                </template>
                <div class="ml-2">
                    <div class="text-gray-500 font-medium text-xs">Horas Vendidas</div>
                    <div class="text-lg font-bold">{{ resumen.total_horas }}</div>
                </div>
            </Message>

            <Message severity="secondary" class="m-0">
                <template #icon>
                    <i class="pi pi-chart-line text-xl"></i>
                </template>
                <div class="ml-2">
                    <div class="text-gray-500 font-medium text-xs">Promedio/Reserva</div>
                    <div class="text-lg font-bold">S/ {{ formatoMoneda(resumen.promedio_reserva) }}</div>
                </div>
            </Message>
        </div>

        <!-- GRÁFICAS - 2 EN FILA -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 mb-4">
            <div class="lg:col-span-7">
                <div class="card p-3">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="pi pi-chart-bar text-primary"></i>
                        <span class="font-semibold text-sm">Ingresos por Día</span>
                    </div>
                    <Chart 
                        type="bar" 
                        :data="graficaIngresosDia" 
                        :options="opcionesGraficaBarras"
                        class="h-64"
                    />
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="card p-3">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="pi pi-chart-pie text-primary"></i>
                        <span class="font-semibold text-sm">Distribución por Método</span>
                    </div>
                    <Chart 
                        type="pie" 
                        :data="graficaDistribucion" 
                        :options="opcionesPie"
                        class="h-64"
                    />
                </div>
            </div>
        </div>

        <!-- TABLA DETALLADA -->
        <div class="">
            <div class="flex items-center gap-2 mb-3">
                <i class="pi pi-table text-primary"></i>
                <span class="font-semibold text-sm">Detalle de Ventas</span>
            </div>
            <DataTable 
                :value="bookings" 
                :loading="cargando"
                stripedRows
                paginator 
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20, 50]"
                responsiveLayout="scroll"
                size="small"
            >
                <Column field="booking_code" header="Código Reserva" sortable></Column>
                <Column field="fecha" header="Fecha" sortable>
                    <template #body="slotProps">
                        {{ formatoFecha(slotProps.data.fecha) }}
                    </template>
                </Column>
                <Column field="habitacion" header="Habitación" sortable></Column>
                <Column field="cliente" header="Cliente" sortable></Column>
                <Column field="precio_unitario" header="Precio Unit." sortable>
                    <template #body="slotProps">
                        <span class="font-semibold">S/ {{ formatoMoneda(slotProps.data.precio_unitario) }}</span>
                    </template>
                </Column>
                <Column field="quantity_label" header="Cantidad" sortable></Column>
                <Column field="monto_total" header="Total" sortable>
                    <template #body="slotProps">
                        <span class="font-semibold text-green-600">S/ {{ formatoMoneda(slotProps.data.monto_total) }}</span>
                    </template>
                </Column>
                <Column field="metodo_pago" header="Método" sortable>
                    <template #body="slotProps">
                        <Tag 
                            :value="slotProps.data.metodo_pago" 
                            :severity="getSeverityMetodo(slotProps.data.metodo_pago)" 
                        />
                    </template>
                </Column>
                <Column field="estado_label" header="Estado" sortable>
                    <template #body="slotProps">
                        <Tag 
                            :value="slotProps.data.estado_label" 
                            :severity="getSeverityEstado(slotProps.data.estado_label)" 
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Calendar from 'primevue/calendar';
import Message from 'primevue/message';

interface Resumen {
    total: number;
    total_reservas: number;
    promedio_reserva: number;
    total_horas: number;
}

interface Booking {
    id: string;
    booking_code: string;
    habitacion: string;
    cliente: string;
    fecha: string;
    precio_unitario: number;
    quantity: number;
    quantity_label: string;
    monto_total: number;
    metodo_pago: string;
    estado: string;
    estado_label: string;
}

interface DatosGrafica {
    dia: string;
    ingresos: string;
}

const cargando = ref(false);
const filtroMes = ref(new Date());
const resumen = ref<Resumen>({
    total: 0,
    total_reservas: 0,
    promedio_reserva: 0,
    total_horas: 0
});
const bookings = ref<Booking[]>([]);
const datosGrafica = ref<DatosGrafica[]>([]);

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

// ✅ Función para parsear fecha sin zona horaria
const parsearFechaSinUTC = (fechaString: string) => {
    // Formato: "2026-01-11" -> "11 ene"
    const [year, month, day] = fechaString.split('-');
    const fecha = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
    return `${parseInt(day)} ${fecha.toLocaleDateString('es-PE', { month: 'short' })}`;
};

const obtenerParametrosMes = () => {
    const fecha = filtroMes.value;
    const year = fecha.getFullYear();
    const month = fecha.getMonth() + 1;
    
    // Primer día del mes
    const date_from = `${year}-${String(month).padStart(2, '0')}-01`;
    
    // Último día del mes
    const ultimoDia = new Date(year, month, 0).getDate();
    const date_to = `${year}-${String(month).padStart(2, '0')}-${ultimoDia}`;
    
    return { date_from, date_to };
};

const cargarDatos = async () => {
    cargando.value = true;
    try {
        const params = obtenerParametrosMes();
        const response = await axios.get('/reports/ingresos-habitaciones', { params });
        resumen.value = response.data;
        await cargarBookingsDetallados();
        await cargarDatosGrafica();
    } catch (error) {
        console.error('Error cargando datos:', error);
    } finally {
        cargando.value = false;
    }
};

const cargarBookingsDetallados = async () => {
    try {
        const params = obtenerParametrosMes();
        console.log('📤 Parámetros enviados:', params);
        
        const response = await axios.get('/bookings', { 
            params: { 
                date_from: params.date_from,
                date_to: params.date_to,
                per_page: 100
            }
        });
        
        console.log('📥 Respuesta recibida:', response.data);
        
        bookings.value = response.data.data || [];
    } catch (error) {
        console.error('❌ Error cargando bookings:', error);
        bookings.value = [];
    }
};

const cargarDatosGrafica = async () => {
    try {
        const params = obtenerParametrosMes();
        const response = await axios.get('/reports/ingresos-habitaciones-grafica', { params });
        console.log('📊 Datos gráfica recibidos:', response.data);
        datosGrafica.value = response.data;
    } catch (error) {
        console.error('Error cargando gráfica:', error);
        datosGrafica.value = [];
    }
};

const graficaIngresosDia = computed(() => {
    if (!datosGrafica.value.length) {
        return {
            labels: ['Sin datos'],
            datasets: [{
                label: 'Ingresos',
                data: [0],
                backgroundColor: '#42A5F5'
            }]
        };
    }

    return {
        labels: datosGrafica.value.map(item => parsearFechaSinUTC(item.dia)),
        datasets: [{
            label: 'Ingresos',
            data: datosGrafica.value.map(item => parseFloat(item.ingresos)),
            backgroundColor: '#42A5F5',
            borderColor: '#1E88E5',
            borderWidth: 1
        }]
    };
});

const graficaDistribucion = computed(() => {
    const metodosPago = bookings.value.reduce((acc: any, booking) => {
        const metodo = booking.metodo_pago;
        acc[metodo] = (acc[metodo] || 0) + booking.monto_total;
        return acc;
    }, {});

    const labels = Object.keys(metodosPago);
    const data = Object.values(metodosPago);

    if (!data.length) {
        return {
            labels: ['Sin datos'],
            datasets: [{ data: [1], backgroundColor: ['#e0e0e0'] }]
        };
    }

    return {
        labels,
        datasets: [{
            data,
            backgroundColor: ['#42A5F5', '#66BB6A', '#FFA726', '#26C6DA', '#7E57C2', '#EC407A']
        }]
    };
});

const opcionesGraficaBarras = {
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (context: any) => 'S/ ' + formatoMoneda(context.parsed.y)
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: (value: any) => 'S/ ' + value.toLocaleString('es-PE')
            }
        }
    }
};

const opcionesPie = {
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: { padding: 8, font: { size: 11 } }
        },
        tooltip: {
            callbacks: {
                label: (context: any) => context.label + ': S/ ' + formatoMoneda(context.parsed)
            }
        }
    }
};

const getSeverityMetodo = (metodo: string) => {
    const metodoLower = metodo.toLowerCase();
    if (metodoLower.includes('efectivo') || metodoLower.includes('cash')) return 'success';
    if (metodoLower.includes('tarjeta') || metodoLower.includes('card')) return 'info';
    if (metodoLower.includes('transferencia') || metodoLower.includes('transfer')) return 'warn';
    return 'secondary';
};

const getSeverityEstado = (estado: string) => {
    const estadoLower = estado.toLowerCase();
    if (estadoLower.includes('finalizada') || estadoLower.includes('completado')) return 'success';
    if (estadoLower.includes('curso') || estadoLower.includes('pendiente')) return 'warn';
    if (estadoLower.includes('cancelada') || estadoLower.includes('fallido')) return 'danger';
    return 'secondary';
};

onMounted(() => {
    cargarDatos();
});
</script>