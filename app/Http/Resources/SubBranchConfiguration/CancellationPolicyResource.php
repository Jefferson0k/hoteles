<?php

namespace App\Http\Resources\SubBranchConfiguration;

use Illuminate\Http\Resources\Json\JsonResource;

class CancellationPolicyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'time_limit_hours' => $this->time_limit_hours,
            'refund_percentage' => (float) $this->refund_percentage,
            'no_show_charge' => (float) $this->no_show_charge,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
