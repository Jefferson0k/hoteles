<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckinSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'checkin_time' => 'required|date_format:H:i',
            'checkout_time' => 'required|date_format:H:i',
            'early_checkin_cost' => 'required|numeric|min:0',
            'late_checkout_cost' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'checkin_time.date_format' => 'El formato de hora de check-in debe ser HH:MM',
            'checkout_time.date_format' => 'El formato de hora de check-out debe ser HH:MM',
        ];
    }
}
