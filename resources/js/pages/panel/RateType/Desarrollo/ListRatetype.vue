<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" severity="contrast" class="mr-2" @click="openNew" />
        </template>
        <template #end>
        </template>
    </Toolbar>

    <DataTable ref="dt" :value="rateTypeStore.rateTypes" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
        :filters="filters" stripedRows responsiveLayout="scroll" :loading="rateTypeStore.isLoading"
        class="p-datatable-sm"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} tipos de tarifa">
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <h4 class="m-0">Gestionar Tipos de Tarifa</h4>

                <div class="flex items-center gap-2">
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                    </IconField>

                    <Button icon="pi pi-refresh" severity="contrast" rounded variant="outlined"
                        @click="fetchRateTypes" />
                </div>
            </div>
        </template>
        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
        <Column field="code" header="Código" :sortable="true" style="min-width: 12rem">
            <template #body="{ data }">
                <Tag :value="data.code" severity="info" />
            </template>
        </Column>

        <Column field="name" header="Nombre" :sortable="true" style="min-width: 16rem" />

        <Column field="description" header="Descripción" style="min-width: 16rem">
            <template #body="{ data }">
                {{ data.description || '-' }}
            </template>
        </Column>

        <Column field="is_active" header="Estado" :sortable="true" style="min-width: 12rem">
            <template #body="{ data }">
                <Tag :value="data.is_active ? 'Activo' : 'Inactivo'"
                    :severity="data.is_active ? 'success' : 'danger'" />
            </template>
        </Column>

        <Column field="created_at" header="Fecha creación" :sortable="true" style="min-width: 12rem">
            <template #body="{ data }">
                {{ formatDate(data.created_at) }}
            </template>
        </Column>

        <Column :exportable="false" style="min-width: 12rem">
            <template #body="{ data }">
                <Button icon="pi pi-pencil" variant="outlined" rounded class="mr-2" severity="info"
                    v-tooltip.top="'Editar'" @click="editRateType(data.id)" />
                <Button icon="pi pi-trash" variant="outlined" rounded severity="danger" v-tooltip.top="'Eliminar'"
                    @click="confirmDelete(data)" />
            </template>
        </Column>

        <template #empty>
            <div class="text-center p-4">
                <i class="pi pi-inbox" style="font-size: 3rem; color: var(--surface-400);"></i>
                <p class="mt-3 text-500">No se encontraron tipos de tarifa</p>
            </div>
        </template>
    </DataTable>

    <ConfirmDialog />
    <Toast />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useRateTypeStore } from '../stores/rateType.store';
import type { RateType } from '../interfaces/rateType.interface';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import Toolbar from 'primevue/toolbar';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';

const emit = defineEmits<{
    edit: [id: number];
}>();

const rateTypeStore = useRateTypeStore();
const confirm = useConfirm();
const toast = useToast();
const dt = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const fetchRateTypes = async () => {
    try {
        await rateTypeStore.fetchRateTypes();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudieron cargar los tipos de tarifa',
            life: 3000
        });
    }
};

const openNew = () => {
    emit('edit', 0);
};

const editRateType = (id: number) => {
    emit('edit', id);
};

const confirmDelete = (rateType: RateType) => {
    confirm.require({
        message: `¿Está seguro de eliminar el tipo de tarifa "${rateType.name}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await rateTypeStore.deleteRateType(rateType.id);
                toast.add({
                    severity: 'success',
                    summary: 'Eliminado',
                    detail: 'Tipo de tarifa eliminado correctamente',
                    life: 3000
                });
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'No se pudo eliminar el tipo de tarifa',
                    life: 3000
                });
            }
        }
    });
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    fetchRateTypes();
});

defineExpose({
    fetchRateTypes
});
</script>