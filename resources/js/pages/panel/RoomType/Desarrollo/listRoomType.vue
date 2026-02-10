<template>
    <DataTable ref="dt" v-model:selection="selectedProducts" :value="roomTypes" dataKey="id" :paginator="true"
        :rows="10" :filters="filters" :loading="loading"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 25]"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tipos de habitación"
        class="p-datatable-sm">
        <template #header>
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <h4 class="m-0">Tipos de Habitación</h4>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                </IconField>
            </div>
        </template>

        <template #empty>
            <div class="text-center p-4">No se encontraron tipos de habitación</div>
        </template>

        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        <Column field="code" header="Código" sortable style="min-width: 10rem"></Column>
        <Column field="name" header="Nombre" sortable style="min-width: 16rem">
            <template #body="slotProps">
                <div
                    class="truncate"
                    v-tooltip.top="slotProps.data.name"
                    style="max-width: 16rem;"
                >
                    {{ slotProps.data.name }}
                </div>
            </template>
        </Column>
        <Column field="description" header="Descripción" sortable style="min-width: 16rem">
            <template #body="slotProps">
                <div
                    class="truncate"
                    v-tooltip.top="slotProps.data.description"
                    style="max-width: 16rem;"
                >
                    {{ slotProps.data.description }}
                </div>
            </template>
        </Column>
        <Column field="capacity" header="Capacidad" sortable style="min-width: 10rem"></Column>
        <Column field="max_capacity" header="Capacidad Máxima" sortable style="min-width: 12rem"></Column>
        <Column field="category" header="Categoría" sortable style="min-width: 12rem"></Column>
        <Column field="is_active" header="Estado" sortable style="min-width: 10rem">
            <template #body="slotProps">
                <Tag :value="slotProps.data.is_active ? 'Activo' : 'Inactivo'"
                    :severity="slotProps.data.is_active ? 'success' : 'danger'" />
            </template>
        </Column>

        <Column :exportable="false" style="min-width: 12rem">
            <template #body="slotProps">
                <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editProduct(slotProps.data)"
                    v-tooltip.top="'Editar'" />
                <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteProduct(slotProps.data)"
                    v-tooltip.top="'Eliminar'" />
            </template>
        </Column>
    </DataTable>

    <ConfirmDialog></ConfirmDialog>
    <Toast />
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoomTypeStore } from '../stores/roomType.store';
import type { IRoomType } from '../interfaces';
import { FilterMatchMode } from '@primevue/core/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const confirm = useConfirm();
const toast = useToast();
const emit = defineEmits<{
    edit: [id: string]
}>();

const roomTypeStore = useRoomTypeStore();

const dt = ref();
const selectedProducts = ref<IRoomType[]>([]);
const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const roomTypes = computed(() => roomTypeStore.roomTypes);
const loading = computed(() => roomTypeStore.loading);

const fetchRoomTypes = async () => {
    try {
        await roomTypeStore.fetchRoomTypes({
            per_page: 100
        });
    } catch (error: any) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar tipos de habitación',
            life: 3000
        });
    }
};

const editProduct = (product: IRoomType) => {
    if (product.id) {
        emit('edit', product.id);
    }
};

const confirmDeleteProduct = (product: IRoomType) => {
    confirm.require({
        message: `¿Está seguro de eliminar el tipo de habitación "${product.name}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',
        accept: () => {
            if (product.id) {
                deleteProduct(product.id);
            }
        }
    });
};

const deleteProduct = async (id: string) => {
    try {
        const response = await roomTypeStore.deleteRoomType(id);

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.message || 'Tipo de habitación eliminado correctamente',
            life: 3000
        });

        await fetchRoomTypes();
    } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'Error al eliminar el tipo de habitación';

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorMessage,
            life: 3000
        });
    }
};

defineExpose({
    fetchRoomTypes
});

onMounted(() => {
    fetchRoomTypes();
});
</script>