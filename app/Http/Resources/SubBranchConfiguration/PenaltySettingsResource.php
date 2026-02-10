<?php

namespace App\Http\Resources\SubBranchConfiguration;

use Illuminate\Http\Resources\Json\JsonResource;

class PenaltySettingsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'penalty_active' => $this->penalty_active,
            'charge_interval_minutes' => $this->charge_interval_minutes,
            'amount_per_interval' => (float) $this->amount_per_interval,
            'penalty_type' => $this->penalty_type,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
