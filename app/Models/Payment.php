<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\GeneratesCode;
use App\Events\PaymentReceived;
use App\Jobs\ProcessPaymentJob;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Payment extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, GeneratesCode, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'payment_code', 'booking_id', 'currency_id', 'amount', 'exchange_rate',
        'amount_base_currency', 'payment_method', 'payment_method_id', 'reference',
        'operation_number', 'cash_register_id', 'payment_date', 'notes', 'status','created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_base_currency' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    const METHOD_CASH = 'cash';
    const METHOD_CARD = 'card';
    const METHOD_TRANSFER = 'transfer';
    const METHOD_CHECK = 'check';
    const METHOD_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Relaciones
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }
    public function creadoPor(){
        return $this->belongsTo(User::class, 'created_by');
    }

    // NUEVOS SCOPES
    public function scopeByCashRegister($query, $cashRegisterId)
    {
        return $query->where('cash_register_id', $cashRegisterId);
    }

    public function scopeByPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // Métodos de negocio
    public function process($userId = null)
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \Exception('El pago ya fue procesado');
        }

        ProcessPaymentJob::dispatch($this, $userId);
    }

    public function complete()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();

        // Actualizar monto pagado en la reserva
        $this->booking->paid_amount += $this->amount_base_currency;
        $this->booking->save();

        event(new PaymentReceived($this));
    }

    public function refund($reason = null, $userId = null)
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            throw new \Exception('Solo se pueden reembolsar pagos completados');
        }

        $this->status = self::STATUS_REFUNDED;
        $this->notes = ($this->notes ? $this->notes . ' | ' : '') . 'Reembolsado: ' . $reason;
        $this->save();

        // Actualizar monto pagado en la reserva
        $this->booking->paid_amount -= $this->amount_base_currency;
        $this->booking->save();
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (empty($payment->payment_code)) {
                $payment->payment_code = $payment->generateUniqueCode('PAY');
            }

            // Calcular monto en moneda base si no está establecido
            if (!$payment->amount_base_currency) {
                $baseCurrency = Currency::getDefault();
                $payment->amount_base_currency = $payment->currency->convertTo($baseCurrency, $payment->amount);
            }
        });

        static::created(function ($payment) {
            if ($payment->status === self::STATUS_COMPLETED) {
                $payment->complete();
            }
        });
    }
    public static function getReportePagos(
        $startDate, 
        $endDate, 
        $subBranchId = null, 
        $paymentMethodId = null,
        $codigoPago = null,
        $habitacion = null,
        $cliente = null,
        $page = 1,
        $perPage = 10
    ) {
        $query = self::with([
            'booking.room.floor.subBranch',
            'booking.customer',
            'booking.consumptions.product',
            'paymentMethod',
            'currency',
            'cashRegister'
        ])
        ->where('status', self::STATUS_COMPLETED)
        ->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        // Filtro por sucursal
        if ($subBranchId) {
            $query->whereHas('booking', function($q) use ($subBranchId) {
                $q->where('sub_branch_id', $subBranchId);
            });
        }

        // Filtro por método de pago
        if ($paymentMethodId) {
            $query->where('payment_method_id', $paymentMethodId);
        }

        // Filtro por código de pago
        if ($codigoPago) {
            $query->where('payment_code', 'like', '%' . $codigoPago . '%');
        }

        // Filtro por habitación
        if ($habitacion) {
            $query->whereHas('booking.room', function($q) use ($habitacion) {
                $q->where('room_number', 'like', '%' . $habitacion . '%');
            });
        }

        // Filtro por cliente
        if ($cliente) {
            $query->whereHas('booking.customer', function($q) use ($cliente) {
                $q->where('name', 'like', '%' . $cliente . '%');
            });
        }

        // Obtener totales ANTES de la paginación
        $totalGeneral = 0;
        $totalesPorMetodo = [];
        
        // Clonar query para obtener todos los registros sin paginación para los totales
        $allPayments = (clone $query)->get();
        
        foreach ($allPayments as $payment) {
            $metodoPago = $payment->paymentMethod->name ?? 'No especificado';
            $montoPago = (float) $payment->amount;
            
            $totalGeneral += $montoPago;
            
            if (!isset($totalesPorMetodo[$metodoPago])) {
                $totalesPorMetodo[$metodoPago] = [
                    'cantidad' => 0,
                    'total' => 0
                ];
            }
            
            $totalesPorMetodo[$metodoPago]['cantidad']++;
            $totalesPorMetodo[$metodoPago]['total'] += $montoPago;
        }

        // Aplicar paginación
        $payments = $query->orderBy('payment_date', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $listadoPagos = $payments->map(function ($payment) {
            $booking = $payment->booking;
            $metodoPago = $payment->paymentMethod->name ?? 'No especificado';
            $montoPago = (float) $payment->amount;

            // CORRECCIÓN: Usar los campos específicos room_subtotal y products_subtotal
            $costoHabitacion = (float) ($booking->room_subtotal ?? 0);
            $totalConsumos = (float) ($booking->products_subtotal ?? 0);
            
            // Cálculo correcto del total a pagar
            $totalAPagar = $costoHabitacion + $totalConsumos + (float) $booking->tax_amount - (float) $booking->discount_amount;

            return [
                'id' => $payment->id,
                'codigo_pago' => $payment->payment_code,
                'fecha_pago' => $payment->payment_date->format('d-m-Y H:i:s A'),
                'monto_pagado' => $montoPago,
                'metodo_pago' => $metodoPago,
                'moneda' => $payment->currency->code ?? 'PEN',
                'referencia' => $payment->reference,
                'numero_operacion' => $payment->operation_number,
                'codigo_reserva' => $booking->booking_code,
                'habitacion' => $booking->room->room_number ?? 'N/A',
                'sucursal' => $booking->room->floor->subBranch->name ?? 'N/A',
                'cliente' => $booking->customer->name ?? 'Sin registrar',
                'hora_inicio' => $booking->check_in ? $booking->check_in->format('d-m-Y H:i:s A') : null,
                'hora_fin' => $booking->check_out ? $booking->check_out->format('d-m-Y H:i:s A') : null,
                'costo_habitacion' => $costoHabitacion,
                'tuvo_consumo' => $totalConsumos > 0 ? 'SI' : 'NO',
                'consumos' => $booking->consumptions->map(function($c) {
                    return [
                        'producto' => $c->product->name,
                        'cantidad' => (float) $c->quantity,
                        'precio' => (float) $c->unit_price,
                        'total' => (float) $c->total_price,
                    ];
                })->toArray(),
                'total_consumos' => $totalConsumos,
                'impuestos' => (float) $booking->tax_amount,
                'descuentos' => (float) $booking->discount_amount,
                'total_a_pagar' => $totalAPagar,
                'total_pagado_reserva' => (float) $booking->paid_amount,
                'saldo_pendiente' => $totalAPagar - (float) $booking->paid_amount,
                'caja_registradora' => $payment->cashRegister->name ?? 'N/A',
                'notas' => $payment->notes,
            ];
        });

        return [
            'pagos' => $listadoPagos->values(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ],
            'TOTAL_GENERAL' => round($totalGeneral, 2),
            'totales_por_medio_pago' => collect($totalesPorMetodo)->map(function($data, $metodo) {
                return [
                    'metodo_pago' => $metodo,
                    'cantidad_pagos' => $data['cantidad'],
                    'total_cobrado' => round($data['total'], 2),
                ];
            })->sortByDesc('total_cobrado')->values()->toArray(),
            'resumen' => [
                'periodo' => [
                    'fecha_inicio' => $startDate,
                    'fecha_fin' => $endDate,
                ],
                'total_pagos_registrados' => $allPayments->count(),
                'total_cobrado' => round($totalGeneral, 2),
            ],
        ];
    }
}