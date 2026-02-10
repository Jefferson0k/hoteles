<?php

namespace App\Http\Requests\SubBranchConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfigurationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Tiempo
            'time.max_allowed_time' => 'required|integer|min:1',
            'time.extra_tolerance' => 'required|integer|min:0',
            'time.apply_tolerance' => 'required|boolean',

            // Check-in/Check-out
            'checkin.checkin_time' => 'required|date_format:H:i',
            'checkin.checkout_time' => 'required|date_format:H:i',
            'checkin.early_checkin_cost' => 'required|numeric|min:0',
            'checkin.late_checkout_cost' => 'required|numeric|min:0',

            // Penalización
            'penalty.penalty_active' => 'required|boolean',
            'penalty.charge_interval_minutes' => 'required|integer|min:1',
            'penalty.amount_per_interval' => 'required|numeric|min:0',
            'penalty.penalty_type' => 'required|in:fixed,progressive',

            // Cancelación
            'cancellation.time_limit_hours' => 'required|integer|min:0',
            'cancellation.refund_percentage' => 'required|numeric|min:0|max:100',
            'cancellation.no_show_charge' => 'required|numeric|min:0',

            // Depósitos
            'deposit.requires_deposit' => 'required|boolean',
            'deposit.deposit_amount' => 'required|numeric|min:0',
            'deposit.payment_method' => 'nullable|string',

            // Impuestos
            'tax.tax_percentage' => 'required|numeric|min:0|max:100',
            'tax.tax_included' => 'required|boolean',

            // Reservas
            'reservation.min_advance_hours' => 'required|integer|min:0',
            'reservation.max_advance_days' => 'required|integer|min:1',
            'reservation.last_minute_surcharge_percentage' => 'required|numeric|min:0',

            // Notificaciones
            'notification.reservation_reminder_active' => 'required|boolean',
            'notification.reminder_hours_before' => 'required|integer|min:0',
            'notification.excess_alert_active' => 'required|boolean',
            'notification.confirmation_email_active' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'time.max_allowed_time.required' => 'El tiempo máximo permitido es obligatorio',
            'time.max_allowed_time.min' => 'El tiempo máximo debe ser mayor a 0',
            'penalty.penalty_type.in' => 'El tipo de penalidad debe ser fixed o progressive',
            'tax.tax_percentage.max' => 'El porcentaje de impuesto no puede ser mayor a 100',
        ];
    }
}
