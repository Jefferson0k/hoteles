<?php

namespace App\Http\Controllers\Web\PricingRange;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class PricingRangeWeb extends Controller{
    public function view(): Response{
        return Inertia::render('panel/');
    }
}
