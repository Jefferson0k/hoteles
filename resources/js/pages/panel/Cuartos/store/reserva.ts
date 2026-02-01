import { defineStore } from 'pinia';

export const useReservaStore = defineStore('reserva', {
  state: () => ({
    habitacion: null as any,
    tarifaSeleccionada: null as 'hour' | 'day' | 'night' | null,
    precioSeleccionado: 0,
    cliente: null,
    productos: [],
    horaInicio: null as Date | null,
  }),
  
  getters: {
    totalReserva: (state) => {
      const precioProductos = state.productos.reduce((sum: number, p: any) => sum + p.total, 0);
      return state.precioSeleccionado + precioProductos;
    },
  },
  
  actions: {
    setHabitacion(habitacion: any) {
      this.habitacion = habitacion;
    },
    
    setTarifa(tipo: 'hour' | 'day' | 'night', precio: number) {
      this.tarifaSeleccionada = tipo;
      this.precioSeleccionado = precio;
    },
    
    setCliente(cliente: any) {
      this.cliente = cliente;
    },
    
    iniciarCronometro() {
      this.horaInicio = new Date();
    },
  },
});