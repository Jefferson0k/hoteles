<?php

namespace App\Http\Requests\PricingRange;

use App\Models\PricingRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePricingRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_room_type_price_id' => [
                'required',
                'uuid',
                Rule::exists('branch_room_type_prices', 'id')->whereNull('deleted_at')
            ],
            'time_from_minutes' => 'required|integer|min:0',
            'time_to_minutes' => 'required|integer|min:1|gt:time_from_minutes',
            'price' => 'required|numeric|min:0|max:999999.99',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_room_type_price_id.required' => 'El precio de habitación es obligatorio.',
            'branch_room_type_price_id.exists' => 'El precio de habitación seleccionado no existe.',
            'time_from_minutes.required' => 'El tiempo desde (en minutos) es obligatorio.',
            'time_from_minutes.min' => 'El tiempo desde debe ser mayor o igual a 0.',
            'time_to_minutes.required' => 'El tiempo hasta (en minutos) es obligatorio.',
            'time_to_minutes.min' => 'El tiempo hasta debe ser mayor a 0.',
            'time_to_minutes.gt' => 'El tiempo hasta debe ser mayor que el tiempo desde.',
            'price.required' => 'El precio es obligatorio.',
            'price.min' => 'El precio debe ser mayor o igual a 0.',
            'price.max' => 'El precio no puede exceder 999,999.99.',
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_room_type_price_id' => 'precio de habitación',
            'time_from_minutes' => 'tiempo desde',
            'time_to_minutes' => 'tiempo hasta',
            'price' => 'precio',
            'is_active' => 'activo',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que no haya solapamiento de rangos
            if ($this->has('branch_room_type_price_id') &&
                $this->has('time_from_minutes') &&
                $this->has('time_to_minutes')) {
                
                $hasOverlap = PricingRange::hasOverlap(
                    $this->branch_room_type_price_id,
                    $this->time_from_minutes,
                    $this->time_to_minutes
                );

                if ($hasOverlap) {
                    $validator->errors()->add(
                        'time_from_minutes',
                        'El rango de tiempo se solapa con un rango existente.'
                    );
                }
            }
        });
    }
}
