<template>
  <div class="edit-branch-room-type-price">
    <div class="card">
      <div class="card-header bg-warning">
        <h5 class="mb-0">
          <i class="bi bi-pencil"></i> Editar Configuración de Precio
        </h5>
      </div>
      <div class="card-body">
        <!-- Loading inicial -->
        <div v-if="initialLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <p class="mt-2">Cargando datos...</p>
        </div>

        <form v-else @submit.prevent="handleSubmit">
          <!-- Sub-sucursal -->
          <div class="mb-3">
            <label for="sub_branch_id" class="form-label">
              Sub-sucursal <span class="text-danger">*</span>
            </label>
            <select
              id="sub_branch_id"
              v-model="form.sub_branch_id"
              class="form-select"
              :class="{ 'is-invalid': errors.sub_branch_id }"
              required
            >
              <option value="">Seleccione una sub-sucursal</option>
              <option
                v-for="subBranch in subBranches"
                :key="subBranch.id"
                :value="subBranch.id"
              >
                {{ subBranch.name }} ({{ subBranch.code }})
              </option>
            </select>
            <div v-if="errors.sub_branch_id" class="invalid-feedback">
              {{ errors.sub_branch_id }}
            </div>
          </div>

          <!-- Tipo de Habitación -->
          <div class="mb-3">
            <label for="room_type_id" class="form-label">
              Tipo de Habitación <span class="text-danger">*</span>
            </label>
            <select
              id="room_type_id"
              v-model="form.room_type_id"
              class="form-select"
              :class="{ 'is-invalid': errors.room_type_id }"
              required
            >
              <option value="">Seleccione un tipo de habitación</option>
              <option
                v-for="roomType in roomTypes"
                :key="roomType.id"
                :value="roomType.id"
              >
                {{ roomType.name }} ({{ roomType.code }})
              </option>
            </select>
            <div v-if="errors.room_type_id" class="invalid-feedback">
              {{ errors.room_type_id }}
            </div>
          </div>

          <!-- Tipo de Tarifa -->
          <div class="mb-3">
            <label for="rate_type_id" class="form-label">
              Tipo de Tarifa <span class="text-danger">*</span>
            </label>
            <select
              id="rate_type_id"
              v-model="form.rate_type_id"
              class="form-select"
              :class="{ 'is-invalid': errors.rate_type_id }"
              required
            >
              <option value="">Seleccione un tipo de tarifa</option>
              <option
                v-for="rateType in rateTypes"
                :key="rateType.id"
                :value="rateType.id"
              >
                {{ rateType.name }} ({{ rateType.code }})
              </option>
            </select>
            <div v-if="errors.rate_type_id" class="invalid-feedback">
              {{ errors.rate_type_id }}
            </div>
          </div>

          <!-- Fechas de vigencia -->
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="effective_from" class="form-label">
                  Vigencia Desde <span class="text-danger">*</span>
                </label>
                <input
                  id="effective_from"
                  v-model="form.effective_from"
                  type="date"
                  class="form-control"
                  :class="{ 'is-invalid': errors.effective_from }"
                  required
                />
                <div v-if="errors.effective_from" class="invalid-feedback">
                  {{ errors.effective_from }}
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="effective_to" class="form-label">
                  Vigencia Hasta
                  <small class="text-muted">(Opcional)</small>
                </label>
                <input
                  id="effective_to"
                  v-model="form.effective_to"
                  type="date"
                  class="form-control"
                  :class="{ 'is-invalid': errors.effective_to }"
                  :min="form.effective_from"
                />
                <div v-if="errors.effective_to" class="invalid-feedback">
                  {{ errors.effective_to }}
                </div>
                <small class="form-text text-muted">
                  Dejar en blanco para vigencia sin límite
                </small>
              </div>
            </div>
          </div>

          <!-- Estado -->
          <div class="mb-3">
            <div class="form-check form-switch">
              <input
                id="is_active"
                v-model="form.is_active"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="is_active">
                Configuración activa
              </label>
            </div>
          </div>

          <!-- Información adicional -->
          <div v-if="currentPrice" class="alert alert-info">
            <h6 class="alert-heading">
              <i class="bi bi-info-circle"></i> Información
            </h6>
            <ul class="mb-0">
              <li>
                <strong>Creado:</strong>
                {{ formatDateTime(currentPrice.created_at) }}
              </li>
              <li v-if="currentPrice.updated_at">
                <strong>Última actualización:</strong>
                {{ formatDateTime(currentPrice.updated_at) }}
              </li>
              <li>
                <strong>Estado actual:</strong>
                <span
                  class="badge ms-1"
                  :class="
                    currentPrice.is_currently_effective
                      ? 'bg-success'
                      : currentPrice.has_expired
                      ? 'bg-warning'
                      : 'bg-secondary'
                  "
                >
                  {{
                    currentPrice.is_currently_effective
                      ? 'Vigente'
                      : currentPrice.has_expired
                      ? 'Expirado'
                      : 'Pendiente'
                  }}
                </span>
              </li>
            </ul>
          </div>

          <!-- Alert de error general -->
          <div v-if="error" class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ error }}
          </div>

          <!-- Botones -->
          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-secondary"
              :disabled="loading"
              @click="$emit('cancel')"
            >
              <i class="bi bi-x-circle"></i> Cancelar
            </button>
            <button type="submit" class="btn btn-warning" :disabled="loading">
              <span
                v-if="loading"
                class="spinner-border spinner-border-sm me-2"
                role="status"
              ></span>
              <i v-else class="bi bi-save"></i>
              {{ loading ? 'Actualizando...' : 'Actualizar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { useBranchRoomTypePriceStore } from '../stores/branchRoomTypePrice.store';
import type { IBranchRoomTypePriceForm } from '../interfaces/branchRoomTypePrice.interface';

// Props
interface Props {
  priceId: string;
  subBranches?: any[];
  roomTypes?: any[];
  rateTypes?: any[];
}

const props = withDefaults(defineProps<Props>(), {
  subBranches: () => [],
  roomTypes: () => [],
  rateTypes: () => [],
});

// Emits
const emit = defineEmits<{
  cancel: [];
  success: [data: any];
}>();

// Store
const store = useBranchRoomTypePriceStore();

// State
const initialLoading = ref(true);

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
const currentPrice = computed(() => store.currentBranchRoomTypePrice);

// Methods
const loadPrice = async () => {
  try {
    initialLoading.value = true;
    const price = await store.fetchBranchRoomTypePriceById(props.priceId);
    
    // Llenar el formulario con los datos
    form.sub_branch_id = price.sub_branch_id;
    form.room_type_id = price.room_type_id;
    form.rate_type_id = price.rate_type_id;
    form.effective_from = price.effective_from;
    form.effective_to = price.effective_to;
    form.is_active = price.is_active;
  } catch (err) {
    console.error('Error al cargar el precio:', err);
  } finally {
    initialLoading.value = false;
  }
};

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
  if (!validateForm()) {
    return;
  }

  try {
    const response = await store.updateBranchRoomTypePrice(props.priceId, form);
    emit('success', response);
  } catch (err: any) {
    // Los errores del servidor se mostrarán automáticamente desde el store
    if (err.response?.data?.errors) {
      Object.keys(err.response.data.errors).forEach((key) => {
        errors[key] = err.response.data.errors[key][0];
      });
    }
  }
};

const formatDateTime = (dateTime: string | undefined) => {
  if (!dateTime) return '';
  const d = new Date(dateTime);
  return d.toLocaleString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Lifecycle
onMounted(() => {
  loadPrice();
});
</script>
