import axios, { AxiosResponse } from 'axios';
import type {
  IBranchRoomTypePrice,
  IBranchRoomTypePriceForm,
  IBranchRoomTypePriceFilters,
  IApiResponse,
  IApiCollectionResponse,
  IPricingOptions,
  ICalculatePriceRequest,
  ICalculatePriceResponse,
} from '../interfaces/branchRoomTypePrice.interface';

const API_URL = '/branch-room-type-prices';

export const branchRoomTypePriceService = {
  /**
   * Obtener todas las configuraciones de precios con filtros opcionales
   */
  async getAll(
    filters?: IBranchRoomTypePriceFilters
  ): Promise<IBranchRoomTypePrice[]> {
    const response: AxiosResponse<IApiCollectionResponse<IBranchRoomTypePrice>> =
      await axios.get(API_URL, { params: filters });
    return response.data.data;
  },

  /**
   * Obtener una configuración de precio por ID
   */
  async getById(id: string): Promise<IBranchRoomTypePrice> {
    const response: AxiosResponse<IApiResponse<IBranchRoomTypePrice>> =
      await axios.get(`${API_URL}/${id}`);
    return response.data.data;
  },

  /**
   * Crear nueva configuración de precio
   */
  async create(
    data: IBranchRoomTypePriceForm
  ): Promise<IApiResponse<IBranchRoomTypePrice>> {
    const response: AxiosResponse<IApiResponse<IBranchRoomTypePrice>> =
      await axios.post(API_URL, data);
    return response.data;
  },

  /**
   * Actualizar configuración de precio existente
   */
  async update(
    id: string,
    data: IBranchRoomTypePriceForm
  ): Promise<IApiResponse<IBranchRoomTypePrice>> {
    const response: AxiosResponse<IApiResponse<IBranchRoomTypePrice>> =
      await axios.put(`${API_URL}/${id}`, data);
    return response.data;
  },

  /**
   * Eliminar configuración de precio
   */
  async delete(id: string): Promise<{ message: string }> {
    const response: AxiosResponse<{ message: string }> = await axios.delete(
      `${API_URL}/${id}`
    );
    return response.data;
  },

  /**
   * Obtener opciones de precio para una configuración específica
   */
  async getPricingOptions(params: {
    sub_branch_id: string;
    room_type_id: string;
    rate_type_id: string;
    date?: string;
  }): Promise<IPricingOptions> {
    const response: AxiosResponse<IApiResponse<IPricingOptions>> =
      await axios.get(`${API_URL}/pricing-options`, { params });
    return response.data.data;
  },

  /**
   * Calcular precio para minutos específicos
   */
  async calculatePrice(
    data: ICalculatePriceRequest
  ): Promise<ICalculatePriceResponse> {
    const response: AxiosResponse<IApiResponse<ICalculatePriceResponse>> =
      await axios.post(`${API_URL}/calculate-price`, data);
    return response.data.data;
  },
};