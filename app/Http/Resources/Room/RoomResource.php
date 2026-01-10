<?php

namespace App\Http\Resources\Room;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        $currentBooking = $this->currentBooking;
        $remainingTime = null;
        $remainingSeconds = 0;
        $isTimeExpired = false;
        $estimatedCheckout = null;

        // Calcular tiempo restante si hay booking activo
        if ($currentBooking && $currentBooking->check_in) {
            $checkIn = Carbon::parse($currentBooking->check_in);
            $now = now();

            // Calcular checkout estimado basado en total_hours
            if ($currentBooking->total_hours) {
                $checkOut = $checkIn->copy()->addHours($currentBooking->total_hours);
                $estimatedCheckout = $checkOut->toDateTimeString();
                
                // Calcular segundos restantes
                if ($now->greaterThan($checkOut)) {
                    // Tiempo expirado - negativo
                    $remainingSeconds = -$now->diffInSeconds($checkOut);
                    $isTimeExpired = true;
                } else {
                    // Tiempo restante - positivo
                    $remainingSeconds = $now->diffInSeconds($checkOut);
                    $isTimeExpired = false;
                }
                
                $totalSecs = abs($remainingSeconds);
                $hours = floor($totalSecs / 3600);
                $minutes = floor(($totalSecs % 3600) / 60);
                $seconds = $totalSecs % 60;
                
                $sign = $remainingSeconds < 0 ? '-' : '';
                $remainingTime = $sign . sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }
        }

        return [
            'id'          => $this->id,
            'room_number' => $this->room_number,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            'is_active'   => $this->is_active,
            'full_name'   => $this->full_name,
            'floor'       => $this->floor ? new FloorResource($this->floor) : null,
            'room_type'   => $this->roomType ? new RoomTypeResource($this->roomType) : null,
            'current_booking' => $currentBooking ? [
                'booking_id'      => $currentBooking->id,
                'booking_code'    => $currentBooking->booking_code,
                'booking_rate_per_unit' => $currentBooking->rate_per_unit,
                'guest_name'      => $currentBooking->customer?->name,
                'guest_client_id' => $currentBooking->customer?->id,
                'guest_document'  => $currentBooking->customer?->document_number,
                'check_in'        => $currentBooking->check_in?->toDateTimeString(),
                'check_out'       => $currentBooking->check_out?->toDateTimeString(),
                'total_hours'     => $currentBooking->total_hours,
                'rate_type'       => $currentBooking->rateType?->name,
                
                'remaining_time'  => $remainingTime,
                'remaining_seconds' => (int) $remainingSeconds,
                'is_time_expired' => $isTimeExpired,
                'estimated_checkout' => $estimatedCheckout,
                
                'consumptions'    => $currentBooking->bookingConsumptions->map(function ($consumption) {
                    return [
                        'id'          => $consumption->id,
                        'product_id'  => $consumption->product_id,
                        'product_name'=> $consumption->product?->name,
                        'quantity'    => $consumption->quantity,
                        'unit_price'  => $consumption->unit_price,
                        'total_price' => $consumption->total_price,
                        'status'      => $consumption->status,
                        'consumed_at' => $consumption->consumed_at?->toDateTimeString(),
                    ];
                }),
            ] : null,
            'created_at'  => $this->created_at?->toDateTimeString(),
            'updated_at'  => $this->updated_at?->toDateTimeString(),
        ];
    }
}