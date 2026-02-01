<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RateType;
use App\Models\Product;
use App\Models\Payment;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\FinishBookingRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Models\BookingConsumption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller{
    public function store(StoreBookingRequest $request){
        try {
            DB::beginTransaction();
            $validated = $request->validated();
            
            // ============================================================
            // OBTENER CAJA ACTIVA DEL USUARIO AUTENTICADO
            // ============================================================
            $userCashRegister = Auth::user()->getActiveCashRegister();
            
            if (!$userCashRegister) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes una caja registradora abierta. Por favor, abre una caja primero.'
                ], 422);
            }
            
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
            
            if (isset($validated['quantity'])) {
                $quantity = $validated['quantity'];
                $totalHours = $quantity * $rateType->duration_hours;
            } else {
                $totalHours = $validated['total_hours'];
                $quantity = ceil($totalHours / $rateType->duration_hours);
            }
            
            $checkOut = $checkIn->copy()->addHours($totalHours);
            $roomSubtotal = $validated['rate_per_hour'] * $quantity;
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
                'quantity' => $quantity,
                'total_hours' => $totalHours,
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
            
            // ============================================================
            // REGISTRAR PAGOS CON LA CAJA DEL USUARIO
            // ============================================================
            $totalPaid = 0;
            foreach ($validated['payments'] as $paymentData) {
                // Usar la caja activa del usuario
                $cashRegisterId = $paymentData['cash_register_id'] ?? $userCashRegister->id;
                
                // Verificar que la caja esté realmente abierta
                $cashRegister = CashRegister::with('currentSession')->find($cashRegisterId);
                
                if (!$cashRegister || !$cashRegister->isOpen()) {
                    throw new \Exception('La caja especificada no está abierta');
                }
                
                // Verificar que la sesión sea del usuario actual
                if ($cashRegister->currentSession->opened_by !== Auth::id()) {
                    throw new \Exception('Solo puedes registrar pagos en tu propia sesión de caja');
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
                    'cash_register_id' => $cashRegisterId,
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
                    'cash_register_used' => [
                        'id' => $userCashRegister->id,
                        'name' => $userCashRegister->name,
                        'session_id' => $userCashRegister->current_session_id
                    ],
                    'breakdown' => [
                        'rate_per_hour' => $booking->rate_per_hour,
                        'quantity' => $quantity,
                        'total_hours' => $totalHours,
                        'room_subtotal' => $booking->room_subtotal,
                        'products_subtotal' => $booking->products_subtotal,
                        'subtotal' => $booking->subtotal,
                        'tax_amount' => $booking->tax_amount,
                        'discount_amount' => $booking->discount_amount,
                        'total_amount' => $booking->total_amount,
                        'paid_amount' => $booking->paid_amount,
                        'balance' => $booking->balance,
                    ],
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear booking:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'sub_branch_id' => Auth::user()->sub_branch_id ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function finishService(FinishBookingRequest $request, Booking $booking){
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
            
            // Calcular tiempo real usado EN MINUTOS
            $minutosUsados = $checkInTime->diffInMinutes($checkOutReal);
            $hoursUsedReal = $minutosUsados / 60;
            
            // Comparar con tiempo contratado
            $hoursContracted = $booking->total_hours;
            $extraHours = max(0, $hoursUsedReal - $hoursContracted);
            
            $extraAmount = 0;
            $extraHoursCeil = 0;
            $tieneRecargo = false;

            // Si hay tiempo extra, cobrarlo
            if ($extraHours > 0) {
                $extraHoursCeil = ceil($extraHours);
                $extraAmount = $extraHoursCeil * $booking->rate_per_hour;
                $tieneRecargo = true;
                
                // Actualizar reserva
                $booking->total_hours += $extraHoursCeil;
                $booking->room_subtotal += $extraAmount;
                $booking->subtotal = $booking->room_subtotal + $booking->products_subtotal;
                $booking->total_amount = $booking->subtotal + $booking->tax_amount - $booking->discount_amount;
                
                $booking->notes = ($booking->notes ?? '') . 
                    "\n[" . $checkOutReal->format('Y-m-d H:i') . "] ⏰ Tiempo extra al checkout: {$extraHoursCeil}h (real: " . 
                    round($extraHours, 2) . "h) = S/ {$extraAmount}";
            }

            // Procesar pagos si vienen
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
                        'payment_method_id' => $paymentData['payment_method_id'] ?? null,
                        'operation_number' => $paymentData['operation_number'] ?? null,
                        'payment_date' => now(),
                        'status' => 'completed',
                        'notes' => $tieneRecargo 
                            ? "Pago al checkout (incluye {$extraHoursCeil}h extras por S/ {$extraAmount})" 
                            : 'Pago al checkout',
                        'created_by' => Auth::id(),
                    ]);

                    $booking->paid_amount += $paymentData['amount'];
                }
            }

            $booking->save();

            // Verificar saldo pendiente
            $balance = $booking->total_amount - $booking->paid_amount;
            $forceCheckout = $request->force_checkout ?? false;
            
            if ($balance > 0 && !$forceCheckout) {
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'requires_payment' => true,
                    'message' => $tieneRecargo 
                        ? "⚠️ Cliente se excedió {$extraHoursCeil}h. Hay saldo pendiente." 
                        : "⚠️ Hay un saldo pendiente de pago",
                    'data' => [
                        'balance' => round($balance, 2),
                        'total_amount' => round($booking->total_amount, 2),
                        'paid_amount' => round($booking->paid_amount, 2),
                        'room_subtotal' => round($booking->room_subtotal, 2),
                        'products_subtotal' => round($booking->products_subtotal, 2),
                        'tax_amount' => round($booking->tax_amount, 2),
                        'discount_amount' => round($booking->discount_amount, 2),
                        'tiempo_extra' => [
                            'se_paso' => $tieneRecargo,
                            'horas_contratadas' => $hoursContracted,
                            'horas_usadas_real' => round($hoursUsedReal, 2),
                            'minutos_usados' => $minutosUsados,
                            'horas_extra' => $extraHoursCeil,
                            'monto_extra' => round($extraAmount, 2),
                        ]
                    ]
                ], 422);
            }

            // Finalizar servicio
            $booking->actual_check_out = $checkOutReal;
            $booking->actual_hours = ceil($hoursUsedReal);
            $booking->finish_type = 'manual';
            $booking->finished_by = Auth::id();
            
            if ($request->notes) {
                $booking->notes = ($booking->notes ?? '') . 
                    "\n[" . $checkOutReal->format('Y-m-d H:i') . "] " . $request->notes;
            }
            
            $booking->save();
            
            // Ejecutar checkout (cambia estado de habitación)
            $booking->checkOut(Auth::id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $tieneRecargo 
                    ? "✅ Servicio finalizado con {$extraHoursCeil}h extras. Habitación en limpieza." 
                    : '✅ Servicio finalizado. Habitación en limpieza.',
                'data' => [
                    'booking' => $booking->fresh(['room', 'customer', 'consumptions', 'payments']),
                    'check_out_time' => $checkOutReal->format('d-m-Y H:i A'),
                    'final_balance' => round($balance, 2),
                    'resumen_tiempo' => [
                        'horas_contratadas' => $hoursContracted,
                        'horas_usadas' => round($hoursUsedReal, 2),
                        'minutos_usados' => $minutosUsados,
                        'se_paso_del_tiempo' => $tieneRecargo,
                        'horas_extra' => $extraHoursCeil,
                        'monto_extra' => round($extraAmount, 2),
                    ],
                    'resumen_financiero' => [
                        'room_subtotal' => round($booking->room_subtotal, 2),
                        'products_subtotal' => round($booking->products_subtotal, 2),
                        'tax_amount' => round($booking->tax_amount, 2),
                        'discount_amount' => round($booking->discount_amount, 2),
                        'total_amount' => round($booking->total_amount, 2),
                        'paid_amount' => round($booking->paid_amount, 2),
                        'balance' => round($balance, 2),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al finalizar booking:', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
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
    public function addConsumption(Request $request, Booking $booking){
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
            $consumptionsAdded = [];

            foreach ($request->consumptions as $consumptionData) {
                $product = Product::findOrFail($consumptionData['product_id']);

                $consumption = $booking->consumptions()->create([
                    'id' => Str::uuid(),
                    'product_id' => $product->id,
                    'quantity' => $consumptionData['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $consumptionData['quantity'] * $product->price,
                    'consumed_at' => now(),
                    'status' => 'pending', // Pendiente hasta que se pague
                    'created_by' => Auth::id(),
                ]);

                $totalAdded += $consumption->total_price;
                $consumptionsAdded[] = [
                    'product' => $product->name,
                    'quantity' => $consumptionData['quantity'],
                    'unit_price' => $product->price,
                    'total' => $consumption->total_price
                ];
            }

            // Actualizar totales del booking
            $booking->products_subtotal += $totalAdded;
            $booking->subtotal += $totalAdded;
            $booking->total_amount += $totalAdded;
            $booking->updated_by = Auth::id();
            
            // Agregar nota del consumo
            $consumptionsList = collect($consumptionsAdded)
                ->map(fn($c) => "{$c['product']} x{$c['quantity']} = {$c['total']}")
                ->join(', ');
            
            $booking->notes = ($booking->notes ?? '') . "\n[" . now() . "] Consumos agregados: {$consumptionsList} | Total: +{$totalAdded}";
            
            $booking->save();

            // Refrescar el balance (automáticamente se recalcula con el accessor)
            $newBalance = $booking->balance; // total_amount - paid_amount

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Consumos agregados correctamente',
                'data' => [
                    'booking' => $booking->fresh(['consumptions.product', 'payments']),
                    'consumptions_added' => $consumptionsAdded,
                    'amount_added' => $totalAdded,
                    'totals' => [
                        'products_subtotal' => $booking->products_subtotal,
                        'room_subtotal' => $booking->room_subtotal,
                        'subtotal' => $booking->subtotal,
                        'total_amount' => $booking->total_amount,
                        'paid_amount' => $booking->paid_amount,
                        'balance' => $newBalance
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al agregar consumos:', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar consumos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function generateBookingCode(): string
    {
        return 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function generatePaymentCode(): string
    {
        return 'PAY-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
    }

    public function getCheckoutDetails($roomId){
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
            $minutesUsed = $checkInTime->diffInMinutes($now);
            $hoursUsed = $minutesUsed / 60;
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
    /**
     * COBRAR TIEMPO EXTRA
     * Cobra el tiempo extra y extiende el checkout
     */
    public function chargeExtraTime(Request $request, $roomId){
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
            
            if ($now <= $checkOutScheduled) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tiempo aún no ha vencido'
                ], 422);
            }

            $extraMinutes = $checkOutScheduled->diffInMinutes($now);
            $extraHours = $extraMinutes / 60;
            $extraHoursCeil = ceil($extraHours);
            $extraAmount = $extraHoursCeil * $booking->rate_per_hour;

            $booking->total_hours += $extraHoursCeil;
            $booking->check_out = $now->copy()->addHours($extraHoursCeil);
            $booking->room_subtotal += $extraAmount;
            $booking->subtotal += $extraAmount;
            $booking->total_amount += $extraAmount;
            $booking->notes = ($booking->notes ?? '') . "\n[" . $now . "] Cobro tiempo extra: {$extraHoursCeil}h = S/ {$extraAmount}";
            $booking->updated_by = Auth::id();
            $booking->save();
            $booking->room->statusLogs()->create([
                'id' => Str::uuid(),
                'room_id' => $booking->room_id,
                'booking_id' => $booking->id,
                'previous_status' => Room::STATUS_OCCUPIED,
                'new_status' => Room::STATUS_OCCUPIED,
                'reason' => "Cobro tiempo extra: +{$extraHoursCeil}h = S/ {$extraAmount}",
                'changed_at' => $now,
                'changed_by' => Auth::id(),
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
    public function checkout($roomId){
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
            $branch = $booking->subBranch->branch ?? null;
            $empresa = [
                'nombre' => $branch->name ?? 'HOTEL',
                'ruc' => $branch->ruc ?? '20000000000',
                'direccion' => $branch->address ?? 'Sin dirección',
                'telefono' => $branch->phone ?? 'Sin teléfono'
            ];
            $comprobante = [
                'tipo' => strtoupper($booking->voucher_type),
                'numero' => $booking->booking_code,
                'fecha' => $booking->check_in->format('d/m/Y'),
                'hora' => $booking->check_in->format('H:i:s')
            ];
            $cliente = [
                'nombre' => $booking->customer->full_name ?? 'Cliente General',
                'documento' => ($booking->customer->document_type ?? 'DNI') . ': ' . ($booking->customer->document_number ?? 'Sin documento'),
                'direccion' => $booking->customer->address ?? ''
            ];
            $habitacion = [
                'numero' => $booking->room->room_number,
                'tipo' => $booking->room->roomType->name ?? 'Habitación',
                'tarifa' => $booking->rateType->name ?? 'Por Hora',
                'cantidad' => $booking->total_hours,
                'precioUnitario' => (float) $booking->rate_per_hour,
                'total' => (float) $booking->room_subtotal
            ];
            $productos = [];
            foreach ($booking->consumptions as $consumption) {
                $productos[] = [
                    'nombre' => $consumption->product->name ?? 'Producto',
                    'cantidad' => (float) $consumption->quantity,
                    'precio' => (float) $consumption->unit_price,
                    'total' => (float) $consumption->total_price
                ];
            }
            $totales = [
                'subtotal' => (float) $booking->subtotal,
                'descuento' => (float) $booking->discount_amount,
                'igv' => (float) $booking->tax_amount,
                'total' => (float) $booking->total_amount
            ];
            $primerPago = $booking->payments->first();
            $pago = [
                'metodo' => $primerPago ? $primerPago->paymentMethod->name ?? 'Efectivo' : 'Efectivo',
                'operacion' => $primerPago ? $primerPago->operation_number : null
            ];
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
            'room',
            'customer',
            'rateType',
            'payments.paymentMethod'
        ]);

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('room', function($q) use ($search) {
                        $q->where('room_number', 'like', "%{$search}%");
                    });
            });
        }

        // Filtro por método de pago
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

        // Mapear los datos simplificados
        $data = $bookings->map(function($booking) {
            $payment = $booking->payments->first();
            $quantityLabel = $this->getQuantityLabel($booking);

            return [
                'id' => $booking->id,
                'payment_code' => $payment ? $payment->payment_code : 'Sin pago',
                'booking_code' => $booking->booking_code,
                'habitacion' => $booking->room->room_number ?? 'N/A',
                'cliente' => $booking->customer->name ?? 'Sin cliente',
                'fecha' => $booking->check_in ? $booking->check_in->format('d/m/Y H:i') : null,
                
                // ✅ PRECIO, CANTIDAD Y TOTAL
                'precio_unitario' => (float) $booking->rate_per_hour,  // Precio por hora/día/noche
                'quantity' => $booking->quantity,  // Cantidad (5, 7, 3)
                'quantity_label' => $quantityLabel,  // "5 hora(s)", "7 día(s)"
                'total_hours' => $booking->total_hours,  // Horas totales
                'monto_total' => (float) $booking->room_subtotal,  // Total = precio × quantity
                
                'rate_type' => $booking->rateType ? [
                    'name' => $booking->rateType->name,
                    'code' => $booking->rateType->code,
                    'duration_hours' => $booking->rateType->duration_hours,
                ] : null,
                
                'metodo_pago' => $payment && $payment->paymentMethod ? $payment->paymentMethod->name : 'N/A',
                'estado' => $booking->status,
                'estado_label' => $this->getStatusLabel($booking->status),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'from' => $bookings->firstItem(),
                'to' => $bookings->lastItem(),
            ],
        ]);
    }

    /**
     * ✅ Obtener etiqueta formateada de la cantidad
     */
    private function getQuantityLabel($booking)
    {
        if (!$booking->rateType || !$booking->quantity) {
            return $booking->quantity . ' unidad(es)';
        }

        $unit = strtoupper($booking->rateType->code);
        
        switch($unit) {
            case 'HOUR':
            case 'HOURLY':
                return $booking->quantity . ' hora(s)';
            case 'DAY':
            case 'DAILY':
                return $booking->quantity . ' día(s)';
            case 'NIGHT':
                return $booking->quantity . ' noche(s)';
            case '12HOURS':
            case 'HALF_DAY':
                return $booking->quantity . ' bloque(s) de 12h';
            case '8HOURS':
                return $booking->quantity . ' bloque(s) de 8h';
            default:
                return $booking->quantity . ' unidad(es)';
        }
    }
    /**
     * Extender tiempo de la reserva
     * Maneja extensión anticipada o regularización de tiempo ya pasado
     */
    public function extenderTiempo(Request $request, Booking $booking)
{
    $request->validate([
        'horas_adicionales' => 'required|numeric|min:0.5',
    ]);

    if ($booking->status !== Booking::STATUS_CHECKED_IN) {
        return response()->json([
            'success' => false,
            'message' => 'Solo se puede extender tiempo en reservas activas'
        ], 422);
    }

    try {
        DB::beginTransaction();

        $ahora = now();
        $checkOutProgramado = $booking->check_out;
        $horasAdicionales = $request->horas_adicionales;
        
        // ============================================
        // 1. CALCULAR HORAS REALES ACTUALES (positivas)
        // ============================================
        $horasUsadasHastaAhora = $booking->check_in->diffInHours($ahora);
        $horasContratadasOriginales = $booking->check_in->diffInHours($checkOutProgramado);
        
        // ============================================
        // 2. VERIFICAR SI SE PASÓ DEL TIEMPO
        // ============================================
        $yaSePaso = $ahora->greaterThan($checkOutProgramado);
        $horasExcedidas = 0;
        $costoExcedido = 0;
        
        if ($yaSePaso) {
            $horasExcedidas = ceil($ahora->diffInHours($checkOutProgramado));
            $costoExcedido = $booking->rate_per_hour * $horasExcedidas;
            
            // Actualizar con el tiempo excedido
            $booking->total_hours = $horasContratadasOriginales + $horasExcedidas;
            $booking->room_subtotal = $booking->total_hours * $booking->rate_per_hour;
            
            $booking->notes = ($booking->notes ?? '') . 
                "\n[" . $ahora->format('Y-m-d H:i') . "] ⚠️ Regularización: {$horasExcedidas}h ya usadas = S/ {$costoExcedido}";
        }
        
        // ============================================
        // 3. EXTENDER EL TIEMPO
        // ============================================
        // Extender desde el checkout programado (no desde ahora)
        $nuevoCheckOut = $checkOutProgramado->copy()->addHours($horasAdicionales);
        $costoExtension = $booking->rate_per_hour * $horasAdicionales;
        
        // Guardar checkout anterior
        $checkOutAnterior = $booking->check_out;
        
        // ============================================
        // 4. ACTUALIZAR BOOKING CON VALORES CORRECTOS
        // ============================================
        $booking->check_out = $nuevoCheckOut;
        
        // Calcular TOTAL de horas (originales + excedidas + extendidas)
        $horasTotalesNuevas = $horasContratadasOriginales + $horasExcedidas + $horasAdicionales;
        
        // Asegurar que sean positivos
        $booking->total_hours = max(0, $horasTotalesNuevas);
        $booking->room_subtotal = $booking->total_hours * $booking->rate_per_hour;
        
        // Recalcular todos los totales
        $booking->subtotal = $booking->room_subtotal + $booking->products_subtotal;
        $booking->total_amount = $booking->subtotal + $booking->tax_amount - $booking->discount_amount;
        
        $booking->updated_by = auth()->id();
        $booking->notes = ($booking->notes ?? '') . 
            "\n[" . $ahora->format('Y-m-d H:i') . "] ✅ Extensión: +{$horasAdicionales}h. " .
            "Nuevo checkout: " . $nuevoCheckOut->format('d-m-Y H:i A') .
            "\nHoras totales: {$booking->total_hours}h, Subtotal: S/ {$booking->room_subtotal}";
        
        $booking->save();

        DB::commit();

        // ============================================
        // 5. RESPUESTA
        // ============================================
        $respuesta = [
            'success' => true,
            'message' => $yaSePaso 
                ? "Se regularizó {$horasExcedidas}h excedidas y se extendió {$horasAdicionales}h"
                : "Tiempo extendido por {$horasAdicionales}h",
            'data' => [
                'booking_code' => $booking->booking_code,
                'check_in' => $booking->check_in->format('d-m-Y H:i A'),
                'checkout_anterior' => $checkOutAnterior->format('d-m-Y H:i A'),
                'checkout_nuevo' => $nuevoCheckOut->format('d-m-Y H:i A'),
                'horas_adicionales' => $horasAdicionales,
                'costo_extension' => round($costoExtension, 2),
                'resumen_financiero' => [
                    'horas_totales' => $booking->total_hours,
                    'dias_totales' => round($booking->total_hours / 24, 1),
                    'room_subtotal' => round($booking->room_subtotal, 2),
                    'total_amount' => round($booking->total_amount, 2),
                    'paid_amount' => round($booking->paid_amount, 2),
                    'saldo_pendiente' => round($booking->total_amount - $booking->paid_amount, 2),
                ]
            ]
        ];
        
        if ($yaSePaso) {
            $respuesta['data']['regularizacion'] = [
                'horas_excedidas' => $horasExcedidas,
                'costo_excedido' => round($costoExcedido, 2),
                'total_adicional' => round($costoExcedido + $costoExtension, 2),
            ];
        }
        
        return response()->json($respuesta);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error al extender tiempo:', [
            'booking_id' => $booking->id,
            'error' => $e->getMessage(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error al extender tiempo',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function show(Booking $booking)
{
    try {
        // Cargar relaciones
        $booking->load([
            'room.floor.subBranch.branch',
            'customer',
            'rateType',
            'currency',
            'consumptions.product',
            'payments.paymentMethod',
            'payments.currency',
            'subBranch',
        ]);

        $ahora = now();
        $checkInTime = $booking->check_in;
        $checkOutProgramado = $booking->check_out;
        
        // ============================================
        // CALCULAR TIEMPO TRANSCURRIDO
        // ============================================
        $minutosTranscurridos = $checkInTime->diffInMinutes($ahora);
        $horasTranscurridas = $minutosTranscurridos / 60;
        
        // ============================================
        // VERIFICAR SI YA SE PASÓ DEL TIEMPO
        // ============================================
        $yaSePaso = $ahora->greaterThan($checkOutProgramado);
        
        // ✅ CORRECCIÓN: Calcular minutos extra correctamente
        if ($yaSePaso) {
            // Si ya se pasó, calcular cuántos minutos DESPUÉS del checkout programado
            $minutosExtra = $checkOutProgramado->diffInMinutes($ahora); // ← Invertido
            $horasExtra = ceil($minutosExtra / 60);
            $costoTiempoExtra = $horasExtra * $booking->rate_per_hour;
            $minutosRestantes = 0;
            $horasRestantes = 0;
        } else {
            // Si NO se pasó, calcular tiempo restante
            $minutosExtra = 0;
            $horasExtra = 0;
            $costoTiempoExtra = 0;
            $minutosRestantes = $ahora->diffInMinutes($checkOutProgramado);
            $horasRestantes = $minutosRestantes / 60;
        }
        
        // ============================================
        // CALCULAR TOTALES FINANCIEROS
        // ============================================
        $totalPagado = $booking->payments()
            ->where('status', 'completed')
            ->sum('amount');
        
        $saldoPendiente = $booking->total_amount - $totalPagado;
        
        // Si hay tiempo extra, calcular el nuevo total
        $totalConTiempoExtra = $booking->total_amount + $costoTiempoExtra;
        $saldoConExtra = $totalConTiempoExtra - $totalPagado;

        // ============================================
        // DETERMINAR ACCIONES DISPONIBLES
        // ============================================
        $accionesDisponibles = [
            'puede_extender' => $booking->status === Booking::STATUS_CHECKED_IN,
            'puede_finalizar' => $booking->status === Booking::STATUS_CHECKED_IN,
            'puede_agregar_consumo' => $booking->status === Booking::STATUS_CHECKED_IN,
            'puede_cancelar' => in_array($booking->status, [
                Booking::STATUS_PENDING, 
                Booking::STATUS_CONFIRMED, 
                Booking::STATUS_CHECKED_IN
            ]),
            'requiere_pago' => $saldoConExtra > 0, // ← Importante: usar saldo CON extra
        ];

        // ============================================
        // PREPARAR RESPUESTA
        // ============================================
        return response()->json([
            'success' => true,
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'status' => $booking->status,
                    'status_label' => $this->getStatusLabel($booking->status),
                    'check_in' => $booking->check_in->format('Y-m-d H:i:s'),
                    'check_out' => $booking->check_out->format('Y-m-d H:i:s'),
                    'actual_check_out' => $booking->actual_check_out?->format('Y-m-d H:i:s'),
                    'total_hours' => $booking->total_hours,
                    'actual_hours' => $booking->actual_hours,
                    'rate_per_hour' => (float) $booking->rate_per_hour,
                    'notes' => $booking->notes,
                ],
                'room' => [
                    'id' => $booking->room->id,
                    'room_number' => $booking->room->room_number,
                    'name' => $booking->room->name,
                    'status' => $booking->room->status,
                    'floor' => $booking->room->floor->name ?? null,
                    'sub_branch' => $booking->room->floor->subBranch->name ?? null,
                    'branch' => $booking->room->floor->subBranch->branch->name ?? null,
                ],
                'customer' => [
                    'id' => $booking->customer->id,
                    'name' => $booking->customer->name,
                    'document_type' => $booking->customer->document_type ?? null,
                    'document_number' => $booking->customer->document_number ?? null,
                    'phone' => $booking->customer->phone ?? null,
                    'email' => $booking->customer->email ?? null,
                ],
                'rate_type' => [
                    'id' => $booking->rateType->id,
                    'name' => $booking->rateType->name,
                    'code' => $booking->rateType->code,
                    'duration_hours' => $booking->rateType->duration_hours,
                ],
                'currency' => [
                    'id' => $booking->currency->id,
                    'code' => $booking->currency->code,
                    'symbol' => $booking->currency->symbol,
                ],
                'consumptions' => $booking->consumptions->map(function($consumption) {
                    return [
                        'id' => $consumption->id,
                        'product_id' => $consumption->product_id,
                        'product_name' => $consumption->product->name,
                        'quantity' => (float) $consumption->quantity,
                        'unit_price' => (float) $consumption->unit_price,
                        'total_price' => (float) $consumption->total_price,
                        'consumed_at' => $consumption->consumed_at->format('Y-m-d H:i:s'),
                        'status' => $consumption->status,
                    ];
                }),
                'payments' => $booking->payments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_code' => $payment->payment_code,
                        'amount' => (float) $payment->amount,
                        'payment_method' => $payment->paymentMethod->name ?? $payment->payment_method,
                        'payment_date' => $payment->payment_date->format('Y-m-d H:i:s'),
                        'status' => $payment->status,
                        'operation_number' => $payment->operation_number,
                        'currency' => $payment->currency->code ?? 'PEN',
                    ];
                }),
                'financial_summary' => [
                    'room_subtotal' => (float) $booking->room_subtotal,
                    'products_subtotal' => (float) $booking->products_subtotal,
                    'tax_amount' => (float) $booking->tax_amount,
                    'discount_amount' => (float) $booking->discount_amount,
                    'subtotal' => (float) $booking->subtotal,
                    'total_amount' => (float) $booking->total_amount,
                    'paid_amount' => (float) $totalPagado,
                    'balance' => (float) $saldoPendiente,
                    
                    // Tiempo extra
                    'tiene_tiempo_extra' => $horasExtra > 0,
                    'costo_tiempo_extra' => (float) $costoTiempoExtra,
                    'total_con_extra' => (float) $totalConTiempoExtra,
                    'saldo_con_extra' => (float) $saldoConExtra,
                ],
                'time_info' => [
                    'check_in_formatted' => $checkInTime->format('d-m-Y H:i A'),
                    'check_out_programado_formatted' => $checkOutProgramado->format('d-m-Y H:i A'),
                    'hora_actual' => $ahora->format('d-m-Y H:i A'),
                    
                    // Tiempo transcurrido desde check-in
                    'minutos_transcurridos' => $minutosTranscurridos,
                    'horas_transcurridas' => round($horasTranscurridas, 2),
                    
                    // ¿Ya se pasó?
                    'ya_se_paso_del_tiempo' => $yaSePaso,
                    'minutos_extra' => $minutosExtra,
                    'horas_extra' => $horasExtra,
                    'costo_horas_extra' => (float) $costoTiempoExtra,
                    
                    // Tiempo restante (si no se pasó)
                    'minutos_restantes' => $minutosRestantes,
                    'horas_restantes' => round($horasRestantes, 2),
                    
                    // Comparación
                    'horas_contratadas' => $booking->total_hours,
                    'horas_usadas' => round($horasTranscurridas, 2),
                    'porcentaje_usado' => round(($horasTranscurridas / $booking->total_hours) * 100, 2),
                ],
                'actions' => $accionesDisponibles,
                'alerts' => $this->generateAlerts(
                    $booking, 
                    $yaSePaso, 
                    $horasExtra, 
                    $saldoConExtra, // ← Usar saldo CON extra
                    $minutosRestantes
                ),
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error al mostrar booking:', [
            'booking_id' => $booking->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar la reserva',
            'error' => $e->getMessage()
        ], 500);
    }
}

// ============================================
// MÉTODOS AUXILIARES
// ============================================
private function getStatusLabel($status)
{
    return match($status) {
        Booking::STATUS_PENDING => 'Pendiente',
        Booking::STATUS_CONFIRMED => 'Confirmada',
        Booking::STATUS_CHECKED_IN => 'Activa',
        Booking::STATUS_CHECKED_OUT => 'Finalizada',
        Booking::STATUS_CANCELLED => 'Cancelada',
        default => 'Desconocido',
    };
}

private function generateAlerts($booking, $yaSePaso, $horasExtra, $saldoPendiente, $minutosRestantes)
{
    $alerts = [];
    
    // ✅ ALERTA CRÍTICA: Tiempo excedido
    if ($yaSePaso && $horasExtra > 0) {
        $dias = intdiv($horasExtra, 24);
        $horasRestantes = $horasExtra % 24;
        
        $tiempoExcedidoTexto = $dias > 0 
            ? "{$dias} día(s) y {$horasRestantes} hora(s)" 
            : "{$horasExtra} hora(s)";
        
        $costoExtra = $horasExtra * $booking->rate_per_hour;
        
        $alerts[] = [
            'type' => 'danger',
            'icon' => '⚠️',
            'title' => '¡TIEMPO EXCEDIDO!',
            'message' => "El cliente se ha excedido {$tiempoExcedidoTexto} del tiempo contratado",
            'detail' => "Costo adicional: S/ " . number_format($costoExtra, 2),
            'action' => 'extend_or_finish',
            'action_text' => 'Extender tiempo o Finalizar servicio',
        ];
    }
    
    // ⚠️ ALERTA: Tiempo por vencer (menos de 30 minutos)
    if (!$yaSePaso && $minutosRestantes <= 30 && $minutosRestantes > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => '⏰',
            'title' => 'Tiempo por vencer',
            'message' => "Quedan solo " . round($minutosRestantes) . " minutos para el checkout",
            'action' => 'extend_time',
            'action_text' => 'Extender tiempo',
        ];
    }
    
    // 💰 ALERTA: Saldo pendiente
    if ($saldoPendiente > 0) {
        $alerts[] = [
            'type' => 'info',
            'icon' => '💰',
            'title' => 'Saldo pendiente',
            'message' => "Hay un saldo pendiente de S/ " . number_format($saldoPendiente, 2),
            'action' => 'add_payment',
            'action_text' => 'Registrar pago',
        ];
    }
    
    // 🛒 ALERTA: Consumos pendientes
    $consumosPendientes = $booking->consumptions->where('status', 'pending')->count();
    if ($consumosPendientes > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => '🛒',
            'title' => 'Consumos pendientes',
            'message' => "Hay {$consumosPendientes} consumo(s) pendiente(s) de pago",
            'action' => 'review_consumptions',
            'action_text' => 'Revisar consumos',
        ];
    }
    
    return $alerts;
}
}