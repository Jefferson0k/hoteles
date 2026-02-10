<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCancellationPolicyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'time_limit_hours' => 'required|integer|min:0',
            'refund_percentage' => 'required|numeric|min:0|max:100',
            'no_show_charge' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'refund_percentage.max' => 'El porcentaje de reembolso no puede ser mayor a 100',
            'time_limit_hours.min' => 'El límite de tiempo no puede ser negativo',
        ];
    }
}
