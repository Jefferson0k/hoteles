<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\TrackUserActivity;
use Illuminate\Support\ServiceProvider;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Inventory;
use App\Models\SubBranch;
use App\Observers\BookingObserver;
use App\Observers\PaymentObserver;
use App\Observers\InventoryObserver;
use App\Observers\SubBranchObserver;

class AppServiceProvider extends ServiceProvider{
    public function register(): void{

    }
    public function boot(): void{
        Route::middleware('auth')->group(function () {
            Route::middleware(TrackUserActivity::class)->group(function () {
            });
        });
        Booking::observe(BookingObserver::class);
        Payment::observe(PaymentObserver::class);
        Inventory::observe(InventoryObserver::class);
        SubBranch::observe(SubBranchObserver::class);
    }
}
