<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reservation_reminder_active' => 'required|boolean',
            'reminder_hours_before' => 'required|integer|min:0',
            'excess_alert_active' => 'required|boolean',
            'confirmation_email_active' => 'required|boolean',
        ];
    }
}
