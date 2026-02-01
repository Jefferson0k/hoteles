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
        $remainingSeconds = null;
        $isTimeExpired = false;
        $estimatedCheckout = null;

        // =========================
        // CALCULO DE TIEMPO (PERÚ)
        // =========================
        if ($currentBooking && $currentBooking->check_in && $currentBooking->total_hours) {

            $tz = 'America/Lima';

            $checkIn = Carbon::parse($currentBooking->check_in, $tz)->startOfSecond();
            $checkOut = $checkIn->copy()
                ->addHours((int) $currentBooking->total_hours)
                ->startOfSecond();

            $now = Carbon::now($tz)->startOfSecond();

            $estimatedCheckout = $checkOut->toDateTimeString();

            if ($now->greaterThan($checkOut)) {
                // YA SE PASÓ → NEGATIVO
                $remainingSeconds = -$checkOut->diffInSeconds($now);
                $isTimeExpired = true;
            } else {
                // AÚN FALTA → POSITIVO
                $remainingSeconds = $now->diffInSeconds($checkOut);
                $isTimeExpired = false;
            }

            $totalSeconds = abs((int) $remainingSeconds);

            $hours = intdiv($totalSeconds, 3600);
            $minutes = intdiv($totalSeconds % 3600, 60);
            $seconds = $totalSeconds % 60;

            $sign = $remainingSeconds < 0 ? '-' : '';
            $remainingTime = $sign . sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return [
            'id'          => $this->id,
            'room_number' => $this->room_number,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            'is_active'   => $this->is_active,
            'full_name'   => $this->full_name,

            'floor' => $this->floor
                ? new FloorResource($this->floor)
                : null,

            'room_type' => $this->roomType
                ? new RoomTypeResource($this->roomType)
                : null,

            'current_booking' => $currentBooking ? [
                'booking_id'   => $currentBooking->id,
                'booking_code' => $currentBooking->booking_code,
                'booking_rate_per_unit' => $currentBooking->rate_per_unit,

                'guest_name'      => $currentBooking->customer?->name,
                'guest_client_id' => $currentBooking->customer?->id,
                'guest_document'  => $currentBooking->customer?->document_number,

                'check_in'  => $currentBooking->check_in?->toDateTimeString(),
                'check_out' => $currentBooking->check_out?->toDateTimeString(),

                'total_hours'  => (int) $currentBooking->total_hours,
                'rate_type'    => $currentBooking->rateType?->name,
                'rate_type_id' => $currentBooking->rate_type_id,

                // 🔥 CLAVES IMPORTANTES
                'remaining_time'     => $remainingTime,
                'remaining_seconds'  => $remainingSeconds,
                'is_time_expired'    => $isTimeExpired,
                'estimated_checkout' => $estimatedCheckout,

                'voucher_type' => $currentBooking->voucher_type,

                'consumptions' => $currentBooking->bookingConsumptions
                    ? $currentBooking->bookingConsumptions->map(function ($consumption) {
                        return [
                            'id'           => $consumption->id,
                            'product_id'   => $consumption->product_id,
                            'product_name' => $consumption->product?->name,
                            'quantity'     => $consumption->quantity,
                            'unit_price'   => $consumption->unit_price,
                            'total_price'  => $consumption->total_price,
                            'status'       => $consumption->status,
                            'consumed_at'  => $consumption->consumed_at?->toDateTimeString(),
                        ];
                    })
                    : [],
            ] : null,

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
