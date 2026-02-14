import axios from 'axios';
import type { 
    RateType, 
    RateTypeFormData, 
    RateTypeResponse, 
    RateTypeListResponse 
} from '../interfaces/rateType.interface';

const API_URL = '/rate-types';

export const rateTypeService = {
    async getAll(): Promise<RateType[]> {
        const response = await axios.get<RateTypeListResponse>(API_URL);
        return response.data.data;
    },

    async getById(id: number): Promise<RateType> {
        const response = await axios.get<RateTypeResponse>(`${API_URL}/${id}`);
        return response.data.data;
    },

    async create(data: RateTypeFormData): Promise<RateTypeResponse> {
        const response = await axios.post<RateTypeResponse>(API_URL, data);
        return response.data;
    },

    async update(id: number, data: RateTypeFormData): Promise<RateTypeResponse> {
        const response = await axios.put<RateTypeResponse>(`${API_URL}/${id}`, data);
        return response.data;
    },

    async delete(id: number): Promise<{ message: string }> {
        const response = await axios.delete<{ message: string }>(`${API_URL}/${id}`);
        return response.data;
    }
};