<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRoomTypePrice\StoreBranchRoomTypePriceRequest;
use App\Http\Requests\BranchRoomTypePrice\UpdateBranchRoomTypePriceRequest;
use App\Http\Resources\BranchRoomTypePrice\BranchRoomTypePriceResource;
use App\Models\BranchRoomTypePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BranchRoomTypePriceController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchRoomTypePrice::with(['subBranch', 'roomType', 'rateType']);

        // Filtros opcionales
        if ($request->has('sub_branch_id')) {
            $query->bySubBranch($request->sub_branch_id);
        }

        if ($request->has('room_type_id')) {
            $query->byRoomType($request->room_type_id);
        }

        if ($request->has('rate_type_id')) {
            $query->byRateType($request->rate_type_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('current_only') && $request->boolean('current_only')) {
            $query->current();
        }

        $prices = $query->get();

        return BranchRoomTypePriceResource::collection($prices);
    }

    public function store(StoreBranchRoomTypePriceRequest $request)
    {
        try {
            DB::beginTransaction();

            $price = BranchRoomTypePrice::create($request->validated());

            DB::commit();

            return response()->json([
                'message' => 'Configuración de precio registrada correctamente.',
                'data' => new BranchRoomTypePriceResource($price->load(['subBranch', 'roomType', 'rateType'])),
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar la configuración de precio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(BranchRoomTypePrice $branchRoomTypePrice)
    {
        $branchRoomTypePrice->load(['subBranch', 'roomType', 'rateType', 'pricingRanges']);

        return response()->json([
            'data' => new BranchRoomTypePriceResource($branchRoomTypePrice),
        ], 200);
    }

    public function update(UpdateBranchRoomTypePriceRequest $request, BranchRoomTypePrice $branchRoomTypePrice)
    {
        try {
            DB::beginTransaction();

            $branchRoomTypePrice->update($request->validated());

            DB::commit();

            return response()->json([
                'message' => 'Configuración de precio actualizada correctamente.',
                'data' => new BranchRoomTypePriceResource($branchRoomTypePrice->fresh(['subBranch', 'roomType', 'rateType'])),
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar la configuración de precio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(BranchRoomTypePrice $branchRoomTypePrice)
    {
        try {
            DB::beginTransaction();

            $branchRoomTypePrice->delete();

            DB::commit();

            return response()->json([
                'message' => 'Configuración de precio eliminada correctamente.',
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al eliminar la configuración de precio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener opciones de precio para una configuración específica
     */
    public function getPricingOptions(Request $request)
    {
        $request->validate([
            'sub_branch_id' => 'required|uuid|exists:sub_branches,id',
            'room_type_id' => 'required|uuid|exists:room_types,id',
            'rate_type_id' => 'required|uuid|exists:rate_types,id',
            'date' => 'nullable|date',
        ]);

        $date = $request->input('date', now());

        // Buscar la configuración activa y vigente
        $branchPrice = BranchRoomTypePrice::with(['subBranch', 'roomType', 'rateType', 'pricingRanges'])
            ->bySubBranch($request->sub_branch_id)
            ->byRoomType($request->room_type_id)
            ->byRateType($request->rate_type_id)
            ->active()
            ->effectiveOn($date)
            ->first();

        if (!$branchPrice) {
            return response()->json([
                'message' => 'No se encontró configuración de precios para los parámetros especificados.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'branch_room_type_price' => new BranchRoomTypePriceResource($branchPrice),
                'pricing_options' => $branchPrice->getPricingOptions(),
            ],
        ], 200);
    }

    /**
     * Calcular precio específico para minutos dados
     */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'sub_branch_id' => 'required|uuid|exists:sub_branches,id',
            'room_type_id' => 'required|uuid|exists:room_types,id',
            'rate_type_id' => 'required|uuid|exists:rate_types,id',
            'minutes' => 'required|integer|min:1',
            'date' => 'nullable|date',
        ]);

        $date = $request->input('date', now());

        $branchPrice = BranchRoomTypePrice::with(['roomType', 'pricingRanges'])
            ->bySubBranch($request->sub_branch_id)
            ->byRoomType($request->room_type_id)
            ->byRateType($request->rate_type_id)
            ->active()
            ->effectiveOn($date)
            ->first();

        if (!$branchPrice) {
            return response()->json([
                'message' => 'No se encontró configuración de precios.',
            ], 404);
        }

        $price = $branchPrice->calculatePrice($request->minutes);

        if ($price === null) {
            return response()->json([
                'message' => 'No se encontró un rango de precio para ' . $request->minutes . ' minutos.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'minutes' => $request->minutes,
                'price' => number_format($price, 2, '.', ''),
                'date' => $date,
            ],
        ], 200);
    }
}
