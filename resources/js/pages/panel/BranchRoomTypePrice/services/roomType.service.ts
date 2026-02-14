import axios, { AxiosResponse } from 'axios';

export interface IRoomType {
  id: string;
  name: string;
  code: string;
  description?: string;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

interface IRoomTypeResponse {
  data: IRoomType[];
}

const API_URL = '/room-types';

export const roomTypeService = {
  /**
   * Obtener opciones de tipos de habitación
   */
  async getOptions(): Promise<IRoomType[]> {
    const response: AxiosResponse<IRoomTypeResponse> = await axios.get(
      `${API_URL}/opciones`
    );
    return response.data.data;
  },
};