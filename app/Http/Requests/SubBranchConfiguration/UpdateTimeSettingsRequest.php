<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'max_allowed_time' => 'required|integer|min:1',
            'extra_tolerance' => 'required|integer|min:0',
            'apply_tolerance' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'max_allowed_time.required' => 'El tiempo máximo permitido es obligatorio',
            'max_allowed_time.min' => 'El tiempo máximo debe ser mayor a 0',
            'extra_tolerance.min' => 'La tolerancia no puede ser negativa',
        ];
    }
}
