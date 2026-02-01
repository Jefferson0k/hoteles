<template>
    <br>
    <div class="p-5 bg-surface-50 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-surface-900 dark:text-surface-0 flex items-center gap-2">
                <i class="pi pi-shopping-cart"></i>
                Productos Adicionales
                <Badge 
                    v-if="isServiceActive" 
                    value="Servicio Activo" 
                    severity="success" 
                    class="text-xs"
                />
            </h3>
            <Button 
                label="Buscar Productos" 
                icon="pi pi-search" 
                severity="contrast"
                size="small"
                @click="openDialog"
                :disabled="disabled"
            />
        </div>

        <!-- Mensaje informativo cuando hay servicio activo -->
        <Message 
            v-if="isServiceActive" 
            severity="info" 
            :closable="false"
            class="mb-4"
        >
            <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                <span class="text-sm">
                    Los productos agregados se registrarán inmediatamente como consumo adicional
                </span>
            </div>
        </Message>

        <!-- Lista de productos agregados al carrito -->
        <DataTable 
            :value="products" 
            :paginator="false"
            class="p-datatable-sm"
            v-if="products.length > 0"
        >
            <Column field="nombre" header="Producto">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-box text-primary-500"></i>
                        <div>
                            <span class="font-medium block">{{ data.nombre }}</span>
                            <span class="text-xs text-surface-500 dark:text-surface-400">
                                <span v-if="data.codigo">Código: {{ data.codigo }}</span>
                                <span v-if="data.status === 'paid'" class="ml-2 text-green-600 font-semibold">
                                    <i class="pi pi-check-circle"></i> Pagado
                                </span>
                            </span>
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="quantity" header="Cantidad" style="width: 150px">
                <template #body="{ data }">
                    <Badge :value="formatQuantityDisplay(data)" severity="info" />
                </template>
            </Column>
            <Column field="precio_venta" header="Precio Unit." style="width: 120px">
                <template #body="{ data }">
                    <span class="font-semibold text-green-600 dark:text-green-400">
                        {{ currencySymbol }} {{ parseFloat(data.precio_venta || data.price || data.unit_price || 0).toFixed(2) }}
                    </span>
                </template>
            </Column>
            <Column header="Subtotal" style="width: 120px">
                <template #body="{ data }">
                    <span class="font-bold text-surface-900 dark:text-surface-0">
                        {{ currencySymbol }} {{ calculateProductSubtotal(data) }}
                    </span>
                </template>
            </Column>
            <Column header="Acciones" style="width: 100px">
                <template #body="{ data }">
                    <!-- Solo mostrar acciones si NO está pagado -->
                    <div v-if="data.status !== 'paid'" class="flex gap-1">
                        <Button 
                            icon="pi pi-pencil" 
                            severity="info"
                            text
                            rounded
                            size="small"
                            @click="editProduct(data)"
                            :disabled="disabled"
                            v-tooltip.top="'Editar cantidad'"
                        />
                        <Button 
                            icon="pi pi-trash" 
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="removeProduct(data.id)"
                            :disabled="disabled"
                            v-tooltip.top="'Eliminar'"
                        />
                    </div>
                    <!-- Mostrar estado pagado -->
                    <div v-else class="text-center">
                        <Tag value="Pagado" severity="success" class="text-xs" />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Sin productos -->
        <div v-else class="text-center py-6 text-surface-500 dark:text-surface-400">
            <i class="pi pi-shopping-cart text-4xl mb-2"></i>
            <p class="text-sm">No hay productos agregados</p>
        </div>

        <!-- Dialog Buscar y Agregar Productos -->
        <Dialog 
            v-model:visible="showDialog" 
            modal 
            header="Buscar Productos"
            :style="{ width: '900px' }"
            @hide="clearDialog"
        >
            <div class="space-y-4">
                <!-- Buscador -->
                <div>
                    <label class="block text-sm font-medium mb-2">
                        Buscar por Código o Nombre
                    </label>
                    <div class="p-inputgroup">
                        <InputText 
                            v-model="searchQuery" 
                            placeholder="Ej: PR9D6AFE76 o nombre del producto..." 
                            @keyup.enter="searchProducts"
                            :disabled="searchLoading"
                        />
                        <Button 
                            icon="pi pi-search" 
                            severity="contrast"
                            :loading="searchLoading"
                            @click="searchProducts"
                            label="Buscar"
                        />
                    </div>
                </div>

                <!-- Tabla de Resultados -->
                <div v-if="hasSearched">
                    <DataTable 
                        :value="searchResults" 
                        :loading="searchLoading"
                        :paginator="searchResults.length > 10"
                        :rows="10"
                        class="p-datatable-sm"
                        stripedRows
                        responsiveLayout="scroll"
                    >
                        <template #empty>
                            <div class="text-center py-4">
                                <i class="pi pi-inbox text-4xl text-surface-400 mb-2"></i>
                                <p class="text-surface-500 dark:text-surface-400">
                                    No se encontraron productos
                                </p>
                            </div>
                        </template>
                        <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
                        <Column field="codigo" header="Código" style="width: 10rem"></Column>
                        <Column field="nombre" header="Producto" style="width: 30rem"></Column>
                        <Column field="stock_actual" header="Stock" style="width: 8rem"></Column>

                        <Column field="precio_venta" header="Precio" style="width: 10rem">
                            <template #body="{ data }">
                                <span class="font-bold text-green-600 dark:text-green-400">
                                    {{ currencySymbol }} {{ parseFloat(data.precio_venta).toFixed(2) }}
                                </span>
                            </template>
                        </Column>

                        <Column header="">
                            <template #body="{ data }">
                                <Button 
                                    icon="pi pi-plus" 
                                    severity="secondary"
                                    rounded
                                    @click="selectProductToAdd(data)"
                                    :disabled="data.stock_actual <= 0"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Mensaje inicial -->
                <Message v-if="!hasSearched" severity="info" :closable="false">
                    Ingrese un código o nombre de producto y presione Enter o haga clic en Buscar
                </Message>
            </div>

            <template #footer>
                <Button 
                    label="Cerrar" 
                    severity="secondary" 
                    icon="pi pi-times"
                    text
                    @click="closeDialog" 
                />
            </template>
        </Dialog>

        <!-- Dialog Agregar Cantidad -->
        <Dialog 
            v-model:visible="showQuantityDialog" 
            modal 
            :header="`Agregar: ${selectedProduct?.nombre}`"
            :style="{ width: '600px' }"
        >
            <!-- Alerta para servicio activo -->
            <Message 
                v-if="isServiceActive" 
                severity="warn" 
                :closable="false"
                class="mb-4"
            >
                <div class="flex items-center gap-2">
                    <i class="pi pi-bolt"></i>
                    <div>
                        <strong>Servicio en Curso</strong>
                        <p class="text-sm mt-1">
                            Este producto se agregará como consumo adicional y se registrará inmediatamente en el sistema. El stock se descontará al confirmar.
                        </p>
                    </div>
                </div>
            </Message>

            <div class="space-y-4" v-if="selectedProduct">
                <!-- Información del Producto -->
                <div class="p-4 bg-surface-100 dark:bg-surface-700 rounded-lg">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-bold text-surface-900 dark:text-surface-0">
                                {{ selectedProduct.nombre }}
                            </p>
                            <p class="text-sm text-surface-600 dark:text-surface-400">
                                Código: {{ selectedProduct.codigo }}
                            </p>
                            <p v-if="selectedProduct.es_fraccionable" 
                               class="text-xs text-blue-600 dark:text-blue-400 font-semibold mt-1">
                                <i class="pi pi-info-circle"></i> Fraccionable: {{ selectedProduct.fracciones_por_unidad }} fracciones = 1 {{ selectedProduct.unidad }}
                            </p>
                        </div>
                        <Tag 
                            :value="`Stock: ${selectedProduct.stock_actual} ${selectedProduct.unidad}`" 
                            severity="info"
                        />
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-surface-300 dark:border-surface-600">
                        <span class="text-sm text-surface-600 dark:text-surface-400">Precio por fracción:</span>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400">
                            {{ currencySymbol }} {{ parseFloat(selectedProduct.precio_venta).toFixed(2) }}
                        </span>
                    </div>
                </div>

                <!-- Cantidad de Fracciones -->
                <div>
                    <label class="block text-sm font-medium mb-2">
                        Cantidad de Fracciones (Unidades Individuales)
                        <span class="text-xs text-surface-500 ml-2">
                            ({{ selectedProduct.fracciones_por_unidad }} fracciones = 1 {{ selectedProduct.unidad }})
                        </span>
                    </label>
                    <InputNumber 
                        v-model="form.totalFractions" 
                        :min="0" 
                        :step="1"
                        showButtons
                        class="w-full"
                        buttonLayout="horizontal"
                        incrementButtonIcon="pi pi-plus"
                        decrementButtonIcon="pi pi-minus"
                        placeholder="Ej: 36 fracciones"
                        @input="calculateUnits"
                    />
                    <small class="text-surface-500 dark:text-surface-400 block mt-1">
                        Ingrese el total de fracciones/unidades individuales
                    </small>
                </div>

                <!-- Conversión Automática -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="pi pi-calculator text-blue-600"></i>
                        <span class="font-semibold text-blue-800 dark:text-blue-200">
                            Conversión Automática
                        </span>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-surface-700 dark:text-surface-300">
                                {{ selectedProduct.unidad }}s completos:
                            </span>
                            <span class="font-bold text-lg text-blue-600 dark:text-blue-400">
                                {{ calculatedUnits }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-surface-700 dark:text-surface-300">
                                Fracciones sueltas:
                            </span>
                            <span class="font-bold text-lg text-blue-600 dark:text-blue-400">
                                {{ remainingFractions }}
                            </span>
                        </div>
                    </div>

                    <div v-if="form.totalFractions > 0" 
                         class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700 text-sm text-surface-600 dark:text-surface-400">
                        <i class="pi pi-info-circle"></i>
                        <span v-if="calculatedUnits > 0 && remainingFractions > 0">
                            {{ form.totalFractions }} fracciones = {{ calculatedUnits }} {{ selectedProduct.unidad }} + {{ remainingFractions }} fracciones
                        </span>
                        <span v-else-if="calculatedUnits > 0">
                            {{ form.totalFractions }} fracciones = {{ calculatedUnits }} {{ selectedProduct.unidad }} exactos
                        </span>
                        <span v-else>
                            {{ form.totalFractions }} fracciones sueltas
                        </span>
                    </div>
                </div>

                <!-- Subtotal -->
                <div class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg border-2 border-primary-300 dark:border-primary-700">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-surface-700 dark:text-surface-300">
                            Subtotal:
                        </span>
                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                            {{ currencySymbol }} {{ calculateSubtotal() }}
                        </span>
                    </div>
                    <p class="text-xs text-surface-500 dark:text-surface-400 mt-2">
                        {{ form.totalFractions }} fracciones × {{ currencySymbol }} {{ parseFloat(selectedProduct.precio_venta).toFixed(2) }}
                    </p>
                </div>
            </div>

            <template #footer>
                <Button 
                    label="Cancelar" 
                    severity="secondary" 
                    icon="pi pi-times"
                    text
                    @click="closeQuantityDialog" 
                />
                <Button 
                    :label="isServiceActive ? 'Registrar Consumo' : 'Agregar al Carrito'" 
                    :severity="isServiceActive ? 'success' : 'contrast'"
                    :icon="isServiceActive ? 'pi pi-check' : 'pi pi-shopping-cart'" 
                    @click="addProduct"
                    :disabled="!selectedProduct || form.totalFractions <= 0"
                />
            </template>
        </Dialog>

        <!-- Dialog Editar Cantidad -->
        <Dialog 
            v-model:visible="showEditDialog" 
            modal 
            :header="`Editar: ${editingProduct?.nombre}`"
            :style="{ width: '500px' }"
        >
            <div class="space-y-4" v-if="editingProduct">
                <!-- Información del Producto -->
                <div class="p-3 bg-surface-100 dark:bg-surface-700 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-semibold text-surface-900 dark:text-surface-0">
                                {{ editingProduct.nombre }}
                            </p>
                            <p class="text-xs text-surface-600 dark:text-surface-400">
                                Stock: {{ editingProduct.stock_actual }} {{ editingProduct.unidad }}
                            </p>
                            <p v-if="editingProduct.es_fraccionable" 
                               class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                {{ editingProduct.fracciones_por_unidad }} fracciones = 1 {{ editingProduct.unidad }}
                            </p>
                        </div>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400">
                            {{ currencySymbol }} {{ parseFloat(editingProduct.precio_venta).toFixed(2) }}
                        </span>
                    </div>
                </div>

                <!-- Cantidad de Fracciones -->
                <div>
                    <label class="block text-sm font-medium mb-2">Cantidad de Fracciones</label>
                    <InputNumber 
                        v-model="editForm.totalFractions" 
                        :min="0" 
                        :step="1"
                        showButtons
                        class="w-full"
                        @input="calculateEditUnits"
                    />
                </div>

                <!-- Conversión -->
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>{{ editingProduct.unidad }}s completos:</span>
                            <span class="font-bold text-blue-600">{{ editCalculatedUnits }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Fracciones sueltas:</span>
                            <span class="font-bold text-blue-600">{{ editRemainingFractions }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total -->
                <div class="p-3 bg-surface-100 dark:bg-surface-700 rounded">
                    <div class="flex justify-between items-center text-sm pt-2">
                        <span>Subtotal:</span>
                        <span class="font-bold text-primary-600 dark:text-primary-400">
                            {{ currencySymbol }} {{ calculateEditSubtotal() }}
                        </span>
                    </div>
                    <p class="text-xs text-surface-500 dark:text-surface-400 mt-1">
                        {{ editForm.totalFractions }} fracciones × {{ currencySymbol }} {{ parseFloat(editingProduct.precio_venta).toFixed(2) }}
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" severity="secondary" @click="closeEditDialog" />
                <Button 
                    label="Actualizar" 
                    severity="success"
                    icon="pi pi-check"
                    @click="updateProduct"
                    :disabled="editForm.totalFractions <= 0"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

interface Product {
    id: string;
    codigo?: string;
    nombre: string;
    product_name?: string;
    descripcion?: string;
    precio_compra?: string;
    precio_venta?: string;
    price?: string;
    unit_price?: string;
    unidad?: string;
    fraction_units?: number;
    fracciones_por_unidad?: number;
    es_fraccionable?: boolean;
    stock_actual?: number;
    min_stock?: number;
    max_stock?: number;
    sub_sucursal?: string;
    quantity?: number;
    cantidad?: number;
    status?: string; // ✅ Agregado para identificar productos pagados
    consumed_at?: string;
    consumption_id?: string;
}

interface Props {
    modelValue?: Product[];
    currencySymbol?: string;
    disabled?: boolean;
    bookingId?: string | null;
    isServiceActive?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    currencySymbol: 'S/',
    disabled: false,
    bookingId: null,
    isServiceActive: false
});

const emit = defineEmits<{
    'update:modelValue': [products: Product[]];
    'products-added': [products: Product[]];
}>();

const toast = useToast();
const showDialog = ref(false);
const showQuantityDialog = ref(false);
const showEditDialog = ref(false);
const searchQuery = ref('');
const searchLoading = ref(false);
const searchResults = ref<Product[]>([]);
const selectedProduct = ref<Product | null>(null);
const editingProduct = ref<Product | null>(null);
const products = ref<Product[]>(props.modelValue);
const hasSearched = ref(false);

const form = ref({
    totalFractions: 0
});

const editForm = ref({
    totalFractions: 0
});

// Watch para sincronizar con el prop
watch(() => props.modelValue, (newValue) => {
    products.value = newValue;
}, { deep: true });

// Calcular unidades completas y fracciones restantes
const calculatedUnits = computed(() => {
    if (!selectedProduct.value || !form.value.totalFractions) return 0;
    return Math.floor(form.value.totalFractions / (selectedProduct.value.fracciones_por_unidad || 1));
});

const remainingFractions = computed(() => {
    if (!selectedProduct.value || !form.value.totalFractions) return 0;
    return form.value.totalFractions % (selectedProduct.value.fracciones_por_unidad || 1);
});

const editCalculatedUnits = computed(() => {
    if (!editingProduct.value || !editForm.value.totalFractions) return 0;
    return Math.floor(editForm.value.totalFractions / (editingProduct.value.fracciones_por_unidad || 1));
});

const editRemainingFractions = computed(() => {
    if (!editingProduct.value || !editForm.value.totalFractions) return 0;
    return editForm.value.totalFractions % (editingProduct.value.fracciones_por_unidad || 1);
});

const calculateUnits = () => {
    // Trigger computed properties
};

const calculateEditUnits = () => {
    // Trigger computed properties
};

const calculateSubtotal = () => {
    if (!selectedProduct.value) return '0.00';
    const price = parseFloat(selectedProduct.value.precio_venta || '0');
    const subtotal = form.value.totalFractions * price;
    return subtotal.toFixed(2);
};

const calculateEditSubtotal = () => {
    if (!editingProduct.value) return '0.00';
    const price = parseFloat(editingProduct.value.precio_venta || editingProduct.value.price || editingProduct.value.unit_price || '0');
    const subtotal = editForm.value.totalFractions * price;
    return subtotal.toFixed(2);
};

const calculateProductSubtotal = (product: Product) => {
    const totalFractions = product.quantity || product.cantidad || 0;
    const price = parseFloat(product.precio_venta || product.price || product.unit_price || '0');
    const subtotal = totalFractions * price;
    return subtotal.toFixed(2);
};

const formatQuantityDisplay = (product: Product) => {
    const quantity = product.quantity || product.cantidad || 0;
    const esFraccionable = product.es_fraccionable || false;
    const fraccionesPorUnidad = product.fracciones_por_unidad || product.fraction_units || 1;
    
    if (!esFraccionable) {
        return `${quantity} unidades`;
    }
    
    const totalFractions = Math.round(quantity);
    const units = Math.floor(totalFractions / fraccionesPorUnidad);
    const fractions = totalFractions % fraccionesPorUnidad;
    
    if (units > 0 && fractions > 0) {
        return `${units} ${product.unidad || 'und'} + ${fractions} frac.`;
    } else if (units > 0) {
        return `${units} ${product.unidad || 'und'}`;
    } else {
        return `${fractions} fracciones`;
    }
};

const searchProducts = async () => {
    if (!searchQuery.value.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Advertencia',
            detail: 'Ingrese un término de búsqueda',
            life: 3000
        });
        return;
    }
    
    searchLoading.value = true;
    hasSearched.value = true;
    searchResults.value = [];
    
    try {
        const response = await axios.get('/producto/search', {
            params: { q: searchQuery.value.trim() }
        });
        
        searchResults.value = (response.data.data || []).map(product => ({
            ...product,
            fracciones_por_unidad: product.fraction_units || 1
        }));
        
        if (searchResults.value.length === 0) {
            toast.add({
                severity: 'info',
                summary: 'Sin resultados',
                detail: 'No se encontraron productos',
                life: 3000
            });
        }
    } catch (error: any) {
        console.error('Error al buscar productos:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'No se pudo buscar productos',
            life: 3000
        });
    } finally {
        searchLoading.value = false;
    }
};

