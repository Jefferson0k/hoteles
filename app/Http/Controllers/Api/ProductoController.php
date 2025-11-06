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
}
