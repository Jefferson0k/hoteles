<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepositSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'requires_deposit' => 'required|boolean',
            'deposit_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:100',
        ];
    }
}
