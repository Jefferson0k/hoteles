// stores/subBranchConfigurationStore.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

// ==================== INTERFACES ====================

export interface TimeSettings {
  id?: string
  max_allowed_time: number
  extra_tolerance: number
  apply_tolerance: boolean
  created_at?: string
  updated_at?: string
}

export interface CheckinSettings {
  id?: string
  checkin_time: string
  checkout_time: string
  early_checkin_cost: number
  late_checkout_cost: number
  created_at?: string
  updated_at?: string
}

export interface PenaltySettings {
  id?: string
  penalty_active: boolean
  charge_interval_minutes: number
  amount_per_interval: number
  penalty_type: 'fixed' | 'progressive'
  created_at?: string
  updated_at?: string
}

export interface CancellationPolicy {
  id?: string
  time_limit_hours: number
  refund_percentage: number
  no_show_charge: number
  created_at?: string
  updated_at?: string
}

export interface DepositSettings {
  id?: string
  requires_deposit: boolean
  deposit_amount: number
  payment_method: string | null
  created_at?: string
  updated_at?: string
}

export interface TaxSettings {
  id?: string
  tax_percentage: number
  tax_included: boolean
  created_at?: string
  updated_at?: string
}

export interface ReservationSettings {
  id?: string
  min_advance_hours: number
  max_advance_days: number
  last_minute_surcharge_percentage: number
  created_at?: string
  updated_at?: string
}

export interface NotificationSettings {
  id?: string
  reservation_reminder_active: boolean
  reminder_hours_before: number
  excess_alert_active: boolean
  confirmation_email_active: boolean
  created_at?: string
  updated_at?: string
}

export interface SubBranchInfo {
  id: string
  name: string
  code: string
}

export interface SubBranch {
  id: string
  branch_id: string
  name: string
  code: string
  address: string
  phone: string
  is_active: boolean
  available_rooms_count: number
  creacion: string
  actualizacion: string
}

export interface Configuration {
  sub_branch: SubBranchInfo
  time: TimeSettings | null
  checkin: CheckinSettings | null
  penalty: PenaltySettings | null
  cancellation: CancellationPolicy | null
  deposit: DepositSettings | null
  tax: TaxSettings | null
  reservation: ReservationSettings | null
  notification: NotificationSettings | null
}

// ==================== STORE ====================

