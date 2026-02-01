<?php

namespace App\Http\Resources\Room;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResourceNew extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'floor_id'        => $this->floor_id,
            'floor_name'      => $this->floor->name ?? null,
            'floor_number'    => $this->floor->floor_number ?? null,
            'room_type_id'    => $this->room_type_id,
            'room_type_name'  => $this->roomType->name ?? null,
            'room_type_description' => $this->roomType->description ?? null,
            'room_capacity'  => $this->roomType->capacity ?? null,
            'room_price_hour' => $this->roomType->base_price_per_hour ?? null,
            'room_price_day'  => $this->roomType->base_price_per_day ?? null,
            'room_price_night'=> $this->roomType->base_price_per_night ?? null,
            'room_number'    => $this->room_number,
            'status_changed_at' => $this->status_changed_at
                ? Carbon::parse($this->status_changed_at)
                    ->locale('es')
                    ->translatedFormat('d/m/Y h:i:s A')
                : null,

            'status'        => $this->status,
            'is_active'     => $this->is_active,
        ];
    }
}
