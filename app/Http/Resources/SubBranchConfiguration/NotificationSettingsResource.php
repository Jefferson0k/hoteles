<?php

namespace App\Http\Resources\SubBranchConfiguration;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationSettingsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reservation_reminder_active' => $this->reservation_reminder_active,
            'reminder_hours_before' => $this->reminder_hours_before,
            'excess_alert_active' => $this->excess_alert_active,
            'confirmation_email_active' => $this->confirmation_email_active,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
