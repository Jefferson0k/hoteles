<?php

namespace App\Http\Resources\SubBranchConfiguration;

use Illuminate\Http\Resources\Json\JsonResource;

class ReservationSettingsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'min_advance_hours' => $this->min_advance_hours,
            'max_advance_days' => $this->max_advance_days,
            'last_minute_surcharge_percentage' => (float) $this->last_minute_surcharge_percentage,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
