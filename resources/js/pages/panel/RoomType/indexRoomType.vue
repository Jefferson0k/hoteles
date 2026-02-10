<template>
    <Head title="Tipo de habitaciones" />
    <AppLayout>
        <div>
            <template v-if="isLoading">
                <Espera />
            </template>
            <template v-else>
                <div class="card">
                    <addRoomType ref="addRef" @refresh="refreshList" />
                    <listRoomType ref="listRef" @edit="handleEdit" />
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
import listRoomType from './Desarrollo/listRoomType.vue';
import addRoomType from './Desarrollo/addRoomType.vue';

const isLoading = ref(true);
const listRef = ref<InstanceType<typeof listRoomType>>();
const addRef = ref<InstanceType<typeof addRoomType>>();

const refreshList = () => {
    if (listRef.value) {
        listRef.value.fetchRoomTypes();
    }
};

const handleEdit = (id: string) => {
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