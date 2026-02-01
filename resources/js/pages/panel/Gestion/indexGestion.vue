<template>
  <Head title="Habitaciones" />
  <AppLayouth>
    <div>
      <template v-if="isLoading">
        <Espera />
      </template>
      <template v-else>
        <indexMenuRecepcionista />
        <div class="card">
          <listGestion />
          <liberarhabitacion 
            v-model:visible="store.liberarDialog"
            :roomId="store.selectedRoomId"
            :roomNumber="store.selectedRoomNumber"
            @room-liberated="store.handleRoomLiberated"
          />
          <extendertiempo />
          <finalizar />
        </div>
      </template>
    </div>
  </AppLayouth>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayouth from '@/layout/AppLayouth.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import listGestion from './Desarrollo/listGestion.vue';
import indexMenuRecepcionista from '../MenuRecepcionista/indexMenuRecepcionista.vue';
import liberarhabitacion from './Desarrollo/liberarhabitacion.vue';
import extendertiempo from './Desarrollo/extendertiempo.vue';
import finalizar from './Desarrollo/finalizar.vue';
import { useRoomManagementStore } from './interface/Roommanagement';

const store = useRoomManagementStore();
const isLoading = ref(true);

onMounted(() => {
  setTimeout(() => {
    isLoading.value = false;
  }, 1000);
});
</script>