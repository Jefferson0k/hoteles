import axios, { AxiosResponse } from 'axios';

export interface ISubBranch {
  id: string;
  branch_id: string;
  name: string;
  code: string;
  address: string;
  phone: string;
  is_active: boolean;
  available_rooms_count: number;
  creacion: string;
  actualizacion: string;
}

interface ISubBranchResponse {
  data: ISubBranch[];
}

const API_URL = '/sub-branches';

export const subBranchService = {
  /**
   * Buscar sub-sucursales
   */
  async search(): Promise<ISubBranch[]> {
    const response: AxiosResponse<ISubBranchResponse> = await axios.get(
      `${API_URL}/search`
    );
    return response.data.data;
  },
};