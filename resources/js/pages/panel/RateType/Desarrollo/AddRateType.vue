<template>
    <Dialog 
        v-model:visible="dialogVisible" 
        :header="isEditing ? 'Editar Tipo de Tarifa' : 'Nuevo Tipo de Tarifa'" 
        :modal="true"
        :style="{ width: '450px' }"
        :closable="!isLoading"
        :closeOnEscape="!isLoading"
    >
        <div class="flex flex-col gap-6">
            <div>
                <label for="name" class="block font-bold mb-3">
                    Nombre <span class="text-red-500">*</span>
                </label>
                <InputText 
                    id="name" 
                    v-model.trim="form.name" 
                    :invalid="submitted && !form.name"
                    placeholder="Ingrese el nombre"
                    :disabled="isLoading"
                    autofocus
                    fluid
                />
                <small v-if="submitted && !form.name" class="text-red-500">El nombre es obligatorio.</small>
                <small v-else-if="errors.name" class="text-red-500">{{ errors.name }}</small>
            </div>

            <div>
                <label for="code" class="block font-bold mb-3">
                    Código <span class="text-red-500">*</span>
                </label>
                <InputText 
                    id="code" 
                    v-model.trim="form.code" 
                    :invalid="submitted && !form.code"
                    placeholder="Ej: NORMAL, ESPECIAL"
                    :disabled="isLoading"
                    @input="form.code = form.code.toUpperCase()"
                    fluid
                />
                <small v-if="submitted && !form.code" class="text-red-500">El código es obligatorio.</small>
                <small v-else-if="errors.code" class="text-red-500">{{ errors.code }}</small>
                <small v-else class="text-surface-500">Solo letras, números, guiones y guiones bajos</small>
            </div>

            <div>
                <label for="description" class="block font-bold mb-3">Descripción</label>
                <Textarea 
                    id="description" 
                    v-model="form.description" 
                    rows="3"
                    placeholder="Descripción del tipo de tarifa (opcional)"
                    :disabled="isLoading"
                    fluid
                />
            </div>

            <div class="flex items-center gap-2">
                <Checkbox 
                    inputId="is_active" 
                    v-model="form.is_active" 
                    :binary="true" 
                    :disabled="isLoading"
                />
                <label for="is_active">Activo</label>
            </div>
        </div>

        <template #footer>
            <Button 
                label="Cancelar" 
                icon="pi pi-times" 
                text
                @click="closeDialog"
                :disabled="isLoading"
                severity="secondary"
            />
            <Button 
                :label="isEditing ? 'Actualizar' : 'Guardar'" 
                icon="pi pi-check"
                @click="onSubmit"
                :loading="isLoading"
            />
        </template>
    </Dialog>
    <Toast />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import { useRateTypeStore } from '../stores/rateType.store';
import type { RateTypeFormData } from '../interfaces/rateType.interface';

const emit = defineEmits<{
    refresh: [];
}>();

const rateTypeStore = useRateTypeStore();
const toast = useToast();

const dialogVisible = ref(false);
const isLoading = ref(false);
const editingId = ref<number | null>(null);
const submitted = ref(false);
const isEditing = computed(() => editingId.value !== null);

const form = ref<RateTypeFormData>({
    name: '',
    code: '',
    description: '',
    is_active: true
});

const errors = ref<Partial<Record<keyof RateTypeFormData, string>>>({});

const openEdit = async (id: number) => {
    if (id === 0) {
        // Nuevo
        resetForm();
        editingId.value = null;
        dialogVisible.value = true;
    } else {
        // Editar
        isLoading.value = true;
        try {
            const rateType = rateTypeStore.rateTypes.find(rt => rt.id === id);
            if (rateType) {
                form.value = {
                    name: rateType.name,
                    code: rateType.code,
                    description: rateType.description || '',
                    is_active: rateType.is_active
                };
                editingId.value = id;
                dialogVisible.value = true;
            }
        } catch (error) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo cargar el tipo de tarifa',
                life: 3000
            });
        } finally {
            isLoading.value = false;
        }
    }
};

const validateForm = (): boolean => {
    errors.value = {};
    let isValid = true;

    if (!form.value.name?.trim()) {
        errors.value.name = 'El nombre es obligatorio';
        isValid = false;
    }

    if (!form.value.code?.trim()) {
        errors.value.code = 'El código es obligatorio';
        isValid = false;
    } else if (!/^[A-Z0-9_-]+$/.test(form.value.code)) {
        errors.value.code = 'El código solo puede contener letras mayúsculas, números, guiones y guiones bajos';
        isValid = false;
    }

    return isValid;
};

const onSubmit = async () => {
    submitted.value = true;
    
    if (!form.value.name?.trim() || !form.value.code?.trim()) {
        return;
    }
    
    if (!validateForm()) return;

    isLoading.value = true;
    
    try {
        if (isEditing.value && editingId.value) {
            await rateTypeStore.updateRateType(editingId.value, form.value);
            toast.add({
                severity: 'success',
                summary: 'Actualizado',
                detail: 'Tipo de tarifa actualizado correctamente',
                life: 3000
            });
        } else {
            await rateTypeStore.createRateType(form.value);
            toast.add({
                severity: 'success',
                summary: 'Creado',
                detail: 'Tipo de tarifa creado correctamente',
                life: 3000
            });
        }
        closeDialog();
        emit('refresh');
    } catch (error: any) {
        const message = error.response?.data?.message || 'Ocurrió un error al guardar';
        const apiErrors = error.response?.data?.errors;
        
        if (apiErrors) {
            Object.keys(apiErrors).forEach(key => {
                if (key in form.value) {
                    errors.value[key as keyof RateTypeFormData] = apiErrors[key][0];
                }
            });
        }
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 3000
        });
    } finally {
        isLoading.value = false;
    }
};

const closeDialog = () => {
    dialogVisible.value = false;
    resetForm();
};

const resetForm = () => {
    form.value = {
        name: '',
        code: '',
        description: '',
        is_active: true
    };
    errors.value = {};
    editingId.value = null;
    submitted.value = false;
};

defineExpose({
    openEdit
});
</script>