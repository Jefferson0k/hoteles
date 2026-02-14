<template>
  <div>
    <!-- Filtros -->
    <div class="card mb-4">
      <Toolbar>
        <template #start>
          <h5 class="m-0">
            <i class="pi pi-filter mr-2"></i>Filtros
          </h5>
        </template>
        <template #end>
          <Button
            label="Limpiar Filtros"
            icon="pi pi-filter-slash"
            severity="secondary"
            @click="clearFilters"
          />
        </template>
      </Toolbar>

      <div class="p-4">
        <div class="grid grid-cols-12 gap-4">
          <div class="col-span-12 md:col-span-3">
            <label for="filterSubBranch" class="block mb-2 font-medium">Sub-sucursal</label>
            <Select
              id="filterSubBranch"
              v-model="filters.sub_branch_id"
              :options="subBranches"
              optionLabel="name"
              optionValue="id"
              placeholder="Todas"
              @change="applyFilters"
              fluid
              showClear
            />
          </div>

          <div class="col-span-12 md:col-span-3">
            <label for="filterRoomType" class="block mb-2 font-medium">Tipo de Habitación</label>
            <Select
              id="filterRoomType"
              v-model="filters.room_type_id"
              :options="roomTypes"
              optionLabel="name"
              optionValue="id"
              placeholder="Todos"
              @change="applyFilters"
              fluid
              showClear
            />
          </div>

          <div class="col-span-12 md:col-span-3">
            <label for="filterRateType" class="block mb-2 font-medium">Tipo de Tarifa</label>
            <Select
              id="filterRateType"
              v-model="filters.rate_type_id"
              :options="rateTypes"
              optionLabel="name"
              optionValue="id"
              placeholder="Todos"
              @change="applyFilters"
              fluid
              showClear
            />
          </div>

          <div class="col-span-12 md:col-span-3">
            <label for="filterStatus" class="block mb-2 font-medium">Estado</label>
            <Select
              id="filterStatus"
              v-model="filters.is_active"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Todos"
              @change="applyFilters"
              fluid
              showClear
            />
          </div>

          <div class="col-span-12 md:col-span-3">
            <div class="flex items-center gap-2 mt-7">
              <Checkbox
                id="filterCurrentOnly"
                v-model="filters.current_only"
                :binary="true"
                @change="applyFilters"
              />
              <label for="filterCurrentOnly">Solo vigentes actualmente</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <h4 class="m-0">
            <i class="pi pi-dollar mr-2"></i>Configuraciones de Precios
            <Tag :value="branchRoomTypePrices.length" class="ml-2" />
          </h4>
        </template>
        <template #end>
          <Button
            label="Nueva Configuración"
            icon="pi pi-plus"
            @click="handleAdd"
          />
        </template>
      </Toolbar>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center py-8">
        <ProgressSpinner style="width: 50px; height: 50px" />
      </div>

      <!-- Error -->
      <Message v-else-if="error" severity="error" :closable="false">
        <template #icon>
          <i class="pi pi-exclamation-triangle"></i>
        </template>
        {{ error }}
      </Message>

      <!-- Sin datos -->
      <div
        v-else-if="branchRoomTypePrices.length === 0"
        class="flex flex-col items-center justify-center py-12"
      >
        <i class="pi pi-inbox text-6xl text-gray-400 mb-4"></i>
        <p class="text-gray-500">No se encontraron configuraciones de precios</p>
      </div>

      <!-- DataTable -->
      <DataTable
        v-else
        ref="dt"
        :value="branchRoomTypePrices"
        dataKey="id"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 25, 50]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} configuraciones"
        responsiveLayout="scroll"
      >
        <Column field="sub_branch.name" header="Sub-sucursal" sortable style="min-width: 200px">
          <template #body="slotProps">
            <div>
              <strong>{{ slotProps.data.sub_branch?.name }}</strong>
              <br />
              <small class="text-gray-500">{{ slotProps.data.sub_branch?.code }}</small>
            </div>
          </template>
        </Column>

        <Column field="room_type.name" header="Tipo de Habitación" sortable style="min-width: 200px">
          <template #body="slotProps">
            <div>
              {{ slotProps.data.room_type?.name }}
              <br />
              <small class="text-gray-500">{{ slotProps.data.room_type?.code }}</small>
            </div>
          </template>
        </Column>

        <Column field="rate_type.name" header="Tipo de Tarifa" sortable style="min-width: 180px">
          <template #body="slotProps">
            <div>
              {{ slotProps.data.rate_type?.name }}
              <br />
              <small class="text-gray-500">{{ slotProps.data.rate_type?.code }}</small>
            </div>
          </template>
        </Column>

        <Column field="effective_from" header="Vigencia Desde" sortable style="min-width: 150px">
          <template #body="slotProps">
            <i class="pi pi-calendar mr-1"></i>
            {{ formatDate(slotProps.data.effective_from) }}
          </template>
        </Column>

        <Column field="effective_to" header="Vigencia Hasta" sortable style="min-width: 150px">
          <template #body="slotProps">
            <i class="pi pi-calendar mr-1"></i>
            {{ slotProps.data.effective_to ? formatDate(slotProps.data.effective_to) : 'Sin límite' }}
          </template>
        </Column>

        <Column field="is_active" header="Estado" sortable style="min-width: 100px">
          <template #body="slotProps">
            <Tag
              :value="slotProps.data.is_active ? 'Activo' : 'Inactivo'"
              :severity="slotProps.data.is_active ? 'success' : 'secondary'"
            />
          </template>
        </Column>

        <Column header="Efectividad" style="min-width: 130px">
          <template #body="slotProps">
            <Tag
              v-if="slotProps.data.is_currently_effective"
              value="Vigente"
              severity="info"
            >
              <template #icon>
                <i class="pi pi-check-circle mr-1"></i>
              </template>
            </Tag>
            <Tag
              v-else-if="slotProps.data.has_expired"
              value="Expirado"
              severity="warn"
            >
              <template #icon>
                <i class="pi pi-clock mr-1"></i>
              </template>
            </Tag>
            <Tag v-else value="Pendiente" severity="secondary">
              <template #icon>
                <i class="pi pi-calendar-times mr-1"></i>
              </template>
            </Tag>
          </template>
        </Column>

        <Column :exportable="false" style="min-width: 150px">
          <template #body="slotProps">
            <div class="flex gap-2">
              <Button
                icon="pi pi-eye"
                rounded
                outlined
                severity="info"
                @click="handleView(slotProps.data.id)"
                v-tooltip.top="'Ver detalles'"
              />
              <Button
                icon="pi pi-pencil"
                rounded
                outlined
                severity="warn"
                @click="handleEdit(slotProps.data.id)"
                v-tooltip.top="'Editar'"
              />
              <Button
                icon="pi pi-trash"
                rounded
                outlined
                severity="danger"
                @click="handleDelete(slotProps.data.id)"
                v-tooltip.top="'Eliminar'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Modal de Agregar -->
    <AddBranchRoomTypePrice
      v-model:visible="showAddModal"
      :subBranches="subBranches"
      :roomTypes="roomTypes"
      :rateTypes="rateTypes"
      @success="handleSuccess"
      @cancel="showAddModal = false"
    />

    <!-- Modal de Editar -->
    <EditBranchRoomTypePrice
      v-if="selectedPriceId"
      v-model:visible="showEditModal"
      :priceId="selectedPriceId"
      :subBranches="subBranches"
      :roomTypes="roomTypes"
      :rateTypes="rateTypes"
      @success="handleSuccess"
      @cancel="showEditModal = false"
    />

    <!-- Modal de Eliminar -->
    <DeleteBranchRoomTypePrice
      v-if="selectedPriceId"
      v-model:visible="showDeleteModal"
      :priceId="selectedPriceId"
      @success="handleSuccess"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useBranchRoomTypePriceStore } from '../stores/branchRoomTypePrice.store';
