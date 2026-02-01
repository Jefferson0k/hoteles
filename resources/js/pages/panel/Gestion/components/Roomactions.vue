<template>
    <div class="flex gap-2" :class="gridLayout ? 'flex-wrap' : ''" v-if="room.status !== 'maintenance'">
        <!-- Botón Ver Detalles - Siempre visible -->
        <Button 
            icon="pi pi-eye" 
            severity="info"
            outlined
            :class="gridLayout ? 'flex-1' : ''"
            size="small"
            @click="$emit('view-details')"
            v-tooltip.top="'Ver detalles'"
        />

        <!-- Botón de Ajustes - Siempre visible -->
        <Button 
            icon="pi pi-cog" 
            severity="secondary"
            outlined
            :class="gridLayout ? 'flex-1' : ''"
            size="small"
            @click="$emit('room-settings')"
            v-tooltip.top="'Ajustes'"
        />

        <!-- Botones para habitación OCUPADA -->
        <template v-if="room.status === 'occupied'">
            <!-- Vender productos -->
            <Button 
                icon="pi pi-shopping-cart" 
                severity="success"
                outlined
                :class="gridLayout ? 'flex-1' : ''"
                size="small"
                @click="$emit('sell-products')"
                v-tooltip.top="'Vender productos'"
            />

            <!-- Extender tiempo -->
            <Button 
                icon="pi pi-clock" 
                severity="warning"
                outlined
                :class="gridLayout ? 'flex-1' : ''"
                size="small"
                @click="handleExtenderTiempo"
                v-tooltip.top="'Extender tiempo'"
            />

            <!-- Finalizar reserva -->
            <Button 
                icon="pi pi-check" 
                severity="primary"
                :class="gridLayout ? 'flex-1' : ''"
                size="small"
                @click="handleFinalizarBooking"
                v-tooltip.top="'Finalizar reserva'"
            />
        </template>

        <!-- Botón para habitación DISPONIBLE -->
        <Button 
            v-if="room.status === 'available'"
            icon="pi pi-plus" 
            severity="primary"
            :class="gridLayout ? 'flex-1' : ''"
            size="small"
            @click="$emit('start-booking')"
            v-tooltip.top="'Iniciar reserva'"
        />

        <!-- Botón para habitación en LIMPIEZA -->
        <Button 
            v-if="room.status === 'cleaning'"
            icon="pi pi-check-circle" 
            severity="success"
            outlined
            :class="gridLayout ? 'flex-1' : ''"
            size="small"
            @click="handleLiberar"
            v-tooltip.top="'Liberar habitación'"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Button from 'primevue/button';
import { useRoomManagementStore, isCheckoutExpired } from '../interface/Roommanagement';
import type { Room } from '../interface/Roommanagement';

const props = defineProps<{
    room: Room;
    gridLayout?: boolean;
}>();

const emit = defineEmits<{
    'view-details': [];
    'room-settings': [];
    'sell-products': [];
    'extend-time': [bookingId: string, roomNumber: string];
    'finish-booking': [bookingId: string, roomNumber: string];
    'start-booking': [];
    'liberar': [roomId: string, roomNumber: string];
}>();

const store = useRoomManagementStore();

const isExpired = computed(() => 
    isCheckoutExpired(props.room.check_out || null, store.currentTime)
);

const handleExtenderTiempo = () => {
    if (!props.room.booking_id) {
        console.error('No hay booking_id para esta habitación');
        return;
    }
    emit('extend-time', props.room.booking_id, props.room.room_number);
};

const handleFinalizarBooking = () => {
    if (!props.room.booking_id) {
        console.error('No hay booking_id para esta habitación');
        return;
    }
    emit('finish-booking', props.room.booking_id, props.room.room_number);
};

const handleLiberar = () => {
    emit('liberar', props.room.id, props.room.room_number);
};
</script>