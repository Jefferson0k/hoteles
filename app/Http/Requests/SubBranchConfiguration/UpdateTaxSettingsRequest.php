<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'tax_included' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'tax_percentage.max' => 'El porcentaje de impuesto no puede ser mayor a 100',
        ];
    }
}
