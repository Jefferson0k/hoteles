<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomType\StoreRoomTypeRequest;
use App\Http\Requests\RoomType\UpdateRoomTypeRequest;
use App\Http\Resources\Room\RoomTypeResource;
use App\Models\RoomType;
use App\Pipelines\FilterByName;
use App\Pipelines\FilterByState;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Throwable;

class RoomTypeController extends Controller{
    use ApiResponse, AuthorizesRequests;
    public function index(Request $request){
        Gate::authorize('viewAny', RoomType::class);
        $perPage = $request->input('per_page', 15);
        $query = app(Pipeline::class)
            ->send(RoomType::query())
            ->through([
                new FilterByName($request->input('search')),
                new FilterByState($request->input('state')),
            ])
            ->thenReturn();
        return RoomTypeResource::collection($query->paginate($perPage));
    }
    public function store(StoreRoomTypeRequest $request){
        try {
            DB::beginTransaction();
            $roomType = RoomType::create($request->validated());
            DB::commit();
            return response()->json([
                'message' => 'Tipo de habitación registrado correctamente.',
                'data' => new RoomTypeResource($roomType),
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el tipo de habitación.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function update(UpdateRoomTypeRequest $request, RoomType $roomType){
        try {
            DB::beginTransaction();
            $roomType->update($request->validated());
            DB::commit();
            return response()->json([
                'message' => 'Tipo de habitación actualizado correctamente.',
                'data' => new RoomTypeResource($roomType->fresh()),
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el tipo de habitación.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show(RoomType $roomType){
        Gate::authorize('view', $roomType);
        try {
            return response()->json([
                'message' => 'Tipo de habitación obtenido correctamente.',
                'data' => new RoomTypeResource($roomType),
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener el tipo de habitación.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy(RoomType $roomType){
        Gate::authorize('delete', $roomType);
        try {
            DB::beginTransaction();
            
            if ($roomType->rooms()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar el tipo de habitación porque tiene habitaciones asociadas.',
                ], 422);
            }
            $roomType->delete();
            DB::commit();
            return response()->json([
                'message' => 'Tipo de habitación eliminado correctamente.',
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el tipo de habitación.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
