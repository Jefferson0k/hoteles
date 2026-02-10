<template>

  <Head title="Configuraciones" />
  <AppLayout>
    <div>
      <template v-if="isLoading">
        <Espera />
      </template>
      <template v-else>
        <div class="card">
          <configuracionesgeneral />
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import configuracionesgeneral from './Desarrollo/configuracionesgeneral.vue';
const isLoading = ref(true);

onMounted(async () => {
  isLoading.value = false;
});
</script>
