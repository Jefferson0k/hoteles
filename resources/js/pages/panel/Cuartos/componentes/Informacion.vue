<template>
    <div class="bg-surface-50 dark:bg-surface-800 p-4 rounded-lg border border-surface-200 dark:border-surface-700">
        <h4 class="font-semibold mb-3 text-surface-900 dark:text-surface-0">
            <i class="pi pi-info-circle mr-2"></i>Información Rápida
        </h4>

        <div class="space-y-2 text-sm">
            <InfoRow
                label="Tipo"
                value="Habitación Simple"
            />

            <InfoRow
                label="Capacidad"
                value="2 persona(s)"
            />

            <InfoRow
                label="Moneda"
                value="S/ PEN"
                value-class="text-green-600 dark:text-green-400"
            />

            <InfoRow
                label="Tarifa"
                value="Por Día"
                value-class="text-green-600 dark:text-green-400"
            />

            <InfoRow
                label="Comprobante"
                value="BOLETA"
                value-class="text-primary-600 dark:text-primary-400"
            />

            <InfoRow
                label="Cliente"
                value="Cliente de Prueba"
                value-class="text-blue-600 dark:text-blue-400"
                with-border
            />

            <InfoRow
                label="Booking ID"
                value="BK-000123"
                value-class="text-purple-600 dark:text-purple-400 text-xs"
                with-border
            />
        </div>
    </div>
</template>

<script setup lang="ts">
// vacío a propósito – solo diseño
</script>
