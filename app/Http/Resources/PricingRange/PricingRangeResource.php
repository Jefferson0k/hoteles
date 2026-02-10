<?php

namespace App\Http\Resources\PricingRange;

use App\Http\Resources\BranchRoomTypePrice\BranchRoomTypePriceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingRangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_room_type_price_id' => $this->branch_room_type_price_id,
            'time_from_minutes' => $this->time_from_minutes,
            'time_to_minutes' => $this->time_to_minutes,
            'price' => number_format($this->price, 2, '.', ''),
            'is_active' => $this->is_active,
            
            // Datos calculados
            'duration_hours' => round($this->getDurationInHours(), 2),
            'formatted_time_range' => $this->getFormattedTimeRange(),
            'price_per_hour' => number_format($this->getPricePerHour(), 2, '.', ''),
            
            // Relaciones
            'branch_room_type_price' => new BranchRoomTypePriceResource($this->whenLoaded('branchRoomTypePrice')),
            
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
