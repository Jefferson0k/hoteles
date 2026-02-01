<template>
  <div class="p-5 bg-surface-50 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700">
    
    <!-- Header con título y botón -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-surface-900 dark:text-surface-0 flex items-center gap-2">
        <i class="pi pi-user text-primary-500"></i>
        Cliente
      </h3>
      <Button 
        label="Registrar Cliente" 
        icon="pi pi-user-plus" 
        severity="info"
        size="small"
        @click="abrirDialog"
        :disabled="!!clienteSeleccionado"
      />
    </div>

    <!-- Cliente Seleccionado -->
    <div v-if="clienteSeleccionado" class="p-4 bg-white dark:bg-surface-700 rounded-lg border border-surface-300 dark:border-surface-600">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
            <i class="pi pi-user text-primary-600 dark:text-primary-400 text-xl"></i>
          </div>
          <div>
            <p class="font-semibold text-surface-900 dark:text-surface-0">{{ clienteSeleccionado.nombre_completo }}</p>
            <p class="text-sm text-surface-600 dark:text-surface-400">
              {{ clienteSeleccionado.tipo_documento }}: {{ clienteSeleccionado.numero_documento }}
            </p>
            <p v-if="clienteSeleccionado.telefono" class="text-xs text-surface-500 dark:text-surface-400">
              <i class="pi pi-phone mr-1"></i>{{ clienteSeleccionado.telefono }}
            </p>
          </div>
        </div>
        <Button 
          icon="pi pi-times" 
          severity="danger"
          text
          rounded
          @click="eliminarCliente"
          v-tooltip.left="'Quitar cliente'"
        />
      </div>
    </div>

    <!-- Sin Cliente -->
    <div v-else class="text-center py-8 text-surface-500 dark:text-surface-400">
      <i class="pi pi-user-plus text-5xl mb-3 opacity-50"></i>
      <p class="text-sm">No hay cliente registrado</p>
      <p class="text-xs mt-1">Presione "Registrar Cliente" para agregar uno</p>
    </div>

    <!-- Dialog para Registrar Cliente -->
    <Dialog 
      v-model:visible="mostrarDialog" 
      modal 
      header="Registrar Nuevo Cliente"
      :style="{ width: '550px' }"
      :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
    >
      <div class="space-y-5 pt-2">
        
        <!-- Nombre Completo -->
        <div>
          <label for="nombre" class="block text-sm font-semibold mb-2 text-surface-900 dark:text-surface-0">
            <i class="pi pi-user mr-1 text-primary-500"></i>
            Nombre Completo <span class="text-red-500">*</span>
          </label>
          <InputText 
            id="nombre"
            v-model="formCliente.nombre_completo" 
            placeholder="Ej: Juan Carlos Pérez López"
            class="w-full"
            :class="{ 'p-invalid border-red-500': errors.nombre_completo }"
            @input="validarCampo('nombre_completo')"
          />
          <small v-if="errors.nombre_completo" class="text-red-500 text-xs mt-1 block">
            <i class="pi pi-exclamation-circle mr-1"></i>{{ errors.nombre_completo }}
          </small>
        </div>

        <!-- Tipo de Documento -->
        <div>
          <label for="tipoDoc" class="block text-sm font-semibold mb-2 text-surface-900 dark:text-surface-0">
            <i class="pi pi-id-card mr-1 text-primary-500"></i>
            Tipo de Documento <span class="text-red-500">*</span>
          </label>
          <Select 
            id="tipoDoc"
            v-model="formCliente.tipo_documento" 
            :options="tiposDocumento" 
            placeholder="Seleccione un tipo"
            class="w-full"
            :class="{ 'p-invalid border-red-500': errors.tipo_documento }"
            @change="validarCampo('tipo_documento')"
          />
          <small v-if="errors.tipo_documento" class="text-red-500 text-xs mt-1 block">
            <i class="pi pi-exclamation-circle mr-1"></i>{{ errors.tipo_documento }}
          </small>
        </div>

        <!-- Número de Documento -->
        <div>
          <label for="numeroDoc" class="block text-sm font-semibold mb-2 text-surface-900 dark:text-surface-0">
            <i class="pi pi-hashtag mr-1 text-primary-500"></i>
            Número de Documento <span class="text-red-500">*</span>
          </label>
          <InputText 
            id="numeroDoc"
            v-model="formCliente.numero_documento" 
            placeholder="Ej: 12345678"
            class="w-full"
            :class="{ 'p-invalid border-red-500': errors.numero_documento }"
            @input="validarCampo('numero_documento')"
          />
          <small v-if="errors.numero_documento" class="text-red-500 text-xs mt-1 block">
            <i class="pi pi-exclamation-circle mr-1"></i>{{ errors.numero_documento }}
          </small>
        </div>

        <!-- Teléfono (Opcional) -->
        <div>
          <label for="telefono" class="block text-sm font-semibold mb-2 text-surface-900 dark:text-surface-0">
            <i class="pi pi-phone mr-1 text-primary-500"></i>
            Teléfono <span class="text-surface-400 font-normal text-xs">(Opcional)</span>
          </label>
          <InputText 
            id="telefono"
            v-model="formCliente.telefono" 
            placeholder="Ej: 987654321"
            class="w-full"
            :class="{ 'p-invalid border-red-500': errors.telefono }"
            @input="validarCampo('telefono')"
          />
          <small v-if="errors.telefono" class="text-red-500 text-xs mt-1 block">
            <i class="pi pi-exclamation-circle mr-1"></i>{{ errors.telefono }}
          </small>
        </div>

      </div>

      <template #footer>
        <div class="flex gap-2 justify-end">
          <Button 
            label="Cancelar" 
            icon="pi pi-times"
            severity="secondary" 
            text 
            @click="cerrarDialog"
          />
          <Button 
            label="Guardar Cliente" 
            icon="pi pi-send"
            severity="success"
            @click="agregarCliente"
            :disabled="!formularioValido"
            :loading="guardando"
          />
        </div>
      </template>
    </Dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useReservaStore } from '../store/reserva';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';

