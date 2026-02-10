<?php

namespace App\Observers;

use App\Models\SubBranch;
use App\Models\SubBranchTimeSetting;
use App\Models\SubBranchCheckinSetting;
use App\Models\SubBranchPenaltySetting;
use App\Models\SubBranchCancellationPolicy;
use App\Models\SubBranchDepositSetting;
use App\Models\SubBranchTaxSetting;
use App\Models\SubBranchReservationSetting;
use App\Models\SubBranchNotificationSetting;
use Illuminate\Support\Facades\Auth;

class SubBranchObserver
{
    /**
     * Cuando se crea una nueva sub-sucursal, crear configuraciones por defecto
     */
    public function created(SubBranch $subBranch)
    {
        $userId = Auth::id();

        // Time Settings - Valores por defecto
        SubBranchTimeSetting::create([
            'sub_branch_id' => $subBranch->id,
            'max_allowed_time' => 75,
            'extra_tolerance' => 15,
            'apply_tolerance' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Check-in Settings
        SubBranchCheckinSetting::create([
            'sub_branch_id' => $subBranch->id,
            'checkin_time' => '14:00',
            'checkout_time' => '12:00',
            'early_checkin_cost' => 0,
            'late_checkout_cost' => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Penalty Settings
        SubBranchPenaltySetting::create([
            'sub_branch_id' => $subBranch->id,
            'penalty_active' => true,
            'charge_interval_minutes' => 15,
            'amount_per_interval' => 15.00,
            'penalty_type' => 'fixed',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Cancellation Policy
        SubBranchCancellationPolicy::create([
            'sub_branch_id' => $subBranch->id,
            'time_limit_hours' => 24,
            'refund_percentage' => 100.00,
            'no_show_charge' => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Deposit Settings
        SubBranchDepositSetting::create([
            'sub_branch_id' => $subBranch->id,
            'requires_deposit' => false,
            'deposit_amount' => 0,
            'payment_method' => null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Tax Settings
        SubBranchTaxSetting::create([
            'sub_branch_id' => $subBranch->id,
            'tax_percentage' => 18.00,
            'tax_included' => false,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Reservation Settings
        SubBranchReservationSetting::create([
            'sub_branch_id' => $subBranch->id,
            'min_advance_hours' => 2,
            'max_advance_days' => 90,
            'last_minute_surcharge_percentage' => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Notification Settings
        SubBranchNotificationSetting::create([
            'sub_branch_id' => $subBranch->id,
            'reservation_reminder_active' => true,
            'reminder_hours_before' => 2,
            'excess_alert_active' => true,
            'confirmation_email_active' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }
}
