/**
 * Interface principal de RoomType
 */
export interface IRoomType {
    id?: string;
    name: string;
    code: string;
    description: string;
    capacity: number;
    max_capacity: number | null;
    category: string | null;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}

/**
 * Interface para crear/actualizar RoomType
 */
export interface IRoomTypeForm {
    name: string;
    code?: string;
    description?: string;
    capacity: number;
    max_capacity?: number | null;
    category?: string | null;
    is_active?: boolean;
}

/**
 * Interface para filtros de búsqueda
 */
export interface IRoomTypeFilters {
    search?: string;
    state?: boolean | string;
    per_page?: number;
    page?: number;
}

/**
 * Interface para errores de validación del formulario
 */
export interface IRoomTypeFormErrors {
    name?: string;
    code?: string;
    description?: string;
    capacity?: string;
    max_capacity?: string;
    category?: string;
    is_active?: string;
}

/**
 * Interface para respuesta paginada de la API
 */
export interface IRoomTypeResponse {
    data: IRoomType[];
    meta?: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

/**
 * Interface para respuesta de una operación exitosa
 */
export interface IRoomTypeApiResponse {
    message: string;
    data: IRoomType;
}

/**
 * Enum para categorías de habitación
 */
export enum RoomTypeCategory {
    ECONOMICA = 'Económica',
    ESTANDAR = 'Estándar',
    PREMIUM = 'Premium',
    LUJO = 'Lujo'
}

/**
 * Array de categorías disponibles
 */
export const ROOM_TYPE_CATEGORIES: string[] = [
    RoomTypeCategory.ECONOMICA,
    RoomTypeCategory.ESTANDAR,
    RoomTypeCategory.PREMIUM,
    RoomTypeCategory.LUJO
];

/**
 * Interface para el estado del store
 */
export interface IRoomTypeState {
    roomTypes: IRoomType[];
    currentRoomType: IRoomType | null;
    loading: boolean;
    error: string | null;
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}