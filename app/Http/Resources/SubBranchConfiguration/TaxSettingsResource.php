<?php

namespace App\Http\Resources\SubBranchConfiguration;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxSettingsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tax_percentage' => (float) $this->tax_percentage,
            'tax_included' => $this->tax_included,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
