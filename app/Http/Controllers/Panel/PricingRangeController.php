<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\PricingRange\StorePricingRangeRequest;
use App\Http\Requests\PricingRange\UpdatePricingRangeRequest;
use App\Http\Resources\PricingRange\PricingRangeResource;
use App\Models\PricingRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PricingRangeController extends Controller
{
    public function index(Request $request)
    {
        $query = PricingRange::with(['branchRoomTypePrice']);

        // Filtros opcionales
        if ($request->has('branch_room_type_price_id')) {
            $query->byBranchRoomTypePrice($request->branch_room_type_price_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('minutes')) {
            $query->forMinutes($request->minutes);
        }

        $pricingRanges = $query->orderByTime()->get();

        return PricingRangeResource::collection($pricingRanges);
    }

    public function store(StorePricingRangeRequest $request)
    {
        try {
            DB::beginTransaction();

            $pricingRange = PricingRange::create($request->validated());

            DB::commit();

            return response()->json([
                'message' => 'Rango de precio registrado correctamente.',
                'data' => new PricingRangeResource($pricingRange->load('branchRoomTypePrice')),
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar el rango de precio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(PricingRange $pricingRange)
    {
        $pricingRange->load(['branchRoomTypePrice.subBranch', 'branchRoomTypePrice.roomType', 'branchRoomTypePrice.rateType']);

        return response()->json([
            'data' => new PricingRangeResource($pricingRange),
        ], 200);
    }

    public function update(UpdatePricingRangeRequest $request, PricingRange $pricingRange)
    {
        try {
            DB::beginTransaction();

            $pricingRange->update($request->validated());

            DB::commit();

            return response()->json([
                'message' => 'Rango de precio actualizado correctamente.',
                'data' => new PricingRangeResource($pricingRange->fresh('branchRoomTypePrice')),
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar el rango de precio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(PricingRange $pricingRange)
    {
        try {
            DB::beginTransaction();

            $pricingRange->delete();

            DB::commit();

            return response()->json([
                'message' => 'Rango de precio eliminado correctamente.',
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al eliminar el rango de precio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint adicional: Obtener precio para una cantidad específica de minutos
     */
    public function getPriceForMinutes(Request $request)
    {
        $request->validate([
            'branch_room_type_price_id' => 'required|uuid|exists:branch_room_type_prices,id',
            'minutes' => 'required|integer|min:1',
        ]);

        $pricingRange = PricingRange::active()
            ->byBranchRoomTypePrice($request->branch_room_type_price_id)
            ->forMinutes($request->minutes)
            ->first();

        if (!$pricingRange) {
            return response()->json([
                'message' => 'No se encontró un rango de precio para la cantidad de minutos especificada.',
            ], 404);
        }

        return response()->json([
            'data' => new PricingRangeResource($pricingRange),
        ], 200);
    }
}