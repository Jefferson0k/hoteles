<?php

namespace App\Http\Controllers\Panel;

use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\Booking;
use App\Models\BookingConsumption;
use App\Models\Customer;
use App\Models\PagoPersonal;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller{
    public function ingresosHabitaciones(Request $request){
        $filtros = $this->getFiltros($request);
        $data = Booking::whereHas('room.floor.subBranch', function($query) use ($filtros) {
                $query->where('id', $filtros['subBranchId']);
            })
            ->whereIn('status', [
                Booking::STATUS_CHECKED_OUT,
                Booking::STATUS_CHECKED_IN
            ])
            ->whereBetween('check_in', [$filtros['startDate'], $filtros['endDate']])
            ->selectRaw('
                SUM(room_subtotal) as total,
                COUNT(*) as total_reservas,
                AVG(room_subtotal) as promedio_reserva,
                SUM(total_hours) as total_horas,
                SUM(quantity) as total_quantity
            ')
            ->first();

        return response()->json([
            'total' => (float) ($data->total ?? 0),
            'total_reservas' => (int) ($data->total_reservas ?? 0),
            'promedio_reserva' => (float) ($data->promedio_reserva ?? 0),
            'total_horas' => (int) ($data->total_horas ?? 0),
            'total_quantity' => (int) ($data->total_quantity ?? 0)
        ]);
    }
    public function ingresosHabitacionesGrafica(Request $request)
{
    $filtros = $this->getFiltros($request);
    
    $datos = Booking::whereHas('room.floor.subBranch', function($query) use ($filtros) {
            $query->where('id', $filtros['subBranchId']);
        })
        ->whereIn('status', [
            Booking::STATUS_CHECKED_OUT,
            Booking::STATUS_CHECKED_IN
        ])
        ->whereBetween('check_in', [$filtros['startDate'], $filtros['endDate']])
        ->select(
            DB::raw('DATE(check_in) as dia'),
            DB::raw('SUM(room_subtotal) as ingresos')
        )
        ->groupBy('dia')
        ->orderBy('dia')
        ->get();
    
    return response()->json($datos);
}

    // 🛍️ 2. INGRESO DE PRODUCTOS
    public function ingresoProductos(Request $request)
    {
        $filtros = $this->getFiltros($request);
        
        $data = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                $query->where('sub_branch_id', $filtros['subBranchId']);
            })
            ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
            ->select(
                DB::raw('SUM(total_price) as total'),
                DB::raw('SUM(quantity) as total_unidades'),
                DB::raw('COUNT(DISTINCT booking_id) as reservas_con_consumo')
            )
            ->first();

        return response()->json([
            'total' => $data->total ?? 0,
            'total_unidades' => $data->total_unidades ?? 0,
            'reservas_con_consumo' => $data->reservas_con_consumo ?? 0
        ]);
    }
    public function ingresoBrutoComparativa(Request $request)
{
    try {
        $filtros = $this->getFiltros($request);
        $subBranchId = $filtros['subBranchId'];
        $datos = [];

        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $startDate = $fecha->copy()->startOfMonth();
            $endDate = $fecha->copy()->endOfMonth();

            $ingresosHabitaciones = Booking::whereHas('room', function($query) use ($subBranchId) {
                    $query->where('sub_branch_id', $subBranchId);
                })
                ->whereIn('status', [
                    Booking::STATUS_CHECKED_OUT,
                    Booking::STATUS_CHECKED_IN
                ])
                ->whereBetween('check_out', [$startDate, $endDate])
                ->sum('room_subtotal');

            $ingresosProductos = BookingConsumption::whereHas('booking.room', function($query) use ($subBranchId) {
                    $query->where('sub_branch_id', $subBranchId);
                })
                ->whereBetween('consumed_at', [$startDate, $endDate])
                ->sum('total_price');

            $datos[] = [
                'mes' => $fecha->format('Y-m'),
                'mes_nombre' => ucfirst($fecha->locale('es')->monthName),
                'ingresos_habitaciones' => (float) $ingresosHabitaciones,
                'ingresos_productos' => (float) $ingresosProductos,
                'total' => (float) ($ingresosHabitaciones + $ingresosProductos)
            ];
        }

        return response()->json($datos);

    } catch (\Exception $e) {
        \Log::error('Error en ingresoBrutoComparativa: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error generando comparativa'
        ], 500);
    }
}
    public function ingresoProductosGrafica(Request $request){
        $filtros = $this->getFiltros($request);
        $datos = BookingConsumption::whereHas('booking.room', function ($query) use ($filtros) {
                $query->where('sub_branch_id', $filtros['subBranchId']);
            })
            ->whereBetween('consumed_at', [
                Carbon::parse($filtros['startDate'])->startOfDay(),
                Carbon::parse($filtros['endDate'])->endOfDay()
            ])
            ->selectRaw('
                DATE(consumed_at) as dia,
                SUM(total_price) as ingresos
            ')
            ->groupByRaw('DATE(consumed_at)')
            ->orderBy('dia')
            ->get();
        $resultado = $datos->map(function ($item) {
            return [
                'dia' => Carbon::parse($item->dia)->format('d/m/Y'),
                'ingresos' => (float) $item->ingresos
            ];
        });
        return response()->json($resultado);
    }
    public function ingresoBruto(Request $request){
        $filtros = $this->getFiltros($request);
        $ingresosHabitaciones = Booking::whereHas('room', function($query) use ($filtros) {
                $query->where('sub_branch_id', $filtros['subBranchId']);
            })
            ->whereIn('status', [
                Booking::STATUS_CHECKED_OUT, // En lugar de 'finished'
                Booking::STATUS_CHECKED_IN   // En lugar de 'active'
            ])
            ->whereBetween('check_in', [$filtros['startDate'], $filtros['endDate']])
            ->sum('room_subtotal');
        $ingresosProductos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                $query->where('sub_branch_id', $filtros['subBranchId']);
            })
            ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
            ->sum('total_price');
        $ingresoBruto = $ingresosHabitaciones + $ingresosProductos;
        return response()->json([
            'total' => $ingresoBruto,
            'ingresos_habitaciones' => $ingresosHabitaciones,
            'ingresos_productos' => $ingresosProductos
        ]);
    }
    public function numeroClientes(Request $request)
    {
        $filtros = $this->getFiltros($request);
        
        $data = Booking::whereHas('room', function($query) use ($filtros) {
                $query->where('sub_branch_id', $filtros['subBranchId']);
            })
            ->whereBetween('check_in', [$filtros['startDate'], $filtros['endDate']])
            ->select(
                DB::raw('COUNT(DISTINCT customers_id) as clientes_unicos'),
                DB::raw('COUNT(*) as total_visitas')
            )
            ->first();

        return response()->json([
            'clientes_unicos' => $data->clientes_unicos ?? 0,
            'total_visitas' => $data->total_visitas ?? 0
        ]);
    }

    // 🔧 FUNCIÓN PARA OBTENER FILTROS
    private function getFiltros(Request $request)
{
    // Si vienen date_from y date_to, usarlos directamente
    if ($request->has('date_from') && $request->has('date_to')) {
        return [
            'subBranchId' => Auth::user()->sub_branch_id,
            'startDate' => Carbon::parse($request->input('date_from'))->startOfDay(),
            'endDate' => Carbon::parse($request->input('date_to'))->endOfDay(),
            'month' => Carbon::parse($request->input('date_from'))->month,
            'year' => Carbon::parse($request->input('date_from'))->year,
        ];
    }
    
    // Si no, usar month y year (para mantener compatibilidad con otras funciones)
    $month = $request->input('month', now()->month);
    $year = $request->input('year', now()->year);
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = Carbon::create($year, $month, 1)->endOfMonth();
    
    return [
        'subBranchId' => Auth::user()->sub_branch_id,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'month' => $month,
        'year' => $year
    ];
}

    // 💸 EGRESOS - TOTALES POR MES
    public function egresos(Request $request){
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            $subBranchId = Auth::user()->sub_branch_id;

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            /*
            |----------------------------------------------------
            | EGRESOS POR MOVIMIENTOS (COMPRAS / GASTOS)
            |----------------------------------------------------
            */
            $movimientos = Movement::where('movement_type', 'egreso')
                ->where('sub_branch_id', $subBranchId)
                ->whereBetween('date', [$startDate, $endDate])
                ->join('movement_details', 'movements.id', '=', 'movement_details.movement_id')
                ->groupBy('movements.id')
                ->select(
                    'movements.id',
                    DB::raw('SUM(movement_details.total_price) as subtotal')
                )
                ->get();

            // 👉 aplicar IGV por movimiento
            $egresosMovimientos = $movimientos->sum(function ($mov) {
                return round($mov->subtotal * 1.18, 2);
            });

            $totalMovimientosCompras = $movimientos->count();

            /*
            |----------------------------------------------------
            | PAGOS AL PERSONAL
            |----------------------------------------------------
            */
            $pagosPersonal = PagoPersonal::where('sub_branch_id', $subBranchId)
                ->whereBetween('fecha_pago', [$startDate, $endDate])
                ->where('estado', 'pagado')
                ->select(
                    DB::raw('SUM(monto) as total_personal'),
                    DB::raw('COUNT(*) as total_pagos_personal')
                )
                ->first();

            $egresosPersonal = (float) ($pagosPersonal->total_personal ?? 0);
            $totalPagosPersonal = $pagosPersonal->total_pagos_personal ?? 0;

            /*
            |----------------------------------------------------
            | TOTALES
            |----------------------------------------------------
            */
            $totalEgresos = round($egresosMovimientos + $egresosPersonal, 2);
            $totalRegistros = $totalMovimientosCompras + $totalPagosPersonal;

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalEgresos,
                    'total_movimientos' => $totalRegistros,

                    // 🔥 AHORA SÍ CUADRA
                    'egresos_movimientos' => round($egresosMovimientos, 2),
                    'egresos_personal' => round($egresosPersonal, 2),

                    'total_movimientos_compras' => $totalMovimientosCompras,
                    'total_pagos_personal' => $totalPagosPersonal,

                    'periodo' => [
                        'month' => $month,
                        'year' => $year,
                        'month_name' => Carbon::create($year, $month, 1)->locale('es')->monthName,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cargando egresos: ' . $e->getMessage()
            ], 500);
        }
    }


    public function egresosDetalle(Request $request){
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            $subBranchId = Auth::user()->sub_branch_id;
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $movimientos = Movement::where('movement_type', 'egreso')
                ->where('sub_branch_id', $subBranchId)
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['provider', 'details.product'])
                ->select('id', 'code', 'date', 'provider_id', 'voucher_type', 'payment_type')
                ->get()
                ->map(function ($movement) {
                    $subtotal = $movement->details->sum('total_price');
                    $igv = round($subtotal * 0.18, 2);
                    $total = round($subtotal + $igv, 2);
                    return [
                        'id' => $movement->id,
                        'tipo' => 'compra_gasto',
                        'codigo' => $movement->code,
                        'fecha' => $movement->date,
                        'proveedor' => $movement->provider->razon_social ?? 'N/A',
                        'concepto' => 'Compra/Gasto',
                        'comprobante' => $movement->voucher_type,
                        'tipo_pago' => $movement->payment_type,
                        'monto' => $total,
                        'subtotal' => $subtotal,
                        'igv' => $igv,
                        'detalles' => $movement->details->map(function ($detail) {
                            return [
                                'producto' => $detail->product->name ?? 'N/A',
                                'cantidad' => $detail->boxes * $detail->units_per_box,
                                'precio_unitario' => $detail->unit_price,
                                'total' => $detail->total_price
                            ];
                        })
                    ];
                });
            $pagosPersonal = PagoPersonal::where('sub_branch_id', $subBranchId)
                ->whereBetween('fecha_pago', [$startDate, $endDate])
                ->where('estado', 'pagado')
                ->with(['empleado'])
                ->get()
                ->map(function ($pago) {
                    return [
                        'id' => $pago->id,
                        'tipo' => 'pago_personal',
                        'codigo' => 'PAGO-' . $pago->id,
                        'fecha' => $pago->fecha_pago->toDateString(),
                        'proveedor' => $pago->empleado->name ?? 'N/A',
                        'concepto' => $pago->concepto,
                        'comprobante' => $pago->comprobante ?? 'N/A',
                        'tipo_pago' => $pago->metodo_pago,
                        'monto' => $pago->monto,
                        'detalles' => [
                            [
                                'producto' => 'Pago Personal - ' . $pago->concepto,
                                'cantidad' => 1,
                                'precio_unitario' => $pago->monto,
                                'total' => $pago->monto
                            ]
                        ]
                    ];
                });
            $egresos = $movimientos->merge($pagosPersonal)
                ->sortByDesc('fecha')
                ->values();
            return response()->json([
                'success' => true,
                'data' => [
                    'egresos' => $egresos,
                    'total_registros' => $egresos->count(),
                    'periodo' => [
                        'month' => $month,
                        'year' => $year,
                        'month_name' => Carbon::create($year, $month, 1)->locale('es')->monthName,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cargando detalle de egresos: ' . $e->getMessage()
            ], 500);
        }
    }
    // 📈 EGRESOS - GRÁFICA POR MES
    public function egresosGrafica(Request $request)
{
    try {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $subBranchId = Auth::user()->sub_branch_id;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        /*
        |----------------------------------------------------
        | TRAER SUBTOTALES POR MOVIMIENTO Y POR DÍA
        |----------------------------------------------------
        */
        $movimientos = Movement::where('movement_type', 'egreso')
            ->where('sub_branch_id', $subBranchId)
            ->whereBetween('date', [$startDate, $endDate])
            ->join('movement_details', 'movements.id', '=', 'movement_details.movement_id')
            ->select(
                'movements.id',
                DB::raw('DATE(movements.date) as dia'),
                DB::raw('SUM(movement_details.total_price) as subtotal')
            )
            ->groupBy('movements.id', 'dia')
            ->get();

        /*
        |----------------------------------------------------
        | CALCULAR TOTAL CON IGV POR MOVIMIENTO
        |----------------------------------------------------
        */
        $movimientosPorDia = $movimientos->groupBy('dia')->map(function ($items, $dia) {

            $totalMovimientos = $items->sum(function ($mov) {
                // 👇 si luego quieres condicionar includes_igv, es aquí
                return round($mov->subtotal * 1.18, 2);
            });

            return [
                'dia' => $dia,
                'egresos_movimientos' => round($totalMovimientos, 2),
                'movimientos' => $items->count()
            ];
        });

        /*
        |----------------------------------------------------
        | PAGOS AL PERSONAL
        |----------------------------------------------------
        */
        $egresosPersonal = PagoPersonal::where('sub_branch_id', $subBranchId)
            ->whereBetween('fecha_pago', [$startDate, $endDate])
            ->where('estado', 'pagado')
            ->select(
                DB::raw('DATE(fecha_pago) as dia'),
                DB::raw('SUM(monto) as egresos_personal'),
                DB::raw('COUNT(*) as pagos_personal')
            )
            ->groupBy('dia')
            ->get()
            ->keyBy('dia');

        /*
        |----------------------------------------------------
        | UNIFICAR DÍAS
        |----------------------------------------------------
        */
        $diasUnicos = collect()
            ->merge($movimientosPorDia->keys())
            ->merge($egresosPersonal->keys())
            ->unique()
            ->sort()
            ->values();

        $datos = $diasUnicos->map(function ($dia) use ($movimientosPorDia, $egresosPersonal) {

            $mov = $movimientosPorDia->get($dia);
            $per = $egresosPersonal->get($dia);

            $totalMovimientos = $mov['egresos_movimientos'] ?? 0;
            $totalPersonal = $per ? (float) $per->egresos_personal : 0;

            return [
                'dia' => $dia,
                'egresos_movimientos' => $totalMovimientos,
                'egresos_personal' => $totalPersonal,
                'egresos_totales' => round($totalMovimientos + $totalPersonal, 2),
                'movimientos' => $mov['movimientos'] ?? 0,
                'pagos_personal' => $per ? $per->pagos_personal : 0
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $datos,
            'periodo' => [
                'month' => $month,
                'year' => $year,
                'month_name' => Carbon::create($year, $month, 1)->locale('es')->monthName,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error cargando gráfica de egresos: ' . $e->getMessage()
        ], 500);
    }
}

    // 🥧 EGRESOS - DISTRIBUCIÓN POR TIPO
    public function egresosDistribucion(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            $subBranchId = Auth::user()->sub_branch_id;

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Total movimientos (compras/gastos)
            $totalMovimientos = Movement::where('movement_type', 'egreso')
                ->where('sub_branch_id', $subBranchId)
                ->whereBetween('date', [$startDate, $endDate])
                ->join('movement_details', 'movements.id', '=', 'movement_details.movement_id')
                ->sum('movement_details.total_price');

            // Total personal - CORREGIDO
            $totalPersonal = PagoPersonal::where('sub_branch_id', $subBranchId)
                ->whereBetween('fecha_pago', [$startDate, $endDate])
                ->where('estado', 'pagado')
                ->sum('monto');

            $distribucion = [
                [
                    'tipo' => 'Compras y Gastos',
                    'total' => $totalMovimientos,
                    'color' => '#EF4444',
                    'icono' => 'pi pi-shopping-cart'
                ],
                [
                    'tipo' => 'Pago al Personal',
                    'total' => $totalPersonal,
                    'color' => '#F59E0B',
                    'icono' => 'pi pi-users'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $distribucion,
                'periodo' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => Carbon::create($year, $month, 1)->locale('es')->monthName
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cargando distribución de egresos: ' . $e->getMessage()
            ], 500);
        }
    }
    // 📈 INGRESO NETO - TOTALES POR MES
    public function ingresoNeto(Request $request)
{
    try {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $subBranchId = Auth::user()->sub_branch_id;
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // ============================================
        // INGRESOS
        // ============================================
        
        // ✅ CORREGIDO: usar check_out y estados correctos
        $ingresosHabitaciones = Booking::whereHas('room', function($query) use ($subBranchId) {
                $query->where('sub_branch_id', $subBranchId);
            })
            ->whereIn('status', [
                Booking::STATUS_CHECKED_OUT,
                Booking::STATUS_CHECKED_IN
            ])
            ->whereBetween('check_out', [$startDate, $endDate])  // ✅ check_out
            ->sum('room_subtotal');

        // Ingresos por productos
        $ingresosProductos = BookingConsumption::whereHas('booking.room', function($query) use ($subBranchId) {
                $query->where('sub_branch_id', $subBranchId);
            })
            ->whereBetween('consumed_at', [$startDate, $endDate])
            ->sum('total_price');

        $ingresoBruto = $ingresosHabitaciones + $ingresosProductos;

        // ============================================
        // EGRESOS
        // ============================================
        
        // ✅ CORREGIDO: Calcular subtotal y aplicar IGV
        $subTotalMovimientos = Movement::where('movement_type', 'egreso')
            ->where('sub_branch_id', $subBranchId)
            ->whereBetween('date', [$startDate, $endDate])
            ->join('movement_details', 'movements.id', '=', 'movement_details.movement_id')
            ->sum('movement_details.total_price');
        
        // Aplicar IGV del 18%
        $egresosMovimientos = $subTotalMovimientos * 1.18;

        // ✅ CORREGIDO: Estado 'pagado' en lugar de 'completado'
        $egresosPersonal = PagoPersonal::where('sub_branch_id', $subBranchId)
            ->whereBetween('fecha_pago', [$startDate, $endDate])
            ->where('estado', 'pagado')  // ✅ CAMBIADO
            ->sum('monto');

        $egresosTotales = $egresosMovimientos + $egresosPersonal;
        $ingresoNeto = $ingresoBruto - $egresosTotales;

        // ============================================
        // MÉTRICAS ADICIONALES
        // ============================================
        
        $margenGanancia = $ingresoBruto > 0 
            ? round(($ingresoNeto / $ingresoBruto) * 100, 2) 
            : 0;
        
        $porcentajeEgresos = $ingresoBruto > 0 
            ? round(($egresosTotales / $ingresoBruto) * 100, 2) 
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'ingreso_neto' => (float) $ingresoNeto,
                'ingreso_bruto' => (float) $ingresoBruto,
                'egresos_totales' => (float) $egresosTotales,
                'ingresos_habitaciones' => (float) $ingresosHabitaciones,
                'ingresos_productos' => (float) $ingresosProductos,
                'egresos_movimientos' => (float) $egresosMovimientos,
                'egresos_personal' => (float) $egresosPersonal,
                'margen_ganancia' => (float) $margenGanancia,
                'porcentaje_egresos' => (float) $porcentajeEgresos,
                // ✅ NUEVO: Desglose de movimientos
                'desglose_movimientos' => [
                    'subtotal' => (float) $subTotalMovimientos,
                    'igv' => (float) ($subTotalMovimientos * 0.18),
                    'total' => (float) $egresosMovimientos
                ],
                // ✅ NUEVO: Métricas adicionales
                'metricas' => [
                    'rentabilidad' => $ingresoNeto >= 0 ? 'positiva' : 'negativa',
                    'porcentaje_habitaciones' => $ingresoBruto > 0 
                        ? round(($ingresosHabitaciones / $ingresoBruto) * 100, 2) 
                        : 0,
                    'porcentaje_productos' => $ingresoBruto > 0 
                        ? round(($ingresosProductos / $ingresoBruto) * 100, 2) 
                        : 0,
                    'porcentaje_movimientos' => $egresosTotales > 0 
                        ? round(($egresosMovimientos / $egresosTotales) * 100, 2) 
                        : 0,
                    'porcentaje_personal' => $egresosTotales > 0 
                        ? round(($egresosPersonal / $egresosTotales) * 100, 2) 
                        : 0
                ],
                'periodo' => [
                    'month' => (int) $month,
                    'year' => (int) $year,
                    'month_name' => ucfirst(Carbon::create($year, $month, 1)->locale('es')->monthName),
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString()
                ]
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Error en ingresoNeto: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error cargando ingreso neto'
        ], 500);
    }
}

    // 📊 INGRESO NETO - GRÁFICA COMPARATIVA
    public function ingresoNetoGrafica(Request $request)
{
    try {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $subBranchId = Auth::user()->sub_branch_id;
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Obtener datos por día para el mes actual
        $datos = [];
        $diasEnMes = $startDate->daysInMonth;

        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $fechaActual = Carbon::create($year, $month, $dia);

            // ============================================
            // INGRESOS DEL DÍA
            // ============================================
            
            // ✅ CORREGIDO: usar check_out y estados correctos
            $ingresosHabitacionesDia = Booking::whereHas('room', function($query) use ($subBranchId) {
                    $query->where('sub_branch_id', $subBranchId);
                })
                ->whereIn('status', [
                    Booking::STATUS_CHECKED_OUT,
                    Booking::STATUS_CHECKED_IN
                ])
                ->whereDate('check_out', $fechaActual)  // ✅ check_out
                ->sum('room_subtotal');

            $ingresosProductosDia = BookingConsumption::whereHas('booking.room', function($query) use ($subBranchId) {
                    $query->where('sub_branch_id', $subBranchId);
                })
                ->whereDate('consumed_at', $fechaActual)
                ->sum('total_price');

            $ingresosDia = $ingresosHabitacionesDia + $ingresosProductosDia;

            // ============================================
            // EGRESOS DEL DÍA
            // ============================================
            
            // ✅ CORREGIDO: Calcular subtotal y aplicar IGV
            $subTotalMovimientosDia = Movement::where('movement_type', 'egreso')
                ->where('sub_branch_id', $subBranchId)
                ->whereDate('date', $fechaActual)
                ->join('movement_details', 'movements.id', '=', 'movement_details.movement_id')
                ->sum('movement_details.total_price');
            
            // Aplicar IGV del 18%
            $egresosMovimientosDia = $subTotalMovimientosDia * 1.18;

            // ✅ CORREGIDO: Estado 'pagado' en lugar de 'completado'
            $egresosPersonalDia = PagoPersonal::where('sub_branch_id', $subBranchId)
                ->whereDate('fecha_pago', $fechaActual)
                ->where('estado', 'pagado')  // ✅ CAMBIADO
                ->sum('monto');

            $egresosDia = $egresosMovimientosDia + $egresosPersonalDia;
            $ingresoNetoDia = $ingresosDia - $egresosDia;

            $datos[] = [
                'dia' => $fechaActual->toDateString(),
                'dia_numero' => (int) $dia,
                'dia_semana' => ucfirst($fechaActual->locale('es')->dayName),  // ✅ NUEVO
                'ingresos' => (float) $ingresosDia,
                'egresos' => (float) $egresosDia,
                'ingreso_neto' => (float) $ingresoNetoDia,
                'ingresos_habitaciones' => (float) $ingresosHabitacionesDia,
                'ingresos_productos' => (float) $ingresosProductosDia,
                'egresos_movimientos' => (float) $egresosMovimientosDia,
                'egresos_personal' => (float) $egresosPersonalDia
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $datos,
            'periodo' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'month_name' => ucfirst(Carbon::create($year, $month, 1)->locale('es')->monthName),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_dias' => $diasEnMes
            ],
            // ✅ NUEVO: Resumen del mes
            'resumen_mes' => [
                'ingreso_bruto_total' => (float) collect($datos)->sum('ingresos'),
                'egresos_totales' => (float) collect($datos)->sum('egresos'),
                'ingreso_neto_total' => (float) collect($datos)->sum('ingreso_neto'),
                'promedio_diario_ingresos' => (float) collect($datos)->avg('ingresos'),
                'promedio_diario_egresos' => (float) collect($datos)->avg('egresos'),
                'promedio_diario_neto' => (float) collect($datos)->avg('ingreso_neto'),
                'mejor_dia' => $this->obtenerMejorDia($datos),
                'peor_dia' => $this->obtenerPeorDia($datos)
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Error en ingresoNetoGrafica: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error cargando gráfica de ingreso neto'
        ], 500);
    }
}

