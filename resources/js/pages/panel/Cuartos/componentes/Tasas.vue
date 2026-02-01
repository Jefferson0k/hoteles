<template>
  <div class="mb-6">
    
    <!-- Header: Número de habitación -->
    <div class="flex justify-between items-start mb-6">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <div class="flex items-center justify-center w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-lg border-2 border-primary-300 dark:border-primary-700">
            <span class="text-2xl font-bold text-primary-700 dark:text-primary-300">
              {{ room.data.room_number }}
            </span>
          </div>
          <div>
            <h2 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
              Habitación {{ room.data.room_number }}
            </h2>
            <p class="text-surface-600 dark:text-surface-400 text-sm mt-1">
              {{ room.data.room_type_name }} - {{ room.data.room_type_description }}
            </p>
          </div>
        </div>
      </div>
      <Tag :value="statusText" :severity="statusSeverity" rounded />
    </div>

    <!-- Seleccionar Tarifa -->
    <div class="p-5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-700">
      <h3 class="text-lg font-bold text-surface-900 dark:text-surface-0 mb-4 flex items-center gap-2">
        <i class="pi pi-money-bill"></i>
        Seleccionar Tarifa
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        
        <!-- Por Hora -->
        <div 
          @click="seleccionarTarifa('hour', parseFloat(room.data.room_price_hour))"
          :class="getRateCardClass('hour')"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-surface-600 dark:text-surface-400">Por Hora</span>
            <i v-if="tarifaSeleccionada === 'hour'" class="pi pi-check-circle text-primary-500"></i>
          </div>
          <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
            S/ {{ formatPrice(room.data.room_price_hour) }}
          </p>
        </div>

        <!-- Por Día -->
        <div 
          @click="seleccionarTarifa('day', parseFloat(room.data.room_price_day))"
          :class="getRateCardClass('day')"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-surface-600 dark:text-surface-400">Por Día</span>
            <i v-if="tarifaSeleccionada === 'day'" class="pi pi-check-circle text-primary-500"></i>
          </div>
          <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
            S/ {{ formatPrice(room.data.room_price_day) }}
          </p>
        </div>

        <!-- Por Noche -->
        <div 
          @click="seleccionarTarifa('night', parseFloat(room.data.room_price_night))"
          :class="getRateCardClass('night')"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-surface-600 dark:text-surface-400">Por Noche</span>
            <i v-if="tarifaSeleccionada === 'night'" class="pi pi-check-circle text-primary-500"></i>
          </div>
          <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
            S/ {{ formatPrice(room.data.room_price_night) }}
          </p>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useReservaStore } from '../store/reserva';
import Tag from 'primevue/tag';

const props = defineProps<{
  room: any;
}>();

const store = useReservaStore();
const tarifaSeleccionada = ref<'hour' | 'day' | 'night' | null>(null);
const precioSeleccionado = ref(0);

onMounted(() => {
  store.setHabitacion(props.room.data);
});

const statusSeverity = computed(() => {
  switch (props.room.data.status) {
    case 'available':
      return 'success';
    case 'occupied':
      return 'danger';
    default:
      return 'info';
  }
});

const statusText = computed(() => {
  switch (props.room.data.status) {
    case 'available':
      return 'Disponible';
    case 'occupied':
      return 'Ocupada';
    default:
      return props.room.data.status;
  }
});

const statusIcon = computed(() => {
  switch (props.room.data.status) {
    case 'available':
      return 'pi-check-circle';
    case 'occupied':
      return 'pi-lock';
    default:
      return 'pi-circle';
  }
});

const getRateCardClass = (rate: 'hour' | 'day' | 'night') => {
  const baseClass = 'p-4 rounded-lg border-2 cursor-pointer transition-all';
  const selectedClass = 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-lg';
  const unselectedClass = 'border-surface-300 dark:border-surface-600 bg-white dark:bg-surface-800 hover:border-primary-300';
  
  return `${baseClass} ${tarifaSeleccionada.value === rate ? selectedClass : unselectedClass}`;
};

const seleccionarTarifa = (tipo: 'hour' | 'day' | 'night', precio: number) => {
  tarifaSeleccionada.value = tipo;
  precioSeleccionado.value = precio;
  store.setTarifa(tipo, precio);
};

const formatPrice = (price: string) => {
  return parseFloat(price).toFixed(2);
};
</script>