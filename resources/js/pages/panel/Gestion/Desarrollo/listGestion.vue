<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import DataView from 'primevue/dataview';
import SelectButton from 'primevue/selectbutton';
import { useRoomManagementStore } from '../interface/Roommanagement';

import LoadingSkeleton from '../components/LoadingSkeleton.vue';
import EmptyState from '../components/EmptyState.vue';
import FloorHeader from '../components/Floorheader.vue';
import RoomListItem from '../components/Roomlistitem.vue';
import RoomGridItem from '../components/Roomgriditem.vue';

const store = useRoomManagementStore();

let timerInterval: NodeJS.Timeout | null = null;

onMounted(async () => {
    await store.fetchFloors();
    
    timerInterval = setInterval(() => {
        store.updateCurrentTime();
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});

// ============================================
// MÉTODOS QUE USAN ROOM_ID (navegación)
// ============================================

const viewRoomDetails = (roomId: string) => {
    console.log('Navegando a habitación:', roomId);
    router.visit(`/panel/cuarto/${roomId}`);
};

const handleRoomSettings = (roomId: string) => {
    console.log('Abrir ajustes de habitación:', roomId);
    router.visit(`/panel/habitacion/${roomId}/ajustes`);
};

const handleSellProducts = (roomId: string) => {
    console.log('Vender productos en habitación:', roomId);
    router.visit(`/panel/habitacion/${roomId}/productos`);
};

const handleStartBooking = (roomId: string) => {
    console.log('Iniciar reserva en habitación:', roomId);
    router.visit(`/panel/habitacion/${roomId}/nueva-reserva`);
};

// ✅ CORREGIDO - Ahora recibe roomId y roomNumber
const handleLiberar = (roomId: string, roomNumber: string) => {
    console.log('🧹 Liberando habitación - Room ID:', roomId, 'Número:', roomNumber);
    store.openLiberarDialog(roomId, roomNumber);
};

// ============================================
// ✅ MÉTODOS CORREGIDOS - USAN BOOKING_ID
// ============================================

const handleExtenderTiempo = (bookingId: string, roomNumber: string) => {
    console.log('🔧 Extendiendo tiempo - Booking ID:', bookingId, 'Habitación:', roomNumber);
    store.openExtenderDialog(bookingId, roomNumber);
};

const handleFinalizarBooking = (bookingId: string, roomNumber: string) => {
    console.log('✅ Finalizando servicio - Booking ID:', bookingId, 'Habitación:', roomNumber);
    store.openFinalizarDialog(bookingId, roomNumber);
};
</script>

<template>
    <div class="">
        <DataView :value="store.floors" :layout="store.layout">
            <template #header>
                <div class="flex justify-between items-center">
                    <div class="text-xl font-semibold">Gestión de Habitaciones</div>
                    <SelectButton v-model="store.layout" :options="store.layoutOptions" :allowEmpty="false">
                        <template #option="{ option }">
                            <i :class="[option === 'list' ? 'pi pi-bars' : 'pi pi-table']" />
                        </template>
                    </SelectButton>
                </div>
            </template>

            <template #list="slotProps">
                <div class="flex flex-col gap-6">
                    <div v-for="floor in slotProps.items" :key="floor.id">
                        <FloorHeader :floor="floor" />

                        <div class="border border-t-0 border-surface-200 dark:border-surface-700 rounded-b-lg">
                            <div v-for="(room, roomIndex) in floor.rooms" :key="room.id">
                                <RoomListItem 
                                    :room="room"
                                    :is-first="roomIndex === 0"
                                    @view-details="() => viewRoomDetails(room.id)"
                                    @room-settings="() => handleRoomSettings(room.id)"
                                    @sell-products="() => handleSellProducts(room.id)"
                                    @extend-time="handleExtenderTiempo"
                                    @finish-booking="handleFinalizarBooking"
                                    @start-booking="() => handleStartBooking(room.id)"
                                    @liberar="() => handleLiberar(room.id, room.room_number)"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #grid="slotProps">
                <div class="flex flex-col gap-6">
                    <div v-for="floor in slotProps.items" :key="floor.id">
                        <FloorHeader :floor="floor" />

                        <div class="grid grid-cols-12 gap-4">
                            <div 
                                v-for="room in floor.rooms" 
                                :key="room.id" 
                                class="col-span-12 sm:col-span-6 md:col-span-4 xl:col-span-3"
                            >
                                <RoomGridItem 
                                    :room="room"
                                    @view-details="() => viewRoomDetails(room.id)"
                                    @room-settings="() => handleRoomSettings(room.id)"
                                    @sell-products="() => handleSellProducts(room.id)"
                                    @extend-time="handleExtenderTiempo"
                                    @finish-booking="handleFinalizarBooking"
                                    @start-booking="() => handleStartBooking(room.id)"
                                    @liberar="() => handleLiberar(room.id, room.room_number)"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #empty>
                <LoadingSkeleton v-if="store.loading" :layout="store.layout" />
                <EmptyState v-else />
            </template>
        </DataView>
    </div>
</template>