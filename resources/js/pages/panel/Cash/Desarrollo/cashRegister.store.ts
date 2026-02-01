import { defineStore } from 'pinia'
import axios from 'axios'

export interface SubBranch {
  id: string
  name: string
}

export interface OccupiedBy {
  id: string
  name: string
}

export interface CashRegister {
  id: string
  name: string
  is_active: boolean
  is_occupied: boolean
  occupied_by?: OccupiedBy | null
  sub_branch?: SubBranch
  created_at: string
}

interface State {
  items: CashRegister[]
  loading: boolean
  error: string | null
}

export const useCashRegisterStore = defineStore('cashRegister', {
  state: (): State => ({
    items: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchAll() {
      this.loading = true
      this.error = null
      try {
        const { data } = await axios.get('/cash')
        if (data.success) {
          this.items = data.data
        } else {
          this.error = data.message || 'Error al obtener las cajas registradoras'
        }
      } catch (e: any) {
        console.error('Error fetching cash registers:', e)
        this.error = e.response?.data?.message || 'Error al obtener las cajas registradoras'
      } finally {
        this.loading = false
      }
    },

    async createMultiple(quantity: number) {
      this.loading = true
      this.error = null
      try {
        const { data } = await axios.post('/cash/multiple', { quantity })
        if (data.success) {
          await this.fetchAll() // Refresh the list
          return { success: true, message: data.message }
        } else {
          this.error = data.message
          return { success: false, message: data.message }
        }
      } catch (e: any) {
        console.error('Error creating cash registers:', e)
        const errorMessage = e.response?.data?.message || 'Error al crear las cajas'
        this.error = errorMessage
        return { success: false, message: errorMessage }
      } finally {
        this.loading = false
      }
    },

    async update(id: string, data: Partial<CashRegister>) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.put(`/cash/${id}`, data)
        if (response.data.success) {
          await this.fetchAll() // Refresh the list
          return { success: true, message: response.data.message }
        } else {
          this.error = response.data.message
          return { success: false, message: response.data.message }
        }
      } catch (e: any) {
        console.error('Error updating cash register:', e)
        const errorMessage = e.response?.data?.message || 'Error al actualizar la caja'
        this.error = errorMessage
        return { success: false, message: errorMessage }
      } finally {
        this.loading = false
      }
    },

    async delete(id: string) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.delete(`/cash/${id}`)
        if (response.data.success) {
          await this.fetchAll() // Refresh the list
          return { success: true, message: response.data.message }
        } else {
          this.error = response.data.message
          return { success: false, message: response.data.message }
        }
      } catch (e: any) {
        console.error('Error deleting cash register:', e)
        const errorMessage = e.response?.data?.message || 'Error al eliminar la caja'
        this.error = errorMessage
        return { success: false, message: errorMessage }
      } finally {
        this.loading = false
      }
    },

    clearError() {
      this.error = null
    }
  },

  getters: {
    activeCashRegisters: (state) => state.items.filter(item => item.is_active),
    occupiedCashRegisters: (state) => state.items.filter(item => item.is_occupied),
    availableCashRegisters: (state) => state.items.filter(item => item.is_active && !item.is_occupied)
  }
})