<template>
  <div class="">
    <!-- Título Principal y Filtros -->
    <div class="mb-6">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-3xl font-bold mb-2">Productos Más Vendidos</h1>
          <p class="">Análisis y estadísticas de productos con mejor desempeño</p>
        </div>
        
        <!-- Filtros en la esquina -->
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="flex flex-col">
            <label class="text-xs font-medium mb-1">Sucursal</label>
            <Dropdown 
              v-model="filtros.subBranchId"
              :options="sucursales"
              optionLabel="name"
              optionValue="id"
              placeholder="Seleccionar"
              class="w-full sm:w-48"
              @change="aplicarFiltros"
            />
          </div>
          <div class="flex flex-col">
            <label class="text-xs font-medium mb-1">Rango de Fechas</label>
            <Calendar 
              v-model="filtros.dateRange"
              selectionMode="range"
              dateFormat="dd/mm/yy"
              :manualInput="false"
              placeholder="Seleccionar fechas"
              class="w-full sm:w-64"
              @date-select="onDateRangeChange"
              showIcon
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Tarjetas de Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <Message severity="info" :closable="false" class="shadow-sm">
        <div class="flex justify-between items-center w-full">
          <div>
            <p class="text-sm font-medium mb-1">Total Productos Vendidos</p>
            <p class="text-3xl font-bold text-blue-600">{{ resumen.totalProductos }}</p>
          </div>
          <div class="p-3 bg-blue-100 rounded-full">
            <i class="pi pi-shopping-bag text-blue-600 text-2xl"></i>
          </div>
        </div>
      </Message>

      <Message severity="success" :closable="false" class="shadow-sm">
        <div class="flex justify-between items-center w-full">
          <div>
            <p class="text-sm font-medium  mb-1">Unidades Vendidas</p>
            <p class="text-3xl font-bold text-green-600">{{ resumen.totalUnidades.toLocaleString() }}</p>
          </div>
          <div class="p-3 bg-green-100 rounded-full">
            <i class="pi pi-chart-bar text-green-600 text-2xl"></i>
          </div>
        </div>
      </Message>

      <Message severity="warn" :closable="false" class="shadow-sm">
        <div class="flex justify-between items-center w-full">
          <div>
            <p class="text-sm font-medium mb-1">Ingreso Total</p>
            <p class="text-3xl font-bold text-orange-600">S/ {{ resumen.totalIngresos.toLocaleString('es-PE', { minimumFractionDigits: 2 }) }}</p>
          </div>
          <div class="p-3 bg-orange-100 rounded-full">
            <i class="pi pi-money-bill text-orange-600 text-2xl"></i>
          </div>
        </div>
      </Message>
    </div>

    <!-- Gráficas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Top 10 Productos Más Vendidos -->
      <div>
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Top 10 Productos Más Vendidos</h3>
          <Button 
            icon="pi pi-download" 
            class="p-button-rounded p-button-text p-button-sm"
            @click="exportarDatos('top-productos')"
            v-tooltip.top="'Exportar datos'"
          />
        </div>
        <Chart 
          type="bar" 
          :data="chartDataTopProductos" 
          :options="chartOptionsBar" 
          class="h-80"
        />
      </div>

      <!-- Distribución por Categoría -->
      <div class="">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Distribución por Categoría</h3>
          <Dropdown 
            v-model="tipoDistribucion"
            :options="opcionesDistribucion"
            optionLabel="label"
            optionValue="value"
            class="w-44"
          />
        </div>
        <Chart 
          type="doughnut" 
          :data="chartDataCategorias" 
          :options="chartOptionsDoughnut" 
          class="h-80"
        />
      </div>
    </div>

    <!-- Evolución Mensual -->
    <div>
      <h3 class="text-lg font-semibold mb-4">Evolución Mensual de Ventas</h3>
      <Chart 
        type="line" 
        :data="chartDataEvolucion" 
        :options="chartOptionsLine" 
        class="h-80"
      />
    </div>