const selectProductToAdd = (product: Product) => {
    selectedProduct.value = product;
    form.value.totalFractions = 0;
    showQuantityDialog.value = true;
};

const addProduct = () => {
    if (!selectedProduct.value || form.value.totalFractions <= 0) {
        toast.add({
            severity: 'warn',
            summary: 'Cantidad Requerida',
            detail: 'Debe ingresar al menos 1 fracción',
            life: 3000
        });
        return;
    }

    const totalFractions = form.value.totalFractions;
    
    // Crear el producto a agregar
    const productToAdd = {
        ...selectedProduct.value,
        quantity: totalFractions
    };

    // Si hay un servicio activo (booking), enviar inmediatamente al backend
    if (props.isServiceActive && props.bookingId) {
        emit('products-added', [productToAdd]);
        
        toast.add({
            severity: 'info',
            summary: 'Procesando...',
            detail: 'Registrando consumo adicional',
            life: 2000
        });
    } else {
        // Si no hay servicio activo, solo agregar al carrito local
        const existingIndex = products.value.findIndex(
            p => p.id === selectedProduct.value!.id && p.status !== 'paid'
        );

        if (existingIndex !== -1) {
            const newQuantity = (products.value[existingIndex].quantity || 0) + totalFractions;
            products.value[existingIndex].quantity = newQuantity;
            
            toast.add({
                severity: 'info',
                summary: 'Actualizado',
                detail: 'Se actualizó la cantidad del producto',
                life: 3000
            });
        } else {
            products.value.push(productToAdd);
            
            toast.add({
                severity: 'success',
                summary: 'Agregado',
                detail: `${selectedProduct.value.nombre} agregado al carrito`,
                life: 3000
            });
        }

        emit('update:modelValue', products.value);
    }

    closeQuantityDialog();
};

