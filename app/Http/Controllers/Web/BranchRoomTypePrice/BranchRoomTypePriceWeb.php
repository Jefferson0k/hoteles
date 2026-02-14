<?php

namespace App\Http\Controllers\Web\BranchRoomTypePrice;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class BranchRoomTypePriceWeb extends Controller{
    public function view(): Response{
        return Inertia::render('panel/BranchRoomTypePrice/indexBranchRoomTypePrice');
    }
}
