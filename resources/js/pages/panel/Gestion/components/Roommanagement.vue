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
                        <!-- Floor Header -->
                        <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-t-lg border-b-2 border-primary-500">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-primary-700 dark:text-primary-300">
                                        {{ floor.name }}
                                    </h3>
                                    <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">
                                        {{ floor.available_rooms }}/{{ floor.total_rooms }} habitaciones disponibles
                                    </p>
                                </div>
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    Piso {{ floor.floor_number }}
                                </div>
                            </div>
                        </div>

                        <div class="border border-t-0 border-surface-200 dark:border-surface-700 rounded-b-lg">
                            <div v-for="(room, roomIndex) in floor.rooms" :key="room.id">
                                <!-- Room List Item -->
                                <div 
                                    class="flex flex-col sm:flex-row sm:items-center p-6 gap-4 hover:bg-surface-50 dark:hover:bg-surface-800/50 transition-colors"
                                    :class="{ 'border-t border-surface-200 dark:border-surface-700': roomIndex !== 0 }"
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
                                            <!-- Customer Info -->
                                            <div v-if="room.status === 'occupied' && room.customer" class="flex flex-col gap-1 text-sm md:text-right">
                                                <div class="flex items-center gap-2 text-surface-600 dark:text-surface-400">
                                                    <i class="pi pi-user text-xs"></i>
                                                    <span class="font-medium">{{ room.customer }}</span>
                                                </div>
                                                <div v-if="room.check_in" class="flex items-center gap-2 text-surface-500 dark:text-surface-500 text-xs">
                                                    <i class="pi pi-sign-in"></i>
                                                    <span>{{ timer.formatCheckIn(room.check_in) }}</span>
                                                </div>
                                                <div v-if="room.check_out" class="flex items-center gap-2 text-surface-500 dark:text-surface-500 text-xs">
                                                    <i class="pi pi-sign-out"></i>
                                                    <span>{{ timer.formatCheckOut(room.check_out) }}</span>
                                                </div>
                                            </div>

                                            <!-- Timer -->
                                            <div 
                                                v-if="room.status === 'occupied'"
                                                class="bg-surface-100 dark:bg-surface-700 px-4 py-2 rounded-lg transition-all duration-300"
                                                :class="getTimerClasses(room.check_out)"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <i class="pi pi-clock text-lg" :class="getTimerIconClasses(room.check_out)"></i>
                                                    <span class="font-mono text-lg font-semibold" :class="getTimerTextClasses(room.check_out)">
                                                        {{ timer.getRemainingTime(room.check_in, room.check_out) }}
                                                    </span>
                                                </div>
                                                <div v-if="timer.isExpired(room.check_out)" class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">
                                                    ¡TIEMPO VENCIDO!
                                                </div>
                                                <div v-else-if="timer.isNear(room.check_out)" class="text-xs text-orange-600 dark:text-orange-400 font-semibold mt-1">
                                                    ¡Próximo a vencer!
                                                </div>
                                                <div v-else-if="timer.isSuspicious(room.check_out)" class="text-xs text-yellow-600 dark:text-yellow-400 font-semibold mt-1">
                                                    ⚠️ Datos sospechosos
                                                </div>
                                                <div v-else class="text-xs text-surface-500 dark:text-surface-400 mt-1">
                                                    Tiempo restante
                                                </div>
                                            </div>
                                            
                                            <!-- Actions -->
                                            <div class="flex gap-2" v-if="room.status !== 'maintenance'">
                                                <Button 
                                                    icon="pi pi-eye" 
                                                    severity="info"
                                                    outlined
                                                    size="small"
                                                    @click="viewRoomDetails(room.id, room.status)"
                                                    v-tooltip.top="'Ver detalles'"
                                                />
                                                
                                                <template v-if="room.status === 'occupied'">
                                                    <Button 
                                                        v-if="timer.isExpired(room.check_out)"
                                                        icon="pi pi-clock" 
                                                        severity="warning"
                                                        outlined
                                                        size="small"
                                                        @click="store.openExtenderDialog(room.booking_id, room.room_number)"
                                                        v-tooltip.top="'Extender tiempo'"
                                                    />
                                                    <Button 
                                                        v-if="timer.isExpired(room.check_out)"
                                                        icon="pi pi-dollar" 
                                                        severity="success"
                                                        outlined
                                                        size="small"
                                                        @click="store.openCobrarDialog(room.booking_id, room.room_number)"
                                                        v-tooltip.top="'Cobrar tiempo extra'"
                                                    />
                                                    <Button 
                                                        icon="pi pi-sign-out" 
                                                        severity="danger"
                                                        outlined
                                                        size="small"
                                                        @click="store.openFinalizarDialog(room.booking_id, room.room_number)"
                                                        v-tooltip.top="'Finalizar reserva'"
                                                    />
                                                </template>

                                                <Button 
                                                    v-if="room.status === 'cleaning'"
                                                    icon="pi pi-check-circle" 
                                                    severity="success"
                                                    outlined
                                                    size="small"
                                                    @click="store.openLiberarDialog(room.id, room.room_number)"
                                                    v-tooltip.top="'Liberar habitación'"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #grid="slotProps">
                <div class="flex flex-col gap-6">
                    <div v-for="floor in slotProps.items" :key="floor.id">
                        <!-- Floor Header -->
                        <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg border border-primary-200 dark:border-primary-800">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-primary-700 dark:text-primary-300">
                                        {{ floor.name }}
                                    </h3>
                                    <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">
                                        {{ floor.available_rooms }}/{{ floor.total_rooms }} disponibles
                                    </p>
                                </div>
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    Piso {{ floor.floor_number }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-4">
                            <div 
                                v-for="room in floor.rooms" 
                                :key="room.id" 
                                class="col-span-12 sm:col-span-6 md:col-span-4 xl:col-span-3"
                            >
                                <!-- Room Grid Item -->
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

                                    <div class="mb-4">
                                        <Badge 
                                            :value="room.is_active ? 'Activa' : 'Inactiva'" 
                                            :severity="room.is_active ? 'success' : 'secondary'"
                                        />
                                    </div>

                                    <!-- Customer Info -->
                                    <div v-if="room.status === 'occupied' && room.customer" class="mb-4 text-sm space-y-2">
                                        <div class="flex items-start gap-2 text-surface-600 dark:text-surface-400">
                                            <i class="pi pi-user text-xs mt-1"></i>
                                            <span class="font-medium">{{ room.customer }}</span>
                                        </div>
                                        <div v-if="room.check_in" class="flex items-center gap-2 text-surface-500 dark:text-surface-500 text-xs">
                                            <i class="pi pi-sign-in"></i>
                                            <span>{{ timer.formatCheckIn(room.check_in) }}</span>
                                        </div>
                                        <div v-if="room.check_out" class="flex items-center gap-2 text-surface-500 dark:text-surface-500 text-xs">
                                            <i class="pi pi-sign-out"></i>
                                            <span>{{ timer.formatCheckOut(room.check_out) }}</span>
                                        </div>
                                    </div>

                                    <!-- Timer -->
                                    <div 
                                        v-if="room.status === 'occupied'"
                                        class="bg-surface-100 dark:bg-surface-700 px-4 py-3 rounded-lg mb-4 transition-all duration-300"
                                        :class="getTimerClasses(room.check_out)"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <i class="pi pi-clock text-lg" :class="getTimerIconClasses(room.check_out)"></i>
                                            <span class="font-mono text-lg font-semibold" :class="getTimerTextClasses(room.check_out)">
                                                {{ timer.getRemainingTime(room.check_in, room.check_out) }}
                                            </span>
                                        </div>
                                        <div v-if="timer.isExpired(room.check_out)" class="text-xs text-center text-red-600 dark:text-red-400 font-semibold mt-1">
                                            ¡TIEMPO VENCIDO!
                                        </div>
                                        <div v-else-if="timer.isNear(room.check_out)" class="text-xs text-center text-orange-600 dark:text-orange-400 font-semibold mt-1">
                                            ¡Próximo a vencer!
                                        </div>
                                        <div v-else-if="timer.isSuspicious(room.check_out)" class="text-xs text-center text-yellow-600 dark:text-yellow-400 font-semibold mt-1">
                                            ⚠️ Datos sospechosos
                                        </div>
                                        <div v-else class="text-xs text-center text-surface-500 dark:text-surface-400 mt-1">
                                            Tiempo restante
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex gap-2 flex-wrap" v-if="room.status !== 'maintenance'">
                                        <Button 
                                            icon="pi pi-eye" 
                                            severity="info"
                                            outlined
                                            class="flex-1"
                                            size="small"
                                            @click="viewRoomDetails(room.id, room.status)"
                                            v-tooltip.top="'Ver detalles'"
                                        />
                                        
                                        <template v-if="room.status === 'occupied'">
                                            <Button 
                                                v-if="timer.isExpired(room.check_out)"
                                                icon="pi pi-clock" 
                                                severity="warning"
                                                outlined
                                                class="flex-1"
                                                size="small"
                                                @click="store.openExtenderDialog(room.booking_id, room.room_number)"
                                                v-tooltip.top="'Extender tiempo'"
                                            />
                                            <Button 
                                                v-if="timer.isExpired(room.check_out)"
                                                icon="pi pi-dollar" 
                                                severity="success"
                                                outlined
                                                class="flex-1"
                                                size="small"
                                                @click="store.openCobrarDialog(room.booking_id, room.room_number)"
                                                v-tooltip.top="'Cobrar tiempo extra'"
                                            />
                                            <Button 
                                                icon="pi pi-sign-out" 
                                                severity="danger"
                                                outlined
                                                class="flex-1"
                                                size="small"
                                                @click="store.openFinalizarDialog(room.booking_id, room.room_number)"
                                                v-tooltip.top="'Finalizar reserva'"
                                            />
                                        </template>

                                        <Button 
                                            v-if="room.status === 'cleaning'"
                                            icon="pi pi-check-circle" 
                                            severity="success"
                                            outlined
                                            class="flex-1"
                                            size="small"
                                            @click="store.openLiberarDialog(room.id, room.room_number)"
                                            v-tooltip.top="'Liberar habitación'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #empty>
                <!-- Loading Skeleton -->
                <div v-if="store.loading">
                    <div v-if="store.layout === 'list'" class="flex flex-col">
                        <div v-for="i in 6" :key="i">
                            <div class="flex flex-col xl:flex-row xl:items-start p-6 gap-6" :class="{ 'border-t border-surface-200 dark:border-surface-700': i !== 0 }">
                                <Skeleton class="w-9/12 sm:w-64 xl:w-40 h-24 mx-auto" />
                                <div class="flex flex-col sm:flex-row justify-between items-center xl:items-start flex-1 gap-6">
                                    <div class="flex flex-col items-center sm:items-start gap-4">
                                        <Skeleton width="8rem" height="2rem" />
                                        <Skeleton width="6rem" height="1rem" />
                                        <div class="flex items-center gap-4">
                                            <Skeleton width="6rem" height="1rem" />
                                            <Skeleton width="3rem" height="1rem" />
                                        </div>
                                    </div>
                                    <div class="flex sm:flex-col items-center sm:items-end gap-4 sm:gap-2">
                                        <Skeleton width="4rem" height="2rem" />
                                        <Skeleton size="3rem" shape="circle" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="grid grid-cols-12 gap-4">
                        <div v-for="i in 6" :key="i" class="col-span-12 sm:col-span-6 xl:col-span-4 p-2">
                            <div class="p-6 border border-surface-200 dark:border-surface-700 bg-surface-0 dark:bg-surface-900 rounded">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <Skeleton width="6rem" height="2rem" />
                                    <Skeleton width="3rem" height="1rem" />
                                </div>
                                <div class="flex flex-col items-center gap-4 py-8">
                                    <Skeleton width="75%" height="10rem" />
                                    <Skeleton width="8rem" height="2rem" />
                                    <Skeleton width="6rem" height="1rem" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <Skeleton width="4rem" height="2rem" />
                                    <Skeleton width="6rem" height="1rem" shape="circle" size="3rem" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Empty State -->
                <div v-else class="text-center p-6">
                    <i class="pi pi-inbox text-4xl text-surface-400 mb-3"></i>
                    <p class="text-surface-600 dark:text-surface-400">No hay habitaciones disponibles</p>
                </div>
            </template>
        </DataView>

        <!-- Diálogos -->
        <liberarhabitacion 
            v-model:visible="store.liberarDialog" 
            :roomId="store.selectedRoomId"
            :roomNumber="store.selectedRoomNumber"
            @room-liberated="store.handleRoomLiberated"
        />

        <ExtenderTiempo 
            v-model:visible="store.extenderDialog" 
            :bookingId="store.selectedBookingId"
            :roomNumber="store.selectedRoomNumber"
            @time-extended="store.handleTimeExtended"
        />

        <CobrarTiempoExtra 
            v-model:visible="store.showCobrarDialog" 
            :bookingId="store.selectedBookingId"
            :roomNumber="store.selectedRoomNumber"
            @extra-time-charged="store.handleExtraTimeCharged"
        />

        <FinalizarReserva 
            v-model:visible="store.finalizarDialog" 
            :bookingId="store.selectedBookingId"
            :roomNumber="store.selectedRoomNumber"
            @booking-finished="store.handleBookingFinished"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import DataView from 'primevue/dataview';
import SelectButton from 'primevue/selectbutton';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Skeleton from 'primevue/skeleton';
import { useRoomManagementStore, useStatusLabel, useRoomTimer } from '../interface/Roommanagement';
import type { RoomStatus } from '../interface/Roommanagement';

import liberarhabitacion from '../Desarrollo/liberarhabitacion.vue';
// Store y composables
const store = useRoomManagementStore();
const { getStatusLabel, getStatusSeverity } = useStatusLabel();
const timer = useRoomTimer(store.currentTime);

// Timer interval
let timerInterval: NodeJS.Timeout | null = null;

// Lifecycle
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

// Computed para clases dinámicas del timer
const getTimerClasses = (checkOut: string | null) => computed(() => ({
    'animate-pulse bg-orange-100 dark:bg-orange-900/30 border-2 border-orange-400': timer.isNear(checkOut),
    'animate-bounce bg-red-100 dark:bg-red-900/30 border-2 border-red-500': timer.isExpired(checkOut),
    'bg-yellow-100 dark:bg-yellow-900/30 border-2 border-yellow-400': timer.isSuspicious(checkOut)
})).value;

const getTimerIconClasses = (checkOut: string | null) => computed(() => ({
    'text-orange-600': timer.isNear(checkOut) && !timer.isExpired(checkOut),
    'text-red-600': timer.isExpired(checkOut),
    'text-yellow-600': timer.isSuspicious(checkOut),
    'text-primary-600': !timer.isNear(checkOut) && !timer.isExpired(checkOut) && !timer.isSuspicious(checkOut)
})).value;

const getTimerTextClasses = (checkOut: string | null) => computed(() => ({
    'text-orange-600 dark:text-orange-400': timer.isNear(checkOut) && !timer.isExpired(checkOut),
    'text-red-600 dark:text-red-400': timer.isExpired(checkOut),
    'text-yellow-600 dark:text-yellow-400': timer.isSuspicious(checkOut),
    'text-primary-700 dark:text-primary-300': !timer.isNear(checkOut) && !timer.isExpired(checkOut) && !timer.isSuspicious(checkOut)
})).value;

// Métodos
const viewRoomDetails = (roomId: number, roomStatus?: RoomStatus) => {
    console.log('Navegando a habitación:', roomId, 'con estado:', roomStatus);
    const url = roomStatus === 'occupied'
        ? `/panel/cuarto/${roomId}/detalles-checkout`
        : `/panel/cuarto/${roomId}`;
    router.visit(url);
};
</script>