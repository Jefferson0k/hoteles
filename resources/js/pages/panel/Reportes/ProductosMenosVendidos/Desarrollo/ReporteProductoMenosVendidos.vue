<template>
  <div class="">
    <!-- Título Principal y Filtros -->
    <div class="mb-6">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-3xl font-bold mb-2">Productos Menos Vendidos</h1>
          <p class="">Análisis de productos con bajo desempeño y sin ventas</p>
        </div>
        
        <!-- Filtros en la esquina -->
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="flex flex-col">
            <label class="text-xs font-medium mb-1">Sucursal</label>
            <Select 
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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <Message severity="error" :closable="false" class="shadow-sm">
        <div class="flex justify-between items-center w-full">
          <div>
            <p class="text-sm font-medium mb-1">Productos Poco Vendidos</p>
            <p class="text-3xl font-bold text-red-600">{{ resumen.totalPocoVendidos }}</p>
          </div>
          <div class="p-3 bg-red-100 rounded-full">
            <i class="pi pi-arrow-down text-red-600 text-2xl"></i>
          </div>
        </div>
      </Message>

      <Message severity="warn" :closable="false" class="shadow-sm">
        <div class="flex justify-between items-center w-full">
          <div>
            <p class="text-sm font-medium mb-1">Productos Sin Ventas</p>
            <p class="text-3xl font-bold text-orange-600">{{ resumen.totalSinVentas }}</p>
          </div>
          <div class="p-3 bg-orange-100 rounded-full">
            <i class="pi pi-times-circle text-orange-600 text-2xl"></i>
          </div>
        </div>
      </Message>

      <Message severity="warn" :closable="false" class="shadow-sm">
        <div class="flex justify-between items-center w-full">
          <div>
            <p class="text-sm font-medium  mb-1">Unidades Mínimas</p>
            <p class="text-3xl font-bold text-yellow-600">{{ resumen.unidadesMinimas.toLocaleString() }}</p>
          </div>
          <div class="p-3 bg-yellow-100 rounded-full">
            <i class="pi pi-exclamation-triangle text-yellow-600 text-2xl"></i>
          </div>
        </div>
      </Message>

      <Message severity="contrast" :closable="false" class="shadow-sm">
        <div class="flex justify-between items-center w-full">
          <div>
            <p class="text-sm font-medium mb-1">Ingresos Mínimos</p>
            <p class="text-3xl font-bold text-purple-600">S/ {{ resumen.ingresosMinimos.toLocaleString('es-PE', { minimumFractionDigits: 2 }) }}</p>
          </div>
          <div class="p-3 bg-purple-100 rounded-full">
            <i class="pi pi-money-bill text-purple-600 text-2xl"></i>
          </div>
        </div>
      </Message>
    </div>

    <!-- Gráficas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Top 10 Productos Menos Vendidos -->
      <div class="">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Top 10 Productos Menos Vendidos</h3>
          <Button 
            icon="pi pi-download" 
            class="p-button-rounded p-button-text p-button-sm"
            @click="exportarDatos('menos-vendidos')"
            v-tooltip.top="'Exportar datos'"
          />
        </div>
        <Chart 
          v-if="chartDataMenosVendidos.labels.length > 0"
          type="bar" 
          :data="chartDataMenosVendidos" 
          :options="chartOptionsBar" 
          class="h-80"
        />
        <div v-else class="h-80 flex items-center justify-center bg-gray-50 rounded-lg">
          <div class="text-center">
            <i class="pi pi-chart-bar text-4xl text-gray-400 mb-2"></i>
            <p class="text-gray-500">No hay datos disponibles</p>
          </div>
        </div>
      </div>

      <!-- Comparativa Más vs Menos Vendidos -->
      <div class="">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Comparativa: Más vs Menos Vendidos</h3>
        </div>
        <Chart 
          v-if="chartDataComparativa.labels.length > 0"
          type="bar" 
          :data="chartDataComparativa" 
          :options="chartOptionsComparativa" 
          class="h-80"
        />
        <div v-else class="h-80 flex items-center justify-center bg-gray-50 rounded-lg">
          <div class="text-center">
            <i class="pi pi-chart-line text-4xl text-gray-400 mb-2"></i>
            <p class="text-gray-500">No hay datos disponibles</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tablas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Productos Menos Vendidos -->
      <div class="">
        <h3 class="text-lg font-semibold mb-4">Productos Menos Vendidos</h3>
        <DataTable 
          :value="productosMenosVendidos"
          :loading="loading"
          paginator 
          :rows="5"
          :rowsPerPageOptions="[5, 10, 20]"
          tableStyle="min-width: 100%"
          :pageLinkSize="3"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        >
          <template #empty>
            <div class="text-center py-6">
              <i class="pi pi-inbox text-4xl text-gray-400 mb-2"></i>
              <p class="text-gray-500">No hay productos con ventas bajas</p>
            </div>
          </template>
          <Column field="name" header="Producto" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <i class="pi pi-box text-gray-500"></i>
                <span class="font-medium">{{ data.name }}</span>
              </div>
            </template>
          </Column>
          <Column field="unidades_vendidas" header="Unidades" sortable>
            <template #body="{ data }">
              <Tag :value="data.unidades_vendidas.toLocaleString()" severity="danger" />
            </template>
          </Column>
          <Column field="ingreso_generado" header="Ingresos" sortable>
            <template #body="{ data }">
              <span class="text-red-600 font-semibold">
                S/ {{ parseFloat(data.ingreso_generado).toLocaleString('es-PE', { minimumFractionDigits: 2 }) }}
              </span>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Productos Sin Ventas -->
      <div>
        <h3 class="text-lg font-semibold mb-4">
          Productos Sin Ventas 
          <span class="text-sm font-normal">(Stock Muerto)</span>
        </h3>
        <DataTable 
          :value="productosSinVentas"
          :loading="loading"
          paginator 
          :rows="5"
          :rowsPerPageOptions="[5, 10, 20]"
          tableStyle="min-width: 100%"
          :pageLinkSize="3"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        >
          <template #empty>
            <div class="text-center py-6">
              <i class="pi pi-check-circle text-4xl text-green-400 mb-2"></i>
              <p class="text-gray-500">¡Excelente! Todos los productos tienen ventas</p>
            </div>
          </template>
          <Column field="name" header="Producto" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <i class="pi pi-box"></i>
                <span class="font-medium">{{ data.name }}</span>
              </div>
            </template>
          </Column>
          <Column field="sale_price" header="Precio" sortable>
            <template #body="{ data }">
              <span class="font-medium">
                S/ {{ Number(data.sale_price || 0).toFixed(2) }}
              </span>
            </template>
          </Column>
          <Column header="Acción" style="width: 100px">
            <template #body="{ data }">
              <Button 
                icon="pi pi-chart-line" 
                class="p-button-text p-button-sm p-button-info" 
                v-tooltip.top="'Ver análisis'"
                @click="verAnalisis(data)"
              />
            </template>
          </Column>
        </DataTable>
      </div>
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
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Chart from 'primevue/chart';
import Message from 'primevue/message';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';
import Select from 'primevue/select';