<br>
<br>
    <!-- Tabla de Productos con Mejor Rendimiento -->
    <div>
      <h3 class="text-lg font-semibold mb-4">Productos con Mejor Rendimiento</h3>
      <DataTable 
        :value="productosRendimiento"
        :loading="loading"
        paginator 
        :rows="10"
        :rowsPerPageOptions="[5, 10, 20, 50]"
        tableStyle="min-width: 50rem"
        :pageLinkSize="5"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
      >
        <Column field="name" header="Producto" sortable>
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <i class="pi pi-box text-gray-500"></i>
              <span class="font-medium text-gray-800">{{ data.name }}</span>
            </div>
          </template>
        </Column>
        <Column field="unidades_vendidas" header="Unidades" sortable>
          <template #body="{ data }">
            <Tag :value="data.unidades_vendidas.toLocaleString()" severity="info" />
          </template>
        </Column>
        <Column field="ingreso_generado" header="Ingresos" sortable>
          <template #body="{ data }">
            <span class="text-green-600 font-semibold">
              S/ {{ data.ingreso_generado.toLocaleString('es-PE', { minimumFractionDigits: 2 }) }}
            </span>
          </template>
        </Column>
        <Column header="Rendimiento" style="width: 150px">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <ProgressBar 
                :value="calcularRendimiento(data)"
                :showValue="false"
                class="flex-1"
                :pt="{
                  value: { class: 'bg-gradient-to-r from-blue-400 to-blue-600' }
                }"
              />
              <span class="text-xs text-gray-500 font-medium">
                {{ calcularRendimiento(data).toFixed(0) }}%
              </span>
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Loading Dialog -->
    <Dialog 
      v-model:visible="loading" 
      modal 
      :closable="false"
      :style="{ width: '350px' }"
      :pt="{
        mask: { class: 'backdrop-blur-sm' }
      }"
    >
      <div class="flex items-center gap-4 p-4">
        <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="4" />
        <div>
          <p class="text-gray-800 font-medium">Cargando datos...</p>
          <p class="text-sm text-gray-500">Por favor espere</p>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import Chart from 'primevue/chart';
import Message from 'primevue/message';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

// Estados reactivos
const loading = ref(false);
const productosMasVendidos = ref([]);
const productosRendimiento = ref([]);
const sucursales = ref([]);

const filtros = ref({
  subBranchId: null,
  dateRange: [
    new Date(Date.now() - 30 * 24 * 60 * 60 * 1000), // 30 días atrás
    new Date()
  ]
});

const resumen = ref({
  totalProductos: 0,
  totalUnidades: 0,
  totalIngresos: 0
});

const tipoDistribucion = ref('unidades');

// Opciones para dropdowns
const opcionesDistribucion = ref([
  { label: 'Por Unidades', value: 'unidades' },
  { label: 'Por Ingresos', value: 'ingresos' }
]);

// Datos para gráficas
const chartDataTopProductos = ref({
  labels: [],
  datasets: [
    {
      label: 'Unidades Vendidas',
      data: [],
      backgroundColor: 'rgba(59, 130, 246, 0.7)',
      borderColor: 'rgba(59, 130, 246, 1)',
      borderWidth: 2,
      borderRadius: 6,
      hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)'
    }
  ]
});

const chartDataCategorias = ref({
  labels: [],
  datasets: [
    {
      data: [],
      backgroundColor: [
        'rgba(239, 68, 68, 0.8)',
        'rgba(59, 130, 246, 0.8)',
        'rgba(251, 191, 36, 0.8)',
        'rgba(34, 197, 94, 0.8)',
        'rgba(168, 85, 247, 0.8)',
        'rgba(249, 115, 22, 0.8)',
      ],
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 10
    }
  ]
});

const chartDataEvolucion = ref({
  labels: [],
  datasets: [
    {
      label: 'Unidades Vendidas',
      data: [],
      borderColor: 'rgba(59, 130, 246, 1)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4,
      fill: true,
      pointRadius: 4,
      pointHoverRadius: 6,
      yAxisID: 'y'
    },
    {
      label: 'Ingresos Generados (S/)',
      data: [],
      borderColor: 'rgba(34, 197, 94, 1)',
      backgroundColor: 'rgba(34, 197, 94, 0.1)',
      tension: 0.4,
      fill: true,
      pointRadius: 4,
      pointHoverRadius: 6,
      yAxisID: 'y1'
    }
  ]
});

// Opciones de gráficas
const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      borderRadius: 8
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.05)'
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
};

const chartOptionsDoughnut = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        padding: 15,
        usePointStyle: true,
        font: {
          size: 12
        }
      }
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      borderRadius: 8
    }
  }
};

const chartOptionsLine = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: {
      position: 'top',
      labels: {
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      borderRadius: 8
    }
  },
  scales: {
    y: {
      type: 'linear',
      display: true,
      position: 'left',
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.05)'
      }
    },
    y1: {
      type: 'linear',
      display: true,
      position: 'right',
      beginAtZero: true,
      grid: {
        drawOnChartArea: false,
      },
    },
    x: {
      grid: {
        display: false
      }
    }
  }
};

// Métodos
const aplicarFiltros = async () => {
  if (filtros.value.dateRange && filtros.value.dateRange[0] && filtros.value.dateRange[1]) {
    await cargarTodosLosDatos();
  }
};