// ✅ MÉTODOS AUXILIARES (agregar al final del controlador)
private function obtenerMejorDia($datos)
{
    $mejorDia = collect($datos)->sortByDesc('ingreso_neto')->first();
    
    return $mejorDia ? [
        'fecha' => $mejorDia['dia'],
        'dia_numero' => $mejorDia['dia_numero'],
        'ingreso_neto' => $mejorDia['ingreso_neto']
    ] : null;
}

private function obtenerPeorDia($datos)
{
    $peorDia = collect($datos)->sortBy('ingreso_neto')->first();
    
    return $peorDia ? [
        'fecha' => $peorDia['dia'],
        'dia_numero' => $peorDia['dia_numero'],
        'ingreso_neto' => $peorDia['ingreso_neto']
    ] : null;
}

    // 📈 INGRESO NETO - COMPARATIVA MENSUAL (ÚLTIMOS 6 MESES)
    public function ingresoNetoComparativa(Request $request)
{
    try {
        $subBranchId = Auth::user()->sub_branch_id;
        $datos = [];

        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $startDate = $fecha->copy()->startOfMonth();
            $endDate = $fecha->copy()->endOfMonth();

            // ============================================
            // INGRESOS DEL MES
            // ============================================
            
            // ✅ CORREGIDO: usar check_out y estados correctos
            $ingresosHabitaciones = Booking::whereHas('room', function($query) use ($subBranchId) {
                    $query->where('sub_branch_id', $subBranchId);
                })
                ->whereIn('status', [
                    Booking::STATUS_CHECKED_OUT,
                    Booking::STATUS_CHECKED_IN
                ])
                ->whereBetween('check_out', [$startDate, $endDate])  // ✅ check_out
                ->sum('room_subtotal');

            $ingresosProductos = BookingConsumption::whereHas('booking.room', function($query) use ($subBranchId) {
                    $query->where('sub_branch_id', $subBranchId);
                })
                ->whereBetween('consumed_at', [$startDate, $endDate])
                ->sum('total_price');

            $ingresoBruto = $ingresosHabitaciones + $ingresosProductos;

            // ============================================
            // EGRESOS DEL MES
            // ============================================
            
            // ✅ CORREGIDO: Calcular subtotal y aplicar IGV
            $subTotalMovimientos = Movement::where('movement_type', 'egreso')
                ->where('sub_branch_id', $subBranchId)
                ->whereBetween('date', [$startDate, $endDate])
                ->join('movement_details', 'movements.id', '=', 'movement_details.movement_id')
                ->sum('movement_details.total_price');
            
            // Aplicar IGV del 18%
            $egresosMovimientos = $subTotalMovimientos * 1.18;

            // ✅ CORREGIDO: Estado 'pagado' en lugar de 'completado'
            $egresosPersonal = PagoPersonal::where('sub_branch_id', $subBranchId)
                ->whereBetween('fecha_pago', [$startDate, $endDate])
                ->where('estado', 'pagado')  // ✅ CAMBIADO
                ->sum('monto');

            $egresosTotales = $egresosMovimientos + $egresosPersonal;
            $ingresoNeto = $ingresoBruto - $egresosTotales;

            $datos[] = [
                'mes' => $fecha->format('Y-m'),
                'mes_nombre' => ucfirst($fecha->locale('es')->monthName),  // ✅ Primera letra mayúscula
                'anio' => (int) $fecha->year,
                'ingreso_bruto' => (float) $ingresoBruto,
                'egresos_totales' => (float) $egresosTotales,
                'ingreso_neto' => (float) $ingresoNeto,
                'margen_ganancia' => $ingresoBruto > 0 
                    ? round(($ingresoNeto / $ingresoBruto) * 100, 2) 
                    : 0,
                // ✅ NUEVO: Desglose adicional para gráficas más detalladas
                'desglose' => [
                    'ingresos_habitaciones' => (float) $ingresosHabitaciones,
                    'ingresos_productos' => (float) $ingresosProductos,
                    'egresos_movimientos' => (float) $egresosMovimientos,
                    'egresos_personal' => (float) $egresosPersonal
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $datos
        ]);

    } catch (\Exception $e) {
        \Log::error('Error en ingresoNetoComparativa: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error cargando comparativa de ingreso neto'
        ], 500);
    }
}

    // 💰 INGRESO NETO - DISTRIBUCIÓN
    public function ingresoNetoDistribucion(Request $request){
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            $subBranchId = Auth::user()->sub_branch_id;
            
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // ============================================
            // INGRESOS
            // ============================================
            
            $ingresosHabitaciones = Booking::whereHas('room', function($query) use ($subBranchId) {
                    $query->where('sub_branch_id', $subBranchId);
                })
                ->whereIn('status', [
                    Booking::STATUS_CHECKED_OUT,
                    Booking::STATUS_CHECKED_IN
                ])
                ->whereBetween('check_out', [$startDate, $endDate])
                ->sum('room_subtotal');

            $ingresosProductos = BookingConsumption::whereHas('booking.room', function($query) use ($subBranchId) {
                    $query->where('sub_branch_id', $subBranchId);
                })
                ->whereBetween('consumed_at', [$startDate, $endDate])
                ->sum('total_price');

            // ============================================
            // EGRESOS
            // ============================================
            
            // Egresos operativos
            $subTotalMovimientos = Movement::where('movement_type', 'egreso')
                ->where('sub_branch_id', $subBranchId)
                ->whereBetween('date', [$startDate, $endDate])
                ->join('movement_details', 'movements.id', '=', 'movement_details.movement_id')
                ->sum('movement_details.total_price');
            
            $egresosMovimientos = $subTotalMovimientos * 1.18;

            // ✅ CORREGIDO: Estado 'pagado' en lugar de 'completado'
            $egresosPersonal = PagoPersonal::where('sub_branch_id', $subBranchId)
                ->whereBetween('fecha_pago', [$startDate, $endDate])
                ->where('estado', 'pagado')  // ← CAMBIO AQUÍ
                ->sum('monto');

            // ============================================
            // CÁLCULOS FINALES
            // ============================================
            
            $ingresoBruto = $ingresosHabitaciones + $ingresosProductos;
            $egresosTotales = $egresosMovimientos + $egresosPersonal;
            $ingresoNeto = $ingresoBruto - $egresosTotales;

            $distribucion = [
                [
                    'categoria' => 'Ingreso Bruto',
                    'valor' => (float) $ingresoBruto,
                    'tipo' => 'ingreso',
                    'color' => '#10B981',
                    'icono' => 'pi pi-arrow-up'
                ],
                [
                    'categoria' => 'Egresos Totales',
                    'valor' => (float) $egresosTotales,
                    'tipo' => 'egreso',
                    'color' => '#EF4444',
                    'icono' => 'pi pi-arrow-down'
                ],
                [
                    'categoria' => 'Ingreso Neto',
                    'valor' => (float) $ingresoNeto,
                    'tipo' => 'neto',
                    'color' => $ingresoNeto >= 0 ? '#3B82F6' : '#F59E0B',
                    'icono' => $ingresoNeto >= 0 ? 'pi pi-chart-line' : 'pi pi-exclamation-triangle'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $distribucion,
                'resumen' => [
                    'ingreso_bruto' => (float) $ingresoBruto,
                    'egresos_totales' => (float) $egresosTotales,
                    'ingreso_neto' => (float) $ingresoNeto,
                    'margen_ganancia' => $ingresoBruto > 0 
                        ? round(($ingresoNeto / $ingresoBruto) * 100, 2) 
                        : 0
                ],
                'desglose_egresos' => [
                    'movimientos_subtotal' => (float) $subTotalMovimientos,
                    'movimientos_igv' => (float) ($subTotalMovimientos * 0.18),
                    'movimientos_total' => (float) $egresosMovimientos,
                    'personal' => (float) $egresosPersonal,
                    'total' => (float) $egresosTotales
                ],
                'desglose_ingresos' => [
                    'habitaciones' => (float) $ingresosHabitaciones,
                    'productos' => (float) $ingresosProductos,
                    'total' => (float) $ingresoBruto
                ],
                'periodo' => [
                    'month' => (int) $month,
                    'year' => (int) $year,
                    'month_name' => ucfirst(Carbon::create($year, $month, 1)->locale('es')->monthName)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en ingresoNetoDistribucion: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error cargando distribución de ingreso neto'
            ], 500);
        }
    }
    public function clientesTotales(){
        try {
            $totalClientes = Customer::count();
            $clientesActivos = Customer::where('is_active', true)->count();
            $clientesInactivos = Customer::where('is_active', false)->count();
            
            // Clientes este mes
            $clientesEsteMes = Customer::whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count();
                
            // Clientes mes anterior
            $clientesMesAnterior = Customer::whereYear('created_at', Carbon::now()->subMonth()->year)
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->count();

            $variacion = $clientesMesAnterior > 0 
                ? (($clientesEsteMes - $clientesMesAnterior) / $clientesMesAnterior) * 100 
                : ($clientesEsteMes > 0 ? 100 : 0);

            return response()->json([
                'total_clientes' => $totalClientes,
                'clientes_activos' => $clientesActivos,
                'clientes_inactivos' => $clientesInactivos,
                'clientes_este_mes' => $clientesEsteMes,
                'clientes_mes_anterior' => $clientesMesAnterior,
                'variacion_porcentual' => round($variacion, 2)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los datos totales',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clientesMensual(){
        try {
            // Clientes registrados por mes del último año - Usando EXTRACT para PostgreSQL
            $clientesPorMes = Customer::selectRaw('
                    EXTRACT(YEAR FROM created_at) as year, 
                    EXTRACT(MONTH FROM created_at) as month, 
                    COUNT(*) as total_clientes
                ')
                ->where('created_at', '>=', Carbon::now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();

            // Formatear los datos para la gráfica
            $labels = [];
            $data = [];

            foreach ($clientesPorMes as $mes) {
                $fecha = Carbon::create((int)$mes->year, (int)$mes->month, 1);
                $labels[] = $fecha->format('M Y');
                $data[] = $mes->total_clientes;
            }

            return response()->json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Clientes Registrados por Mes',
                        'data' => $data,
                        'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 2
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los datos mensuales',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clientesDiarios($year, $month){
        try {
            // Validar mes y año
            if (!checkdate($month, 1, $year)) {
                return response()->json([
                    'error' => 'Fecha inválida',
                    'message' => 'El mes o año proporcionado no es válido'
                ], 400);
            }

            // Obtener número de días en el mes
            $diasEnMes = Carbon::create($year, $month, 1)->daysInMonth;

            // Clientes registrados por día en el mes específico - Usando EXTRACT para PostgreSQL
            $clientesPorDia = Customer::selectRaw('
                    EXTRACT(DAY FROM created_at) as day, 
                    COUNT(*) as total_clientes
                ')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->groupBy('day')
                ->orderBy('day', 'asc')
                ->get();

            // Crear array con todos los días del mes
            $labels = [];
            $data = array_fill(0, $diasEnMes, 0);

            for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                $fecha = Carbon::create($year, $month, $dia);
                $labels[] = $dia . ' ' . $fecha->format('M');
            }

            // Llenar con datos reales
            foreach ($clientesPorDia as $registro) {
                $diaInt = (int)$registro->day;
                if ($diaInt >= 1 && $diaInt <= $diasEnMes) {
                    $data[$diaInt - 1] = $registro->total_clientes;
                }
            }

            return response()->json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => "Clientes Registrados - " . Carbon::create($year, $month, 1)->format('F Y'),
                        'data' => $data,
                        'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                        'borderColor' => 'rgba(75, 192, 192, 1)',
                        'borderWidth' => 1,
                        'tension' => 0.4
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los datos diarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Método alternativo usando Carbon (más compatible)
    public function clientesMensualCarbon(){
        try {
            $clientesPorMes = Customer::where('created_at', '>=', Carbon::now()->subMonths(12))
                ->get()
                ->groupBy(function ($date) {
                    return Carbon::parse($date->created_at)->format('Y-m');
                })
                ->map(function ($group) {
                    return $group->count();
                })
                ->sortKeys();

            $labels = [];
            $data = [];

            foreach ($clientesPorMes as $mesAnio => $total) {
                $fecha = Carbon::createFromFormat('Y-m', $mesAnio);
                $labels[] = $fecha->format('M Y');
                $data[] = $total;
            }

            return response()->json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Clientes Registrados por Mes',
                        'data' => $data,
                        'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 2
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los datos mensuales',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Método alternativo para datos diarios usando Carbon
    public function clientesDiariosCarbon($year, $month){
        try {
            if (!checkdate($month, 1, $year)) {
                return response()->json(['error' => 'Fecha inválida'], 400);
            }

            $fechaInicio = Carbon::create($year, $month, 1);
            $fechaFin = $fechaInicio->copy()->endOfMonth();
            $diasEnMes = $fechaInicio->daysInMonth;

            $clientes = Customer::whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->get()
                ->groupBy(function ($date) {
                    return Carbon::parse($date->created_at)->format('d');
                })
                ->map(function ($group) {
                    return $group->count();
                });

            $labels = [];
            $data = array_fill(0, $diasEnMes, 0);

            for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                $fecha = Carbon::create($year, $month, $dia);
                $labels[] = $dia . ' ' . $fecha->format('M');
                
                $diaStr = str_pad($dia, 2, '0', STR_PAD_LEFT);
                if (isset($clientes[$diaStr])) {
                    $data[$dia - 1] = $clientes[$diaStr];
                }
            }

            return response()->json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => "Clientes Registrados - " . $fechaInicio->format('F Y'),
                        'data' => $data,
                        'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                        'borderColor' => 'rgba(75, 192, 192, 1)',
                        'borderWidth' => 1,
                        'tension' => 0.4
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los datos diarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Método para obtener años disponibles
    public function añosDisponibles(){
        try {
            $años = Customer::selectRaw('EXTRACT(YEAR FROM created_at) as year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->map(function ($year) {
                    return (int)$year;
                })
                ->toArray();

            if (empty($años)) {
                $años = [Carbon::now()->year];
            }

            return response()->json($años);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los años disponibles',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function productosMasVendidos(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            $productos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->groupBy('products.id', 'products.name')
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                    DB::raw('SUM(booking_consumptions.total_price) as ingreso_generado')
                )
                ->orderByDesc('unidades_vendidas')
                ->limit(10)
                ->get();

            return response()->json($productos);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar productos más vendidos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Gráfica de barras - Top 10 productos más vendidos
    public function productosMasVendidosGrafica(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            $productos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->groupBy('products.id', 'products.name')
                ->select(
                    'products.name',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas')
                )
                ->orderByDesc('unidades_vendidas')
                ->limit(10)
                ->get();

            $labels = $productos->pluck('name')->toArray();
            $data = $productos->pluck('unidades_vendidas')->toArray();

            return response()->json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Unidades Vendidas',
                        'data' => $data,
                        'backgroundColor' => [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                            'rgba(199, 199, 199, 0.6)',
                            'rgba(83, 102, 255, 0.6)',
                            'rgba(40, 159, 64, 0.6)',
                            'rgba(210, 99, 132, 0.6)'
                        ],
                        'borderColor' => [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(199, 199, 199, 1)',
                            'rgba(83, 102, 255, 1)',
                            'rgba(40, 159, 64, 1)',
                            'rgba(210, 99, 132, 1)'
                        ],
                        'borderWidth' => 1
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar gráfica de productos más vendidos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Gráfica de dona - Distribución por categorías (CORREGIDO)
    public function productosPorCategoria(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            $categorias = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->join('product_categories', 'products.category_id', '=', 'product_categories.id') // CORREGIDO
                ->groupBy('product_categories.id', 'product_categories.name')
                ->select(
                    'product_categories.name as categoria',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                    DB::raw('SUM(booking_consumptions.total_price) as ingreso_generado')
                )
                ->orderByDesc('unidades_vendidas')
                ->get();

            $labels = $categorias->pluck('categoria')->toArray();
            $dataUnidades = $categorias->pluck('unidades_vendidas')->toArray();
            $dataIngresos = $categorias->pluck('ingreso_generado')->toArray();

            return response()->json([
                'unidades' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'data' => $dataUnidades,
                            'backgroundColor' => [
                                'rgba(255, 99, 132, 0.6)',
                                'rgba(54, 162, 235, 0.6)',
                                'rgba(255, 206, 86, 0.6)',
                                'rgba(75, 192, 192, 0.6)',
                                'rgba(153, 102, 255, 0.6)',
                                'rgba(255, 159, 64, 0.6)',
                            ]
                        ]
                    ]
                ],
                'ingresos' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'data' => $dataIngresos,
                            'backgroundColor' => [
                                'rgba(255, 99, 132, 0.6)',
                                'rgba(54, 162, 235, 0.6)',
                                'rgba(255, 206, 86, 0.6)',
                                'rgba(75, 192, 192, 0.6)',
                                'rgba(153, 102, 255, 0.6)',
                                'rgba(255, 159, 64, 0.6)',
                            ]
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar productos por categoría',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Evolución mensual de ventas de productos
    public function evolucionVentasMensual(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            $ventasMensuales = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->selectRaw('
                    EXTRACT(YEAR FROM consumed_at) as year,
                    EXTRACT(MONTH FROM consumed_at) as month,
                    SUM(quantity) as unidades_vendidas,
                    SUM(total_price) as ingreso_generado
                ')
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();

            $labels = [];
            $unidades = [];
            $ingresos = [];

            foreach ($ventasMensuales as $venta) {
                $fecha = Carbon::create((int)$venta->year, (int)$venta->month, 1);
                $labels[] = $fecha->format('M Y');
                $unidades[] = $venta->unidades_vendidas;
                $ingresos[] = $venta->ingreso_generado;
            }

            return response()->json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Unidades Vendidas',
                        'data' => $unidades,
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                        'yAxisID' => 'y'
                    ],
                    [
                        'label' => 'Ingresos Generados',
                        'data' => $ingresos,
                        'borderColor' => 'rgba(75, 192, 192, 1)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
                        'yAxisID' => 'y1'
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar evolución de ventas',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function productosMejorRendimiento(Request $request)
{
    try {
        $filtros = $this->getFiltros($request);

        $productos = BookingConsumption::join('products', 'booking_consumptions.product_id', '=', 'products.id')
            ->join('bookings', 'booking_consumptions.booking_id', '=', 'bookings.id')
            ->join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->where('rooms.sub_branch_id', $filtros['subBranchId'])
            ->whereBetween('booking_consumptions.consumed_at', [
                $filtros['startDate'],
                $filtros['endDate']
            ])
            ->groupBy('products.id', 'products.name')
            ->select(
                'products.name',
                DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                DB::raw('SUM(booking_consumptions.total_price) as ingreso_generado'),
                DB::raw('SUM(booking_consumptions.total_price) / NULLIF(SUM(booking_consumptions.quantity), 0) as precio_promedio')
            )
            ->havingRaw('SUM(booking_consumptions.quantity) > 0')
            ->orderByDesc(DB::raw('SUM(booking_consumptions.total_price)'))
            ->limit(15)
            ->get();

        return response()->json($productos);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error al cargar productos con mejor rendimiento',
            'message' => $e->getMessage()
        ], 500);
    }
}

    // Método para obtener productos más rentables (combinando unidades e ingresos)
    public function productosMasRentables(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            $productos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->groupBy('products.id', 'products.name')
                ->select(
                    'products.name',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                    DB::raw('SUM(booking_consumptions.total_price) as ingreso_generado'),
                    DB::raw('CASE WHEN SUM(booking_consumptions.quantity) > 0 THEN SUM(booking_consumptions.total_price) / SUM(booking_consumptions.quantity) ELSE 0 END as precio_promedio'),
                    DB::raw('(SUM(booking_consumptions.quantity) * SUM(booking_consumptions.total_price)) as score_rentabilidad') // Puntaje combinado
                )
                ->havingRaw('SUM(booking_consumptions.quantity) > 0')
                ->orderByDesc('score_rentabilidad')
                ->limit(15)
                ->get();

            return response()->json($productos);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar productos más rentables',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Método para obtener estadísticas generales de productos
    public function estadisticasProductos(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            $estadisticas = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->select(
                    DB::raw('COUNT(DISTINCT product_id) as total_productos_vendidos'),
                    DB::raw('SUM(quantity) as total_unidades_vendidas'),
                    DB::raw('SUM(total_price) as total_ingresos_generados'),
                    DB::raw('AVG(total_price) as promedio_venta'),
                    DB::raw('MAX(total_price) as venta_maxima'),
                    DB::raw('COUNT(*) as total_transacciones')
                )
                ->first();

            return response()->json($estadisticas);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar estadísticas de productos',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function productosMenosVendidos(Request $request)
{
    try {
        $filtros = $this->getFiltros($request);
        
        // Validar que existan los filtros necesarios
        if (!isset($filtros['subBranchId']) || !isset($filtros['startDate']) || !isset($filtros['endDate'])) {
            return response()->json([
                'error' => 'Filtros incompletos',
                'message' => 'Se requieren subBranchId, startDate y endDate'
            ], 400);
        }
        
        $productos = Product::select('products.id', 'products.name')
            ->withSum([
                'consumptions as unidades_vendidas' => function($query) use ($filtros) {
                    $query->whereHas('booking.room', function($q) use ($filtros) {
                        $q->where('sub_branch_id', $filtros['subBranchId']);
                    })
                    ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']]);
                }
            ], 'quantity')
            ->withSum([
                'consumptions as ingreso_generado' => function($query) use ($filtros) {
                    $query->whereHas('booking.room', function($q) use ($filtros) {
                        $q->where('sub_branch_id', $filtros['subBranchId']);
                    })
                    ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']]);
                }
            ], 'total_price')
            ->get()
            ->filter(function($producto) {
                return $producto->unidades_vendidas > 0;
            })
            ->sortBy('unidades_vendidas')
            ->take(10)
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => $productos
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error en productosMenosVendidos: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => 'Error al cargar productos menos vendidos',
            'message' => $e->getMessage()
        ], 500);
    }
}
    public function productosMenosVendidosGrafica(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            $productos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->groupBy('products.id', 'products.name')
                ->select(
                    'products.name',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                    DB::raw('SUM(booking_consumptions.total_price) as ingreso_generado')
                )
                ->orderBy('unidades_vendidas')
                ->limit(10)
                ->get();

            $labels = $productos->pluck('name')->toArray();
            $dataUnidades = $productos->pluck('unidades_vendidas')->toArray();
            $dataIngresos = $productos->pluck('ingreso_generado')->toArray();

            return response()->json([
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Unidades Vendidas',
                        'data' => $dataUnidades,
                        'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                        'borderColor' => 'rgba(255, 99, 132, 1)',
                        'borderWidth' => 1
                    ],
                    [
                        'label' => 'Ingresos Generados',
                        'data' => $dataIngresos,
                        'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar gráfica de productos menos vendidos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Productos sin ventas (stock muerto)
    public function productosSinVentas(Request $request)
{
    try {
        $filtros = $this->getFiltros($request);
        
        // Validar que existan los filtros necesarios
        if (!isset($filtros['subBranchId']) || !isset($filtros['startDate']) || !isset($filtros['endDate'])) {
            return response()->json([
                'error' => 'Filtros incompletos',
                'message' => 'Se requieren subBranchId, startDate y endDate'
            ], 400);
        }
        
        // Productos que existen pero no tienen ventas en el período
        $productosSinVentas = Product::whereDoesntHave('consumptions', function($query) use ($filtros) {
                $query->whereHas('booking.room', function($q) use ($filtros) {
                    $q->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']]);
            })
            ->where('is_active', true) // ✅ Solo productos activos
            ->select('id', 'name', 'sale_price')
            ->orderBy('name', 'ASC')
            ->limit(20)
            ->get()
            ->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'name' => $producto->name,
                    'sale_price' => number_format($producto->sale_price, 2, '.', ''),
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $productosSinVentas,
            'total' => $productosSinVentas->count()
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error en productosSinVentas: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => 'Error al cargar productos sin ventas',
            'message' => $e->getMessage()
        ], 500);
    }
}
    // Análisis de productos con bajo rendimiento
    public function productosBajoRendimiento(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            $productos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->groupBy('products.id', 'products.name', 'products.sale_price')
                ->select(
                    'products.name',
                    'products.sale_price',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                    DB::raw('SUM(booking_consumptions.total_price) as ingreso_generado'),
                    DB::raw('CASE WHEN SUM(booking_consumptions.quantity) > 0 THEN SUM(booking_consumptions.total_price) / SUM(booking_consumptions.quantity) ELSE 0 END as precio_promedio'),
                    DB::raw('(products.sale_price - (SUM(booking_consumptions.total_price) / NULLIF(SUM(booking_consumptions.quantity), 0))) as diferencia_precio')
                )
                ->havingRaw('SUM(booking_consumptions.quantity) BETWEEN 1 AND 5') // Productos con muy pocas ventas
                ->orderBy('unidades_vendidas')
                ->limit(15)
                ->get();

            return response()->json($productos);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar productos con bajo rendimiento',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    // Comparativa entre productos más y menos vendidos - CORREGIDO
    public function comparativaVentas(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            // Productos más vendidos
            $masVendidos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->groupBy('products.id', 'products.name')
                ->select(
                    'products.name',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                    DB::raw("'mas_vendidos' as tipo") // CORREGIDO: comillas simples
                )
                ->orderByDesc('unidades_vendidas')
                ->limit(5)
                ->get();

            // Productos menos vendidos
            $menosVendidos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->groupBy('products.id', 'products.name')
                ->select(
                    'products.name',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                    DB::raw("'menos_vendidos' as tipo") // CORREGIDO: comillas simples
                )
                ->havingRaw('SUM(booking_consumptions.quantity) > 0') // Excluir productos sin ventas
                ->orderBy('unidades_vendidas')
                ->limit(5)
                ->get();

            $comparativa = $masVendidos->merge($menosVendidos);

            return response()->json($comparativa);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar comparativa de ventas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Método alternativo más simple para comparativa
    public function comparativaVentasSimple(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            // Obtener todos los productos con ventas
            $productos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->groupBy('products.id', 'products.name')
                ->select(
                    'products.name',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                    DB::raw('SUM(booking_consumptions.total_price) as ingreso_generado')
                )
                ->havingRaw('SUM(booking_consumptions.quantity) > 0')
                ->orderBy('unidades_vendidas')
                ->get();

            // Separar en más y menos vendidos
            $totalProductos = $productos->count();
            $limite = min(5, ceil($totalProductos / 2));

            $menosVendidos = $productos->take($limite);
            $masVendidos = $productos->sortByDesc('unidades_vendidas')->take($limite);

            $resultado = [
                'mas_vendidos' => $masVendidos->values(),
                'menos_vendidos' => $menosVendidos->values()
            ];

            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar comparativa de ventas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Método para comparativa con datos estructurados para gráficas
    public function comparativaVentasGrafica(Request $request)
{
    try {
        $filtros = $this->getFiltros($request);
        
        if (!isset($filtros['subBranchId']) || !isset($filtros['startDate']) || !isset($filtros['endDate'])) {
            return response()->json([
                'error' => 'Filtros incompletos',
                'message' => 'Se requieren subBranchId, startDate y endDate'
            ], 400);
        }
        
        // Query base reutilizable
        $baseQuery = function() use ($filtros) {
            return BookingConsumption::with('product:id,name')
                ->whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->select('product_id', DB::raw('SUM(quantity) as unidades_vendidas'))
                ->groupBy('product_id');
        };
        
        // Top 5 más vendidos
        $masVendidos = $baseQuery()
            ->orderByDesc('unidades_vendidas')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->product->name,
                    'unidades_vendidas' => (int) $item->unidades_vendidas
                ];
            });
        
        // Top 5 menos vendidos
        $menosVendidos = $baseQuery()
            ->havingRaw('SUM(quantity) > 0')
            ->orderBy('unidades_vendidas', 'ASC')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->product->name,
                    'unidades_vendidas' => (int) $item->unidades_vendidas
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => [
                'mas_vendidos' => [
                    'labels' => $masVendidos->pluck('name')->toArray(),
                    'data' => $masVendidos->pluck('unidades_vendidas')->toArray()
                ],
                'menos_vendidos' => [
                    'labels' => $menosVendidos->pluck('name')->toArray(),
                    'data' => $menosVendidos->pluck('unidades_vendidas')->toArray()
                ]
            ],
            'periodo' => [
                'desde' => $filtros['startDate'],
                'hasta' => $filtros['endDate']
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error en comparativaVentasGrafica: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => 'Error al cargar comparativa de ventas para gráfica',
            'message' => $e->getMessage()
        ], 500);
    }
}

    // Método para análisis completo de rendimiento de productos
    public function analisisRendimientoProductos(Request $request){
        try {
            $filtros = $this->getFiltros($request);
            
            $productos = BookingConsumption::whereHas('booking.room', function($query) use ($filtros) {
                    $query->where('sub_branch_id', $filtros['subBranchId']);
                })
                ->whereBetween('consumed_at', [$filtros['startDate'], $filtros['endDate']])
                ->join('products', 'booking_consumptions.product_id', '=', 'products.id')
                ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
                ->groupBy('products.id', 'products.name', 'product_categories.name')
                ->select(
                    'products.name as producto',
                    'product_categories.name as categoria',
                    DB::raw('SUM(booking_consumptions.quantity) as unidades_vendidas'),
                    DB::raw('SUM(booking_consumptions.total_price) as ingreso_generado'),
                    DB::raw('CASE WHEN SUM(booking_consumptions.quantity) > 0 THEN SUM(booking_consumptions.total_price) / SUM(booking_consumptions.quantity) ELSE 0 END as precio_promedio'),
                    DB::raw('COUNT(DISTINCT booking_id) as veces_vendido')
                )
                ->orderBy('unidades_vendidas')
                ->get();

            // Clasificar productos por rendimiento
            $clasificacion = [
                'alto_rendimiento' => $productos->where('unidades_vendidas', '>=', 10)->values(),
                'medio_rendimiento' => $productos->whereBetween('unidades_vendidas', [3, 9])->values(),
                'bajo_rendimiento' => $productos->whereBetween('unidades_vendidas', [1, 2])->values(),
                'sin_ventas' => $productos->where('unidades_vendidas', 0)->values()
            ];

            return response()->json([
                'clasificacion' => $clasificacion,
                'estadisticas' => [
                    'total_productos' => $productos->count(),
                    'total_unidades' => $productos->sum('unidades_vendidas'),
                    'total_ingresos' => $productos->sum('ingreso_generado'),
                    'productos_con_ventas' => $productos->where('unidades_vendidas', '>', 0)->count(),
                    'productos_sin_ventas' => $productos->where('unidades_vendidas', 0)->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar análisis de rendimiento',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
