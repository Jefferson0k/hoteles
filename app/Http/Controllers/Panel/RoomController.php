<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\{
    IndexRoomRequest,
    ShowRoomRequest,
    CreateRoomRequest,
    UpdateRoomRequest,
    ChangeRoomStatusRequest,
    RoomStatusLogsRequest,
    RoomsByFloorRequest,
    RoomStatsRequest
};
use App\Http\Resources\Room\RoomResource;
use App\Http\Resources\Room\RoomStatusLogResource;
use App\Models\Room;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class RoomController extends Controller{
    use ApiResponse, AuthorizesRequests;
    public function __construct(){
        $this->authorizeResource(Room::class, 'room');
    }
    public function index(IndexRoomRequest $request){
        try {
            Gate::authorize('viewAny', Room::class);
            $query = Room::with([
                'floor.subBranch',
                'roomType',
                'currentBooking'
            ]);
            $this->applyFilters($query, $request->validated());
            $sortBy = $request->validated()['sort_by'] ?? 'created_at';
            $sortOrder = $request->validated()['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);
            $perPage = $request->validated()['per_page'] ?? 15;
            $rooms = $query->paginate($perPage);
            return RoomResource::collection($rooms);
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudieron listar las habitaciones.');
        }
    }
    public function show(Room $room, ShowRoomRequest $request){
        try {
            $room->load([
                'floor.subBranch',
                'roomType',
                'currentBooking.customer',
                'statusLogs' => fn($q) => $q->latest()->limit(10)->with('changedBy'),
            ]);
            return new RoomResource($room);
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudo obtener la habitación.');
        }
    }
    public function store(CreateRoomRequest $request){
        try {
            Gate::authorize('create', Room::class);
            $validatedData = $request->validated();
            $validatedData['status'] = $validatedData['status'] ?? 'available';
            $validatedData['is_active'] = $validatedData['is_active'] ?? true;
            Room::create($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Habitación creada correctamente.'
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear la habitación.'
            ], 500);
        }
    }
    public function update(Room $room, UpdateRoomRequest $request){
        try {
            $room->update($request->validated());
            $room->load(['floor.subBranch', 'roomType']);
            return $this->ok(
                new RoomResource($room),
                'Habitación actualizada correctamente.'
            );
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudo actualizar la habitación.');
        }
    }
    public function destroy(Room $room){
        try {
            if ($room->hasActiveBooking()) {
                return $this->error('No se puede eliminar una habitación con reservas activas.');
            }
            $room->delete();
            return $this->ok(null, 'Habitación eliminada correctamente.');
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudo eliminar la habitación.');
        }
    }
    public function changeStatus(Room $room, ChangeRoomStatusRequest $request){
        try {
            $validated = $request->validated();
            $room->changeStatus(
                $validated['new_status'],
                $validated['reason'] ?? null,
                Auth::id()
            );
            $room->load(['floor.subBranch', 'roomType', 'currentBooking']);
            return $this->ok(
                new RoomResource($room),
                'Estado de habitación actualizado correctamente.'
            );
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudo cambiar el estado de la habitación.');
        }
    }
    public function statusLogs(Room $room, RoomStatusLogsRequest $request){
        try {
            $validated = $request->validated();
            $query = $room->statusLogs()->with(['changedBy']);
            if (isset($validated['from_date'])) {
                $query->where('changed_at', '>=', $validated['from_date']);
            }
            if (isset($validated['to_date'])) {
                $query->where('changed_at', '<=', $validated['to_date']);
            }
            if (isset($validated['status'])) {
                $query->where('new_status', $validated['status']);
            }
            $perPage = $validated['per_page'] ?? 20;
            $logs = $query->paginate($perPage);
            return RoomStatusLogResource::collection($logs);
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudo obtener el historial de la habitación.');
        }
    }
    public function byFloor($floorId, RoomsByFloorRequest $request){
        try {
            $validated = $request->validated();
            $query = Room::with(['roomType', 'currentBooking'])
                ->where('floor_id', $floorId);
            if (isset($validated['status'])) {
                $query->where('status', $validated['status']);
            }
            if (isset($validated['room_type_id'])) {
                $query->where('room_type_id', $validated['room_type_id']);
            }
            if (!($validated['include_inactive'] ?? false)) {
                $query->where('is_active', true);
            }
            $rooms = $query->orderBy('room_number')->get();
            return RoomResource::collection($rooms);
        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudieron obtener las habitaciones del piso.');
        }
    }
    public function stats(RoomStatsRequest $request){
        try {
            $validated = $request->validated();
            $query = Room::where('is_active', true);
            if (isset($validated['sub_branch_id'])) {
                $query->whereHas('floor', function ($q) use ($validated) {
                    $q->where('sub_branch_id', $validated['sub_branch_id']);
                });
            }
            if (isset($validated['floor_id'])) {
                $query->where('floor_id', $validated['floor_id']);
            }
            $stats = [
                'total' => $query->count(),
                'available' => $query->clone()->where('status', 'available')->count(),
                'occupied' => $query->clone()->where('status', 'occupied')->count(),
                'maintenance' => $query->clone()->where('status', 'maintenance')->count(),
                'cleaning' => $query->clone()->where('status', 'cleaning')->count(),
            ];

            $stats['occupancy_rate'] = $stats['total'] > 0 
                ? round(($stats['occupied'] / $stats['total']) * 100, 2) 
                : 0;

            return $this->ok($stats, 'Estadísticas obtenidas correctamente.');

        } catch (Throwable $e) {
            return $this->exception($e, 'No se pudieron obtener las estadísticas.');
        }
    }
    private function applyFilters($query, array $filters): void{
        if (isset($filters['sub_branch_id'])) {
            $query->whereHas('floor', function ($q) use ($filters) {
                $q->where('sub_branch_id', $filters['sub_branch_id']);
            });
        }
        if (isset($filters['floor_id'])) {
            $query->where('floor_id', $filters['floor_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['room_type_id'])) {
            $query->where('room_type_id', $filters['room_type_id']);
        }
        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        } else {
            $query->where('is_active', true);
        }
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('room_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('floor', function ($floorQuery) use ($search) {
                      $floorQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('roomType', function ($typeQuery) use ($search) {
                      $typeQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
    }
    public function liberar($id){
        try {
            $room = Room::findOrFail($id);
            if ($room->status !== 'cleaning') {
                return response()->json([
                    'message' => 'La habitación no está en estado de limpieza'
                ], 400);
            }
            $room->status = 'available';
            $room->save();
            return response()->json([
                'message' => 'Habitación liberada correctamente',
                'data' => $room
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al liberar la habitación'
            ], 500);
        }
    }
}