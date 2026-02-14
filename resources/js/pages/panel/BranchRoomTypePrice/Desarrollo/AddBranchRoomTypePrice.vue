<template>
  <Dialog
    v-model:visible="visible"
    :style="{ width: '600px' }"
    header="Nueva Configuración de Precio"
    :modal="true"
    class="p-fluid"
  >
    <div class="flex flex-col gap-6">
      <!-- Sub-sucursal -->
      <div>
        <label for="sub_branch_id" class="block font-bold mb-3">
          Sub-sucursal <span class="text-red-500">*</span>
        </label>
        <Select
          id="sub_branch_id"
          v-model="form.sub_branch_id"
          :options="subBranches"
          optionLabel="name"
          optionValue="id"
          placeholder="Seleccione una sub-sucursal"
          :invalid="submitted && !form.sub_branch_id"
          fluid
        >
          <template #option="slotProps">
            <div class="flex items-center">
              <span>{{ slotProps.option.name }}</span>
              <Tag :value="slotProps.option.code" severity="secondary" class="ml-2" />
            </div>
          </template>
        </Select>
        <small v-if="submitted && !form.sub_branch_id" class="text-red-500">
          La sub-sucursal es obligatoria
        </small>
        <small v-if="errors.sub_branch_id" class="text-red-500">
          {{ errors.sub_branch_id }}
        </small>
      </div>

      <!-- Tipo de Habitación -->
      <div>
        <label for="room_type_id" class="block font-bold mb-3">
          Tipo de Habitación <span class="text-red-500">*</span>
        </label>
        <Select
          id="room_type_id"
          v-model="form.room_type_id"
          :options="roomTypes"
          optionLabel="name"
          optionValue="id"
          placeholder="Seleccione un tipo de habitación"
          :invalid="submitted && !form.room_type_id"
          fluid
        >
          <template #option="slotProps">
            <div class="flex items-center">
              <span>{{ slotProps.option.name }}</span>
              <Tag :value="slotProps.option.code" severity="secondary" class="ml-2" />
            </div>
          </template>
        </Select>
        <small v-if="submitted && !form.room_type_id" class="text-red-500">
          El tipo de habitación es obligatorio
        </small>
        <small v-if="errors.room_type_id" class="text-red-500">
          {{ errors.room_type_id }}
        </small>
      </div>

      <!-- Tipo de Tarifa -->
      <div>
        <label for="rate_type_id" class="block font-bold mb-3">
          Tipo de Tarifa <span class="text-red-500">*</span>
        </label>
        <Select
          id="rate_type_id"
          v-model="form.rate_type_id"
          :options="rateTypes"
          optionLabel="name"
          optionValue="id"
          placeholder="Seleccione un tipo de tarifa"
          :invalid="submitted && !form.rate_type_id"
          fluid
        >
          <template #option="slotProps">
            <div class="flex items-center">
              <span>{{ slotProps.option.name }}</span>
              <Tag :value="slotProps.option.code" severity="secondary" class="ml-2" />
            </div>
          </template>
        </Select>
        <small v-if="submitted && !form.rate_type_id" class="text-red-500">
          El tipo de tarifa es obligatorio
        </small>
        <small v-if="errors.rate_type_id" class="text-red-500">
          {{ errors.rate_type_id }}
        </small>
      </div>

      <!-- Fechas de vigencia -->
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-6">
          <label for="effective_from" class="block font-bold mb-3">
            Vigencia Desde <span class="text-red-500">*</span>
          </label>
          <DatePicker
            id="effective_from"
            v-model="form.effective_from"
            dateFormat="yy-mm-dd"
            :invalid="submitted && !form.effective_from"
            fluid
          />
          <small v-if="submitted && !form.effective_from" class="text-red-500">
            La fecha de inicio es obligatoria
          </small>
          <small v-if="errors.effective_from" class="text-red-500">
            {{ errors.effective_from }}
          </small>
        </div>

        <div class="col-span-6">
          <label for="effective_to" class="block font-bold mb-3">
            Vigencia Hasta
            <small class="text-muted">(Opcional)</small>
          </label>
          <DatePicker
            id="effective_to"
            v-model="form.effective_to"
            dateFormat="yy-mm-dd"
            :minDate="form.effective_from"
            :invalid="!!errors.effective_to"
            fluid
          />
          <small class="text-gray-500">Dejar en blanco para vigencia sin límite</small>
          <small v-if="errors.effective_to" class="text-red-500 block">
            {{ errors.effective_to }}
          </small>
        </div>
      </div>

      <!-- Estado -->
      <div class="flex items-center gap-2">
        <Checkbox
          id="is_active"
          v-model="form.is_active"
          :binary="true"
        />
        <label for="is_active">Configuración activa</label>
      </div>

      <!-- Alert de error general -->
      <Message v-if="error" severity="error" :closable="false">
        <template #icon>
          <i class="pi pi-exclamation-triangle"></i>
        </template>
        {{ error }}
      </Message>
    </div>

    <template #footer>
      <Button
        label="Cancelar"
        icon="pi pi-times"
        text
        @click="handleCancel"
        :disabled="loading"
        severity="secondary"
      />
      <Button
        label="Guardar"
        icon="pi pi-check"
        @click="handleSubmit"
        :loading="loading"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { useBranchRoomTypePriceStore } from '../stores/branchRoomTypePrice.store';
