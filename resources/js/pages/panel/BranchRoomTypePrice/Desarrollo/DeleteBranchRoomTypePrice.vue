<template>
  <Dialog
    v-model:visible="visible"
    :style="{ width: '500px' }"
    header="Eliminar Configuración de Precio"
    :modal="true"
  >
    <!-- Loading inicial -->
    <div v-if="initialLoading" class="flex justify-center items-center py-8">
      <ProgressSpinner style="width: 50px; height: 50px" />
    </div>

    <!-- Contenido -->
    <div v-else>
      <Message severity="warn" :closable="false">
        <template #icon>
          <i class="pi pi-exclamation-triangle"></i>
        </template>
        <strong>¡Advertencia!</strong> Esta acción no se puede deshacer.
      </Message>

      <p class="my-4">
        ¿Está seguro que desea eliminar la siguiente configuración de precio?
      </p>

      <div v-if="currentPrice" class="surface-border border rounded p-4">
        <div class="grid grid-cols-12 gap-3 text-sm">
          <div class="col-span-12">
            <strong>Sub-sucursal:</strong>
            {{ currentPrice.sub_branch?.name }}
            <Tag
              :value="currentPrice.sub_branch?.code"
              severity="secondary"
              class="ml-2"
            />
          </div>
          <div class="col-span-12">
            <strong>Tipo de Habitación:</strong>
            {{ currentPrice.room_type?.name }}
            <Tag
              :value="currentPrice.room_type?.code"
              severity="secondary"
              class="ml-2"
            />
          </div>
          <div class="col-span-12">
            <strong>Tipo de Tarifa:</strong>
            {{ currentPrice.rate_type?.name }}
            <Tag
              :value="currentPrice.rate_type?.code"
              severity="secondary"
              class="ml-2"
            />
          </div>
          <div class="col-span-6">
            <strong>Vigencia Desde:</strong><br />
            <i class="pi pi-calendar mr-1"></i>
            {{ formatDate(currentPrice.effective_from) }}
          </div>
          <div class="col-span-6">
            <strong>Vigencia Hasta:</strong><br />
            <i class="pi pi-calendar mr-1"></i>
            {{ currentPrice.effective_to ? formatDate(currentPrice.effective_to) : 'Sin límite' }}
          </div>
          <div class="col-span-6">
            <strong>Estado:</strong><br />
            <Tag
              :value="currentPrice.is_active ? 'Activo' : 'Inactivo'"
              :severity="currentPrice.is_active ? 'success' : 'secondary'"
            />
          </div>
          <div class="col-span-6">
            <strong>Efectividad:</strong><br />
            <Tag
              :value="
                currentPrice.is_currently_effective
                  ? 'Vigente'
                  : currentPrice.has_expired
                  ? 'Expirado'
                  : 'Pendiente'
              "
              :severity="
                currentPrice.is_currently_effective
                  ? 'info'
                  : currentPrice.has_expired
                  ? 'warn'
                  : 'secondary'
              "
            />
          </div>
        </div>
      </div>

      <!-- Información adicional si hay rangos de precio -->
      <Message
        v-if="currentPrice?.pricing_ranges && currentPrice.pricing_ranges.length > 0"
        severity="info"
        :closable="false"
        class="mt-4"
      >
        <template #icon>
          <i class="pi pi-info-circle"></i>
        </template>
        <strong>Nota:</strong> Esta configuración tiene
        {{ currentPrice.pricing_ranges.length }} rango(s) de precio asociado(s) que también
        serán eliminados.
      </Message>

      <!-- Error -->
      <Message v-if="error" severity="error" :closable="false" class="mt-4">
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
        :disabled="loading || initialLoading"
        severity="secondary"
      />
      <Button
        label="Eliminar"
        icon="pi pi-trash"
        @click="handleDelete"
        :loading="loading"
        :disabled="initialLoading"
        severity="danger"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useBranchRoomTypePriceStore } from '../stores/branchRoomTypePrice.store';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

// Props
interface Props {
  priceId: string;
  visible: boolean;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
  'update:visible': [value: boolean];
  cancel: [];
  success: [message: string];
}>();

// Store & Toast
const store = useBranchRoomTypePriceStore();
const toast = useToast();

// State
const initialLoading = ref(false);

// Computed
const loading = computed(() => store.loading);
const error = computed(() => store.error);
const currentPrice = computed(() => store.currentBranchRoomTypePrice);
const visible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
});

// Methods
const loadPrice = async () => {
  try {
    initialLoading.value = true;
    await store.fetchBranchRoomTypePriceById(props.priceId);
  } catch (err) {
    console.error('Error al cargar el precio:', err);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la configuración de precio',
      life: 3000,
    });
  } finally {
    initialLoading.value = false;
  }
};

const handleDelete = async () => {
  try {
    const response = await store.deleteBranchRoomTypePrice(props.priceId);
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: response.message || 'Configuración de precio eliminada correctamente',
      life: 3000,
    });
    
    emit('success', response.message);
    visible.value = false;
  } catch (err) {
    console.error('Error al eliminar:', err);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo eliminar la configuración de precio',
      life: 3000,
    });
  }
};

const handleCancel = () => {
  emit('cancel');
  visible.value = false;
};

const formatDate = (date: string) => {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

// Watch para cargar datos cuando se abre el modal
watch(
  () => props.visible,
  (newVal) => {
    if (newVal) {
      loadPrice();
    }
  }
);
</script>