export const useSubBranchConfigurationStore = defineStore('subBranchConfiguration', () => {
  // ==================== STATE ====================
  
  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)
  const configuration = ref<Configuration | null>(null)
  const currentSubBranchId = ref<string | null>(null)
  
  // Estado para sucursales
  const subBranches = ref<SubBranch[]>([])
  const loadingSubBranches = ref(false)

  // ==================== GETTERS ====================
  
  const hasConfiguration = computed(() => configuration.value !== null)
  const timeSettings = computed(() => configuration.value?.time)
  const checkinSettings = computed(() => configuration.value?.checkin)
  const penaltySettings = computed(() => configuration.value?.penalty)
  const cancellationPolicy = computed(() => configuration.value?.cancellation)
  const depositSettings = computed(() => configuration.value?.deposit)
  const taxSettings = computed(() => configuration.value?.tax)
  const reservationSettings = computed(() => configuration.value?.reservation)
  const notificationSettings = computed(() => configuration.value?.notification)

  // ==================== ACTIONS - SUCURSALES ====================

  /**
   * Cargar lista de sucursales
   */
  async function loadSubBranches() {
    loadingSubBranches.value = true
    error.value = null
    
    try {
      const response = await axios.get('/sub-branches/search')
      subBranches.value = response.data.data || []
      return { success: true, data: subBranches.value }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al cargar las sucursales'
      error.value = errorMessage
      console.error('Error loading sub-branches:', err)
      return { success: false, error: errorMessage }
    } finally {
      loadingSubBranches.value = false
    }
  }

  // ==================== ACTIONS - CONFIGURACIÓN ====================

  /**
   * Cargar configuración completa de una sub-sucursal
   */
  async function loadConfiguration(subBranchId: string) {
    loading.value = true
    error.value = null

    try {
      const response = await axios.get(`/sub-branches/${subBranchId}/configuration`)
      configuration.value = response.data.data
      currentSubBranchId.value = subBranchId
      return { success: true, data: response.data.data }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al cargar la configuración'
      error.value = errorMessage
      console.error('Error loading configuration:', err)
      return { success: false, error: errorMessage }
    } finally {
      loading.value = false
    }
  }

  /**
   * Guardar configuración completa
   */
  async function saveFullConfiguration(subBranchId: string, data: any) {
    saving.value = true
    error.value = null

    try {
      const response = await axios.post(`/sub-branches/${subBranchId}/configuration`, data)
      configuration.value = response.data.data
      return { 
        success: true, 
        data: response.data.data, 
        message: response.data.message || 'Configuración guardada exitosamente' 
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al guardar la configuración'
      error.value = errorMessage
      console.error('Error saving configuration:', err)
      return { success: false, error: errorMessage }
    } finally {
      saving.value = false
    }
  }

  /**
   * Actualizar configuración de tiempo
   */
  async function updateTimeSettings(subBranchId: string, data: Partial<TimeSettings>) {
    saving.value = true
    error.value = null

    try {
      const response = await axios.put(`/sub-branches/${subBranchId}/configuration/time`, data)
      
      if (configuration.value) {
        configuration.value.time = response.data.data
      }
      
      return { 
        success: true, 
        data: response.data.data, 
        message: response.data.message || 'Configuración de tiempo actualizada' 
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al actualizar configuración de tiempo'
      error.value = errorMessage
      console.error('Error updating time settings:', err)
      return { success: false, error: errorMessage }
    } finally {
      saving.value = false
    }
  }

  /**
   * Actualizar configuración de check-in
   */
  function normalizeTime(time?: string) {
    if (!time) return time
    return time.slice(0, 5) // "14:00:00" → "14:00"
  }

  async function updateCheckinSettings(subBranchId: string, data: Partial<CheckinSettings>) {
    saving.value = true
    error.value = null

    try {
      const payload = {
        ...data,
        checkin_time: normalizeTime(data.checkin_time),
        checkout_time: normalizeTime(data.checkout_time),
      }

      const response = await axios.put(
        `/sub-branches/${subBranchId}/configuration/checkin`,
        payload
      )

      if (configuration.value) {
        configuration.value.checkin = response.data.data
      }

      return {
        success: true,
        data: response.data.data,
        message: response.data.message || 'Configuración de check-in actualizada'
      }

    } catch (err: any) {
      const errorMessage =
        err.response?.data?.message || 'Error al actualizar configuración de check-in'

      error.value = errorMessage
      return { success: false, error: errorMessage }

    } finally {
      saving.value = false
    }
  }


  /**
   * Actualizar configuración de penalización
   */
  async function updatePenaltySettings(subBranchId: string, data: Partial<PenaltySettings>) {
    saving.value = true
    error.value = null

    try {
      const response = await axios.put(`/sub-branches/${subBranchId}/configuration/penalty`, data)
      
      if (configuration.value) {
        configuration.value.penalty = response.data.data
      }
      
      return { 
        success: true, 
        data: response.data.data, 
        message: response.data.message || 'Configuración de penalización actualizada' 
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al actualizar configuración de penalización'
      error.value = errorMessage
      console.error('Error updating penalty settings:', err)
      return { success: false, error: errorMessage }
    } finally {
      saving.value = false
    }
  }

  /**
   * Actualizar política de cancelación
   */
  async function updateCancellationPolicy(subBranchId: string, data: Partial<CancellationPolicy>) {
    saving.value = true
    error.value = null

    try {
      const response = await axios.put(`/sub-branches/${subBranchId}/configuration/cancellation`, data)
      
      if (configuration.value) {
        configuration.value.cancellation = response.data.data
      }
      
      return { 
        success: true, 
        data: response.data.data, 
        message: response.data.message || 'Política de cancelación actualizada' 
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al actualizar política de cancelación'
      error.value = errorMessage
      console.error('Error updating cancellation policy:', err)
      return { success: false, error: errorMessage }
    } finally {
      saving.value = false
    }
  }

  /**
   * Actualizar configuración de depósitos
   */
  async function updateDepositSettings(subBranchId: string, data: Partial<DepositSettings>) {
    saving.value = true
    error.value = null

    try {
      const response = await axios.put(`/sub-branches/${subBranchId}/configuration/deposit`, data)
      
      if (configuration.value) {
        configuration.value.deposit = response.data.data
      }
      
      return { 
        success: true, 
        data: response.data.data, 
        message: response.data.message || 'Configuración de depósitos actualizada' 
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al actualizar configuración de depósitos'
      error.value = errorMessage
      console.error('Error updating deposit settings:', err)
      return { success: false, error: errorMessage }
    } finally {
      saving.value = false
    }
  }

  /**
   * Actualizar configuración de impuestos
   */
  async function updateTaxSettings(subBranchId: string, data: Partial<TaxSettings>) {
    saving.value = true
    error.value = null

    try {
      const response = await axios.put(`/sub-branches/${subBranchId}/configuration/tax`, data)
      
      if (configuration.value) {
        configuration.value.tax = response.data.data
      }
      
      return { 
        success: true, 
        data: response.data.data, 
        message: response.data.message || 'Configuración de impuestos actualizada' 
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al actualizar configuración de impuestos'
      error.value = errorMessage
      console.error('Error updating tax settings:', err)
      return { success: false, error: errorMessage }
    } finally {
      saving.value = false
    }
  }

  /**
   * Actualizar configuración de reservas
   */
  async function updateReservationSettings(subBranchId: string, data: Partial<ReservationSettings>) {
    saving.value = true
    error.value = null

    try {
      const response = await axios.put(`/sub-branches/${subBranchId}/configuration/reservation`, data)
      
      if (configuration.value) {
        configuration.value.reservation = response.data.data
      }
      
      return { 
        success: true, 
        data: response.data.data, 
        message: response.data.message || 'Configuración de reservas actualizada' 
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al actualizar configuración de reservas'
      error.value = errorMessage
      console.error('Error updating reservation settings:', err)
      return { success: false, error: errorMessage }
    } finally {
      saving.value = false
    }
  }

  /**
   * Actualizar configuración de notificaciones
   */
  async function updateNotificationSettings(subBranchId: string, data: Partial<NotificationSettings>) {
    saving.value = true
    error.value = null

    try {
      const response = await axios.put(`/sub-branches/${subBranchId}/configuration/notification`, data)
      
      if (configuration.value) {
        configuration.value.notification = response.data.data
      }
      
      return { 
        success: true, 
        data: response.data.data, 
        message: response.data.message || 'Configuración de notificaciones actualizada' 
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al actualizar configuración de notificaciones'
      error.value = errorMessage
      console.error('Error updating notification settings:', err)
      return { success: false, error: errorMessage }
    } finally {
      saving.value = false
    }
  }

  /**
   * Clonar configuración de una sub-sucursal a otra
   */
  async function cloneConfiguration(sourceSubBranchId: string, targetSubBranchId: string) {
    saving.value = true
    error.value = null

    try {
      const response = await axios.post(
        `/sub-branches/${sourceSubBranchId}/configuration/clone`,
        { target_sub_branch_id: targetSubBranchId }
      )
      
      return { 
        success: true, 
        data: response.data, 
        message: response.data.message || 'Clonación de configuración iniciada' 
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.message || 'Error al clonar configuración'
      error.value = errorMessage
      console.error('Error cloning configuration:', err)
      return { success: false, error: errorMessage }
    } finally {
      saving.value = false
    }
  }

  // ==================== UTILITY FUNCTIONS ====================

  /**
   * Resetear el estado del store
   */
  function resetState() {
    configuration.value = null
    currentSubBranchId.value = null
    error.value = null
  }

  /**
   * Limpiar errores
   */
  function clearError() {
    error.value = null
  }

  /**
   * Obtener sucursal por ID
   */
  function getSubBranchById(id: string) {
    return subBranches.value.find(branch => branch.id === id)
  }

  /**
   * Verificar si una sección está configurada
   */
  function hasSection(section: keyof Omit<Configuration, 'sub_branch'>) {
    return configuration.value?.[section] !== null && configuration.value?.[section] !== undefined
  }

  // ==================== RETURN ====================

  return {
    // State
    loading,
    saving,
    error,
    configuration,
    currentSubBranchId,
    subBranches,
    loadingSubBranches,

    // Getters
    hasConfiguration,
    timeSettings,
    checkinSettings,
    penaltySettings,
    cancellationPolicy,
    depositSettings,
    taxSettings,
    reservationSettings,
    notificationSettings,

    // Actions - Sucursales
    loadSubBranches,
    getSubBranchById,

    // Actions - Configuración
    loadConfiguration,
    saveFullConfiguration,
    updateTimeSettings,
    updateCheckinSettings,
    updatePenaltySettings,
    updateCancellationPolicy,
    updateDepositSettings,
    updateTaxSettings,
    updateReservationSettings,
    updateNotificationSettings,
    cloneConfiguration,

    // Utility
    resetState,
    clearError,
    hasSection,
  }
})