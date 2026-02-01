<template>
  <Head title="Habitaciones" />
  <AppLayouth>
    <div>
      <template v-if="isLoading">
        <Espera />
      </template>
      <template v-else>
        <div class="card">
          <general :room="room" />
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
import general from './Desarrollo/general.vue';

const props = defineProps<{
  room: {
    data: {
      id: string;
      floor_name: string;
      floor_number: number;
      room_type_name: string;
      room_type_description: string;
      room_capacity: number;
      room_price_hour: string;
      room_price_day: string;
      room_price_night: string;
      room_number: string;
      status: string;
      is_active: boolean;
    };
  };
}>();

const isLoading = ref(true);

onMounted(() => {
  setTimeout(() => {
    isLoading.value = false;
  }, 1000);
});
</script>