const toast = useToast();

// Estados reactivos
const loading = ref(false);
const productosMenosVendidos = ref([]);
const productosSinVentas = ref([]);
const sucursales = ref([]);

const filtros = ref({
  subBranchId: null,
  dateRange: [
    new Date(Date.now() - 30 * 24 * 60 * 60 * 1000),
    new Date()
  ]
});

const resumen = ref({
  totalPocoVendidos: 0,
  totalSinVentas: 0,
  unidadesMinimas: 0,
  ingresosMinimos: 0
});

// Datos para gráficas
const chartDataMenosVendidos = ref({
  labels: [],
  datasets: [
    {
      label: 'Unidades Vendidas',
      data: [],
      backgroundColor: 'rgba(239, 68, 68, 0.7)',
      borderColor: 'rgba(239, 68, 68, 1)',
      borderWidth: 2,
      borderRadius: 6,
      hoverBackgroundColor: 'rgba(239, 68, 68, 0.9)'
    }
  ]
});

const chartDataComparativa = ref({
  labels: [],
  datasets: [
    {
      label: 'Más Vendidos',
      data: [],
      backgroundColor: 'rgba(34, 197, 94, 0.7)',
      borderColor: 'rgba(34, 197, 94, 1)',
      borderWidth: 2,
      borderRadius: 6
    },
    {
      label: 'Menos Vendidos',
      data: [],
      backgroundColor: 'rgba(239, 68, 68, 0.7)',
      borderColor: 'rgba(239, 68, 68, 1)',
      borderWidth: 2,
      borderRadius: 6
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

const chartOptionsComparativa = {
  responsive: true,
  maintainAspectRatio: false,
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
      beginAtZero: true,
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
    const data = response.data.data || response.data;
    sucursales.value = Array.isArray(data) ? data : [];
    
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

const cargarProductosMenosVendidos = async () => {
  try {
    const params = {
      sub_branch_id: filtros.value.subBranchId,
      start_date: formatDate(filtros.value.dateRange[0]),
      end_date: formatDate(filtros.value.dateRange[1])
    };

    const response = await axios.get('/reports/menos-vendidos', { params });
    
    // ✅ CORRECCIÓN: Manejar estructura {success, data}
    const responseData = response.data.data || response.data;
    productosMenosVendidos.value = Array.isArray(responseData) ? responseData : [];
    
    // Actualizar gráfica
    if (productosMenosVendidos.value.length > 0) {
      chartDataMenosVendidos.value.labels = productosMenosVendidos.value.map(p => p.name);
      chartDataMenosVendidos.value.datasets[0].data = productosMenosVendidos.value.map(p => Number(p.unidades_vendidas));

      // Calcular resumen
      resumen.value.totalPocoVendidos = productosMenosVendidos.value.length;
      resumen.value.unidadesMinimas = productosMenosVendidos.value.reduce((sum, p) => sum + Number(p.unidades_vendidas), 0);
      resumen.value.ingresosMinimos = productosMenosVendidos.value.reduce((sum, p) => sum + parseFloat(p.ingreso_generado), 0);
    } else {
      // Resetear si no hay datos
      chartDataMenosVendidos.value.labels = [];
      chartDataMenosVendidos.value.datasets[0].data = [];
      resumen.value.totalPocoVendidos = 0;
      resumen.value.unidadesMinimas = 0;
      resumen.value.ingresosMinimos = 0;
    }

  } catch (error) {
    console.error('Error cargando productos menos vendidos:', error);
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'No se pudieron cargar los productos menos vendidos', 
      life: 3000 
    });
  }
};

const cargarProductosSinVentas = async () => {
  try {
    const params = {
      sub_branch_id: filtros.value.subBranchId,
      start_date: formatDate(filtros.value.dateRange[0]),
      end_date: formatDate(filtros.value.dateRange[1])
    };

    const response = await axios.get('/reports/sin-ventas', { params });
    
    // ✅ CORRECCIÓN: Manejar estructura {success, data, total}
    const responseData = response.data.data || response.data;
    productosSinVentas.value = Array.isArray(responseData) ? responseData : [];
    resumen.value.totalSinVentas = response.data.total || productosSinVentas.value.length;

  } catch (error) {
    console.error('Error cargando productos sin ventas:', error);
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'No se pudieron cargar los productos sin ventas', 
      life: 3000 
    });
  }
};

const cargarComparativaVentas = async () => {
  try {
    const params = {
      sub_branch_id: filtros.value.subBranchId,
      start_date: formatDate(filtros.value.dateRange[0]),
      end_date: formatDate(filtros.value.dateRange[1])
    };

    const response = await axios.get('/reports/comparativa-ventas-grafica', { params });
    
    // ✅ CORRECCIÓN: Acceder correctamente a data.data
    const data = response.data.data || response.data;

    // Verificar que existan los datos
    if (data && data.mas_vendidos && data.menos_vendidos) {
      // Preparar datos para gráfica comparativa
      chartDataComparativa.value.labels = data.mas_vendidos.labels || [];
      chartDataComparativa.value.datasets[0].data = data.mas_vendidos.data || [];
      chartDataComparativa.value.datasets[1].data = data.menos_vendidos.data || [];
    } else {
      // Resetear si no hay datos
      chartDataComparativa.value.labels = [];
      chartDataComparativa.value.datasets[0].data = [];
      chartDataComparativa.value.datasets[1].data = [];
    }

  } catch (error) {
    console.error('Error cargando comparativa de ventas:', error);
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'No se pudo cargar la comparativa de ventas', 
      life: 3000 
    });
  }
};

const cargarTodosLosDatos = async () => {
  loading.value = true;
  try {
    await Promise.all([
      cargarProductosMenosVendidos(),
      cargarProductosSinVentas(),
      cargarComparativaVentas()
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

const formatDate = (date: Date) => {
  if (!date || !(date instanceof Date)) {
    return new Date().toISOString().split('T')[0];
  }
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

const verAnalisis = (producto: any) => {
  toast.add({
    severity: 'info',
    summary: 'Análisis de Producto',
    detail: `Ver análisis detallado de: ${producto.name}`,
    life: 3000
  });
};

// Inicialización
onMounted(async () => {
  await cargarSucursales();
  await cargarTodosLosDatos();
});
</script>