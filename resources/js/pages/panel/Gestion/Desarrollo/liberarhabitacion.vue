<template>
    <Dialog v-model:visible="dialogVisible" modal :header="`Liberar Habitación ${roomNumber}`"
        :style="{ width: '30rem' }" @hide="handleClose">
        <div class="flex flex-col gap-4">

            <!-- Message sistema -->
            <Message v-if="messageType" :severity="messageType">
                {{ messageText }}
            </Message>

            <!-- Pregunta + explicación -->
            <div>
                <p class="font-semibold">
                    ¿Confirmar liberación?
                </p>
                <p>
                    La habitación pasará de "Limpieza" a "Disponible"
                </p>
            </div>

            <!-- Detalle -->
            <div>
                <p>
                    <strong>Habitación:</strong> {{ roomNumber }}
                </p>
                <p class="flex items-center gap-2">
                    <strong>Estado actual:</strong>
                    <Tag severity="info" value="En Limpieza" />
                </p>
            </div>

        </div>


        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" @click="handleClose" severity="secondary" outlined />
            <Button label="Liberar Habitación" icon="pi pi-check-circle" @click="handleLiberar" severity="success"
                :loading="loading" />
        </template>
    </Dialog>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import { useRoomManagementStore } from '../interface/Roommanagement';

const props = defineProps<{
    visible: boolean;
    roomId: string | null;
    roomNumber: string | null;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    'room-liberated': [];
}>();

const store = useRoomManagementStore();
const loading = ref(false);

// 🔥 Message simple PrimeVue
const messageType = ref<
    'success' | 'info' | 'warn' | 'error' | 'secondary' | 'contrast' | null
>(null);

const messageText = ref('');

const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

const handleClose = () => {
    messageType.value = null;
    messageText.value = '';
    dialogVisible.value = false;
};

const handleLiberar = async () => {
    messageType.value = null;
    messageText.value = '';

    if (!props.roomId) {
        messageType.value = 'error';
        messageText.value = 'No se ha seleccionado una habitación';
        return;
    }

    loading.value = true;

    try {
        await store.liberarHabitacion(props.roomId);

        messageType.value = 'success';
        messageText.value = 'Habitación liberada correctamente';

        emit('room-liberated');
        setTimeout(handleClose, 1200);
    } catch (error: any) {
        messageType.value = 'error';
        messageText.value =
            error.response?.data?.message || 'No se pudo liberar la habitación';
    } finally {
        loading.value = false;
    }
};
</script>
