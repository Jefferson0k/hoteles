<?php

namespace App\Http\Resources\SubBranchConfiguration;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'sub_branch' => [
                'id' => $this->id,
                'name' => $this->name,
                'code' => $this->code,
            ],
            'time' => new TimeSettingsResource($this->whenLoaded('timeSettings')),
            'checkin' => new CheckinSettingsResource($this->whenLoaded('checkinSettings')),
            'penalty' => new PenaltySettingsResource($this->whenLoaded('penaltySettings')),
            'cancellation' => new CancellationPolicyResource($this->whenLoaded('cancellationPolicies')),
            'deposit' => new DepositSettingsResource($this->whenLoaded('depositSettings')),
            'tax' => new TaxSettingsResource($this->whenLoaded('taxSettings')),
            'reservation' => new ReservationSettingsResource($this->whenLoaded('reservationSettings')),
            'notification' => new NotificationSettingsResource($this->whenLoaded('notificationSettings')),
        ];
    }
}
