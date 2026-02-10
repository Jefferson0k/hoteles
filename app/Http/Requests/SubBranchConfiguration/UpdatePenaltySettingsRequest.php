<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePenaltySettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'penalty_active' => 'required|boolean',
            'charge_interval_minutes' => 'required|integer|min:1',
            'amount_per_interval' => 'required|numeric|min:0',
            'penalty_type' => 'required|in:fixed,progressive',
        ];
    }

    public function messages()
    {
        return [
            'penalty_type.in' => 'El tipo de penalidad debe ser fixed o progressive',
            'charge_interval_minutes.min' => 'El intervalo debe ser mayor a 0',
        ];
    }
}
