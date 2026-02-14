<template>
    <Head title="Tipos de Tarifa" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <AddRateType ref="addRef" @refresh="refreshList" />
                    <ListRateType ref="listRef" @edit="handleEdit" />
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
import ListRateType from './Desarrollo/ListRatetype.vue';
import AddRateType from './Desarrollo/AddRateType.vue';

const isLoading = ref(true);
const listRef = ref<InstanceType<typeof ListRateType>>();
const addRef = ref<InstanceType<typeof AddRateType>>();

const refreshList = () => {
    // Solo refrescar si realmente necesitas recargar desde el servidor
    // En este caso, NO es necesario porque el store ya actualiza el array localmente
    // if (listRef.value) {
    //     listRef.value.fetchRateTypes();
    // }
};

const handleEdit = (id: number) => {
    if (addRef.value) {
        addRef.value.openEdit(id);
    }
};

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>