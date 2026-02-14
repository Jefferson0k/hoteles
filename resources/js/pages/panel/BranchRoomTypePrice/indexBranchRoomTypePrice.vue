<template>
  <Head title="Configuración de Precios" />
  <AppLayout>
    <div>
      <template v-if="isLoading">
        <Espera />
      </template>
      <template v-else>
        <div class="card">
          <div class="card-body">
            <!-- Header -->
            <div class="page-header mb-4">
              <h2>
                <i class="bi bi-currency-dollar"></i> Gestión de Configuración de Precios
              </h2>
              <p class="text-muted">
                Administre las configuraciones de precios por sub-sucursal, tipo de habitación
                y tipo de tarifa
              </p>
            </div>

            <!-- Vista Listar -->
            <ListarBranchRoomTypePrice
              v-if="currentView === 'list'"
              :sub-branches="subBranches"
              :room-types="roomTypes"
              :rate-types="rateTypes"
              @add="showAddView"
              @edit="showEditView"
              @delete="showDeleteView"
              @view="showViewDetails"
            />

            <!-- Vista Agregar -->
            <AddBranchRoomTypePrice
              v-else-if="currentView === 'add'"
              :sub-branches="subBranches"
              :room-types="roomTypes"
              :rate-types="rateTypes"
              @cancel="showListView"
              @success="handleAddSuccess"
            />

            <!-- Vista Editar -->
            <EditBranchRoomTypePrice
              v-else-if="currentView === 'edit'"
              :price-id="selectedPriceId"
              :sub-branches="subBranches"
              :room-types="roomTypes"
              :rate-types="rateTypes"
              @cancel="showListView"
              @success="handleEditSuccess"
            />

            <!-- Modal Eliminar -->
            <DeleteBranchRoomTypePrice
              v-if="currentView === 'delete'"
              :price-id="selectedPriceId"
              @cancel="showListView"
              @success="handleDeleteSuccess"
            />
          </div>
        </div>

        <!-- Toast de notificaciones -->
        <div
          class="toast-container position-fixed bottom-0 end-0 p-3"
          style="z-index: 9999"
        >
          <div
            v-if="toast.show"
            class="toast show"
            :class="`bg-${toast.type}`"
            role="alert"
          >
            <div class="toast-header">
              <i
                class="bi me-2"
                :class="{
                  'bi-check-circle-fill text-success': toast.type === 'success',
                  'bi-exclamation-triangle-fill text-warning': toast.type === 'warning',
                  'bi-info-circle-fill text-info': toast.type === 'info',
                  'bi-x-circle-fill text-danger': toast.type === 'danger',
                }"
              ></i>
              <strong class="me-auto">{{ toast.title }}</strong>
              <button
                type="button"
                class="btn-close"
                @click="toast.show = false"
              ></button>
            </div>
            <div class="toast-body text-white">
              {{ toast.message }}
            </div>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Espera from '@/components/Espera.vue';
import ListarBranchRoomTypePrice from './Desarrollo/ListarBranchRoomTypePrice.vue';
import AddBranchRoomTypePrice from './Desarrollo/AddBranchRoomTypePrice.vue';
import EditBranchRoomTypePrice from './Desarrollo/EditBranchRoomTypePrice.vue';
import DeleteBranchRoomTypePrice from './Desarrollo/DeleteBranchRoomTypePrice.vue';
import { subBranchService, type ISubBranch } from './services/subBranch.service';
import { rateTypeService, type IRateType } from './services/rateType.service';
import { roomTypeService, type IRoomType } from './services/roomType.service';

// Types
type ViewType = 'list' | 'add' | 'edit' | 'delete';
type ToastType = 'success' | 'warning' | 'info' | 'danger';

interface Toast {
  show: boolean;
  type: ToastType;
  title: string;
  message: string;
}

// State
const isLoading = ref(true);
const currentView = ref<ViewType>('list');
const selectedPriceId = ref<string>('');

const toast = reactive<Toast>({
  show: false,
  type: 'info',
  title: '',
  message: '',
});

// Catálogos
const subBranches = ref<ISubBranch[]>([]);
const roomTypes = ref<IRoomType[]>([]);
const rateTypes = ref<IRateType[]>([]);

// Methods - Navegación entre vistas
const showListView = () => {
  currentView.value = 'list';
  selectedPriceId.value = '';
};

const showAddView = () => {
  currentView.value = 'add';
};

const showEditView = (priceId: string) => {
  selectedPriceId.value = priceId;
  currentView.value = 'edit';
};

const showDeleteView = (priceId: string) => {
  selectedPriceId.value = priceId;
  currentView.value = 'delete';
};

const showViewDetails = (priceId: string) => {
  // Por ahora redirigimos a editar
  showEditView(priceId);
};

// Methods - Handlers de éxito
const handleAddSuccess = (response: any) => {
  showToast('success', 'Éxito', response.message || 'Configuración creada correctamente');
  showListView();
};

const handleEditSuccess = (response: any) => {
  showToast('success', 'Éxito', response.message || 'Configuración actualizada correctamente');
  showListView();
};

const handleDeleteSuccess = (message: string) => {
  showToast('success', 'Éxito', message || 'Configuración eliminada correctamente');
  showListView();
};

// Toast notifications
const showToast = (type: ToastType, title: string, message: string) => {
  toast.type = type;
  toast.title = title;
  toast.message = message;
  toast.show = true;

  // Auto ocultar después de 5 segundos
  setTimeout(() => {
    toast.show = false;
  }, 5000);
};

// Cargar catálogos
const loadCatalogs = async () => {
  try {
    const [subBranchesData, roomTypesData, rateTypesData] = await Promise.all([
      subBranchService.search(),
      roomTypeService.getOptions(),
      rateTypeService.getOptions(),
    ]);

    subBranches.value = subBranchesData;
    roomTypes.value = roomTypesData;
    rateTypes.value = rateTypesData;
  } catch (error) {
    console.error('Error al cargar catálogos:', error);
    showToast('danger', 'Error', 'No se pudieron cargar los catálogos');
  }
};

// Lifecycle
onMounted(async () => {
  try {
    await loadCatalogs();
  } catch (error) {
    console.error('Error en onMounted:', error);
  } finally {
    setTimeout(() => {
      isLoading.value = false;
    }, 1000);
  }
});
</script>
