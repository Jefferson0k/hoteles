<?php

namespace App\Http\Resources\SubBranchConfiguration;

use Illuminate\Http\Resources\Json\JsonResource;

class CheckinSettingsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'checkin_time' => $this->checkin_time,
            'checkout_time' => $this->checkout_time,
            'early_checkin_cost' => (float) $this->early_checkin_cost,
            'late_checkout_cost' => (float) $this->late_checkout_cost,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
