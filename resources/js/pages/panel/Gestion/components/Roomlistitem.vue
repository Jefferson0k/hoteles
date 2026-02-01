<template>
    <div 
        class="flex flex-col sm:flex-row sm:items-center p-6 gap-4 hover:bg-surface-50 dark:hover:bg-surface-800/50 transition-colors"
        :class="{ 'border-t border-surface-200 dark:border-surface-700': !isFirst }"
    >
        <div class="flex items-center justify-center w-20 h-20 bg-primary-100 dark:bg-primary-900/30 rounded-lg border-2 border-primary-300 dark:border-primary-700">
            <span class="text-2xl font-bold text-primary-700 dark:text-primary-300">
                {{ room.room_number }}
            </span>
        </div>

        <div class="flex flex-col md:flex-row justify-between md:items-center flex-1 gap-6">
            <div class="flex flex-col gap-3">
                <div>
                    <span class="font-medium text-surface-500 dark:text-surface-400 text-sm">
                        Tipo de Habitación
                    </span>
                    <div class="text-lg font-semibold mt-1">{{ room.room_type }}</div>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <Tag 
                        :value="getStatusLabel(room.status)" 
                        :severity="getStatusSeverity(room.status)"
                    />
                    <Badge 
                        :value="room.is_active ? 'Activa' : 'Inactiva'" 
                        :severity="room.is_active ? 'success' : 'secondary'"
                    />
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <RoomCustomerInfo 
                    v-if="room.status === 'occupied' && room.customer"
                    :customer="room.customer"
                    :check-in="room.check_in"
                    :check-out="room.check_out"
                />

                <RoomTimer 
                    v-if="room.status === 'occupied'"
                    :check-in="room.check_in"
                    :check-out="room.check_out"
                />

                <RoomActions
                    :room="room"
                    @view-details="$emit('view-details', room.id, room.status)"
                    @room-settings="$emit('room-settings', room.id)"
                    @sell-products="$emit('sell-products', room.id)"
                    @extend-time="(bookingId, roomNumber) => $emit('extend-time', bookingId, roomNumber)"
                    @finish-booking="(bookingId, roomNumber) => $emit('finish-booking', bookingId, roomNumber)"
                    @start-booking="$emit('start-booking', room.id)"
                    @liberar="$emit('liberar', room.id)"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import { useStatusLabel } from '../interface/Roommanagement';
import type { Room } from '../interface/Roommanagement';
import RoomTimer from './Roomtimer.vue';
import RoomCustomerInfo from './Roomcustomerinfo.vue';
import RoomActions from './Roomactions.vue';

defineProps<{
    room: Room;
    isFirst: boolean;
}>();

defineEmits<{
    'view-details': [roomId: string, roomStatus: string];
    'room-settings': [roomId: string];
    'sell-products': [roomId: string];
    'extend-time': [bookingId: string, roomNumber: string];  // ← ACTUALIZADO
    'finish-booking': [bookingId: string, roomNumber: string]; // ← ACTUALIZADO
    'start-booking': [roomId: string];
    'liberar': [roomId: string];
}>();

const { getStatusLabel, getStatusSeverity } = useStatusLabel();
</script>