const store = useReservaStore();

const mostrarDialog = ref(false);
const clienteSeleccionado = ref<any>(null);
const guardando = ref(false);

const formCliente = ref({
  nombre_completo: '',
  tipo_documento: 'DNI',
  numero_documento: '',
  telefono: ''
});

const errors = ref({
  nombre_completo: '',
  tipo_documento: '',
  numero_documento: '',
  telefono: ''
});

const tiposDocumento = ref(['DNI', 'CE', 'Pasaporte', 'RUC']);

// Validación individual de campos
const validarCampo = (campo: string) => {
  errors.value[campo] = '';
  
  switch (campo) {
    case 'nombre_completo':
      if (!formCliente.value.nombre_completo.trim()) {
        errors.value.nombre_completo = 'El nombre completo es obligatorio';
      } else if (formCliente.value.nombre_completo.trim().length < 3) {
        errors.value.nombre_completo = 'El nombre debe tener al menos 3 caracteres';
      } else if (!/^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/.test(formCliente.value.nombre_completo)) {
        errors.value.nombre_completo = 'El nombre solo puede contener letras';
      }
      break;
      
    case 'tipo_documento':
      if (!formCliente.value.tipo_documento) {
        errors.value.tipo_documento = 'Debe seleccionar un tipo de documento';
      }
      break;
      
    case 'numero_documento':
      if (!formCliente.value.numero_documento.trim()) {
        errors.value.numero_documento = 'El número de documento es obligatorio';
      } else if (formCliente.value.tipo_documento === 'DNI' && !/^\d{8}$/.test(formCliente.value.numero_documento)) {
        errors.value.numero_documento = 'El DNI debe tener 8 dígitos';
      } else if (formCliente.value.tipo_documento === 'RUC' && !/^\d{11}$/.test(formCliente.value.numero_documento)) {
        errors.value.numero_documento = 'El RUC debe tener 11 dígitos';
      } else if (formCliente.value.tipo_documento === 'CE' && formCliente.value.numero_documento.length < 8) {
        errors.value.numero_documento = 'El CE debe tener al menos 8 caracteres';
      }
      break;
      
    case 'telefono':
      if (formCliente.value.telefono && !/^\d{7,15}$/.test(formCliente.value.telefono)) {
        errors.value.telefono = 'El teléfono debe tener entre 7 y 15 dígitos';
      }
      break;
  }
};

// Validar todos los campos
const validarFormulario = (): boolean => {
  validarCampo('nombre_completo');
  validarCampo('tipo_documento');
  validarCampo('numero_documento');
  validarCampo('telefono');
  
  return !Object.values(errors.value).some(error => error !== '');
};

const formularioValido = computed(() => {
  return (
    formCliente.value.nombre_completo.trim() !== '' &&
    formCliente.value.tipo_documento !== '' &&
    formCliente.value.numero_documento.trim() !== '' &&
    !Object.values(errors.value).some(error => error !== '')
  );
});

const abrirDialog = () => {
  mostrarDialog.value = true;
};

const cerrarDialog = () => {
  mostrarDialog.value = false;
  limpiarFormulario();
  limpiarErrores();
};

const agregarCliente = async () => {
  if (!validarFormulario()) {
    return;
  }

  guardando.value = true;
  
  // Simular guardado (reemplazar con llamada real a API)
  setTimeout(() => {
    clienteSeleccionado.value = { ...formCliente.value };
    store.setCliente(clienteSeleccionado.value);
    guardando.value = false;
    cerrarDialog();
  }, 500);
};

const eliminarCliente = () => {
  clienteSeleccionado.value = null;
  store.setCliente(null);
};

const limpiarFormulario = () => {
  formCliente.value = {
    nombre_completo: '',
    tipo_documento: 'DNI',
    numero_documento: '',
    telefono: ''
  };
};

const limpiarErrores = () => {
  errors.value = {
    nombre_completo: '',
    tipo_documento: '',
    numero_documento: '',
    telefono: ''
  };
};
</script>

<style scoped>
.p-invalid {
  border-color: rgb(239 68 68) !important;
}

.p-dialog-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}
</style>