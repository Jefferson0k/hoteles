<?php

namespace App\Http\Requests\RateType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRateTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array{
        $rateTypeId = $this->route('rateType')->id;
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rate_types', 'name')
                    ->ignore($rateTypeId)
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('rate_types', 'code')
                    ->ignore($rateTypeId)
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del tipo de tarifa es obligatorio.',
            'name.unique' => 'Ya existe un tipo de tarifa con este nombre.',
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'El código ya está en uso.',
            'code.alpha_dash' => 'El código solo puede contener letras, números, guiones y guiones bajos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'code' => 'código',
            'description' => 'descripción',
            'is_active' => 'activo',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code),
            ]);
        }
    }
}
