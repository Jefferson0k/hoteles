<template>
    <h4 class="text-center py-6"> Estado de la Habitación </h4>
    <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 p-8 rounded-xl border-2 border-primary-200 dark:border-primary-700 mb-6 ">
    <div class="text-center">
        <!-- Icono -->
        <i class="pi pi-clock text-4xl text-primary-500 mb-3"></i>

        <!-- Texto superior -->
        <p class="text-sm text-surface-600 dark:text-surface-400 mb-2">
            Tiempo Restante
        </p>

        <!-- Tiempo estático -->
        <div class="text-4xl font-bold text-primary-600 dark:text-primary-400">
            02:45:30
        </div>

        <!-- Mensaje de estado -->
        <p class="text-xs text-surface-500 dark:text-surface-400 mt-1">
            Servicio activo
        </p>

        <!-- Barra de progreso (estática) -->
        <div class="mt-4">
            <div class="w-full bg-surface-300 dark:bg-surface-600 rounded-full h-2">
                <div 
                    class="bg-primary-500 h-2 rounded-full transition-all"
                    style="width: 65%;"
                ></div>
            </div>
            <p class="text-xs mt-2 text-surface-600 dark:text-surface-400">
                65% del tiempo restante
            </p>
        </div>
    </div>
</div>

</template>

<script setup>
import { ref } from 'vue';
</script>
