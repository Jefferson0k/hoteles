<?php

namespace App\Http\Requests\RoomType;

use App\Models\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomTypeRequest extends FormRequest{
    public function authorize(): bool
    {
        $roomType = $this->route('roomType');
        return $roomType && $this->user()->can('update', $roomType);
    }

    public function rules(): array
    {
        // Obtener la instancia del modelo desde la ruta
        $roomType = $this->route('roomType') ?? $this->route('room_type');
        
        // Si es una instancia del modelo, obtener su ID
        $roomTypeId = $roomType instanceof RoomType ? $roomType->id : $roomType;
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('room_types', 'name')
                    ->ignore($roomTypeId)
                    ->whereNull('deleted_at')
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('room_types', 'code')
                    ->ignore($roomTypeId)
                    ->whereNull('deleted_at')
            ],
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'max_capacity' => 'nullable|integer|min:1|gte:capacity',
            'category' => [
                'nullable',
                'string',
                Rule::in(RoomType::getCategories())
            ],
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del tipo de habitación es obligatorio.',
            'name.unique' => 'Ya existe un tipo de habitación con este nombre.',
            'code.unique' => 'El código ya está en uso.',
            'capacity.required' => 'La capacidad es obligatoria.',
            'capacity.min' => 'La capacidad debe ser al menos 1.',
            'max_capacity.gte' => 'La capacidad máxima debe ser mayor o igual a la capacidad estándar.',
            'category.in' => 'La categoría seleccionada no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'code' => 'código',
            'description' => 'descripción',
            'capacity' => 'capacidad',
            'max_capacity' => 'capacidad máxima',
            'category' => 'categoría',
            'is_active' => 'activo',
        ];
    }
}
