<?php

namespace App\Http\Resources\Booking;

use App\Http\Resources\BookingConsumption\BookingConsumptionResource;
use App\Http\Resources\Payment\PaymentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'room' => [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'number' => $this->room->room_number,
                'floor' => $this->room->floor->name ?? null,
                'branch' => $this->room->floor->branch->name ?? null,
            ],
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->full_name,
                'document' => $this->customer->document_number,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ],
            'rate_type' => [
                'id' => $this->rateType->id,
                'name' => $this->rateType->name,
                'code' => $this->rateType->code,
            ],
            'currency' => [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'symbol' => $this->currency->symbol,
            ],
            'check_in' => $this->check_in?->toISOString(),
            'check_out' => $this->check_out?->toISOString(),
            'total_hours' => $this->total_hours,
            'rate_per_hour' => (float) $this->rate_per_hour,
            'room_subtotal' => (float) $this->room_subtotal,
            'products_subtotal' => (float) $this->products_subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'balance' => (float) $this->balance,
            'status' => $this->status,
            'voucher_type' => $this->voucher_type,
            'notes' => $this->notes,
            'duration_in_words' => $this->duration_in_words,
            'is_paid' => $this->isPaid(),
            'payments_count' => $this->payments_count ?? $this->payments->count(),
            'consumptions_count' => $this->consumptions_count ?? $this->consumptions->count(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'consumptions' => BookingConsumptionResource::collection($this->whenLoaded('consumptions')),
        ];
    }
}