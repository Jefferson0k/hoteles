<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\BookingListResource;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RateType;
use App\Models\Product;
use App\Models\Payment;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\FinishBookingRequest;
use App\Models\BookingConsumption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller{
    /**
     * INICIAR SERVICIO
     * Crea booking, procesa pago y hace check-in automático
     */
    public function store(StoreBookingRequest $request){
        try {
            DB::beginTransaction();
            $validated = $request->validated();
            $room = Room::findOrFail($validated['room_id']);
            if ($room->status !== Room::STATUS_AVAILABLE) {
                return response()->json([
                    'success' => false,
                    'message' => 'La habitación no está disponible. Estado actual: ' . $room->status
                ], 422);
            }
            if ($room->hasActiveBooking()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La habitación ya tiene una reserva activa'
                ], 422);
            }
            $rateType = RateType::findOrFail($validated['rate_type_id']);
            $checkIn = now();
            $checkOut = $this->calculateCheckOut($checkIn, $rateType->code, $validated['total_hours']);
            $roomSubtotal = $validated['rate_per_hour'] * $validated['total_hours'];
            $productsSubtotal = 0;
            $bookingCode = $this->generateBookingCode();
            $booking = Booking::create([
                'id' => Str::uuid(),
                'booking_code' => $bookingCode,
                'room_id' => $validated['room_id'],
                'customers_id' => $validated['customers_id'],
                'rate_type_id' => $validated['rate_type_id'],
                'currency_id' => $validated['currency_id'],
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'total_hours' => $validated['total_hours'],
                'rate_per_hour' => $validated['rate_per_hour'],
                'rate_per_unit' => $validated['rate_per_hour'],
                'room_subtotal' => $roomSubtotal,
                'products_subtotal' => 0,
                'subtotal' => $roomSubtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $roomSubtotal,
                'paid_amount' => 0,
                'status' => Booking::STATUS_CONFIRMED,
                'voucher_type' => $validated['voucher_type'] ?? 'ticket',
                'sub_branch_id' => Auth::user()->sub_branch_id,
                'created_by' => Auth::id(),
            ]);
            if (isset($validated['consumptions']) && count($validated['consumptions']) > 0) {
                foreach ($validated['consumptions'] as $consumption) {
                    $totalPrice = $consumption['quantity'] * $consumption['unit_price'];
                    
                    $booking->consumptions()->create([
                        'id' => Str::uuid(),
                        'product_id' => $consumption['product_id'],
                        'quantity' => $consumption['quantity'],
                        'unit_price' => $consumption['unit_price'],
                        'total_price' => $totalPrice,
                        'status' => BookingConsumption::STATUS_PAID,
                        'consumed_at' => now(),
                        'created_by' => Auth::id(),
                    ]);
                    $productsSubtotal += $totalPrice;
                }
                $booking->products_subtotal = $productsSubtotal;
                $booking->subtotal = $roomSubtotal + $productsSubtotal;
                $booking->total_amount = $booking->subtotal;
                $booking->save();
            }
            $totalPaid = 0;
            foreach ($validated['payments'] as $paymentData) {
                if (isset($paymentData['cash_register_id'])) {
                    $cashRegister = CashRegister::find($paymentData['cash_register_id']);
                    if (!$cashRegister || !$cashRegister->isOpen()) {
                        throw new \Exception('La caja especificada no está abierta');
                    }
                }
                $paymentMethod = PaymentMethod::find($paymentData['payment_method_id']);
                if ($paymentMethod && $paymentMethod->requires_reference && empty($paymentData['operation_number'])) {
                    throw new \Exception("El método de pago {$paymentMethod->name} requiere un número de operación");
                }
                Payment::create([
                    'id' => Str::uuid(),
                    'payment_code' => $this->generatePaymentCode(),
                    'booking_id' => $booking->id,
                    'currency_id' => $validated['currency_id'],
                    'amount' => $paymentData['amount'],
                    'amount_base_currency' => $paymentData['amount'],
                    'payment_method' => $paymentMethod->code ?? 'cash',
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'cash_register_id' => $paymentData['cash_register_id'] ?? null,
                    'operation_number' => $paymentData['operation_number'] ?? null,
                    'payment_date' => now(),
                    'status' => 'completed',
                    'notes' => 'Pago inicial al check-in',
                    'created_by' => Auth::id(),
                ]);
                $totalPaid += $paymentData['amount'];
            }
            $booking->paid_amount = $totalPaid;
            $booking->save();
            $booking->checkIn(Auth::id());
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => '✅ Servicio iniciado. Habitación ocupada.',
                'data' => [
                    'booking' => $booking->fresh([
                        'customer',
                        'room',
                        'rateType',
                        'currency',
                        'payments.paymentMethod',
                        'consumptions.product'
                    ]),
                    'check_in' => $checkIn->toDateTimeString(),
                    'check_out_scheduled' => $checkOut->toDateTimeString(),
                    'total_paid' => $booking->paid_amount,
                    'balance' => $booking->balance,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear booking:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * FINALIZAR SERVICIO
     * Calcula tiempo REAL usado, cobra extras si se pasó, hace check-out
     */
    public function finishService(FinishBookingRequest $request, Booking $booking)
    {
        try {
            DB::beginTransaction();

            if ($booking->status !== Booking::STATUS_CHECKED_IN) {
                return response()->json([
                    'success' => false,
                    'message' => 'La reserva debe estar activa para finalizarla'
                ], 422);
            }

            $checkOutReal = now();
            $checkInTime = $booking->check_in;
            
            // CALCULAR TIEMPO REAL USADO
            $hoursUsedReal = $checkInTime->diffInMinutes($checkOutReal) / 60;
            $hoursContracted = $booking->total_hours;
            
            // CALCULAR HORAS EXTRAS
            $extraHours = max(0, $hoursUsedReal - $hoursContracted);
            $extraAmount = 0;
            
            if ($extraHours > 0) {
                // Redondear hacia arriba (fracción de hora se cobra completa)
                $extraHoursCeil = ceil($extraHours);
                $extraAmount = $extraHoursCeil * $booking->rate_per_hour;
                
                // Actualizar booking con el tiempo extra
                $booking->total_hours += $extraHoursCeil;
                $booking->room_subtotal += $extraAmount;
                $booking->subtotal += $extraAmount;
                $booking->total_amount += $extraAmount;
                $booking->notes = ($booking->notes ?? '') . "\n[" . $checkOutReal . "] Tiempo extra: {$extraHoursCeil}h = {$extraAmount}";
            }

            // PROCESAR PAGOS ADICIONALES (si hay balance pendiente)
            if ($request->has('payments') && count($request->payments) > 0) {
                foreach ($request->payments as $paymentData) {
                    $paymentMethod = PaymentMethod::find($paymentData['payment_method_id']);
                    
                    if ($paymentMethod && $paymentMethod->requires_reference && empty($paymentData['operation_number'])) {
                        throw new \Exception("El método de pago {$paymentMethod->name} requiere un número de operación");
                    }

                    Payment::create([
                        'id' => Str::uuid(),
                        'payment_code' => $this->generatePaymentCode(),
                        'booking_id' => $booking->id,
                        'currency_id' => $booking->currency_id,
                        'amount' => $paymentData['amount'],
                        'amount_base_currency' => $paymentData['amount'],
                        'payment_method' => $paymentMethod->code ?? 'cash',
                        'payment_method_id' => $paymentData['payment_method_id'],
                        'cash_register_id' => $paymentData['cash_register_id'] ?? null,
                        'operation_number' => $paymentData['operation_number'] ?? null,
                        'payment_date' => now(),
                        'status' => 'completed',
                        'notes' => $extraHours > 0 ? "Pago al check-out (incluye {$extraHoursCeil}h extras)" : 'Pago al check-out',
                        'created_by' => Auth::id(),
                    ]);

                    $booking->paid_amount += $paymentData['amount'];
                }
            }

            $booking->save();

            // VERIFICAR BALANCE (permitir check-out con deuda si force_checkout=true)
            $forceCheckout = $request->force_checkout ?? false;
            if ($booking->balance > 0 && !$forceCheckout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hay un saldo pendiente de pago',
                    'data' => [
                        'balance' => $booking->balance,
                        'total_amount' => $booking->total_amount,
                        'paid_amount' => $booking->paid_amount,
                        'extra_hours' => $extraHours > 0 ? ceil($extraHours) : 0,
                        'extra_amount' => $extraAmount,
                        'hours_contracted' => $hoursContracted,
                        'hours_used' => round($hoursUsedReal, 2),
                    ]
                ], 422);
            }

            // HACER CHECK-OUT (cambia habitación a CLEANING)
            $booking->checkOut(Auth::id());

            if ($request->notes) {
                $booking->notes = ($booking->notes ?? '') . "\n[" . $checkOutReal . "] " . $request->notes;
                $booking->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Servicio finalizado. Habitación en limpieza.',
                'data' => [
                    'booking' => $booking->fresh(['room', 'customer', 'consumptions', 'payments']),
                    'check_out_time' => $checkOutReal->toDateTimeString(),
                    'final_balance' => $booking->balance,
                    'time_summary' => [
                        'hours_contracted' => $hoursContracted,
                        'hours_used' => round($hoursUsedReal, 2),
                        'extra_hours' => $extraHours > 0 ? ceil($extraHours) : 0,
                        'extra_amount' => $extraAmount,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al finalizar booking:', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar consumos adicionales durante la estadía
     */
    public function addConsumption(Request $request, Booking $booking)
    {
        $request->validate([
            'consumptions' => 'required|array|min:1',
            'consumptions.*.product_id' => 'required|uuid|exists:products,id',
            'consumptions.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            if ($booking->status !== Booking::STATUS_CHECKED_IN) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden agregar consumos a reservas activas'
                ], 422);
            }

            $totalAdded = 0;

            foreach ($request->consumptions as $consumptionData) {
                $product = Product::findOrFail($consumptionData['product_id']);
                
                $consumption = $booking->consumptions()->create([
                    'id' => Str::uuid(),
                    'product_id' => $product->id,
                    'quantity' => $consumptionData['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $consumptionData['quantity'] * $product->price,
                    'consumed_at' => now(),
                    'created_by' => Auth::id(),
                ]);

                $totalAdded += $consumption->total_price;
            }

            // Actualizar totales
            $booking->products_subtotal += $totalAdded;
            $booking->total_amount += $totalAdded;
            $booking->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Consumos agregados. Se cobrarán al finalizar.',
                'data' => [
                    'booking' => $booking->fresh(['consumptions.product']),
                    'new_balance' => $booking->balance
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar consumos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extender tiempo (se cobra al salir)
     */
    public function extendTime(Request $request, Booking $booking)
    {
        $request->validate([
            'additional_hours' => 'required|integer|min:1|max:24',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            if ($booking->status !== Booking::STATUS_CHECKED_IN) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede extender el tiempo de reservas activas'
                ], 422);
            }

            $additionalHours = $request->additional_hours;
            $additionalAmount = $booking->rate_per_hour * $additionalHours;

            // Actualizar booking
            $booking->total_hours += $additionalHours;
            $booking->check_out = $booking->check_out->addHours($additionalHours);
            $booking->room_subtotal += $additionalAmount;
            $booking->subtotal += $additionalAmount;
            $booking->total_amount += $additionalAmount;
            $booking->notes = ($booking->notes ?? '') . "\n[" . now() . "] Extensión: +{$additionalHours}h = {$additionalAmount}";
            $booking->updated_by = Auth::id();
            $booking->save();

            // Registrar en log
            $booking->room->statusLogs()->create([
                'id' => Str::uuid(),
                'room_id' => $booking->room_id,
                'booking_id' => $booking->id,
                'previous_status' => Room::STATUS_OCCUPIED,
                'new_status' => Room::STATUS_OCCUPIED,
                'reason' => "Extensión: +{$additionalHours}h",
                'changed_at' => now(),
                'changed_by' => Auth::id(), // UUID del usuario
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "⏰ Tiempo extendido: +{$additionalHours}h. Se cobrará al salir.",
                'data' => [
                    'booking' => $booking->fresh(['room', 'customer']),
                    'additional_amount' => $additionalAmount,
                    'new_total' => $booking->total_amount,
                    'new_balance' => $booking->balance,
                    'new_checkout' => $booking->check_out->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al extender tiempo: ' . $e->getMessage()
            ], 500);
        }
    }

    // MÉTODOS AUXILIARES

    private function calculateCheckOut(Carbon $checkIn, string $rateTypeCode, int $totalHours): Carbon
    {
        return match($rateTypeCode) {
            'HOUR' => $checkIn->copy()->addHours($totalHours),
            'DAY' => $checkIn->copy()->addDays($totalHours),
            'NIGHT' => $checkIn->copy()->addHours($totalHours * 12),
            default => $checkIn->copy()->addHours($totalHours)
        };
    }

    private function generateBookingCode(): string
    {
        return 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function generatePaymentCode(): string
    {
        return 'PAY-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
    }

    public function getCheckoutDetails($roomId)
    {
        try {
            $room = Room::with(['activeBooking.customer', 'activeBooking.consumptions.product'])
                ->findOrFail($roomId);

            if (!$room->activeBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay reserva activa en esta habitación'
                ], 404);
            }

            $booking = $room->activeBooking;
            $checkInTime = $booking->check_in;
            $now = now();
            
            // Calcular tiempo total usado
            $minutesUsed = $checkInTime->diffInMinutes($now);
            $hoursUsed = $minutesUsed / 60;
            
            // Calcular tiempo extra si existe
            $hoursContracted = $booking->total_hours;
            $extraHours = max(0, $hoursUsed - $hoursContracted);
            $extraAmount = 0;
            
            if ($extraHours > 0) {
                $extraHoursCeil = ceil($extraHours);
                $extraAmount = $extraHoursCeil * $booking->rate_per_hour;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => $booking->customer->name ?? 'Sin cliente',
                    'check_in_formatted' => $checkInTime->format('d-m-Y H:i:s A'),
                    'total_time' => sprintf('%dh %dm', floor($hoursUsed), $minutesUsed % 60),
                    'has_extra_charges' => $extraAmount > 0,
                    'extra_charges' => number_format($extraAmount, 2),
                    'total_amount' => $booking->total_amount + $extraAmount,
                    'paid_amount' => $booking->paid_amount,
                    'balance' => ($booking->total_amount + $extraAmount) - $booking->paid_amount,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener detalles de checkout:', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles de la habitación'
            ], 500);
        }
    }

    public function calculateExtraTime($roomId)
    {
        try {
            $room = Room::with('activeBooking')->findOrFail($roomId);

            if (!$room->activeBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay reserva activa en esta habitación'
                ], 404);
            }

            $booking = $room->activeBooking;
            $checkInTime = $booking->check_in;
            $checkOutScheduled = $booking->check_out;
            $now = now();
            
            // Verificar si realmente hay tiempo vencido
            if ($now <= $checkOutScheduled) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tiempo aún no ha vencido'
                ], 422);
            }

            // Calcular tiempo extra
            $extraMinutes = $checkOutScheduled->diffInMinutes($now);
            $extraHours = $extraMinutes / 60;
            $extraHoursCeil = ceil($extraHours);
            
            // Calcular costo
            $ratePerHour = $booking->rate_per_hour;
            $totalCharge = $extraHoursCeil * $ratePerHour;

            return response()->json([
                'success' => true,
                'data' => [
                    'hours' => floor($extraHours),
                    'minutes' => $extraMinutes % 60,
                    'extra_hours_ceil' => $extraHoursCeil,
                    'rate_per_hour' => number_format($ratePerHour, 2),
                    'total_charge' => number_format($totalCharge, 2),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al calcular tiempo extra:', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al calcular tiempo extra'
            ], 500);
        }
    }

    /**
     * COBRAR TIEMPO EXTRA
     * Cobra el tiempo extra y extiende el checkout
     */
    public function chargeExtraTime(Request $request, $roomId)
    {
        try {
            DB::beginTransaction();

            $room = Room::with('activeBooking')->findOrFail($roomId);

            if (!$room->activeBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay reserva activa en esta habitación'
                ], 404);
            }

            $booking = $room->activeBooking;
            $checkOutScheduled = $booking->check_out;
            $now = now();
            
            // Verificar tiempo vencido
            if ($now <= $checkOutScheduled) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tiempo aún no ha vencido'
                ], 422);
            }

            // Calcular tiempo extra
            $extraMinutes = $checkOutScheduled->diffInMinutes($now);
            $extraHours = $extraMinutes / 60;
            $extraHoursCeil = ceil($extraHours);
            $extraAmount = $extraHoursCeil * $booking->rate_per_hour;

            // Actualizar booking
            $booking->total_hours += $extraHoursCeil;
            $booking->check_out = $now->copy()->addHours($extraHoursCeil); // Nuevo checkout desde ahora
            $booking->room_subtotal += $extraAmount;
            $booking->subtotal += $extraAmount;
            $booking->total_amount += $extraAmount;
            $booking->notes = ($booking->notes ?? '') . "\n[" . $now . "] Cobro tiempo extra: {$extraHoursCeil}h = S/ {$extraAmount}";
            $booking->updated_by = Auth::id();
            $booking->save();

            // Registrar en log - CORREGIDO
            $booking->room->statusLogs()->create([
                'id' => Str::uuid(),
                'room_id' => $booking->room_id,
                'booking_id' => $booking->id,
                'previous_status' => Room::STATUS_OCCUPIED,
                'new_status' => Room::STATUS_OCCUPIED,
                'reason' => "Cobro tiempo extra: +{$extraHoursCeil}h = S/ {$extraAmount}",
                'changed_at' => $now,
                'changed_by' => Auth::id(), // ← USAR ID DEL USUARIO
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Tiempo extra cobrado: {$extraHoursCeil}h por S/ {$extraAmount}. Nuevo checkout programado.",
                'data' => [
                    'booking' => $booking->fresh(['room', 'customer']),
                    'extra_hours' => $extraHoursCeil,
                    'extra_amount' => $extraAmount,
                    'new_total' => $booking->total_amount,
                    'new_balance' => $booking->balance,
                    'new_checkout' => $booking->check_out->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al cobrar tiempo extra:', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cobrar tiempo extra'
            ], 500);
        }
    }

    /**
     * EXTENDER TIEMPO (mejorado)
     * Para el diálogo extenderTiempo.vue
     */
    public function extendTimeDialog(Request $request, $roomId)
    {
        $request->validate([
            'extra_hours' => 'required|integer|min:1|max:12',
        ]);

        try {
            DB::beginTransaction();

            $room = Room::with('activeBooking')->findOrFail($roomId);

            if (!$room->activeBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay reserva activa en esta habitación'
                ], 404);
            }

            $booking = $room->activeBooking;
            $additionalHours = $request->extra_hours;
            $additionalAmount = $booking->rate_per_hour * $additionalHours;

            // Actualizar booking
            $booking->total_hours += $additionalHours;
            $booking->check_out = now()->addHours($additionalHours); // Nuevo checkout desde ahora
            $booking->room_subtotal += $additionalAmount;
            $booking->subtotal += $additionalAmount;
            $booking->total_amount += $additionalAmount;
            $booking->notes = ($booking->notes ?? '') . "\n[" . now() . "] Extensión: +{$additionalHours}h = S/ {$additionalAmount}";
            $booking->updated_by = Auth::id();
            $booking->save();

            // Registrar en log - CORREGIDO
            $booking->room->statusLogs()->create([
                'id' => Str::uuid(),
                'room_id' => $booking->room_id,
                'booking_id' => $booking->id,
                'previous_status' => Room::STATUS_OCCUPIED,
                'new_status' => Room::STATUS_OCCUPIED,
                'reason' => "Extensión: +{$additionalHours}h = S/ {$additionalAmount}",
                'changed_at' => now(),
                'changed_by' => Auth::id(), // ← USAR ID DEL USUARIO
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Tiempo extendido: +{$additionalHours}h por S/ {$additionalAmount}",
                'data' => [
                    'booking' => $booking->fresh(['room', 'customer']),
                    'additional_amount' => $additionalAmount,
                    'new_total' => $booking->total_amount,
                    'new_balance' => $booking->balance,
                    'new_checkout' => $booking->check_out->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al extender tiempo:', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al extender tiempo'
            ], 500);
        }
    }

    public function checkout($roomId)
    {
        try {
            DB::beginTransaction();

            $room = Room::with('activeBooking.customer')->findOrFail($roomId);

            if (!$room->activeBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay reserva activa en esta habitación'
                ], 404);
            }

            $booking = $room->activeBooking;

            if ($booking->status !== Booking::STATUS_CHECKED_IN) {
                return response()->json([
                    'success' => false,
                    'message' => 'La reserva debe estar activa para finalizarla'
                ], 422);
            }

            // Calcular tiempo extra si existe
            $checkOutReal = now();
            $checkInTime = $booking->check_in;
            $hoursUsedReal = $checkInTime->diffInMinutes($checkOutReal) / 60;
            $hoursContracted = $booking->total_hours;
            $extraHours = max(0, $hoursUsedReal - $hoursContracted);
            
            if ($extraHours > 0) {
                $extraHoursCeil = ceil($extraHours);
                $extraAmount = $extraHoursCeil * $booking->rate_per_hour;
                
                $booking->total_hours += $extraHoursCeil;
                $booking->room_subtotal += $extraAmount;
                $booking->subtotal += $extraAmount;
                $booking->total_amount += $extraAmount;
                $booking->notes = ($booking->notes ?? '') . "\n[" . $checkOutReal . "] Tiempo extra al checkout: {$extraHoursCeil}h = S/ {$extraAmount}";
            }

            $booking->save();

            // Hacer checkout (cambia habitación a CLEANING)
            $booking->checkOut(Auth::id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reserva finalizada correctamente. Habitación en limpieza.',
                'data' => [
                    'booking' => $booking->fresh(['room', 'customer', 'consumptions', 'payments']),
                    'check_out_time' => $checkOutReal->toDateTimeString(),
                    'final_balance' => $booking->balance,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al hacer checkout:', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar la reserva'
            ], 500);
        }
    }
    /**
     * GENERAR TICKET/COMPROBANTE
     * Retorna los datos formateados para el ticket
     */
    public function ticket($bookingId){
        try {
            $booking = Booking::with([
                'customer',
                'room.roomType',
                'rateType',
                'currency',
                'consumptions.product',
                'payments.paymentMethod',
                'subBranch.branch'
            ])->findOrFail($bookingId);

            // Datos de la empresa (ajusta según tu modelo)
            $branch = $booking->subBranch->branch ?? null;
            $empresa = [
                'nombre' => $branch->name ?? 'HOTEL',
                'ruc' => $branch->ruc ?? '20000000000',
                'direccion' => $branch->address ?? 'Sin dirección',
                'telefono' => $branch->phone ?? 'Sin teléfono'
            ];

            // Datos del comprobante
            $comprobante = [
                'tipo' => strtoupper($booking->voucher_type),
                'numero' => $booking->booking_code,
                'fecha' => $booking->check_in->format('d/m/Y'),
                'hora' => $booking->check_in->format('H:i:s')
            ];

            // Datos del cliente
            $cliente = [
                'nombre' => $booking->customer->full_name ?? 'Cliente General',
                'documento' => ($booking->customer->document_type ?? 'DNI') . ': ' . ($booking->customer->document_number ?? 'Sin documento'),
                'direccion' => $booking->customer->address ?? ''
            ];

            // Datos de la habitación
            $habitacion = [
                'numero' => $booking->room->room_number,
                'tipo' => $booking->room->roomType->name ?? 'Habitación',
                'tarifa' => $booking->rateType->name ?? 'Por Hora',
                'cantidad' => $booking->total_hours,
                'precioUnitario' => (float) $booking->rate_per_hour,
                'total' => (float) $booking->room_subtotal
            ];

            // Productos consumidos
            $productos = [];
            foreach ($booking->consumptions as $consumption) {
                $productos[] = [
                    'nombre' => $consumption->product->name ?? 'Producto',
                    'cantidad' => (float) $consumption->quantity,
                    'precio' => (float) $consumption->unit_price,
                    'total' => (float) $consumption->total_price
                ];
            }

            // Totales
            $totales = [
                'subtotal' => (float) $booking->subtotal,
                'descuento' => (float) $booking->discount_amount,
                'igv' => (float) $booking->tax_amount,
                'total' => (float) $booking->total_amount
            ];

            // Método de pago (tomar el primero o el principal)
            $primerPago = $booking->payments->first();
            $pago = [
                'metodo' => $primerPago ? $primerPago->paymentMethod->name ?? 'Efectivo' : 'Efectivo',
                'operacion' => $primerPago ? $primerPago->operation_number : null
            ];

            // Footer
            $footer = [
                'mensaje' => '¡Esperamos su próxima visita!',
                'sistema' => config('app.name', 'Sistema Hotelero') . ' v1.0'
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'empresa' => $empresa,
                    'comprobante' => $comprobante,
                    'cliente' => $cliente,
                    'habitacion' => $habitacion,
                    'productos' => $productos,
                    'totales' => $totales,
                    'pago' => $pago,
                    'footer' => $footer
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al generar ticket:', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }
        public function index(Request $request)
    {
        $query = Booking::with([
            'room.floor.subBranch',
            'customer',
            'rateType',
            'currency',
            'payments.paymentMethod',
            'consumptions'
        ]);

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('room', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('room_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por tipo de pago
        if ($request->filled('payment_method_id')) {
            $query->whereHas('payments', function($q) use ($request) {
                $q->where('payment_method_id', $request->payment_method_id);
            });
        }

        // Filtro por rango de fechas
        if ($request->filled('date_from')) {
            $query->whereDate('check_in', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('check_in', '<=', $request->date_to);
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por sucursal
        if ($request->filled('sub_branch_id')) {
            $query->where('sub_branch_id', $request->sub_branch_id);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $bookings = $query->paginate($perPage);

        // Calcular totales generales
        $totals = $this->calculateTotals($request);

        // Calcular totales por método de pago
        $totalsByPaymentMethod = $this->calculateTotalsByPaymentMethod($request);

        return response()->json([
            'success' => true,
            'data' => BookingListResource::collection($bookings),
            'pagination' => [
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'from' => $bookings->firstItem(),
                'to' => $bookings->lastItem(),
            ],
            'totals' => $totals,
            'totals_by_payment_method' => $totalsByPaymentMethod,
        ]);
    }

    private function calculateTotals(Request $request)
    {
        $query = Booking::query();

        // Aplicar los mismos filtros de búsqueda
        $this->applyFilters($query, $request);

        return [
            'total_bookings' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'total_paid' => $query->sum('paid_amount'),
            'total_balance' => $query->sum(DB::raw('total_amount - paid_amount')),
            'total_room_subtotal' => $query->sum('room_subtotal'),
            'total_products_subtotal' => $query->sum('products_subtotal'),
            'total_tax' => $query->sum('tax_amount'),
            'total_discount' => $query->sum('discount_amount'),
        ];
    }

    private function calculateTotalsByPaymentMethod(Request $request)
    {
        $query = Booking::query();
        
        // Aplicar filtros excepto el de payment_method_id
        $filters = $request->except('payment_method_id');
        $this->applyFilters($query, new Request($filters));

        $totals = $query->join('payments', 'bookings.id', '=', 'payments.booking_id')
            ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->select(
                'payment_methods.id',
                'payment_methods.name',
                DB::raw('COUNT(DISTINCT bookings.id) as total_bookings'),
                DB::raw('SUM(payments.amount) as total_paid'),
                DB::raw('SUM(bookings.total_amount) as total_amount')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

        return $totals->map(function($item) {
            return [
                'payment_method_id' => $item->id,
                'payment_method_name' => $item->name,
                'total_bookings' => (int) $item->total_bookings,
                'total_paid' => (float) $item->total_paid,
                'total_amount' => (float) $item->total_amount,
            ];
        });
    }

    private function applyFilters($query, Request $request)
    {
        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('room', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('room_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por tipo de pago
        if ($request->filled('payment_method_id')) {
            $query->whereHas('payments', function($q) use ($request) {
                $q->where('payment_method_id', $request->payment_method_id);
            });
        }

        // Filtro por fechas
        if ($request->filled('date_from')) {
            $query->whereDate('check_in', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('check_in', '<=', $request->date_to);
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por sucursal
        if ($request->filled('sub_branch_id')) {
            $query->where('sub_branch_id', $request->sub_branch_id);
        }

        return $query;
    }
}