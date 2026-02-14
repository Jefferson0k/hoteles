<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\RateType\StoreRateTypeRequest;
use App\Http\Requests\RateType\UpdateRateTypeRequest;
use App\Http\Resources\RateType\RateTypeResource;
use App\Models\RateType;
use Illuminate\Support\Facades\DB;
use Throwable;

class RateTypeController extends Controller{
    public function index(){
        $rateTypes = RateType::all();
        return RateTypeResource::collection($rateTypes);
    }
    public function indexOpciones()
    {
        $rateTypes = RateType::where('is_active', 1)->get();
        return RateTypeResource::collection($rateTypes);
    }
    public function store(StoreRateTypeRequest $request){
        try {
            DB::beginTransaction();
            $rateType = RateType::create($request->validated());
            DB::commit();
            return response()->json([
                'message' => 'Tipo de tarifa registrado correctamente.',
                'data' => new RateTypeResource($rateType),
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el tipo de tarifa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show(RateType $rateType){
        return response()->json([
            'data' => new RateTypeResource($rateType),
        ], 200);
    }
    public function update(UpdateRateTypeRequest $request, RateType $rateType){
        try {
            DB::beginTransaction();
            $rateType->update($request->validated());
            DB::commit();
            return response()->json([
                'message' => 'Tipo de tarifa actualizado correctamente.',
                'data' => new RateTypeResource($rateType->fresh()),
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el tipo de tarifa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy(RateType $rateType){
        try {
            DB::beginTransaction();
            $rateType->delete();
            DB::commit();
            return response()->json([
                'message' => 'Tipo de tarifa eliminado correctamente.',
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el tipo de tarifa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
