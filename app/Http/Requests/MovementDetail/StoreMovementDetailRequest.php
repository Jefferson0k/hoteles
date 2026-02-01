<?php
namespace App\Http\Requests\MovementDetail;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Movement;

class StoreMovementDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array{
        return [
            'movement_id'   => 'required|uuid|exists:movements,id',
            'product_id'    => 'required|uuid|exists:products,id',
            'unit_price'    => 'required|numeric|min:0',
            'boxes'         => 'required|integer|min:0',
            'units_per_box' => 'nullable|integer|min:1', // ← Ahora es nullable
            'fractions'     => 'required|integer|min:0',
            'quantity_type' => 'required|in:packages,fractions,both',
            'expiry_date'   => 'nullable|date|after:today',
            'total_price'   => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->movement_id) {
                $movement = Movement::find($this->movement_id);
                if (!$movement) {
                    $validator->errors()->add('movement_id', 'El movimiento no existe.');
                }
            }

            // Validación según quantity_type
            if ($this->quantity_type === 'packages') {
                // Solo paquetes: no necesita units_per_box ni fracciones
                if ($this->fractions > 0) {
                    $validator->errors()->add('fractions', 'No puede haber fracciones cuando el tipo es solo paquetes.');
                }
                if ($this->boxes < 1) {
                    $validator->errors()->add('boxes', 'Debe haber al menos 1 caja cuando el tipo es paquetes.');
                }
            }

            if ($this->quantity_type === 'fractions') {
                // Solo fracciones: SÍ necesita units_per_box
                if ($this->boxes > 0) {
                    $validator->errors()->add('boxes', 'No puede haber cajas cuando el tipo es solo fracciones.');
                }
                if (!$this->units_per_box || $this->units_per_box < 1) {
                    $validator->errors()->add('units_per_box', 'Las unidades por caja son obligatorias para productos fraccionables.');
                }
                if ($this->fractions < 1) {
                    $validator->errors()->add('fractions', 'Debe haber al menos 1 fracción cuando el tipo es fracciones.');
                }
            }

            if ($this->quantity_type === 'both') {
                // Ambos: SÍ necesita units_per_box
                if (!$this->units_per_box || $this->units_per_box < 1) {
                    $validator->errors()->add('units_per_box', 'Las unidades por caja son obligatorias cuando hay fracciones.');
                }
                if ($this->boxes < 1 && $this->fractions < 1) {
                    $validator->errors()->add('quantity_type', 'Debe haber cajas o fracciones cuando el tipo es ambos.');
                }
            }
        });
    }
    protected function prepareForValidation(){
        if ($this->quantity_type === 'packages') {
            $this->merge([
                'units_per_box' => null,
                'fractions' => 0
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'movement_id.required' => 'El movimiento es obligatorio.',
            'movement_id.exists'   => 'El movimiento seleccionado no existe.',
            'product_id.required'  => 'El producto es obligatorio.',
            'product_id.exists'    => 'El producto seleccionado no existe.',
            'unit_price.required'  => 'El precio unitario es obligatorio.',
            'boxes.required'       => 'La cantidad de cajas es obligatoria.',
            'units_per_box.required' => 'Las unidades por caja son obligatorias.',
            'fractions.required'   => 'Las fracciones son obligatorias.',
            'quantity_type.required' => 'El tipo de cantidad es obligatorio.',
            'quantity_type.in'     => 'El tipo de cantidad debe ser: paquetes, fracciones o ambas.',
            'total_price.required' => 'El precio total es obligatorio.',
            'total_price.min'      => 'El precio total debe ser mayor o igual a 0.',
        ];
    }
}