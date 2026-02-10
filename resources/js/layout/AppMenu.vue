<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppMenuItem from './AppMenuItem.vue'

const page = usePage()
const permissions = computed(() => page.props.auth.user?.permissions ?? [])
const hasPermission = (perm) => permissions.value.includes(perm)

const model = computed(() => [
    {
        label: 'Home',
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/dashboard' }
        ]
    },

    {
        label: 'Operación',
        items: [
            hasPermission('view branches') && { label: 'Sucursales', icon: 'pi pi-fw pi-building', to: '/panel/sucursales' },
            hasPermission('view caja') && { label: 'Cajas', icon: 'pi pi-fw pi-sitemap', to: '/panel/cajas' },
            hasPermission('view rooms') && { label: 'Habitaciones', icon: 'pi pi-fw pi-home', to: '/panel/habitaciones' },
        ].filter(Boolean),
    },

    {
        label: 'Pagos',
        items: [
            hasPermission('view pagos personal') && { label: 'Gestión de Pagos', icon: 'pi pi-fw pi-credit-card', to: '/panel/pagos/personal' },
        ].filter(Boolean),
    },

    {
        label: 'Inventario',
        items: [
            hasPermission('view inventory') && { label: 'Gestión de Inventario', icon: 'pi pi-fw pi-box', to: '/panel/inventario' },
            hasPermission('adjust inventory stock') && { label: 'Ajustes de Stock', icon: 'pi pi-fw pi-sort-amount-up', to: '/panel/inventario/ajustes' },
            hasPermission('transfer inventory') && { label: 'Transferencias', icon: 'pi pi-fw pi-exchange', to: '/panel/inventario/transferencias' },
        ].filter(Boolean),
    },

    {
        label: 'Gestión',
        items: [
            hasPermission('view suppliers') && { label: 'Proveedores', icon: 'pi pi-fw pi-truck', to: '/panel/proveedores' },
            hasPermission('create suppliers') && { label: 'Nuevo Proveedor', icon: 'pi pi-fw pi-plus', to: '/panel/proveedores/nuevo' },
            hasPermission('view clients') && { label: 'Clientes', icon: 'pi pi-fw pi-user', to: '/panel/clientes' },
            hasPermission('create clients') && { label: 'Nuevo Cliente', icon: 'pi pi-fw pi-user-plus', to: '/panel/clientes/nuevo' },
            hasPermission('view products') && { label: 'Productos', icon: 'pi pi-fw pi-tags', to: '/panel/productos' },
            hasPermission('ver categorias') && { label: 'Categorías', icon: 'pi pi-fw pi-list', to: '/panel/categorias' },
        ].filter(Boolean),
    },

    {
        label: 'Kardex',
        items: [
            hasPermission('view kardex general') && { label: 'Kardex General', icon: 'pi pi-fw pi-file-o', to: '/panel/kardex/general' },
            hasPermission('view kardex producto') && { label: 'Kardex por Producto', icon: 'pi pi-fw pi-list', to: '/panel/kardex' },
            hasPermission('view kardex valorizado') && { label: 'Kardex Valorizado', icon: 'pi pi-fw pi-dollar', to: '/panel/kardex/valorizado' },
        ].filter(Boolean),
    },

    {
        label: 'Movimientos',
        items: [
            hasPermission('view movimiento') && { label: 'Todos los Movimientos', icon: 'pi pi-fw pi-history', to: '/panel/movimientos' },
            hasPermission('view movements') && { label: 'Entradas', icon: 'pi pi-fw pi-arrow-down', to: '/panel/movimientos/entradas' },
            hasPermission('view movements') && { label: 'Salidas', icon: 'pi pi-fw pi-arrow-up', to: '/panel/movimientos/salidas' },
            hasPermission('create movements') && { label: 'Nuevo Movimiento', icon: 'pi pi-fw pi-plus-circle', to: '/panel/movimientos/nuevo' },
        ].filter(Boolean),
    },

    {
        label: 'Reportes',
        items: [
            hasPermission('view reportes ingresos de habitacion') && { label: 'Ingresos de Habitaciones', icon: 'pi pi-fw pi-home', to: '/panel/reportes/ingresos-habitaciones' },
            hasPermission('view reportes ingresos de productos') && { label: 'Ingreso de Productos', icon: 'pi pi-fw pi-shopping-cart', to: '/panel/reportes/ingreso-productos' },
            hasPermission('view reportes ingresos brutos') && { label: 'Ingreso Bruto', icon: 'pi pi-fw pi-chart-line', to: '/panel/reportes/ingreso-bruto' },
            hasPermission('view reportes ingresos brutos') && { label: 'Ingreso Neto', icon: 'pi pi-fw pi-chart-bar', to: '/panel/reportes/ingreso-neto' },
            hasPermission('view reportes de egresos') && { label: 'Egresos', icon: 'pi pi-fw pi-money-bill', to: '/panel/reportes/egresos' },
            hasPermission('view reportes de numero de clientes') && { label: 'Número de Clientes', icon: 'pi pi-fw pi-users', to: '/panel/reportes/numero-clientes' },
            hasPermission('view reportes productos mas vendidos') && { label: 'Productos Más Vendidos', icon: 'pi pi-fw pi-star', to: '/panel/reportes/productos-mas-vendidos' },
            hasPermission('view reportes productos menos vendidos') && { label: 'Productos Menos Vendidos', icon: 'pi pi-fw pi-chart-pie', to: '/panel/reportes/productos-menos-vendidos' },
        ].filter(Boolean),
    },

    {
        label: 'Administración',
        items: [
            hasPermission('ver usuarios') && { label: 'Usuarios', icon: 'pi pi-fw pi-users', to: '/panel/usuario' },
            hasPermission('ver roles') && { label: 'Roles', icon: 'pi pi-fw pi-id-card', to: '/panel/roles' },
        ].filter(Boolean),
    },
    {
        label: 'Configuración avanzada',
        items: [
            { label: 'Parámetros del Sistema', icon: 'pi pi-fw pi-cog', to: '/configuracion/parametros' },
            { 
                label: 'Tarifas de Habitaciones', 
                icon: 'pi pi-fw pi-th-large', 
                to: '/configuracion/tarifas-habitaciones',
                items: [ // Submenú interno
                    { label: 'Tipos de Habitación', icon: 'pi pi-fw pi-home', to: '/configuracion/tarifas-habitaciones/room-type' },
                    { label: 'Tipos de Tarifa', icon: 'pi pi-fw pi-dollar', to: '/configuracion/tarifas-habitaciones/rate-type' },
                    { label: 'Precios por Tipo y Sucursal', icon: 'pi pi-fw pi-tag', to: '/configuracion/tarifas-habitaciones/branch-room-type-price' },
                    { label: 'Rangos de Precio', icon: 'pi pi-fw pi-clock', to: '/configuracion/tarifas-habitaciones/pricing-range' },
                ]
            },
            { label: 'Integraciones', icon: 'pi pi-fw pi-link', to: '/configuracion/integraciones' },
            { label: 'Auditoría / Logs', icon: 'pi pi-fw pi-eye', to: '/configuracion/logs' },
        ].filter(Boolean),
    }
].filter(section => section.items.length > 0))
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="i">
            <AppMenuItem :item="item" :index="i" />
        </template>
    </ul>
</template>
