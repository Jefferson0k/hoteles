export interface ISubBranch {
  id: string;
  name: string;
  code: string;
}

export interface IRoomType {
  id: string;
  name: string;
  code: string;
  description?: string;
}

export interface IRateType {
  id: string;
  name: string;
  code: string;
  description?: string;
}

export interface IPricingRange {
  id: string;
  min_minutes: number;
  max_minutes: number | null;
  price: number;
  is_active: boolean;
}

export interface IBranchRoomTypePrice {
  id: string;
  sub_branch_id: string;
  room_type_id: string;
  rate_type_id: string;
  effective_from: string;
  effective_to: string | null;
  is_active: boolean;
  is_currently_effective: boolean;
  has_expired: boolean;
  sub_branch?: ISubBranch;
  room_type?: IRoomType;
  rate_type?: IRateType;
  pricing_ranges?: IPricingRange[];
  created_at?: string;
  updated_at?: string;
}

export interface IBranchRoomTypePriceForm {
  sub_branch_id: string;
  room_type_id: string;
  rate_type_id: string;
  effective_from: string;
  effective_to: string | null;
  is_active: boolean;
}

export interface IPricingOptions {
  branch_room_type_price: IBranchRoomTypePrice;
  pricing_options: IPricingRange[];
}

export interface ICalculatePriceRequest {
  sub_branch_id: string;
  room_type_id: string;
  rate_type_id: string;
  minutes: number;
  date?: string;
}

export interface ICalculatePriceResponse {
  minutes: number;
  price: string;
  date: string;
}

export interface IBranchRoomTypePriceFilters {
  sub_branch_id?: string;
  room_type_id?: string;
  rate_type_id?: string;
  is_active?: boolean;
  current_only?: boolean;
}

export interface IApiResponse<T> {
  data: T;
  message?: string;
}

export interface IApiCollectionResponse<T> {
  data: T[];
  message?: string;
}