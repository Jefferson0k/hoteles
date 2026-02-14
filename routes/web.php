<?php

use App\Http\Controllers\Api\CashRegisterClosingController;
use App\Http\Controllers\Api\CashRegisterSessionController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ConsultasDni;
use App\Http\Controllers\Api\HabitacionController;
use App\Http\Controllers\Api\HorarioController;
use App\Http\Controllers\Api\PisoController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\SubBranchConfigurationController;
use App\Http\Controllers\Api\TipoHabitacionController;
use App\Http\Controllers\Api\UsoHabitacionController;
use App\Http\Controllers\Api\UsuariosController;
use App\Http\Controllers\Panel\BookingConsumptionController;
use App\Http\Controllers\Panel\BookingController;
use App\Http\Controllers\Panel\BranchController;
use App\Http\Controllers\Panel\BranchRoomTypePriceController;
use App\Http\Controllers\Panel\CashRegisterController;
use App\Http\Controllers\Panel\CurrencyController;
use App\Http\Controllers\Panel\CustomerController;
use App\Http\Controllers\Panel\FloorController;
use App\Http\Controllers\Panel\kardexController;
use App\Http\Controllers\Panel\MovementDetailController;
use App\Http\Controllers\Panel\MovementsController;
use App\Http\Controllers\Panel\PagoPersonalController;
use App\Http\Controllers\Panel\PaymentController;
use App\Http\Controllers\Panel\PricingRangeController;
use App\Http\Controllers\Panel\ProviderController;
use App\Http\Controllers\Panel\RateTypeController;
use App\Http\Controllers\Panel\ReportController;
use App\Http\Controllers\Panel\RoomController;
use App\Http\Controllers\Panel\RoomStatusController;
use App\Http\Controllers\Panel\RoomTypeController;
use App\Http\Controllers\Panel\SubBranchController;
use App\Http\Controllers\Panel\SystemSettingController;
use App\Http\Controllers\Web\Branch\BranchWeb;
use App\Http\Controllers\Web\BranchRoomTypePrice\BranchRoomTypePriceWeb;
use App\Http\Controllers\Web\Cash\CashWeb;
use App\Http\Controllers\Web\Cash\cashWebHabitaciones;
use App\Http\Controllers\Web\Cash\Cloasebox;
use App\Http\Controllers\Web\CashSession\CashSession;
use App\Http\Controllers\Web\SubBranch\SubBranchWeb;
use App\Http\Controllers\Web\Categoria\CategoriaWeb;
use App\Http\Controllers\Web\Cliente\ClienteWeb;
use App\Http\Controllers\Web\Configuraciones\Configuraciones;
use App\Http\Controllers\Web\Floor\FloorWeb;
use App\Http\Controllers\Web\Horario\HorarioWeb;
use App\Http\Controllers\Web\DetailsMovements\DetailsMovementsWeb;
use App\Http\Controllers\Web\Habitaciones\habitacionesGestion;
use App\Http\Controllers\Web\Habitaciones\habitacionesWeb;
use App\Http\Controllers\Web\Inventario\InventarioWeb;
use App\Http\Controllers\Web\Kardex\kardexWeb;
use App\Http\Controllers\Web\Movements\MovementsWeb;
use App\Http\Controllers\Web\PagoPersonal\PagoPersonalWeb;
use App\Http\Controllers\Web\Piso\PisoWeb;
use App\Http\Controllers\Web\PricingRange\PricingRangeWeb;
use App\Http\Controllers\Web\Producto\ProductoWeb;
use App\Http\Controllers\Web\RateType\RateTypeWeb;
use App\Http\Controllers\Web\Reportes\EgresoWeb;
use App\Http\Controllers\Web\Reportes\IngresoBrutoWeb;
use App\Http\Controllers\Web\Reportes\IngresoNetoWeb;
use App\Http\Controllers\Web\Reportes\IngresoProducto;
use App\Http\Controllers\Web\Reportes\IngressoHabitacionWeb;
use App\Http\Controllers\Web\Reportes\NumeroClientesWeb;
use App\Http\Controllers\Web\Reportes\ProductosMasVendidosWeb;
use App\Http\Controllers\Web\Reportes\ProductosMenosVendidosWeb;
use App\Http\Controllers\Web\Room\RoomFloorWeb;
use App\Http\Controllers\Web\Room\RoomWeb;
use App\Http\Controllers\Web\RoomType\RoomtypeWeb;
use App\Http\Controllers\Web\SystemSetting\SystemSettingWeb;
use App\Http\Controllers\Web\UsuarioWebController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified','cash.register.open'])->group(function () {
    #PARA QUE CUANDO SE CREA UN USUARIO O MODIFICA SU PASSWORD LO REDIRECCIONE PARA QUE PUEDA ACTUALIZAR
    Route::get('/dashboard', function () {
        $user = Auth::user();
        return Inertia::render('Dashboard', [
            'mustReset' => $user->restablecimiento == 0,
        ]);
    })->name('dashboard');

    # VISTAS DEL FRONTEND
    Route::prefix('panel')->group(function () {
        Route::get('/reportes/ingresos-habitaciones', [IngressoHabitacionWeb::class, 'view'])->name('ingresos.view');
        Route::get('/reportes/ingreso-productos', [IngresoProducto::class, 'view'])->name('ingreso.view');
        Route::get('/reportes/ingreso-bruto', [IngresoBrutoWeb::class, 'view'])->name('ingreso.bruto.view');
        Route::get('/reportes/egresos', [EgresoWeb::class, 'view'])->name('egresos.view');
        Route::get('/reportes/ingreso-neto', [IngresoNetoWeb::class, 'view'])->name('ingreso.neto.view');
        Route::get('/reportes/numero-clientes', [NumeroClientesWeb::class, 'view'])->name('numero.view');
        Route::get('/reportes/productos-mas-vendidos', [ProductosMasVendidosWeb::class, 'view'])->name('productos.vendidos.view');
        Route::get('/reportes/productos-menos-vendidos', [ProductosMenosVendidosWeb::class, 'view'])->name('menos.view');

        Route::get('/pagos/personal', [PagoPersonalWeb::class, 'view'])->name('pagos.view');

        Route::get('/inventario', [InventarioWeb::class, 'view'])->name('kardex.view');
        Route::get('/cajas', [CashWeb::class, 'view'])->name('cajas.view');
        Route::get('/habitaciones', [habitacionesWeb::class, 'view'])->name('habitaciones.view');
        Route::get('/aperturar', [cashWebHabitaciones::class, 'view'])->name('aperturar.view');
        Route::get('/habitaciones/online', [habitacionesGestion::class, 'view'])->name('online.view');
        Route::get('/cuarto/{id}', [RoomFloorWeb::class, 'view'])->name('cuarto.view');
        Route::get('/caja/cerrar', [Cloasebox::class, 'view'])->name('cloasebox.view');

        # 🔹 Kardex (por producto, general y valorizado)
        Route::get('/kardex', [kardexWeb::class, 'view'])->name('kardex.view');
        Route::get('/kardex/general', [kardexWeb::class, 'viewGeneral'])->name('kardex.general.view');
        Route::get('/kardex/valorizado', [kardexWeb::class, 'viewValorizado'])->name('kardex.valorizado.view');

        # 🔹 Movimientos
        Route::get('/movimientos', [MovementsWeb::class,'view'])->name('movimientos.view');
        Route::get('/movimientos/{movement_id}', [DetailsMovementsWeb::class, 'view'])->name('movimientos.details.view');

        # 🔹 Sucursales y sub-sucursales
        Route::get('/branches/{id}/sub-branches', [SubBranchWeb::class,'view'])->name('branches.subbranches.view');
        Route::get('/sucursales', [BranchWeb::class, 'view'])->name('sucursales.index');

        # 🔹 Usuarios y roles
        Route::get('/usuario', [UsuarioWebController::class,'index'])->name('usuario.index');
        Route::get('/roles', [UsuarioWebController::class, 'roles'])->name('roles.index');

        # 🔹 Pisos, habitaciones, tipos y usos
        Route::get('/sub-branches/{subBranch}/floors', [FloorWeb::class, 'view'])->name('habitaciones.index');
        Route::get('/floors/{floor}/rooms', [RoomWeb::class, 'view'])->name('rooms.index');
        Route::get('/pisos', [PisoWeb::class, 'view'])->name('pisos.index');

        # 🔹 Categorías y productos
        Route::get('/categorias', [CategoriaWeb::class, 'view'])->name('categorias.index');
        Route::get('/productos', [ProductoWeb::class, 'view'])->name('productos.index');

        # 🔹 Horarios y configuración del sistema
        Route::get('/horarios', [HorarioWeb::class, 'view'])->name('horarios.index');

        Route::get('/cajas/usuarios/{id}', [CashSession::class, 'view'])->name('cajas.usuarios');
        Route::get('/huespedes', [ClienteWeb::class, 'view'])->name('huespedes.view');
        Route::get('/habitacion/{id}/nueva-reserva', [RoomFloorWeb::class, 'view'])->name('nueva.view');
    });

    Route::prefix('configuracion')->group(function () {
        Route::get('/parametros', [Configuraciones::class, 'view'])->name('configuraciones.avanzadas.view');
        Route::get('/tarifas-habitaciones/room-type', [RoomtypeWeb::class, 'view'])->name('tipos.habitacion.view');
        Route::get('/tarifas-habitaciones/rate-type', [RateTypeWeb::class, 'view'])->name('tipos.tarifa.view');
        Route::get('/tarifas-habitaciones/branch-room-type-price', [BranchRoomTypePriceWeb::class, 'view'])->name('precios.sucursal.tipo.habitacion.view');
        Route::get('/tarifas-habitaciones/pricing-range', [PricingRangeWeb::class, 'view'])->name('rangos.precios.view');

    });

    Route::prefix('consumo-pendiente')->group(function () {
        Route::post('/add', [ProductoController::class, 'addProducto']);
        Route::put('/{id}', [ProductoController::class, 'updateProducto']);
        Route::delete('/{id}', [ProductoController::class, 'deleteProducto']);
    });

    Route::prefix('reports')->group(function () {
        Route::get('/ingresos-habitaciones', [ReportController::class, 'ingresosHabitaciones']);
        Route::get('/ingreso-productos', [ReportController::class, 'ingresoProductos']);
        Route::get('/ingreso-bruto', [ReportController::class, 'ingresoBruto']);
        Route::get('/numero-clientes', [ReportController::class, 'numeroClientes']);
        Route::get('/productos-mas-vendidos', [ReportController::class, 'productosMasVendidos']);
        Route::get('/productos-menos-vendidos', [ReportController::class, 'productosMenosVendidos']);
        
        Route::get('/booking-consumptions', [BookingConsumptionController::class, 'index']);
        Route::get('/ingreso-bruto-comparativa', [ReportController::class, 'ingresoBrutoComparativa']);
        Route::get('/egresos-top-proveedores', [ReportController::class, 'egresosTopProveedores']);

        Route::get('/ingresos-habitaciones-grafica', [ReportController::class, 'ingresosHabitacionesGrafica']);
        Route::get('/ingreso-productos-grafica', [ReportController::class, 'ingresoProductosGrafica']);
        Route::get('/egresos-grafica', [ReportController::class, 'egresosGrafica']);

        Route::get('/egresos', [ReportController::class, 'egresos']);
        Route::get('/egresos-detalle', [ReportController::class, 'egresosDetalle']);
        Route::get('/egresos-distribucion', [ReportController::class, 'egresosDistribucion']);

        Route::get('/ingreso-neto', [ReportController::class, 'ingresoNeto']);
        Route::get('/ingreso-neto-grafica', [ReportController::class, 'ingresoNetoGrafica']);
        Route::get('/ingreso-neto-comparativa', [ReportController::class, 'ingresoNetoComparativa']);
        Route::get('/ingreso-neto-distribucion', [ReportController::class, 'ingresoNetoDistribucion']);

        Route::get('/clientes-totales', [ReportController::class, 'clientesTotales']);
        Route::get('/clientes-mensual', [ReportController::class, 'clientesMensual']);
        Route::get('/clientes-diarios/{year}/{month}', [ReportController::class, 'clientesDiarios']);

        Route::get('/mas-vendidos', [ReportController::class, 'productosMasVendidos']);
        Route::get('/mas-vendidos-grafica', [ReportController::class, 'productosMasVendidosGrafica']);
        Route::get('/por-categoria', [ReportController::class, 'productosPorCategoria']);
        Route::get('/evolucion-ventas', [ReportController::class, 'evolucionVentasMensual']);
        Route::get('/mejor-rendimiento', [ReportController::class, 'productosMejorRendimiento']);

        Route::get('/menos-vendidos', [ReportController::class, 'productosMenosVendidos']);
        Route::get('/menos-vendidos-grafica', [ReportController::class, 'productosMenosVendidosGrafica']);
        Route::get('/sin-ventas', [ReportController::class, 'productosSinVentas']);
        Route::get('/comparativa-ventas', [ReportController::class, 'comparativaVentasSimple']);
        Route::get('/comparativa-ventas-grafica', [ReportController::class, 'comparativaVentasGrafica']);
        Route::get('/analisis-rendimiento', [ReportController::class, 'analisisRendimientoProductos']);
        Route::get('/bajo-rendimiento', [ReportController::class, 'productosBajoRendimiento']);

    });


    Route::prefix('branch-room-type-prices')->group(function () {
        Route::get('/', [BranchRoomTypePriceController::class, 'index']);
        Route::post('/', [BranchRoomTypePriceController::class, 'store']);
        
        // Nuevos endpoints útiles
        Route::get('/pricing-options', [BranchRoomTypePriceController::class, 'getPricingOptions']);
        Route::post('/calculate-price', [BranchRoomTypePriceController::class, 'calculatePrice']);
        
        Route::get('/{branch_room_type_price}', [BranchRoomTypePriceController::class, 'show']);
        Route::put('/{branch_room_type_price}', [BranchRoomTypePriceController::class, 'update']);
        Route::delete('/{branch_room_type_price}', [BranchRoomTypePriceController::class, 'destroy']);
    });

    Route::prefix('pricing-ranges')->group(function () {
        Route::get('/', [PricingRangeController::class, 'index']);
        Route::post('/', [PricingRangeController::class, 'store']);
        Route::get('/price-for-minutes', [PricingRangeController::class, 'getPriceForMinutes']);
        Route::get('/{pricing_range}', [PricingRangeController::class, 'show']);
        Route::put('/{pricing_range}', [PricingRangeController::class, 'update']);
        Route::delete('/{pricing_range}', [PricingRangeController::class, 'destroy']);
    });

    Route::prefix('sub-branches/{subBranchId}/configuration')->group(function () {
        Route::get('/', [SubBranchConfigurationController::class, 'show']);
        Route::post('/', [SubBranchConfigurationController::class, 'store']);
        
        Route::put('/time', [SubBranchConfigurationController::class, 'updateTimeSettings']);
        Route::put('/checkin', [SubBranchConfigurationController::class, 'updateCheckinSettings']);
        Route::put('/penalty', [SubBranchConfigurationController::class, 'updatePenaltySettings']);
        Route::put('/cancellation', [SubBranchConfigurationController::class, 'updateCancellationPolicy']);
        Route::put('/deposit', [SubBranchConfigurationController::class, 'updateDepositSettings']);
        Route::put('/tax', [SubBranchConfigurationController::class, 'updateTaxSettings']);
        Route::put('/reservation', [SubBranchConfigurationController::class, 'updateReservationSettings']);
        Route::put('/notification', [SubBranchConfigurationController::class, 'updateNotificationSettings']);
        
        Route::post('/clone', [SubBranchConfigurationController::class, 'cloneConfiguration']);
    });
    
    Route::prefix('cash-register-sessions')->group(function () {
        Route::post('/close', [CashRegisterClosingController::class, 'close']);
    });

    Route::prefix('cash-registers')->group(function () {
        Route::get('/{cashRegisterId}/sessions',[CashRegisterSessionController::class, 'byCashRegister']
    );
    });

    Route::prefix('cuarto')->group(function () {
        Route::get('/{roomId}/detalles-checkout', [BookingController::class, 'getCheckoutDetails']);
        Route::get('/{roomId}/calcular-tiempo-extra', [BookingController::class, 'calculateExtraTime']);
        Route::post('/{roomId}/cobrar-tiempo-extra', [BookingController::class, 'chargeExtraTime']);
        Route::post('/{roomId}/extender-tiempo', [BookingController::class, 'extendTimeDialog']);
        Route::post('/{roomId}/checkout', [BookingController::class, 'checkout']);
        Route::post('/{id}/liberar', [RoomController::class, 'liberar']);
    });
    // Gestión de uso de habitaciones
    Route::get('/bookings/{booking}/ticket', [BookingController::class, 'ticket']);
    Route::post('/bookings/{booking}/start', [RoomStatusController::class, 'startRoom']);
    Route::post('/bookings/{booking}/extend', [RoomStatusController::class, 'extendTime']);
    #Route::post('/bookings/{booking}/finish', [RoomStatusController::class, 'finishRoom']);

    // Agregar productos/consumos a una reserva existente
    Route::post('bookings/{booking}/consumptions', [BookingConsumptionController::class, 'addConsumptions'])
    ->name('bookings.consumptions.store');
    
    // Gestión manual de estados
    Route::post('/rooms/{room}/change-status', [RoomStatusController::class, 'changeRoomStatus']);
    Route::post('/rooms/{room}/mark-ready', [RoomStatusController::class, 'markAsReady']);
    
    // Historial
    Route::get('/rooms/{room}/status-history', [RoomStatusController::class, 'statusHistory']);

    Route::prefix('pagos')->name('pagos.')->group(function () {
        Route::get('/', [PagoPersonalController::class, 'index'])->name('index');
        Route::post('/', [PagoPersonalController::class, 'store'])->name('store');
        Route::get('/{pagoPersonal}', [PagoPersonalController::class, 'show'])->name('show');
        Route::put('/{pagoPersonal}', [PagoPersonalController::class, 'update'])->name('update');
        Route::delete('/{id}', [PagoPersonalController::class, 'destroy'])->name('destroy');
        
        Route::get('/historial/reporte', [PagoPersonalController::class, 'historial'])->name('historial');
        Route::patch('/{pagoPersonal}/aprobar', [PagoPersonalController::class, 'approve'])->name('approve');
        Route::patch('/{pagoPersonal}/anular', [PagoPersonalController::class, 'cancel'])->name('cancel');
        Route::get('/exportar/reporte', [PagoPersonalController::class, 'export'])->name('export');
    });
    
    Route::prefix('rate-types')->group(function () {
        Route::get('/', [RateTypeController::class, 'index']);
        Route::get('/opcones', [RateTypeController::class, 'indexOpciones']);
        Route::post('/', [RateTypeController::class, 'store']);
        Route::get('/{rateType}', [RateTypeController::class, 'show']);
        Route::put('/{rateType}', [RateTypeController::class, 'update']);
        Route::delete('/{rateType}', [RateTypeController::class, 'destroy']);
    });

    Route::get('currencies', [CurrencyController::class, 'index']);

    Route::prefix('detalles')->group(function () {
        Route::get('/bookings/{booking}/habitacion', [BookingController::class, 'show']);
    });

    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::post('/{booking}/add-consumption', [BookingController::class, 'addConsumption']);
        Route::post('/{booking}/extend-time', [BookingController::class, 'extenderTiempo']);
        Route::post('/{booking}/finish', [BookingController::class, 'finishService']);
        Route::post('/rooms/{room}/change-status', [RoomStatusController::class, 'changeRoomStatus']);
        Route::post('/rooms/{room}/mark-ready', [RoomStatusController::class, 'markAsReady']);
        Route::get('/rooms/{room}/status-history', [RoomStatusController::class, 'statusHistory']);
    });

    Route::prefix('reporte-pagos')->group(function () {
        Route::get('/', [PaymentController::class, 'reportePagos']);
        Route::get('{id}', [PaymentController::class, 'show']);
    });

    Route::get('/rooms/dashboard', [RoomStatusController::class, 'dashboard']);

    Route::prefix('payments')->group(function () {
        Route::get('/user-cash-register', [PaymentController::class, 'getUserCashRegister']);
        Route::get('/methods', [PaymentController::class, 'getPaymentMethods']);
    });

    Route::prefix('floors-rooms')->group(function () {
        Route::get('/', [FloorController::class, 'floorRoom'])->name('floors.rooms.index');
    });

    # CUSTOMER => BACKEND
    Route::prefix('customer')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('customer.index');
        Route::post('/', [CustomerController::class, 'store'])->name('customer.store');
    });

    # CASH => BACKEND
    Route::prefix('cash')->group(function () {
        Route::get('/', [CashRegisterController::class, 'index'])->name('cash.cash-registers.index');
        Route::post('/multiple', [CashRegisterController::class, 'storeMultiple'])->name('cash.cash-registers.multiple');
        Route::get('/{id}', [CashRegisterController::class, 'show'])->name('cash.cash-registers.show');
        Route::post('/{id}/open', [CashRegisterController::class, 'open'])->name('cash.cash-registers.open');
    });

    # KARDEX => BACKEND
    Route::prefix('kardex')->group(function () {
        Route::get('/', [kardexController::class, 'index']);
        Route::get('/general', [kardexController::class, 'indexGeneral']);
        Route::get('/valorizado', [kardexController::class, 'indexKardexValorizado']);
        Route::get('/sub-branches/{id}/inventario', [kardexController::class, 'inventario'])
        ->name('subbranches.inventario');
    });
    
    #MOVEMENT DETAIL => BACKEND
    Route::prefix('movement-detail')->group(function () {
        Route::get('{movementId}/details', [MovementDetailController::class, 'index']);
        Route::post('{id}/restore', [MovementDetailController::class, 'restore']);
        Route::post('/', [MovementDetailController::class, 'store']);
        Route::get('{movementDetail}', [MovementDetailController::class, 'show']);
        Route::put('{movementDetail}', [MovementDetailController::class, 'update']);
        Route::delete('{movementDetail}', [MovementDetailController::class, 'destroy']);
    });

    #PROVIDERS => BACKEND
    Route::prefix('providers')->group(function () {
        Route::get('/', [ProviderController::class, 'index']);
    });

    #MOVEMENTS => BACKEND
    Route::prefix('movements')->group(function () {
        Route::get('/', [MovementsController::class, 'index'])->name('movements.index');
        Route::post('/', [MovementsController::class, 'store'])->name('movements.store');
        Route::get('/{movement}', [MovementsController::class, 'show'])->name('movements.show');
        Route::put('/{movement}', [MovementsController::class, 'update'])->name('movements.update');
        Route::patch('/{movement}', [MovementsController::class, 'update']);
        Route::delete('/{movement}', [MovementsController::class, 'destroy'])->name('movements.destroy');
    });

    #ROOM TYPE => BACKEND
    Route::prefix('room-types')->group(function () {
        Route::get('/', [RoomTypeController::class, 'index'])->name('room.index');
        Route::get('/opciones', [RoomTypeController::class, 'indexOpciones'])->name('room.index-opciones');
        Route::post('/', [RoomTypeController::class, 'store'])->name('room.store');
        Route::get('/{roomType}', [RoomTypeController::class, 'show'])->name('room.show');
        Route::put('/{roomType}', [RoomTypeController::class, 'update'])->name('room.update');
        Route::delete('/{roomType}', [RoomTypeController::class, 'destroy'])->name('room.destroy');
    });
    
    #CONSULTAS DE DNI => BACKEND
    Route::get('/consulta/{dni}', [ConsultasDni::class, 'consultar'])->name('consultar.dni');

    #FLOORS => BACKEND
    Route::apiResource('floors', FloorController::class);
    Route::prefix('floors')->controller(FloorController::class)->group(function () {
        Route::get('with-room-counts/index', 'withRoomCounts')->name('floors.with-room-counts');
    });
    Route::get('sub-branches/{sub_branch}/floors', [FloorController::class, 'bySubBranch'])
        ->name('sub-branches.floors');

    #BRANCHES => BACKEND
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index'])->name('branches.index');
        Route::post('/', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/{id}', [BranchController::class, 'show'])->name('branches.show');
        Route::put('/{branch}', [BranchController::class, 'update']);
        Route::delete('/{id}', [BranchController::class, 'delete'])->name('branches.delete');
    });

    # SUB BRANCH => BACKEND
    Route::prefix('sub-branches')->group(function () {
        Route::get('/search', [SubBranchController::class, 'search'])->name('subbranches.search');
        Route::get('/branch/{branchId}', [SubBranchController::class, 'index'])->name('subbranches.by-branch');
        Route::get('/show/{id}', [SubBranchController::class, 'show'])->name('subbranches.show');
        
        Route::get('/{id}', [SubBranchController::class, 'index']);
        
        Route::post('/', [SubBranchController::class, 'store'])->name('subbranches.store');
        Route::put('/{sub_branch}', [SubBranchController::class, 'update'])->name('subbranches.update');
        Route::delete('/{id}', [SubBranchController::class, 'destroy'])->name('subbranches.delete');
    });

    Route::prefix('rooms')->controller(RoomController::class)->group(function () {
        Route::apiResource('/', RoomController::class)->parameters(['' => 'room']);
        Route::patch('{room}/status', 'changeStatus')->name('rooms.change-status');
        Route::get('{room}/status-logs', 'statusLogs')->name('rooms.status-logs');
        Route::get('stats/general', 'stats')->name('rooms.stats');
        Route::get('available/search', 'availableRooms')->name('rooms.available');
        Route::get('search/advanced', 'advancedSearch')->name('rooms.advanced-search');
        Route::get('with-stats/index', 'indexWithStats')->name('rooms.index-with-stats');
    });

    Route::prefix('system-settings')->group(function () {
        Route::get('/', [SystemSettingController::class, 'index'])
            ->name('index');
        Route::get('/{key}', [SystemSettingController::class, 'show'])
            ->name('show');
        Route::put('/{setting}', [SystemSettingController::class, 'update'])
            ->name('update');
    });

    #CLIENTES -> CLIENTES
    Route::prefix('cliente')->group(function () {
        Route::get('/', [ClienteController::class, 'index']);
        Route::get('/{cliente}', [ClienteController::class, 'show']);
        Route::post('/', [ClienteController::class, 'store']);
        Route::put('/{cliente}', [ClienteController::class, 'update']);
        Route::delete('/{cliente}', [ClienteController::class, 'destroy']);
    });

    #PRODUCTOS -> BACKEND
    Route::prefix('producto')->group(function(){
        Route::get('/', [ProductoController::class, 'index'])->name('Productos.index');
        Route::post('/',[ProductoController::class, 'store'])->name('Productos.store');
        Route::get('/search', [ProductoController::class, 'searchProducto'])->name('Productos.search');
        Route::get('/{product}',[ProductoController::class, 'show'])->name('Productos.show');
        Route::put('/{product}',[ProductoController::class, 'update'])->name('Productos.update');
        Route::delete('/{product}',[ProductoController::class, 'destroy'])->name('Productos.destroy');
    });

    #HABITACIONES -> BACKEND
    Route::prefix('habitacion')->group(function () {
        Route::get('/', [HabitacionController::class, 'index']);
        Route::get('/{habitacion}', [HabitacionController::class, 'show']);
        Route::post('/', [HabitacionController::class, 'store']);
        Route::put('/{habitacion}', [HabitacionController::class, 'update']);
        Route::delete('/{habitacion}', [HabitacionController::class, 'destroy']);
    });

    #HORARIOS -> BACKEND
    Route::prefix('horario')->group(function () {
        Route::get('/', [HorarioController::class, 'index']);
        Route::get('/{horario}', [HorarioController::class, 'show']);
        Route::post('/', [HorarioController::class, 'store']);
        Route::put('/{horario}', [HorarioController::class, 'update']);
        Route::delete('/{horario}', [HorarioController::class, 'destroy']);
    });

    #=PISOS -> BACKEND
    Route::prefix('piso')->group(function () {
        Route::get('/', [PisoController::class, 'index']);
        Route::get('/{piso}', [PisoController::class, 'show']);
        Route::post('/', [PisoController::class, 'store']);
        Route::put('/{piso}', [PisoController::class, 'update']);
        Route::delete('/{piso}', [PisoController::class, 'destroy']);
    });

    #TIPOS DE HABITACION -> BACKEND
    Route::prefix('tipo-habitacion')->group(function () {
        Route::get('/', [TipoHabitacionController::class, 'index']);
        Route::get('/{tipoHabitacion}', [TipoHabitacionController::class, 'show']);
        Route::post('/', [TipoHabitacionController::class, 'store']);
        Route::put('/{tipoHabitacion}', [TipoHabitacionController::class, 'update']);
        Route::delete('/{tipoHabitacion}', [TipoHabitacionController::class, 'destroy']);
    });

    #USO DE HABITACIONES -> BACKEND
    Route::prefix('uso-habitacion')->group(function () {
        Route::get('/', [UsoHabitacionController::class, 'index']);
        Route::get('/{usoHabitacion}', [UsoHabitacionController::class, 'show']);
        Route::post('/', [UsoHabitacionController::class, 'store']);
        Route::put('/{usoHabitacion}', [UsoHabitacionController::class, 'update']);
        Route::delete('/{usoHabitacion}', [UsoHabitacionController::class, 'destroy']);
    });
    
    #CATEGORIA -> BACKEND
    Route::prefix('categoria')->group(function(){
        Route::get('/', [CategoriaController::class, 'index'])->name('Categoria.index');
        Route::post('/',[CategoriaController::class, 'store'])->name('Categoria.store');
        Route::get('/{category}',[CategoriaController::class, 'show'])->name('Categoria.show');
        Route::put('/{category}',[CategoriaController::class, 'update'])->name('Categoria.update');
        Route::delete('/{category}',[CategoriaController::class, 'destroy'])->name('Categoria.destroy');
    });

    #USUARIOS -> BACKEND
    Route::prefix('usuarios')->group(function(){
        Route::get('/', [UsuariosController::class, 'index'])->name('usuarios.index');
        Route::post('/',[UsuariosController::class, 'store'])->name('usuarios.store');
        Route::get('/{user}',[UsuariosController::class, 'show'])->name('usuarios.show');
        Route::put('/{user}',[UsuariosController::class, 'update'])->name('usuarios.update');
        Route::delete('/{user}',[UsuariosController::class, 'destroy'])->name('usuarios.destroy');
        Route::get('/search/by-subranch', [UsuariosController::class, 'search'])->name('usuarios.search');
    });
    
    #ROLES => BACKEND
    Route::prefix('rol')->group(function () {
        Route::get('/', [RolesController::class, 'index'])->name('roles.index');
        Route::get('/Permisos', [RolesController::class, 'indexPermisos'])->name('roles.indexPermisos');
        Route::post('/', [RolesController::class, 'store'])->name('roles.store');
        Route::get('/{id}', [RolesController::class, 'show'])->name('roles.show');
        Route::put('/{id}', [RolesController::class, 'update'])->name('roles.update');
        Route::delete('/{id}', [RolesController::class, 'destroy'])->name('roles.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
