<?php

namespace App\Jobs;

use App\Models\SubBranch;
use App\Models\SubBranchTimeSetting;
use App\Models\SubBranchCheckinSetting;
use App\Models\SubBranchPenaltySetting;
use App\Models\SubBranchCancellationPolicy;
use App\Models\SubBranchDepositSetting;
use App\Models\SubBranchTaxSetting;
use App\Models\SubBranchReservationSetting;
use App\Models\SubBranchNotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloneSubBranchConfigurationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sourceSubBranchId;
    protected $targetSubBranchId;
    protected $userId;

    public function __construct($sourceSubBranchId, $targetSubBranchId, $userId)
    {
        $this->sourceSubBranchId = $sourceSubBranchId;
        $this->targetSubBranchId = $targetSubBranchId;
        $this->userId = $userId;
    }

    public function handle()
    {
        DB::beginTransaction();

        try {
            $source = SubBranch::with([
                'timeSettings',
                'checkinSettings',
                'penaltySettings',
                'cancellationPolicies',
                'depositSettings',
                'taxSettings',
                'reservationSettings',
                'notificationSettings'
            ])->findOrFail($this->sourceSubBranchId);

            // Clonar cada configuración
            if ($source->timeSettings) {
                SubBranchTimeSetting::updateOrCreate(
                    ['sub_branch_id' => $this->targetSubBranchId],
                    array_merge(
                        $source->timeSettings->only([
                            'max_allowed_time',
                            'extra_tolerance',
                            'apply_tolerance'
                        ]),
                        ['created_by' => $this->userId, 'updated_by' => $this->userId]
                    )
                );
            }

            if ($source->checkinSettings) {
                SubBranchCheckinSetting::updateOrCreate(
                    ['sub_branch_id' => $this->targetSubBranchId],
                    array_merge(
                        $source->checkinSettings->only([
                            'checkin_time',
                            'checkout_time',
                            'early_checkin_cost',
                            'late_checkout_cost'
                        ]),
                        ['created_by' => $this->userId, 'updated_by' => $this->userId]
                    )
                );
            }

            if ($source->penaltySettings) {
                SubBranchPenaltySetting::updateOrCreate(
                    ['sub_branch_id' => $this->targetSubBranchId],
                    array_merge(
                        $source->penaltySettings->only([
                            'penalty_active',
                            'charge_interval_minutes',
                            'amount_per_interval',
                            'penalty_type'
                        ]),
                        ['created_by' => $this->userId, 'updated_by' => $this->userId]
                    )
                );
            }

            if ($source->cancellationPolicies) {
                SubBranchCancellationPolicy::updateOrCreate(
                    ['sub_branch_id' => $this->targetSubBranchId],
                    array_merge(
                        $source->cancellationPolicies->only([
                            'time_limit_hours',
                            'refund_percentage',
                            'no_show_charge'
                        ]),
                        ['created_by' => $this->userId, 'updated_by' => $this->userId]
                    )
                );
            }

            if ($source->depositSettings) {
                SubBranchDepositSetting::updateOrCreate(
                    ['sub_branch_id' => $this->targetSubBranchId],
                    array_merge(
                        $source->depositSettings->only([
                            'requires_deposit',
                            'deposit_amount',
                            'payment_method'
                        ]),
                        ['created_by' => $this->userId, 'updated_by' => $this->userId]
                    )
                );
            }

            if ($source->taxSettings) {
                SubBranchTaxSetting::updateOrCreate(
                    ['sub_branch_id' => $this->targetSubBranchId],
                    array_merge(
                        $source->taxSettings->only([
                            'tax_percentage',
                            'tax_included'
                        ]),
                        ['created_by' => $this->userId, 'updated_by' => $this->userId]
                    )
                );
            }

            if ($source->reservationSettings) {
                SubBranchReservationSetting::updateOrCreate(
                    ['sub_branch_id' => $this->targetSubBranchId],
                    array_merge(
                        $source->reservationSettings->only([
                            'min_advance_hours',
                            'max_advance_days',
                            'last_minute_surcharge_percentage'
                        ]),
                        ['created_by' => $this->userId, 'updated_by' => $this->userId]
                    )
                );
            }

            if ($source->notificationSettings) {
                SubBranchNotificationSetting::updateOrCreate(
                    ['sub_branch_id' => $this->targetSubBranchId],
                    array_merge(
                        $source->notificationSettings->only([
                            'reservation_reminder_active',
                            'reminder_hours_before',
                            'excess_alert_active',
                            'confirmation_email_active'
                        ]),
                        ['created_by' => $this->userId, 'updated_by' => $this->userId]
                    )
                );
            }

            DB::commit();

            // Aquí puedes enviar una notificación al usuario
            // Notification::send($user, new ConfigurationClonedNotification());

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error clonando configuración: ' . $e->getMessage());
            throw $e;
        }
    }
}
