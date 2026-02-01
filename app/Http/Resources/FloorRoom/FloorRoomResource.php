<?php

namespace App\Http\Resources\FloorRoom;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FloorRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'floor_number' => $this->floor_number,
            'description' => $this->description,
            'total_rooms' => $this->rooms->count(),
            'available_rooms' => $this->availableRooms->count(),
            'rooms' => $this->rooms->map(function ($room) {
                $activeBooking = $room->bookings
                    ->whereIn('status', ['checked_in'])
                    ->sortByDesc('check_in')
                    ->first();

                $checkIn = null;
                $checkOut = null;
                $elapsed = null;
                $elapsedMinutes = null;
                $remainingTime = null;
                $customerName = null;

                if ($activeBooking && $activeBooking->check_in) {
                    $checkIn = Carbon::parse($activeBooking->check_in);
                    $now = now();
                    
                    // Calcular check_out basado en total_hours
                    if ($activeBooking->total_hours) {
                        $checkOut = $checkIn->copy()->addHours($activeBooking->total_hours);
                    } elseif ($activeBooking->check_out) {
                        $checkOut = Carbon::parse($activeBooking->check_out);
                    }

                    // CORRECCIÓN: Calcular tiempo transcurrido correctamente
                    $totalSeconds = $now->diffInSeconds($checkIn);
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = $totalSeconds % 60;
                    
                    $elapsed = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    $elapsedMinutes = floor($totalSeconds / 60);

                    // Calcular tiempo restante
                    if ($checkOut) {
                        if ($now->greaterThan($checkOut)) {
                            // Tiempo expirado
                            $remainingSeconds = $now->diffInSeconds($checkOut);
                            $remainingTime = '-' . $this->formatTimeFromSeconds($remainingSeconds);
                        } else {
                            // Tiempo restante
                            $remainingSeconds = $now->diffInSeconds($checkOut);
                            $remainingTime = $this->formatTimeFromSeconds($remainingSeconds);
                        }
                    }

                    $customerName = $activeBooking->customer?->name;
                }

                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'name' => $room->name,
                    'status' => $room->status,
                    'is_active' => $room->is_active,
                    'room_type' => $room->roomType?->name,
                    
                    // Tiempos
                    'check_in' => $checkIn?->toDateTimeString(),
                    'check_out' => $checkOut?->toDateTimeString(),
                    'elapsed_time' => $elapsed,
                    'elapsed_minutes' => $elapsedMinutes,
                    'remaining_time' => $remainingTime,
                    
                    // Cliente y reserva
                    'customer' => $customerName,
                    'booking_code' => $activeBooking?->booking_code,
                    'booking_id' => $activeBooking?->id,
                    // Información adicional
                    'total_hours_contracted' => $activeBooking?->total_hours,
                    'rate_type' => $activeBooking?->rateType?->name,
                ];
            }),
        ];
    }

    /**
     * Formatear segundos a HH:MM:SS
     */
    private function formatTimeFromSeconds(int $totalSeconds): string
    {
        $hours = floor(abs($totalSeconds) / 3600);
        $minutes = floor((abs($totalSeconds) % 3600) / 60);
        $seconds = abs($totalSeconds) % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}