const onDateRangeChange = () => {
  if (filtros.value.dateRange && filtros.value.dateRange[0] && filtros.value.dateRange[1]) {
    aplicarFiltros();
  }
};

const cargarSucursales = async () => {
  try {
    const response = await axios.get('/sub-branches/search');
    sucursales.value = response.data.data || response.data;
    if (sucursales.value.length > 0) {
      filtros.value.subBranchId = sucursales.value[0].id;
    }
  } catch (error) {
    console.error('Error cargando sucursales:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las sucursales',
      life: 3000
    });
  }
};

const cargarProductosMasVendidos = async () => {
  try {
    const params = {
      sub_branch_id: filtros.value.subBranchId,
      start_date: formatDate(filtros.value.dateRange[0]),
      end_date: formatDate(filtros.value.dateRange[1])
    };

    const response = await axios.get('/reports/mas-vendidos', { params });
    productosMasVendidos.value = response.data;
    
    // Actualizar gráfica
    chartDataTopProductos.value.labels = productosMasVendidos.value.map(p => p.name);
    chartDataTopProductos.value.datasets[0].data = productosMasVendidos.value.map(p => p.unidades_vendidas);

    // Calcular resumen
    resumen.value.totalProductos = productosMasVendidos.value.length;
    resumen.value.totalUnidades = productosMasVendidos.value.reduce((sum, p) => sum + p.unidades_vendidas, 0);
    resumen.value.totalIngresos = productosMasVendidos.value.reduce((sum, p) => sum + p.ingreso_generado, 0);

  } catch (error) {
    console.error('Error cargando productos más vendidos:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar los productos más vendidos',
      life: 3000
    });
  }
};

const cargarProductosPorCategoria = async () => {
  try {
    const params = {
      sub_branch_id: filtros.value.subBranchId,
      start_date: formatDate(filtros.value.dateRange[0]),
      end_date: formatDate(filtros.value.dateRange[1])
    };

    const response = await axios.get('/reports/por-categoria', { params });
    const data = response.data;

    if (tipoDistribucion.value === 'unidades') {
      chartDataCategorias.value.labels = data.unidades.labels;
      chartDataCategorias.value.datasets[0].data = data.unidades.datasets[0].data;
    } else {
      chartDataCategorias.value.labels = data.ingresos.labels;
      chartDataCategorias.value.datasets[0].data = data.ingresos.datasets[0].data;
    }

  } catch (error) {
    console.error('Error cargando productos por categoría:', error);
  }
};

const cargarEvolucionVentas = async () => {
  try {
    const params = {
      sub_branch_id: filtros.value.subBranchId,
      start_date: formatDate(filtros.value.dateRange[0]),
      end_date: formatDate(filtros.value.dateRange[1])
    };

    const response = await axios.get('/reports/evolucion-ventas', { params });
    const data = response.data;

    chartDataEvolucion.value.labels = data.labels;
    chartDataEvolucion.value.datasets[0].data = data.datasets[0].data;
    chartDataEvolucion.value.datasets[1].data = data.datasets[1].data;

  } catch (error) {
    console.error('Error cargando evolución de ventas:', error);
  }
};

const cargarProductosRendimiento = async () => {
  try {
    const params = {
      sub_branch_id: filtros.value.subBranchId,
      start_date: formatDate(filtros.value.dateRange[0]),
      end_date: formatDate(filtros.value.dateRange[1])
    };

    const response = await axios.get('/reports/mejor-rendimiento', { params });
    productosRendimiento.value = response.data;

  } catch (error) {
    console.error('Error cargando productos con mejor rendimiento:', error);
  }
};

const cargarTodosLosDatos = async () => {
  loading.value = true;
  try {
    await Promise.all([
      cargarProductosMasVendidos(),
      cargarProductosPorCategoria(),
      cargarEvolucionVentas(),
      cargarProductosRendimiento()
    ]);
    
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Datos actualizados correctamente',
      life: 3000
    });
  } catch (error) {
    console.error('Error cargando datos:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al cargar los datos',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const calcularRendimiento = (producto: any) => {
  const maxIngreso = Math.max(...productosRendimiento.value.map((p: any) => p.ingreso_generado));
  return maxIngreso > 0 ? (producto.ingreso_generado / maxIngreso) * 100 : 0;
};

const formatDate = (date: Date) => {
  return date.toISOString().split('T')[0];
};

const exportarDatos = (tipo: string) => {
  toast.add({
    severity: 'info',
    summary: 'Exportar',
    detail: `Exportando datos de ${tipo}`,
    life: 3000
  });
};

// Watchers
watch(tipoDistribucion, () => {
  cargarProductosPorCategoria();
});

// Inicialización
onMounted(async () => {
  await cargarSucursales();
  await cargarTodosLosDatos();
});
</script>