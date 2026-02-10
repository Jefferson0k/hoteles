<?php

namespace App\Http\Requests\BranchRoomTypePrice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRoomTypePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sub_branch_id' => [
                'required',
                'uuid',
                Rule::exists('sub_branches', 'id')->whereNull('deleted_at')
            ],
            'room_type_id' => [
                'required',
                'uuid',
                Rule::exists('room_types', 'id')->whereNull('deleted_at')
            ],
            'rate_type_id' => [
                'required',
                'uuid',
                Rule::exists('rate_types', 'id')->whereNull('deleted_at')
            ],
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sub_branch_id.required' => 'La sub-sucursal es obligatoria.',
            'sub_branch_id.exists' => 'La sub-sucursal seleccionada no existe.',
            'room_type_id.required' => 'El tipo de habitación es obligatorio.',
            'room_type_id.exists' => 'El tipo de habitación seleccionado no existe.',
            'rate_type_id.required' => 'El tipo de tarifa es obligatorio.',
            'rate_type_id.exists' => 'El tipo de tarifa seleccionado no existe.',
            'effective_from.required' => 'La fecha de inicio de vigencia es obligatoria.',
            'effective_to.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'sub_branch_id' => 'sub-sucursal',
            'room_type_id' => 'tipo de habitación',
            'rate_type_id' => 'tipo de tarifa',
            'effective_from' => 'fecha de inicio',
            'effective_to' => 'fecha de fin',
            'is_active' => 'activo',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $priceId = $this->route('branch_room_type_price');

            // Validar que no exista una combinación duplicada en el mismo rango de fechas
            $exists = \App\Models\BranchRoomTypePrice::where('id', '!=', $priceId)
                ->where('sub_branch_id', $this->sub_branch_id)
                ->where('room_type_id', $this->room_type_id)
                ->where('rate_type_id', $this->rate_type_id)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('effective_from', '<=', $this->effective_from)
                            ->where(function ($q2) {
                                $q2->whereNull('effective_to')
                                    ->orWhere('effective_to', '>=', $this->effective_from);
                            });
                    })
                    ->orWhere(function ($q) {
                        if ($this->effective_to) {
                            $q->where('effective_from', '<=', $this->effective_to)
                                ->where(function ($q2) {
                                    $q2->whereNull('effective_to')
                                        ->orWhere('effective_to', '>=', $this->effective_to);
                                });
                        }
                    });
                })
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'effective_from',
                    'Ya existe una configuración de precio para esta combinación en el rango de fechas especificado.'
                );
            }
        });
    }
}