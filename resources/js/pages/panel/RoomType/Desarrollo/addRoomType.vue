<template>
    <Toolbar class="mb-6">
        <template #start>
            <Button label="Nuevo" icon="pi pi-plus" class="mr-2" @click="openNew" severity="contrast" />
        </template>
    </Toolbar>

    <Dialog v-model:visible="productDialog" :style="{ width: '700px' }"
        :header="isEditing ? 'Editar Tipo de Habitación' : 'Registrar Tipo de Habitación'" :modal="true">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-2">
                <label for="name" class="font-semibold">Nombre <span class="text-red-500">*</span></label>
                <InputText id="name" v-model="roomType.name" :invalid="!!errors.name" />
                <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <label for="description" class="font-semibold">Descripción</label>
                <Textarea id="description" v-model="roomType.description" rows="3" />
                <small v-if="errors.description" class="text-red-500">{{ errors.description }}</small>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-2">
                    <label for="capacity" class="font-semibold">Capacidad <span class="text-red-500">*</span></label>
                    <InputNumber id="capacity" v-model="roomType.capacity" :min="1" :invalid="!!errors.capacity" />
                    <small v-if="errors.capacity" class="text-red-500">{{ errors.capacity }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="max_capacity" class="font-semibold">Capacidad Máxima</label>
                    <InputNumber id="max_capacity" v-model="roomType.max_capacity" :min="1"
                        :invalid="!!errors.max_capacity" />
                    <small v-if="errors.max_capacity" class="text-red-500">{{ errors.max_capacity }}</small>
                    <small class="text-gray-500">Debe ser mayor o igual a la capacidad</small>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label for="category" class="font-semibold">Categoría</label>
                <Select id="category" v-model="roomType.category" :options="categories"
                    placeholder="Seleccione una categoría" :invalid="!!errors.category" />
                <small v-if="errors.category" class="text-red-500">{{ errors.category }}</small>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox id="is_active" v-model="roomType.is_active" :binary="true" />
                <label for="is_active">Activo</label>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" :disabled="loading"
                severity="secondary" />
            <Button :label="isEditing ? 'Actualizar' : 'Guardar'" icon="pi pi-check" @click="saveProduct"
                severity="contrast" :loading="loading" />
        </template>
    </Dialog>

    <Toast />
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoomTypeStore } from '../stores/roomType.store';
import { ROOM_TYPE_CATEGORIES } from '../interfaces';
import type { IRoomTypeForm, IRoomTypeFormErrors } from '../interfaces';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Select from 'primevue/select';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const emit = defineEmits<{
    refresh: []
}>();

const roomTypeStore = useRoomTypeStore();

const productDialog = ref(false);
const loading = ref(false);
const errors = ref<IRoomTypeFormErrors>({});
const isEditing = ref(false);
const editingId = ref<string | null>(null);

const categories = ref<string[]>(ROOM_TYPE_CATEGORIES);

const roomType = ref<IRoomTypeForm>({
    name: '',
    code: '',
    description: '',
    capacity: 1,
    max_capacity: null,
    category: null,
    is_active: true
});

const openNew = () => {
    isEditing.value = false;
    editingId.value = null;
    resetForm();
    productDialog.value = true;
};

const openEdit = async (id: string) => {
    try {
        isEditing.value = true;
        editingId.value = id;
        loading.value = true;

        const data = await roomTypeStore.fetchRoomTypeById(id);

        roomType.value = {
            name: data.name,
            code: data.code,
            description: data.description,
            capacity: data.capacity,
            max_capacity: data.max_capacity,
            category: data.category,
            is_active: data.is_active
        };

        productDialog.value = true;
    } catch (error: any) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Error al cargar los datos del tipo de habitación',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const hideDialog = () => {
    productDialog.value = false;
    resetForm();
};

const resetForm = () => {
    roomType.value = {
        name: '',
        code: '',
        description: '',
        capacity: 1,
        max_capacity: null,
        category: null,
        is_active: true
    };
    errors.value = {};
    isEditing.value = false;
    editingId.value = null;
};

const saveProduct = async () => {
    try {
        loading.value = true;
        errors.value = {};

        let response;
        if (isEditing.value && editingId.value) {
            response = await roomTypeStore.updateRoomType(editingId.value, roomType.value);
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: response.message || 'Tipo de habitación actualizado correctamente',
                life: 3000
            });
        } else {
            response = await roomTypeStore.createRoomType(roomType.value);
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: response.message || 'Tipo de habitación creado correctamente',
                life: 3000
            });
        }

        hideDialog();
        emit('refresh');
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            toast.add({
                severity: 'warn',
                summary: 'Validación',
                detail: 'Por favor verifica los campos del formulario',
                life: 3000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || 'Error al guardar el tipo de habitación',
                life: 3000
            });
        }
    } finally {
        loading.value = false;
    }
};

defineExpose({
    openEdit
});
</script>