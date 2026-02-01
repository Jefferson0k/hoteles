<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Producto\ProductoResource;
use App\Jobs\AssignProductToSubBranches;
use App\Jobs\UpdateSubBranchProductsFraction;
use App\Jobs\UpdateSubBranchProductsStock;
use App\Models\Booking;
use App\Models\BookingConsumption;
use App\Models\Kardex;
use App\Models\Product;
use App\Models\SubBranchProduct;
use App\Pipelines\FilterByCategory;
use App\Pipelines\FilterByName;
use App\Pipelines\FilterByState;
use App\Pipelines\Product\FilterByNameOrCode;
use App\Pipelines\Product\FilterByStock;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

class ProductoController extends Controller{
    public function index(Request $request){
        Gate::authorize('viewAny', Product::class);
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $state = $request->input('state');
        $category = $request->input('category');
        $query = app(Pipeline::class)
            ->send(Product::query()->with('category'))
            ->through([
                new FilterByName($search),
                new FilterByState($state),
                new FilterByCategory($category),
            ])
            ->thenReturn();
        return ProductoResource::collection($query->paginate($perPage));
    }
    public function store(StoreProductRequest $request){
        try {
            Gate::authorize('create', Product::class);
            DB::beginTransaction();
            $validated = $request->validated();
            $validated['created_by'] = Auth::id();
            $product = Product::create($validated);
            AssignProductToSubBranches::dispatchSync(
                $product,
                (int) $request->min_stock,
                (int) $request->max_stock
            );
            DB::commit();
            return response()->json([
                'state'   => true,
                'message' => 'Producto registrado exitosamente. Se asignó a las sub-sucursales.',
                'product' => $product
            ]);
        } catch (AuthorizationException $e) {
            DB::rollBack();
            return response()->json([
                'state'   => false,
                'message' => 'No tienes permiso para crear un producto.'
            ], 403);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'state'   => false,
                'message' => 'Error al crear el producto.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function show(Product $product){
        Gate::authorize('view', $product);
        return response()->json([
            'state' => true,
            'message' => 'Producto encontrado',
            'product' => new ProductoResource($product),
        ], 200);
    }
    public function update(UpdateProductRequest $request, Product $product){
        Gate::authorize('update', $product);
        $validated = $request->validated();
        $validated['updated_by'] = Auth::id();
        $product->update($validated);
        if ($request->hasAny(['is_fractionable', 'fraction_units'])) {
            UpdateSubBranchProductsFraction::dispatch($product);
        }
        if ($request->hasAny(['min_stock', 'max_stock'])) {
            UpdateSubBranchProductsStock::dispatch(
                $product,
                (int) $request->min_stock,
                (int) $request->max_stock
            );
        }
        return response()->json([
            'state'   => true,
            'message' => 'Product updated successfully.',
            'product' => $product->refresh()
        ]);
    }
    public function destroy(Product $product){
        Gate::authorize('delete', $product);
        $product->deleted_by = Auth::id();
        $product->save();
        $product->delete();
        return response()->json([
            'state' => true,
            'message' => 'Producto eliminado correctamente',
        ]);
    }
    public function searchProducto(){
        $user = Auth::user();
        if (!$user || !$user->sub_branch_id) {
            return response()->json([
                'message' => 'El usuario no tiene una sub-sucursal asignada.',
            ], 403);
        }
        $perPage = request('per_page', 10);
        $query = app(Pipeline::class)
            ->send(
                SubBranchProduct::with('product', 'subBranch')
                    ->active()
                    ->bySubBranch($user->sub_branch_id)
                    ->whereHas('product', fn($q) => $q->where('is_active', true))
            )
            ->through([
                FilterByName::class,
                FilterByStock::class,
            ])
            ->thenReturn();
        $productos = $query->paginate($perPage);
        return ProductResource::collection($productos);
    }
    public function addProducto(Request $request){
        $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'product_id' => 'required|uuid|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);
        try {
            DB::beginTransaction();
            $user = Auth::user();
            if (!$user || !$user->sub_branch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene sucursal asignada'
                ], 403);
            }
            $booking = Booking::findOrFail($request->booking_id);
            $product = Product::findOrFail($request->product_id);
            $subBranchProduct = SubBranchProduct::where('sub_branch_id', $user->sub_branch_id)
                ->where('product_id', $request->product_id)
                ->first();
            if (!$subBranchProduct) {
                return response()->json([
                    'success' => false,
                    'message' => "El producto {$product->name} no está disponible en esta sucursal"
                ], 400);
            }
            if ($subBranchProduct->current_stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Stock insuficiente para {$product->name}. Disponible: {$subBranchProduct->current_stock}"
                ], 400);
            }
            $totalPrice = $request->quantity * $request->unit_price;
            $consumption = BookingConsumption::create([
                'id' => Str::uuid(),
                'booking_id' => $request->booking_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_price' => $totalPrice,
                'status' => BookingConsumption::STATUS_PENDING,
                'consumed_at' => now(),
                'notes' => $request->notes,
                'created_by' => $user->id,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Producto agregado al consumo (pendiente de pago)',
                'data' => $consumption->fresh(['product'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateProducto(Request $request, $id){
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            if (!$user || !$user->sub_branch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene sucursal asignada'
                ], 403);
            }

            // Buscar el consumo
            $consumption = BookingConsumption::findOrFail($id);

            // Solo se pueden actualizar consumos pendientes
            if ($consumption->status !== BookingConsumption::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden actualizar consumos pendientes'
                ], 400);
            }

            // Obtener el producto
            $product = Product::findOrFail($consumption->product_id);

            // Verificar stock disponible
            $subBranchProduct = SubBranchProduct::where('sub_branch_id', $user->sub_branch_id)
                ->where('product_id', $consumption->product_id)
                ->first();

            if (!$subBranchProduct) {
                return response()->json([
                    'success' => false,
                    'message' => "El producto {$product->name} no está disponible en esta sucursal"
                ], 400);
            }

            // Calcular diferencia de stock
            $cantidadAnterior = $consumption->quantity;
            $cantidadNueva = $request->quantity;
            $diferencia = $cantidadNueva - $cantidadAnterior;

            // Si aumenta la cantidad, verificar stock
            if ($diferencia > 0) {
                if ($subBranchProduct->current_stock < $diferencia) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente. Disponible: {$subBranchProduct->current_stock}"
                    ], 400);
                }
            }

            // Ajustar stock según la diferencia
            if ($diferencia != 0) {
                $unitsPerPackage = $product->is_fractionable ? ($product->fraction_units ?? 1) : 1;
                if ($unitsPerPackage <= 0) $unitsPerPackage = 1;

                // Stock anterior
                $SAnteriorCaja = $subBranchProduct->packages_in_stock;
                $SAnteriorFraccion = $product->is_fractionable 
                    ? ($subBranchProduct->current_stock % $unitsPerPackage) 
                    : 0;

                // Ajustar stock
                if ($product->is_fractionable) {
                    $nuevoCurrentStock = $subBranchProduct->current_stock - $diferencia;
                    $nuevosPaquetes = intdiv($nuevoCurrentStock, $unitsPerPackage);
                    $nuevasFracciones = $nuevoCurrentStock % $unitsPerPackage;

                    $cajasSalientes = intdiv(abs($diferencia), $unitsPerPackage);
                    $fraccionesSalientes = abs($diferencia) % $unitsPerPackage;
                } else {
                    $nuevosPaquetes = $subBranchProduct->packages_in_stock - $diferencia;
                    $nuevasFracciones = 0;
                    $nuevoCurrentStock = $nuevosPaquetes;

                    $cajasSalientes = abs($diferencia);
                    $fraccionesSalientes = 0;
                }

                // Actualizar stock
                $subBranchProduct->current_stock = $nuevoCurrentStock;
                $subBranchProduct->packages_in_stock = $nuevosPaquetes;
                $subBranchProduct->updated_by = $user->id;
                $subBranchProduct->save();

                // Registrar en Kardex
                Kardex::create([
                    'product_id' => $consumption->product_id,
                    'sub_branch_id' => $subBranchProduct->sub_branch_id,
                    'movement_detail_id' => null,
                    'sale_id' => null,
                    'precio_total' => abs($diferencia * $request->unit_price),
                    'SAnteriorCaja' => $SAnteriorCaja,
                    'SAnteriorFraccion' => $SAnteriorFraccion,
                    'cantidadCaja' => $diferencia > 0 ? $cajasSalientes : 0,
                    'cantidadFraccion' => $diferencia > 0 ? $fraccionesSalientes : 0,
                    'movement_type' => $diferencia > 0 ? 'salida' : 'entrada',
                    'movement_category' => $diferencia > 0 ? 'venta' : 'ajuste',
                    'estado' => 1,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            // Actualizar el consumo
            $consumption->quantity = $request->quantity;
            $consumption->unit_price = $request->unit_price;
            $consumption->total_price = $request->quantity * $request->unit_price;
            $consumption->notes = $request->notes ?? $consumption->notes;
            $consumption->updated_by = $user->id;
            $consumption->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Consumo actualizado correctamente',
                'data' => $consumption->fresh(['product'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el consumo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un consumo pendiente
     */
    public function deleteProducto($id)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            if (!$user || !$user->sub_branch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene sucursal asignada'
                ], 403);
            }

            // Buscar el consumo
            $consumption = BookingConsumption::findOrFail($id);

            // Solo se pueden eliminar consumos pendientes
            if ($consumption->status !== BookingConsumption::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar consumos pendientes'
                ], 400);
            }

            // Obtener el producto
            $product = Product::findOrFail($consumption->product_id);

            // Devolver el stock
            $subBranchProduct = SubBranchProduct::where('sub_branch_id', $user->sub_branch_id)
                ->where('product_id', $consumption->product_id)
                ->first();

            if ($subBranchProduct) {
                $unitsPerPackage = $product->is_fractionable ? ($product->fraction_units ?? 1) : 1;
                if ($unitsPerPackage <= 0) $unitsPerPackage = 1;

                // Stock anterior
                $SAnteriorCaja = $subBranchProduct->packages_in_stock;
                $SAnteriorFraccion = $product->is_fractionable 
                    ? ($subBranchProduct->current_stock % $unitsPerPackage) 
                    : 0;

                // Devolver stock
                if ($product->is_fractionable) {
                    $nuevoCurrentStock = $subBranchProduct->current_stock + $consumption->quantity;
                    $nuevosPaquetes = intdiv($nuevoCurrentStock, $unitsPerPackage);
                    $nuevasFracciones = $nuevoCurrentStock % $unitsPerPackage;

                    $cajasEntrantes = intdiv($consumption->quantity, $unitsPerPackage);
                    $fraccionesEntrantes = $consumption->quantity % $unitsPerPackage;
                } else {
                    $nuevosPaquetes = $subBranchProduct->packages_in_stock + $consumption->quantity;
                    $nuevasFracciones = 0;
                    $nuevoCurrentStock = $nuevosPaquetes;

                    $cajasEntrantes = $consumption->quantity;
                    $fraccionesEntrantes = 0;
                }

                // Actualizar stock
                $subBranchProduct->current_stock = $nuevoCurrentStock;
                $subBranchProduct->packages_in_stock = $nuevosPaquetes;
                $subBranchProduct->updated_by = $user->id;
                $subBranchProduct->save();

                // Registrar en Kardex como entrada (devolución)
                Kardex::create([
                    'product_id' => $consumption->product_id,
                    'sub_branch_id' => $subBranchProduct->sub_branch_id,
                    'movement_detail_id' => null,
                    'sale_id' => null,
                    'precio_total' => $consumption->total_price,
                    'SAnteriorCaja' => $SAnteriorCaja,
                    'SAnteriorFraccion' => $SAnteriorFraccion,
                    'cantidadCaja' => $cajasEntrantes,
                    'cantidadFraccion' => $fraccionesEntrantes,
                    'SParcialCaja' => $nuevosPaquetes,
                    'SParcialFraccion' => $nuevasFracciones,
                    'movement_type' => 'entrada',
                    'movement_category' => 'ajuste',
                    'estado' => 1,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            // Soft delete
            $consumption->deleted_by = $user->id;
            $consumption->save();
            $consumption->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Consumo eliminado y stock devuelto correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el consumo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
