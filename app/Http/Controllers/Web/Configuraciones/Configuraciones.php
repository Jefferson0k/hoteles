<?php

namespace App\Http\Controllers\Web\Configuraciones;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class Configuraciones extends Controller{
    public function view(): Response{
        return Inertia::render('panel/Configuraciones/indexConfiguraciones');
    }
}
