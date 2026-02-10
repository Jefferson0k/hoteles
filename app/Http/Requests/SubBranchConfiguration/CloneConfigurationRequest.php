<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class CloneConfigurationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'target_sub_branch_id' => 'required|uuid|exists:sub_branches,id|different:sub_branch_id',
        ];
    }

    public function messages()
    {
        return [
            'target_sub_branch_id.exists' => 'La sub-sucursal destino no existe',
            'target_sub_branch_id.different' => 'No puedes clonar a la misma sub-sucursal',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'sub_branch_id' => $this->route('subBranchId'),
        ]);
    }
}
