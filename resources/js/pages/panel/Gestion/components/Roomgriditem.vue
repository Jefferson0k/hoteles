<template>
    <br>
    <div class="p-6 border border-surface-200 dark:border-surface-700 bg-surface-0 dark:bg-surface-900 rounded-lg hover:shadow-lg transition-shadow h-full flex flex-col">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center justify-center w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-lg border-2 border-primary-300 dark:border-primary-700">
                <span class="text-xl font-bold text-primary-700 dark:text-primary-300">
                    {{ room.room_number }}
                </span>
            </div>
            <Tag 
                :value="getStatusLabel(room.status)" 
                :severity="getStatusSeverity(room.status)"
            />
        </div>

        <div class="mb-4 flex-1">
            <span class="font-medium text-surface-500 dark:text-surface-400 text-sm">
                Tipo de Habitación
            </span>
            <div class="text-lg font-semibold mt-1">{{ room.room_type }}</div>
        </div>

        <RoomCustomerInfo 
            v-if="room.status === 'occupied' && room.customer"
            :customer="room.customer"
            :check-in="room.check_in"
            :check-out="room.check_out"
            class="mb-4"
        />

        <RoomTimer 
            v-if="room.status === 'occupied'"
            :check-in="room.check_in"
            :check-out="room.check_out"
            :centered="true"
            class="mb-4"
        />

        <RoomActions
            :room="room"
            :grid-layout="true"
            @view-details="$emit('view-details', room.id, room.status)"
            @room-settings="$emit('room-settings', room.id)"
            @sell-products="$emit('sell-products', room.id)"
            @extend-time="(bookingId, roomNumber) => $emit('extend-time', bookingId, roomNumber)"
            @finish-booking="(bookingId, roomNumber) => $emit('finish-booking', bookingId, roomNumber)"
            @start-booking="$emit('start-booking', room.id)"
            @liberar="$emit('liberar', room.id)"
        />
    </div>
</template>

<script setup lang="ts">
import Tag from 'primevue/tag';
import { useStatusLabel } from '../interface/Roommanagement';
import type { Room } from '../interface/Roommanagement';
import RoomTimer from './Roomtimer.vue';
import RoomCustomerInfo from './Roomcustomerinfo.vue';
import RoomActions from './Roomactions.vue';

defineProps<{
    room: Room;
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