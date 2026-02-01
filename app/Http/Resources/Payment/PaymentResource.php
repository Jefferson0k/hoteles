<?php

namespace App\Http\Resources\Payment;

use App\Http\Resources\Booking\BookingResource;
use App\Http\Resources\CashRegister\CashRegisterResource;
use App\Http\Resources\Currency\CurrencyResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'payment_code' => $this->payment_code,
            'amount' => (float) $this->amount,
            'amount_base_currency' => (float) $this->amount_base_currency,
            'exchange_rate' => (float) $this->exchange_rate,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'payment_date' => $this->payment_date?->toISOString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relaciones opcionales
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'cash_register' => new CashRegisterResource($this->whenLoaded('cashRegister')),
            'created_by_user' => new UserResource($this->whenLoaded('createdBy')),
        ];
    }
}