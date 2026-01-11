<template>

  <Head title="habitacion" />
  <AppLayouth>
    <div>
      <template v-if="isLoading">
        <Espera />
      </template>
      <template v-else>
        <div class="card">
          <listRommFloor :roomData="roomData" />
        </div>
      </template>
    </div>
  </AppLayouth>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayouth from '@/layout/AppLayouth.vue';
import { Head, usePage } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import listRommFloor from './Desarrollo/listRommFloor.vue';

const page = usePage();

const roomData = computed(() => page.props.data?.data || page.props.data);

const isLoading = ref(true);

onMounted(() => {
  setTimeout(() => {
    isLoading.value = false;
  }, 1000);
});
</script>