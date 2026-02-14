export interface RateType {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface RateTypeFormData {
    name: string;
    code: string;
    description?: string;
    is_active: boolean;
}

export interface RateTypeResponse {
    data: RateType;
    message?: string;
}

export interface RateTypeListResponse {
    data: RateType[];
}

export interface RateTypeFilters {
    search?: string;
    is_active?: boolean;
}