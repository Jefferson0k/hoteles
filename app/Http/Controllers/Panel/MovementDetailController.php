<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\MovementDetail\StoreMovementDetailRequest;
use App\Http\Requests\MovementDetail\UpdateMovementDetailRequest;
use App\Http\Resources\MovementDetail\MovementDetailResource;
use App\Http\Resources\MovementDetail\MovementDetailShowResource;
use App\Models\Kardex;
use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\Product;
use App\Models\SubBranchProduct;
use App\Pipelines\MovementDetail\OrderByLatest;
use App\Pipelines\MovementDetail\SearchMovementDetail;
use App\Support\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MovementDetailController extends Controller{
    use ApiResponse, AuthorizesRequests;
    public function index(Request $request, string $movementId){
        $perPage = $request->input('per_page', 15);
        
        $query = app(Pipeline::class)
            ->send(MovementDetail::with(['movement', 'product'])->where('movement_id', $movementId))
            ->through([
                SearchMovementDetail::class,
                OrderByLatest::class,
            ])
            ->thenReturn();
        $movementDetails = $query->paginate($perPage);
        return MovementDetailResource::collection($movementDetails);
    }
    public function show(MovementDetail $movementDetail){
        $movementDetail->load(['movement', 'product']);
        return $this->ok(
            new MovementDetailShowResource($movementDetail),
            'Detalle del movimiento obtenido correctamente.'
        );
    }
    public function store(StoreMovementDetailRequest $request){
        try {
            DB::beginTransaction();
            $validated = $request->validated();
            $movement = Movement::find($validated['movement_id']);
            if (!$movement) {
                return $this->fail('El movimiento no existe.', 404);
            }
            if ($validated['quantity_type'] === 'packages') {
                $validated['units_per_box'] = null;
                $validated['fractions'] = 0;
            }
            $movementDetail = MovementDetail::create($validated);
            DB::commit();
            return $this->created(
                new MovementDetailResource($movementDetail->load(['movement', 'product'])),
                'Detalle de movimiento creado correctamente.'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->exception($e, 'Error al crear el detalle del movimiento.');
        }
    }
    public function update(UpdateMovementDetailRequest $request, MovementDetail $movementDetail){
        try {
            DB::beginTransaction();
            $user = Auth::user();
            if (!$user || !$user->sub_branch_id) {
                DB::rollBack();
                return $this->fail('Usuario no tiene sub-sucursal asignada.', 400);
            }
            $subBranchId = $user->sub_branch_id;
            $productId = $request->input('product_id', $movementDetail->product_id);
            $product = Product::find($productId);
            if (!$product) {
                DB::rollBack();
                return $this->fail('Producto no encontrado.', 404);
            }
            $oldProduct = Product::find($movementDetail->product_id);
            $oldSubBranchProduct = SubBranchProduct::where('sub_branch_id', $subBranchId)
                ->where('product_id', $movementDetail->product_id)
                ->first();
            if ($oldSubBranchProduct && $oldProduct) {
                $oldUnitsPerPackage = $oldProduct->is_fractionable ? ($oldProduct->fraction_units ?? 1) : 1;
                if ($oldUnitsPerPackage <= 0) $oldUnitsPerPackage = 1;
                $oldCajasEntrantes = 0;
                $oldFraccionesEntrantes = 0;
                
                switch($movementDetail->quantity_type) {
                    case 'packages':
                        $oldCajasEntrantes = (int)($movementDetail->boxes ?? 0);
                        break;
                    case 'fractions':
                        if (!$oldProduct->is_fractionable) {
                            $oldCajasEntrantes = (int)($movementDetail->fractions ?? 0);
                        } else {
                            $oldFraccionesEntrantes = (int)($movementDetail->fractions ?? 0);
                        }
                        break;
                    case 'both':
                        $oldCajasEntrantes = (int)($movementDetail->boxes ?? 0);
                        $oldFraccionesEntrantes = $oldProduct->is_fractionable ? (int)($movementDetail->fractions ?? 0) : 0;
                        break;
                }
                
                // Stock anterior antes de revertir
                $SAnteriorCajaReversion = $oldSubBranchProduct->packages_in_stock;
                $SAnteriorFraccionReversion = $oldProduct->is_fractionable 
                    ? ($oldSubBranchProduct->current_stock % $oldUnitsPerPackage) 
                    : 0;
                
                // RESTAR el stock anterior
                if ($oldProduct->is_fractionable) {
                    $unidadesARestar = ($oldCajasEntrantes * $oldUnitsPerPackage) + $oldFraccionesEntrantes;
                    $nuevoCurrentStock = $oldSubBranchProduct->current_stock - $unidadesARestar;
                    
                    if ($nuevoCurrentStock < 0) {
                        DB::rollBack();
                        return $this->fail('Stock insuficiente para revertir la operación anterior.', 400);
                    }
                    
                    $nuevosPaquetesReversion = intdiv($nuevoCurrentStock, $oldUnitsPerPackage);
                    $nuevasFraccionesReversion = $nuevoCurrentStock % $oldUnitsPerPackage;
                } else {
                    $nuevosPaquetesReversion = $oldSubBranchProduct->packages_in_stock - $oldCajasEntrantes;
                    
                    if ($nuevosPaquetesReversion < 0) {
                        DB::rollBack();
                        return $this->fail('Stock insuficiente para revertir la operación anterior.', 400);
                    }
                    
                    $nuevasFraccionesReversion = 0;
                    $nuevoCurrentStock = $nuevosPaquetesReversion;
                }
                
                // Actualizar el stock (reversión)
                $oldSubBranchProduct->current_stock = $nuevoCurrentStock;
                $oldSubBranchProduct->packages_in_stock = $nuevosPaquetesReversion;
                $oldSubBranchProduct->updated_by = $user->id;
                $oldSubBranchProduct->save();
                
                // Registrar en Kardex la reversión (SALIDA)
                Kardex::create([
                    'product_id' => $movementDetail->product_id,
                    'sub_branch_id' => $oldSubBranchProduct->sub_branch_id,
                    'movement_detail_id' => $movementDetail->id,
                    'precio_total' => -($movementDetail->total_price ?? 0),
                    'SAnteriorCaja' => $SAnteriorCajaReversion,
                    'SAnteriorFraccion' => $SAnteriorFraccionReversion,
                    'cantidadCaja' => $oldCajasEntrantes,
                    'cantidadFraccion' => $oldFraccionesEntrantes,
                    'SParcialCaja' => $nuevosPaquetesReversion,
                    'SParcialFraccion' => $nuevasFraccionesReversion,
                    'movement_type' => 'salida',
                    'movement_category' => 'ajuste',
                    'estado' => 1,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
            
            // ═══════════════════════════════════════════════════════════
            // PASO 2: ACTUALIZAR EL MOVEMENTDETAIL CON LOS NUEVOS DATOS
            // ═══════════════════════════════════════════════════════════
            
            $movementDetail->fill($request->validated());
            $movementDetail->updated_by = $user->id;
            
            // Recalcular el total_price si cambió algo
            if ($request->has('unit_price') || $request->has('boxes') || $request->has('fractions')) {
                $unitPrice = $request->input('unit_price', $movementDetail->unit_price);
                $boxes = $request->input('boxes', $movementDetail->boxes ?? 0);
                $fractions = $request->input('fractions', $movementDetail->fractions ?? 0);
                $unitsPerBox = $request->input('units_per_box', $movementDetail->units_per_box ?? 1);
                
                $totalQuantity = ($boxes * $unitsPerBox) + $fractions;
                $movementDetail->total_price = $unitPrice * $totalQuantity;
            }
            
            $movementDetail->save();
            
            // ═══════════════════════════════════════════════════════════
            // PASO 3: APLICAR EL NUEVO STOCK (lo nuevo que entra)
            // ═══════════════════════════════════════════════════════════
            
            // Buscar o crear SubBranchProduct para el nuevo producto
            $newSubBranchProduct = SubBranchProduct::where('sub_branch_id', $subBranchId)
                ->where('product_id', $productId)
                ->first();
            
            if (!$newSubBranchProduct) {
                $unitsPerPackage = $product->is_fractionable ? ($product->fraction_units ?? 1) : 1;
                
                $newSubBranchProduct = SubBranchProduct::create([
                    'sub_branch_id' => $subBranchId,
                    'product_id' => $productId,
                    'packages_in_stock' => 0,
                    'units_per_package' => $unitsPerPackage,
                    'current_stock' => 0,
                    'min_stock' => 0,
                    'max_stock' => 0,
                    'is_fractionable' => $product->is_fractionable,
                    'is_active' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
            
            // Calcular unidades por paquete
            $newUnitsPerPackage = $product->is_fractionable ? ($product->fraction_units ?? 1) : 1;
            if ($newUnitsPerPackage <= 0) $newUnitsPerPackage = 1;
            
            // Calcular lo nuevo que entra
            $newCajasEntrantes = 0;
            $newFraccionesEntrantes = 0;
            
            switch($movementDetail->quantity_type) {
                case 'packages':
                    $newCajasEntrantes = (int)($movementDetail->boxes ?? 0);
                    break;
                case 'fractions':
                    if (!$product->is_fractionable) {
                        $newCajasEntrantes = (int)($movementDetail->fractions ?? 0);
                    } else {
                        $newFraccionesEntrantes = (int)($movementDetail->fractions ?? 0);
                    }
                    break;
                case 'both':
                    $newCajasEntrantes = (int)($movementDetail->boxes ?? 0);
                    $newFraccionesEntrantes = $product->is_fractionable ? (int)($movementDetail->fractions ?? 0) : 0;
                    break;
            }
            
            // Stock anterior antes de sumar lo nuevo
            $SAnteriorCajaNuevo = $newSubBranchProduct->packages_in_stock;
            $SAnteriorFraccionNuevo = $product->is_fractionable 
                ? ($newSubBranchProduct->current_stock % $newUnitsPerPackage) 
                : 0;
            
            // SUMAR el nuevo stock
            if ($product->is_fractionable) {
                $unidadesEntrantes = ($newCajasEntrantes * $newUnitsPerPackage) + $newFraccionesEntrantes;
                $nuevoCurrentStock = $newSubBranchProduct->current_stock + $unidadesEntrantes;
                $nuevosPaquetesNuevo = intdiv($nuevoCurrentStock, $newUnitsPerPackage);
                $nuevasFraccionesNuevo = $nuevoCurrentStock % $newUnitsPerPackage;
            } else {
                $nuevosPaquetesNuevo = $newSubBranchProduct->packages_in_stock + $newCajasEntrantes;
                $nuevasFraccionesNuevo = 0;
                $nuevoCurrentStock = $nuevosPaquetesNuevo;
            }
            
            // Actualizar el stock (nueva entrada)
            $newSubBranchProduct->current_stock = $nuevoCurrentStock;
            $newSubBranchProduct->packages_in_stock = $nuevosPaquetesNuevo;
            $newSubBranchProduct->units_per_package = $newUnitsPerPackage;
            $newSubBranchProduct->updated_by = $user->id;
            $newSubBranchProduct->save();
            
            // Registrar en Kardex la nueva entrada (ENTRADA)
            Kardex::create([
                'product_id' => $productId,
                'sub_branch_id' => $newSubBranchProduct->sub_branch_id,
                'movement_detail_id' => $movementDetail->id,
                'precio_total' => $movementDetail->total_price ?? 0,
                'SAnteriorCaja' => $SAnteriorCajaNuevo,
                'SAnteriorFraccion' => $SAnteriorFraccionNuevo,
                'cantidadCaja' => $newCajasEntrantes,
                'cantidadFraccion' => $newFraccionesEntrantes,
                'SParcialCaja' => $nuevosPaquetesNuevo,
                'SParcialFraccion' => $nuevasFraccionesNuevo,
                'movement_type' => 'entrada',
                'movement_category' => 'compra',
                'estado' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            
            DB::commit();
            
            $movementDetail->load(['product', 'movement']);
            
            return $this->ok($movementDetail, 'Detalle de movimiento actualizado correctamente.');
            
        } catch (Exception $e) {
            DB::rollBack();
            return $this->exception($e, 'Error al actualizar el detalle del movimiento.');
        }
    }
    public function destroy(MovementDetail $movementDetail){
        try {
            DB::beginTransaction();
            $user = Auth::user();
            if (!$user || !$user->sub_branch_id) {
                DB::rollBack();
                return $this->fail('Usuario no tiene sub-sucursal asignada.', 400);
            }
            $subBranchId = $user->sub_branch_id;
            $product = Product::find($movementDetail->product_id);
            if (!$product) {
                DB::rollBack();
                return $this->fail('Producto no encontrado.', 404);
            }
            $subBranchProduct = SubBranchProduct::where('sub_branch_id', $subBranchId)
                ->where('product_id', $movementDetail->product_id)
                ->first();
            if (!$subBranchProduct) {
                DB::rollBack();
                return $this->fail('Producto no encontrado en la sub-sucursal del usuario.', 404);
            }
            $unitsPerPackage = $product->is_fractionable ? ($product->fraction_units ?? 1) : 1;
            if ($unitsPerPackage <= 0) $unitsPerPackage = 1;
            $cajasSalientes = 0;
            $fraccionesSalientes = 0;
            switch($movementDetail->quantity_type) {
                case 'packages':
                    $cajasSalientes = (int)($movementDetail->boxes ?? 0);
                    $fraccionesSalientes = 0;
                    break;
                case 'fractions':
                    if (!$product->is_fractionable) {
                        $cajasSalientes = (int)($movementDetail->fractions ?? 0);
                        $fraccionesSalientes = 0;
                    } else {
                        $cajasSalientes = 0;
                        $fraccionesSalientes = (int)($movementDetail->fractions ?? 0);
                    }
                    break;
                case 'both':
                    $cajasSalientes = (int)($movementDetail->boxes ?? 0);
                    $fraccionesSalientes = $product->is_fractionable ? (int)($movementDetail->fractions ?? 0) : 0;
                    break;
            }
            $SAnteriorCaja = $subBranchProduct->packages_in_stock;
            $SAnteriorFraccion = $product->is_fractionable 
                ? ($subBranchProduct->current_stock % $unitsPerPackage) 
                : 0;
            if ($product->is_fractionable) {
                $unidadesSalientes = ($cajasSalientes * $unitsPerPackage) + $fraccionesSalientes;
                $nuevoCurrentStock = $subBranchProduct->current_stock - $unidadesSalientes;
                if ($nuevoCurrentStock < 0) {
                    DB::rollBack();
                    return $this->fail('Stock insuficiente para realizar el descuento. Stock actual: ' . $subBranchProduct->current_stock . ', Intentando descontar: ' . $unidadesSalientes, 400);
                }
                $nuevosPaquetes = intdiv($nuevoCurrentStock, $unitsPerPackage);
                $nuevasFracciones = $nuevoCurrentStock % $unitsPerPackage;
            } else {
                $nuevosPaquetes = $subBranchProduct->packages_in_stock - $cajasSalientes;
                if ($nuevosPaquetes < 0) {
                    DB::rollBack();
                    return $this->fail('Stock insuficiente para realizar el descuento. Paquetes actuales: ' . $subBranchProduct->packages_in_stock . ', Intentando descontar: ' . $cajasSalientes, 400);
                }
                $nuevasFracciones = 0;
                $nuevoCurrentStock = $nuevosPaquetes;
            }
            $subBranchProduct->current_stock = $nuevoCurrentStock;
            $subBranchProduct->packages_in_stock = $nuevosPaquetes;
            $subBranchProduct->updated_by = $user->id;
            $subBranchProduct->save();
            Kardex::create([
                'product_id' => $movementDetail->product_id,
                'sub_branch_id' => $subBranchProduct->sub_branch_id,
                'movement_detail_id' => $movementDetail->id,
                'precio_total' => -($movementDetail->total_price ?? 0),
                'SAnteriorCaja' => $SAnteriorCaja,
                'SAnteriorFraccion' => $SAnteriorFraccion,
                'cantidadCaja' => $cajasSalientes,
                'cantidadFraccion' => $fraccionesSalientes,
                'SParcialCaja' => $nuevosPaquetes,
                'SParcialFraccion' => $nuevasFracciones,
                'movement_type' => 'salida',
                'movement_category' => 'ajuste',
                'estado' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $movementDetail->delete();
            DB::commit();
            return $this->ok(null, 'Detalle de movimiento eliminado y stock actualizado correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            if ($e instanceof ModelNotFoundException) {
                return $this->fail('Detalle del movimiento no encontrado.', 404);
            }
            return $this->exception($e, 'Error al eliminar el detalle del movimiento: ' . $e->getMessage());
        }
    }
}