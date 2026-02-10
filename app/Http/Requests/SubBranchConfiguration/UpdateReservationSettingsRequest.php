<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'min_advance_hours' => 'required|integer|min:0',
            'max_advance_days' => 'required|integer|min:1',
            'last_minute_surcharge_percentage' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'max_advance_days.min' => 'La anticipación máxima debe ser al menos 1 día',
        ];
    }
}
