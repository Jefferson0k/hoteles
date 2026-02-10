<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubBranch;
use App\Models\SubBranchTimeSetting;
use App\Models\SubBranchCheckinSetting;
use App\Models\SubBranchPenaltySetting;
use App\Models\SubBranchCancellationPolicy;
use App\Models\SubBranchDepositSetting;
use App\Models\SubBranchTaxSetting;
use App\Models\SubBranchReservationSetting;
use App\Models\SubBranchNotificationSetting;
use App\Http\Requests\SubBranchConfiguration\StoreConfigurationRequest;
use App\Http\Requests\SubBranchConfiguration\UpdateTimeSettingsRequest;
use App\Http\Requests\SubBranchConfiguration\UpdateCheckinSettingsRequest;
use App\Http\Requests\SubBranchConfiguration\UpdatePenaltySettingsRequest;
use App\Http\Requests\SubBranchConfiguration\UpdateCancellationPolicyRequest;
use App\Http\Requests\SubBranchConfiguration\UpdateDepositSettingsRequest;
use App\Http\Requests\SubBranchConfiguration\UpdateTaxSettingsRequest;
use App\Http\Requests\SubBranchConfiguration\UpdateReservationSettingsRequest;
use App\Http\Requests\SubBranchConfiguration\UpdateNotificationSettingsRequest;
use App\Http\Requests\SubBranchConfiguration\CloneConfigurationRequest;
use App\Http\Resources\SubBranchConfiguration\ConfigurationResource;
use App\Jobs\CloneSubBranchConfigurationJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubBranchConfigurationController extends Controller{
    /**
     * Obtener la configuración completa de una sub-sucursal
     */
    public function show($subBranchId)
    {
        $subBranch = SubBranch::with([
            'timeSettings',
            'checkinSettings',
            'penaltySettings',
            'cancellationPolicies',
            'depositSettings',
            'taxSettings',
            'reservationSettings',
            'notificationSettings'
        ])->findOrFail($subBranchId);

        return new ConfigurationResource($subBranch);
    }

    /**
     * Guardar/Actualizar TODA la configuración
     */
    public function store(StoreConfigurationRequest $request, $subBranchId)
    {
        DB::beginTransaction();

        try {
            $subBranch = SubBranch::findOrFail($subBranchId);
            $userId = Auth::id();

            // 1. Time Settings
            SubBranchTimeSetting::updateOrCreate(
                ['sub_branch_id' => $subBranchId],
                array_merge($request->input('time'), [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])
            );

            // 2. Check-in Settings
            SubBranchCheckinSetting::updateOrCreate(
                ['sub_branch_id' => $subBranchId],
                array_merge($request->input('checkin'), [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])
            );

            // 3. Penalty Settings
            SubBranchPenaltySetting::updateOrCreate(
                ['sub_branch_id' => $subBranchId],
                array_merge($request->input('penalty'), [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])
            );

            // 4. Cancellation Policy
            SubBranchCancellationPolicy::updateOrCreate(
                ['sub_branch_id' => $subBranchId],
                array_merge($request->input('cancellation'), [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])
            );

            // 5. Deposit Settings
            SubBranchDepositSetting::updateOrCreate(
                ['sub_branch_id' => $subBranchId],
                array_merge($request->input('deposit'), [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])
            );

            // 6. Tax Settings
            SubBranchTaxSetting::updateOrCreate(
                ['sub_branch_id' => $subBranchId],
                array_merge($request->input('tax'), [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])
            );

            // 7. Reservation Settings
            SubBranchReservationSetting::updateOrCreate(
                ['sub_branch_id' => $subBranchId],
                array_merge($request->input('reservation'), [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])
            );

            // 8. Notification Settings
            SubBranchNotificationSetting::updateOrCreate(
                ['sub_branch_id' => $subBranchId],
                array_merge($request->input('notification'), [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])
            );

            DB::commit();

            $subBranch->load([
                'timeSettings',
                'checkinSettings',
                'penaltySettings',
                'cancellationPolicies',
                'depositSettings',
                'taxSettings',
                'reservationSettings',
                'notificationSettings'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuración guardada exitosamente',
                'data' => new ConfigurationResource($subBranch)
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar configuración de TIEMPO
     */
    public function updateTimeSettings(UpdateTimeSettingsRequest $request, $subBranchId)
    {
        $setting = SubBranchTimeSetting::updateOrCreate(
            ['sub_branch_id' => $subBranchId],
            array_merge($request->validated(), ['updated_by' => Auth::id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración de tiempo actualizada',
            'data' => $setting
        ], 200);
    }

    /**
     * Actualizar configuración de CHECK-IN
     */
    public function updateCheckinSettings(UpdateCheckinSettingsRequest $request, $subBranchId)
    {
        $setting = SubBranchCheckinSetting::updateOrCreate(
            ['sub_branch_id' => $subBranchId],
            array_merge($request->validated(), ['updated_by' => Auth::id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración de check-in actualizada',
            'data' => $setting
        ], 200);
    }

    /**
     * Actualizar configuración de PENALIZACIONES
     */
    public function updatePenaltySettings(UpdatePenaltySettingsRequest $request, $subBranchId)
    {
        $setting = SubBranchPenaltySetting::updateOrCreate(
            ['sub_branch_id' => $subBranchId],
            array_merge($request->validated(), ['updated_by' => Auth::id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración de penalización actualizada',
            'data' => $setting
        ], 200);
    }

    /**
     * Actualizar configuración de CANCELACIÓN
     */
    public function updateCancellationPolicy(UpdateCancellationPolicyRequest $request, $subBranchId)
    {
        $setting = SubBranchCancellationPolicy::updateOrCreate(
            ['sub_branch_id' => $subBranchId],
            array_merge($request->validated(), ['updated_by' => Auth::id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Política de cancelación actualizada',
            'data' => $setting
        ], 200);
    }

    /**
     * Actualizar configuración de DEPÓSITOS
     */
    public function updateDepositSettings(UpdateDepositSettingsRequest $request, $subBranchId)
    {
        $setting = SubBranchDepositSetting::updateOrCreate(
            ['sub_branch_id' => $subBranchId],
            array_merge($request->validated(), ['updated_by' => Auth::id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración de depósitos actualizada',
            'data' => $setting
        ], 200);
    }

    /**
     * Actualizar configuración de IMPUESTOS
     */
    public function updateTaxSettings(UpdateTaxSettingsRequest $request, $subBranchId)
    {
        $setting = SubBranchTaxSetting::updateOrCreate(
            ['sub_branch_id' => $subBranchId],
            array_merge($request->validated(), ['updated_by' => Auth::id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración de impuestos actualizada',
            'data' => $setting
        ], 200);
    }

    /**
     * Actualizar configuración de RESERVAS
     */
    public function updateReservationSettings(UpdateReservationSettingsRequest $request, $subBranchId)
    {
        $setting = SubBranchReservationSetting::updateOrCreate(
            ['sub_branch_id' => $subBranchId],
            array_merge($request->validated(), ['updated_by' => Auth::id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración de reservas actualizada',
            'data' => $setting
        ], 200);
    }

    /**
     * Actualizar configuración de NOTIFICACIONES
     */
    public function updateNotificationSettings(UpdateNotificationSettingsRequest $request, $subBranchId)
    {
        $setting = SubBranchNotificationSetting::updateOrCreate(
            ['sub_branch_id' => $subBranchId],
            array_merge($request->validated(), ['updated_by' => Auth::id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración de notificaciones actualizada',
            'data' => $setting
        ], 200);
    }

    /**
     * Clonar configuración (ejecuta Job en segundo plano)
     */
    public function cloneConfiguration(CloneConfigurationRequest $request, $subBranchId)
    {
        CloneSubBranchConfigurationJob::dispatch(
            $subBranchId,
            $request->target_sub_branch_id,
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Clonación de configuración iniciada. Recibirás una notificación cuando termine.'
        ], 202);
    }
}
