<?php

namespace App\Http\Controllers\Web\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class IngressoHabitacionWeb extends Controller{
    public function view(): Response{
        Gate::authorize('viewAny', Payment::class);
        return Inertia::render('panel/Reportes/IngresoHabitacion/indexIngresoHabitacion');
    }
}
