<?php

namespace App\Http\Controllers\Web\RateType;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class RateTypeWeb extends Controller{
    public function view(): Response{
        return Inertia::render('panel/');
    }
}
