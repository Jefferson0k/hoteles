import axios, { AxiosResponse } from 'axios';

export interface IRateType {
  id: string;
  name: string;
  code: string;
  description: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

interface IRateTypeResponse {
  data: IRateType[];
}

const API_URL = '/rate-types';

export const rateTypeService = {
  /**
   * Obtener opciones de tipos de tarifa
   */
  async getOptions(): Promise<IRateType[]> {
    const response: AxiosResponse<IRateTypeResponse> = await axios.get(
      `${API_URL}/opcones`
    );
    return response.data.data;
  },
};