import { useToast } from 'primevue/usetoast';
import type { IBranchRoomTypePriceForm } from '../interfaces/branchRoomTypePrice.interface';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

// Props
interface Props {
  subBranches?: any[];
  roomTypes?: any[];
  rateTypes?: any[];
  visible: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  subBranches: () => [],
  roomTypes: () => [],
  rateTypes: () => [],
  visible: false,
});

// Emits
const emit = defineEmits<{
  'update:visible': [value: boolean];
  cancel: [];
  success: [data: any];
}>();

// Store & Toast
const store = useBranchRoomTypePriceStore();
const toast = useToast();

// State
const submitted = ref(false);
const form = reactive<IBranchRoomTypePriceForm>({
  sub_branch_id: '',
  room_type_id: '',
  rate_type_id: '',
  effective_from: '',
  effective_to: null,
  is_active: true,
});

const errors = reactive<Record<string, string>>({
  sub_branch_id: '',
  room_type_id: '',
  rate_type_id: '',
  effective_from: '',
  effective_to: '',
});

// Computed
const loading = computed(() => store.loading);
const error = computed(() => store.error);
const visible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
});

// Methods
const validateForm = (): boolean => {
  // Limpiar errores
  Object.keys(errors).forEach((key) => {
    errors[key] = '';
  });

  let isValid = true;

  if (!form.sub_branch_id) {
    errors.sub_branch_id = 'La sub-sucursal es obligatoria';
    isValid = false;
  }

  if (!form.room_type_id) {
    errors.room_type_id = 'El tipo de habitación es obligatorio';
    isValid = false;
  }

  if (!form.rate_type_id) {
    errors.rate_type_id = 'El tipo de tarifa es obligatorio';
    isValid = false;
  }

  if (!form.effective_from) {
    errors.effective_from = 'La fecha de inicio es obligatoria';
    isValid = false;
  }

  if (form.effective_to && form.effective_from) {
    if (new Date(form.effective_to) < new Date(form.effective_from)) {
      errors.effective_to = 'La fecha de fin debe ser posterior a la fecha de inicio';
      isValid = false;
    }
  }

  return isValid;
};

const handleSubmit = async () => {
  submitted.value = true;

  if (!validateForm()) {
    return;
  }

  try {
    const response = await store.createBranchRoomTypePrice(form);
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Configuración de precio creada correctamente',
      life: 3000,
    });
    
    // Reset form
    resetForm();
    emit('success', response);
    visible.value = false;
  } catch (err: any) {
    // Los errores del servidor se mostrarán automáticamente desde el store
    if (err.response?.data?.errors) {
      Object.keys(err.response.data.errors).forEach((key) => {
        errors[key] = err.response.data.errors[key][0];
      });
    }
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo crear la configuración de precio',
      life: 3000,
    });
  }
};

const handleCancel = () => {
  resetForm();
  emit('cancel');
  visible.value = false;
};

const resetForm = () => {
  submitted.value = false;
  form.sub_branch_id = '';
  form.room_type_id = '';
  form.rate_type_id = '';
  form.effective_from = '';
  form.effective_to = null;
  form.is_active = true;
  
  Object.keys(errors).forEach((key) => {
    errors[key] = '';
  });
};
</script>