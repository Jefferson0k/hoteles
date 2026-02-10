import axios, { AxiosResponse } from 'axios';
import type {
    IRoomType,
    IRoomTypeForm,
    IRoomTypeFilters,
    IRoomTypeResponse,
    IRoomTypeApiResponse
} from '../interfaces';

const API_URL = '/room-types';

export class RoomTypeService {
    /**
     * Obtener listado de tipos de habitación
     */
    static async getAll(filters?: IRoomTypeFilters): Promise<IRoomTypeResponse> {
        const response: AxiosResponse<IRoomTypeResponse> = await axios.get(API_URL, {
            params: filters
        });
        return response.data;
    }

    /**
     * Obtener un tipo de habitación por ID
     */
    static async getById(id: string): Promise<IRoomType> {
        const response: AxiosResponse<IRoomTypeApiResponse> = await axios.get(`${API_URL}/${id}`);
        return response.data.data;
    }

    /**
     * Crear nuevo tipo de habitación
     */
    static async create(data: IRoomTypeForm): Promise<IRoomTypeApiResponse> {
        const response: AxiosResponse<IRoomTypeApiResponse> = await axios.post(API_URL, data);
        return response.data;
    }

    /**
     * Actualizar tipo de habitación
     */
    static async update(id: string, data: IRoomTypeForm): Promise<IRoomTypeApiResponse> {
        const response: AxiosResponse<IRoomTypeApiResponse> = await axios.put(`${API_URL}/${id}`, data);
        return response.data;
    }

    /**
     * Eliminar tipo de habitación
     */
    static async delete(id: string): Promise<{ message: string }> {
        const response: AxiosResponse<{ message: string }> = await axios.delete(`${API_URL}/${id}`);
        return response.data;
    }
}