import type { IBranchRoomTypePriceFilters } from '../interfaces/branchRoomTypePrice.interface';
import AddBranchRoomTypePrice from './AddBranchRoomTypePrice.vue';
import EditBranchRoomTypePrice from './EditBranchRoomTypePrice.vue';
import DeleteBranchRoomTypePrice from './DeleteBranchRoomTypePrice.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

// Store
const store = useBranchRoomTypePriceStore();

// State
const dt = ref();
const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedPriceId = ref<string>('');

const filters = ref<IBranchRoomTypePriceFilters>({
  sub_branch_id: '',
  room_type_id: '',
  rate_type_id: '',
  is_active: undefined,
  current_only: false,
});

// Datos de catálogos (deberían venir de otros stores o props)
const subBranches = ref<any[]>([]);
const roomTypes = ref<any[]>([]);
const rateTypes = ref<any[]>([]);

const statusOptions = ref([
  { label: 'Activos', value: true },
  { label: 'Inactivos', value: false },
]);

// Computed
const branchRoomTypePrices = computed(() => store.branchRoomTypePrices);
const loading = computed(() => store.loading);
const error = computed(() => store.error);

// Methods
const loadData = async () => {
  try {
    await store.fetchBranchRoomTypePrices(filters.value);
  } catch (err) {
    console.error('Error al cargar datos:', err);
  }
};

const applyFilters = () => {
  // Limpiar valores vacíos
  const cleanFilters: IBranchRoomTypePriceFilters = {};

  if (filters.value.sub_branch_id) {
    cleanFilters.sub_branch_id = filters.value.sub_branch_id;
  }
  if (filters.value.room_type_id) {
    cleanFilters.room_type_id = filters.value.room_type_id;
  }
  if (filters.value.rate_type_id) {
    cleanFilters.rate_type_id = filters.value.rate_type_id;
  }
  if (filters.value.is_active !== undefined && filters.value.is_active !== '') {
    cleanFilters.is_active = filters.value.is_active;
  }
  if (filters.value.current_only) {
    cleanFilters.current_only = filters.value.current_only;
  }

  store.fetchBranchRoomTypePrices(cleanFilters);
};

const clearFilters = () => {
  filters.value = {
    sub_branch_id: '',
    room_type_id: '',
    rate_type_id: '',
    is_active: undefined,
    current_only: false,
  };
  loadData();
};

const formatDate = (date: string) => {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const handleAdd = () => {
  showAddModal.value = true;
};

const handleEdit = (id: string) => {
  selectedPriceId.value = id;
  showEditModal.value = true;
};

const handleDelete = (id: string) => {
  selectedPriceId.value = id;
  showDeleteModal.value = true;
};

const handleView = (id: string) => {
  // Implementar vista de detalles si es necesario
  console.log('Ver detalles de:', id);
};

const handleSuccess = () => {
  loadData();
};

// Lifecycle
onMounted(() => {
  loadData();
  // Aquí deberías cargar los catálogos de subBranches, roomTypes, rateTypes
  // desde sus respectivos stores
  // Ejemplo:
  // subBranches.value = await subBranchStore.fetchSubBranches();
  // roomTypes.value = await roomTypeStore.fetchRoomTypes();
  // rateTypes.value = await rateTypeStore.fetchRateTypes();
});
</script>