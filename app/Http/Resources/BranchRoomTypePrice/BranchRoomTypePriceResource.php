<?php

namespace App\Http\Resources\BranchRoomTypePrice;

use App\Http\Resources\SubBranch\SubBranchResource;
use App\Http\Resources\RateType\RateTypeResource;
use App\Http\Resources\Room\RoomTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchRoomTypePriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sub_branch_id' => $this->sub_branch_id,
            'room_type_id' => $this->room_type_id,
            'rate_type_id' => $this->rate_type_id,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'is_currently_effective' => $this->isCurrentlyEffective(),
            'has_expired' => $this->hasExpired(),
            
            // Relaciones
            'sub_branch' => new SubBranchResource($this->whenLoaded('subBranch')),
            'room_type' => new RoomTypeResource($this->whenLoaded('roomType')),
            'rate_type' => new RateTypeResource($this->whenLoaded('rateType')),
            'pricing_ranges' => $this->whenLoaded('pricingRanges', function () {
                return $this->getPricingOptions();
            }),
            
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}