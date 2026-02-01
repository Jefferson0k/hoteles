<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Payment\PaymentShowResource;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller{
    public function getUserCashRegister(){
        $user = Auth::user();
        $session = CashRegisterSession::with([
                'cashRegister.subBranch'
            ])
            ->where('opened_by', $user->id)
            ->where('status', 'abierta')
            ->whereNull('closed_at')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una caja abierta'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'opened_at'  => $session->opened_at->format('d/m/Y h:i:s A'),
                'opening_amount' => $session->opening_amount,
                'cash_register' => [
                    'id'        => $session->cashRegister->id,
                    'name'      => $session->cashRegister->name,
                    'is_active' => $session->cashRegister->is_active,
                    'sub_branch'=> [
                        'id'   => $session->cashRegister->subBranch->id,
                        'name' => $session->cashRegister->subBranch->name,
                    ]
                ]
            ]
        ]);
    }
    public function getPaymentMethods(){
        $paymentMethods = PaymentMethod::active()->ordered()->get();
        return response()->json([
            'success' => true,
            'data' => $paymentMethods
        ]);
    }
    public function reportePagos(Request $request){
        $fechaInicio = $request->fecha_inicio ?? Carbon::today()->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?? Carbon::today()->format('Y-m-d');
        
        $request->merge([
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);
        
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'sucursal_id' => 'nullable|exists:sub_branches,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'codigo_pago' => 'nullable|string',
            'habitacion' => 'nullable|string',
            'cliente' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);
        
        try {
            $reporte = Payment::getReportePagos(
                $request->fecha_inicio,
                $request->fecha_fin,
                $request->sucursal_id,
                $request->payment_method_id,
                $request->codigo_pago,
                $request->habitacion,
                $request->cliente,
                $request->page ?? 1,
                $request->per_page ?? 10
            );
            
            return response()->json([
                'success' => true,
                'data' => $reporte,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte de pagos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($id){
        $payment = Payment::with(['booking.customer', 'currency', 'paymentMethod', 'cashRegister'])->findOrFail($id);
        return new PaymentShowResource($payment);
    }
    public function extendertiempo(){}
    public function cobrartiempoextra(){}
}