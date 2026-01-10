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

            <div class="flex flex-col gap-2">
                <label for="capacity" class="font-semibold">Capacidad <span class="text-red-500">*</span></label>
                <InputNumber id="capacity" v-model="roomType.capacity" :min="1" :invalid="!!errors.capacity" />
                <small v-if="errors.capacity" class="text-red-500">{{ errors.capacity }}</small>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-2">
                    <label for="price_hour" class="font-semibold">Precio/Hora <span class="text-red-500">*</span></label>
                    <InputNumber id="price_hour" v-model="roomType.base_price_per_hour" mode="currency" currency="PEN"
                        locale="es-PE" :invalid="!!errors.base_price_per_hour" />
                    <small class="text-gray-500">60 minutos</small>
                    <small v-if="errors.base_price_per_hour" class="text-red-500">{{ errors.base_price_per_hour
                        }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="price_day" class="font-semibold">Precio/Día <span class="text-red-500">*</span></label>
                    <InputNumber id="price_day" v-model="roomType.base_price_per_day" mode="currency" currency="PEN"
                        locale="es-PE" :invalid="!!errors.base_price_per_day" />
                    <small class="text-gray-500">24 horas</small>
                    <small v-if="errors.base_price_per_day" class="text-red-500">{{ errors.base_price_per_day }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="price_night" class="font-semibold">Precio/Noche <span class="text-red-500">*</span></label>
                    <InputNumber id="price_night" v-model="roomType.base_price_per_night" mode="currency" currency="PEN"
                        locale="es-PE" :invalid="!!errors.base_price_per_night" />
                    <small class="text-gray-500">12 horas</small>
                    <small v-if="errors.base_price_per_night" class="text-red-500">{{ errors.base_price_per_night
                        }}</small>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox id="is_active" v-model="roomType.is_active" :binary="true" />
                <label for="is_active">Activo</label>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" :disabled="loading"  severity="secondary" />
            <Button :label="isEditing ? 'Actualizar' : 'Guardar'" icon="pi pi-check" @click="saveProduct" severity="contrast"
                :loading="loading" />
        </template>
    </Dialog>

    <Toast />
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const emit = defineEmits(['refresh']);

const productDialog = ref(false);
const loading = ref(false);
const errors = ref({});
const isEditing = ref(false);
const editingId = ref(null);

const roomType = ref({
    name: '',
    description: '',
    capacity: 1,
    base_price_per_hour: 0,
    base_price_per_day: 0,
    base_price_per_night: 0,
    is_active: true
});

const openNew = () => {
    isEditing.value = false;
    editingId.value = null;
    resetForm();
    productDialog.value = true;
};

const openEdit = async (id) => {
    try {
        isEditing.value = true;
        editingId.value = id;
        loading.value = true;

        const response = await axios.get(`/room-types/${id}`);
        roomType.value = { ...response.data.data };

        productDialog.value = true;
    } catch (error) {
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
        description: '',
        capacity: 1,
        base_price_per_hour: 0,
        base_price_per_day: 0,
        base_price_per_night: 0,
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
        if (isEditing.value) {
            response = await axios.put(`/room-types/${editingId.value}`, roomType.value);
        } else {
            response = await axios.post('/room-types', roomType.value);
        }

        toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: response.data.message,
            life: 3000
        });

        hideDialog();
        emit('refresh');
    } catch (error) {
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