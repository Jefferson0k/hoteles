<template>
    <div
        class="mb-4 p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface-200 dark:border-surface-700">
        <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
            Cantidad de Tiempo
        </label>
        <div class="flex gap-2">
            <InputNumber @update:model-value="$emit('update:modelValue', $event)" :min="1" :max="24" showButtons
                class="flex-1" />
            <Button severity="secondary" disabled />
        </div>
    </div>
</template>

<script setup lang="ts">
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
</script>