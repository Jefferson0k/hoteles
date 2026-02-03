<template>
    <div class="flex flex-wrap justify-center gap-4">
        <div class="flex-auto">
            <h2 class="text-3xl font-bold">Cerrar Caja</h2>
            <p class="mt-2">Cierra tu caja y registra el monto final</p>
            
            <div class="mt-8 flex flex-col items-center gap-3 py-6">
                <Avatar icon="pi pi-user" size="large" />
                <p class="text-xl font-bold mb-0">{{ authenticatedUser?.name }}</p>
                <Button label="Ver Último Reporte" icon="pi pi-file-pdf" severity="info" fluid
                    @click="viewUserReport" />
            </div>

            <div class="mt-12 py-10">
                <Message severity="warn" :closable="false">
                    <template #icon>
                        <i class="pi pi-info-circle text-2xl"></i>
                    </template>
                    <div>
                        <p class="font-bold mb-2">Información Importante</p>
                        <ul class="pl-4 space-y-1">
                            <li>Ingrese el monto recibido por cada método de pago</li>
                            <li>El total se calculará automáticamente</li>
                            <li>Verifique los montos antes de cerrar la caja</li>
                            <li>Una vez cerrada, no podrá realizar más operaciones</li>
                        </ul>
                    </div>
                </Message>
            </div>
        </div>

        <div class="flex-auto">
            <div class="flex align-items-center gap-2 mb-3">
                <i class="pi pi-money-bill text-xl"></i>
                <span class="text-xl font-bold">Métodos de Pago</span>
            </div>

            <div v-if="cashRegisterStore.loadingPaymentMethods" class="text-center py-4">
                <ProgressSpinner style="width: 50px; height: 50px" />
                <p class="mt-2">Cargando métodos de pago...</p>
            </div>

            <div v-else>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="method in cashRegisterStore.paymentMethods" :key="method.id">
                        <label :for="`method-${method.id}`" class="font-bold block mb-2">
                            <i class="pi pi-wallet mr-2"></i>
                            {{ method.name }}
                        </label>
                        <InputNumber :id="`method-${method.id}`"
                            :model-value="cashRegisterStore.paymentAmounts[method.id] || 0"
                            @update:model-value="(value) => cashRegisterStore.setPaymentAmount(method.id, value)"
                            mode="currency" currency="PEN" locale="es-PE" :placeholder="`Monto en ${method.name}`"
                            :min="0" :minFractionDigits="2" :maxFractionDigits="2" fluid />
                        <small v-if="method.requires_reference" class="block mt-1">
                            <i class="pi pi-info-circle mr-1"></i>
                            Requiere referencia
                        </small>
                    </div>
                </div>
                <div class="mt-4">
                    <label for="notes" class="font-bold block mb-2">
                        <i class="pi pi-file-edit mr-2"></i>
                        Observaciones (Opcional)
                    </label>
                    <Textarea id="notes" :model-value="cashRegisterStore.notes"
                        @update:model-value="cashRegisterStore.setNotes" rows="3"
                        placeholder="Ingrese observaciones sobre el cierre de caja..." class="w-full" />
                </div>

                <Button label="Cerrar Caja" icon="pi pi-lock" @click="handleCloseCashRegister"
                    :loading="cashRegisterStore.isClosing" :disabled="!cashRegisterStore.canCloseCashRegister"
                    severity="danger" class="w-full mt-3" size="large" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import { useCashRegisterStore } from './useCashRegisterStore';

interface User {
    id: string;
    name: string;
    email: string;
    sub_branch_id: string;
}

const page = usePage();
const authenticatedUser = computed(() => page.props.auth?.user as User);

const cashRegisterStore = useCashRegisterStore();

const viewUserReport = () => {
    console.log('Ver último reporte del usuario');
};

const handleCloseCashRegister = async () => {
    try {
        await cashRegisterStore.closeCashRegister();
        router.reload();
    } catch (error) {
        console.error('Error al cerrar caja:', error);
    }
};

onMounted(async () => {
    await cashRegisterStore.loadPaymentMethods();
});
</script>