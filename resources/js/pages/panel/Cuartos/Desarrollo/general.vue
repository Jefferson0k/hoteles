<template>
  <div class="grid grid-cols-12 gap-6">
    <!-- Información Principal -->
    <div class="col-span-12 lg:col-span-8">
      <Tasas :room="room" />
      <Cliente />
      <Productos />
      <Total />
    </div>

    <!-- Panel Lateral - Cronómetro y Acción -->
    <div class="col-span-12 lg:col-span-4">
      <div class="sticky top-6">
        <Cronometro />
        <Tiempo />
        <Informacion />
      </div>
    </div>
  </div>

</template>

<script setup lang="ts">
import Tasas from '../componentes/Tasas.vue';
import Cronometro from '../componentes/Cronometro.vue';
import Cliente from '../componentes/Cliente.vue';
import Tiempo from '../componentes/Tiempo.vue';
import Productos from '../componentes/Productos.vue';
import Total from '../componentes/Total.vue';
import Informacion from '../componentes/Informacion.vue';

defineProps<{
  room: any;
}>();
</script>