<?php

namespace App\Http\Controllers\Web\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class IngresoProducto extends Controller{
    public function view(): Response{
        Gate::authorize('viewAny', Product::class);
        return Inertia::render('panel/Reportes/IngresoProducto/indexIngresoProducto');
    }
}