const editProduct = (product: Product) => {
    if (product.status === 'paid') {
        toast.add({
            severity: 'warn',
            summary: 'No Permitido',
            detail: 'No se pueden editar productos ya pagados',
            life: 3000
        });
        return;
    }
    
    editingProduct.value = product;
    editForm.value.totalFractions = Math.round(product.quantity || product.cantidad || 0);
    showEditDialog.value = true;
};

const updateProduct = () => {
    if (!editingProduct.value || editForm.value.totalFractions <= 0) {
        toast.add({
            severity: 'warn',
            summary: 'Cantidad Requerida',
            detail: 'Debe ingresar al menos 1 fracción',
            life: 3000
        });
        return;
    }
    
    const totalFractions = editForm.value.totalFractions;
    const index = products.value.findIndex(p => p.id === editingProduct.value!.id);
    
    if (index !== -1) {
        products.value[index].quantity = totalFractions;
        emit('update:modelValue', products.value);
        
        toast.add({
            severity: 'success',
            summary: 'Actualizado',
            detail: 'Cantidad actualizada correctamente',
            life: 3000
        });
    }
    
    closeEditDialog();
};

const removeProduct = (id: string) => {
    const product = products.value.find(p => p.id === id);
    
    if (product?.status === 'paid') {
        toast.add({
            severity: 'warn',
            summary: 'No Permitido',
            detail: 'No se pueden eliminar productos ya pagados',
            life: 3000
        });
        return;
    }
    
    products.value = products.value.filter(p => p.id !== id);
    emit('update:modelValue', products.value);
    
    toast.add({
        severity: 'warn',
        summary: 'Eliminado',
        detail: 'Producto eliminado del carrito',
        life: 3000
    });
};

const openDialog = () => {
    showDialog.value = true;
};

const closeDialog = () => {
    showDialog.value = false;
};

const closeQuantityDialog = () => {
    showQuantityDialog.value = false;
    selectedProduct.value = null;
    form.value.totalFractions = 0;
};

const closeEditDialog = () => {
    showEditDialog.value = false;
    editingProduct.value = null;
    editForm.value.totalFractions = 0;
};

const clearDialog = () => {
    searchQuery.value = '';
    searchResults.value = [];
    hasSearched.value = false;
};
</script>

<style scoped>
.p-inputgroup {
    display: flex;
}

.p-inputgroup .p-inputtext {
    flex: 1;
}

.font-mono {
    font-family: 'Courier New', monospace;
}
</style>