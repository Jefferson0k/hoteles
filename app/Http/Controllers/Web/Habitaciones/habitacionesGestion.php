<?php

namespace App\Http\Controllers\Web\Habitaciones;

use App\Http\Controllers\Controller;
use App\Http\Resources\Room\RoomResourceNew;
use App\Models\Room;
use Inertia\Inertia;
use Inertia\Response;
class habitacionesGestion extends Controller{
    public function view(): Response{
        return Inertia::render('panel/Gestion/indexGestion');
    }
    

    public function nuevaReserva(string $roomId)
    {
        $room = Room::findOrFail($roomId);

        return new RoomResourceNew($room);
    }
}
