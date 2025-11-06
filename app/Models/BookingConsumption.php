<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class BookingConsumption extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'booking_id', 'product_id', 'quantity', 'unit_price', 'total_price', 'status',
        'consumed_at', 'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'consumed_at' => 'datetime',
    ];

    // Constantes de estado
    const STATUS_PAID = 'paid';
    const STATUS_PENDING = 'pending';

    // Relaciones
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('consumed_at', today());
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->whereHas('booking', function ($q) use ($roomId) {
            $q->where('room_id', $roomId);
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    // ────────────────────────────
    // ⚙️ Eventos automáticos - DESCUENTO DE STOCK
    // ────────────────────────────
    protected static function booted()
    {
        static::created(function ($consumption) {
            $user = Auth::user();
            if (!$user) return;
            
            $subBranchId = $user->sub_branch_id ?? null;
            if (!$subBranchId) return;
            
            // Refrescar el modelo para obtener todos los datos
            $consumption->refresh();
            
            // Obtener el producto
            $product = Product::find($consumption->product_id);
            if (!$product) return;
            
            // Buscar producto en la sucursal del usuario
            $subBranchProduct = SubBranchProduct::where('sub_branch_id', $subBranchId)
                ->where('product_id', $consumption->product_id)
                ->first();
            
            if (!$subBranchProduct) {
                throw new \Exception("El producto {$product->name} no está disponible en esta sucursal");
            }
            
            // Verificar si hay stock suficiente
            if ($subBranchProduct->current_stock < $consumption->quantity) {
                throw new \Exception("Stock insuficiente para el producto {$product->name}. Stock disponible: {$subBranchProduct->current_stock}");
            }
            
            // ✅ OBTENER LAS UNIDADES POR PAQUETE DEL PRODUCTO
            $unitsPerPackage = $product->is_fractionable ? ($product->fraction_units ?? 1) : 1;
            if ($unitsPerPackage <= 0) $unitsPerPackage = 1;
            
            // Stock anterior
            $SAnteriorCaja = $subBranchProduct->packages_in_stock;
            $SAnteriorFraccion = $product->is_fractionable 
                ? ($subBranchProduct->current_stock % $unitsPerPackage) 
                : 0;
            
            // ✅ CALCULAR LO QUE SALE (CONSUMO)
            $cantidadSaliente = $consumption->quantity;
            
            if ($product->is_fractionable) {
                // Producto fraccionable: calcular con conversiones
                $nuevoCurrentStock = $subBranchProduct->current_stock - $cantidadSaliente;
                $nuevosPaquetes = intdiv($nuevoCurrentStock, $unitsPerPackage);
                $nuevasFracciones = $nuevoCurrentStock % $unitsPerPackage;
                
                // Calcular cuántas cajas y fracciones salen
                $cajasSalientes = intdiv($cantidadSaliente, $unitsPerPackage);
                $fraccionesSalientes = $cantidadSaliente % $unitsPerPackage;
            } else {
                // Producto NO fraccionable: solo contar paquetes completos
                $nuevosPaquetes = $subBranchProduct->packages_in_stock - $cantidadSaliente;
                $nuevasFracciones = 0;
                $nuevoCurrentStock = $nuevosPaquetes;
                
                $cajasSalientes = $cantidadSaliente;
                $fraccionesSalientes = 0;
            }
            
            // ✅ Actualizar el stock
            $subBranchProduct->current_stock = $nuevoCurrentStock;
            $subBranchProduct->packages_in_stock = $nuevosPaquetes;
            $subBranchProduct->updated_by = $user->id;
            $subBranchProduct->save();
            
            // ✅ Crear línea de Kardex como SALIDA
            Kardex::create([
                'product_id' => $consumption->product_id,
                'sub_branch_id' => $subBranchProduct->sub_branch_id,
                'movement_detail_id' => null,
                'sale_id' => null,
                'precio_total' => $consumption->total_price ?? 0,
                'SAnteriorCaja' => $SAnteriorCaja,
                'SAnteriorFraccion' => $SAnteriorFraccion,
                'cantidadCaja' => $cajasSalientes,
                'cantidadFraccion' => $fraccionesSalientes,
                'SParcialCaja' => $nuevosPaquetes,
                'SParcialFraccion' => $nuevasFracciones,
                'movement_type' => 'salida',
                'movement_category' => 'venta', // ✅ Es una VENTA de producto
                'estado' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }
    public function markAsPaid(){
        $this->status = self::STATUS_PAID;
        $this->save();
    }
    public function isPending(){
        return $this->status === self::STATUS_PENDING;
    }
    public function isPaid(){
        return $this->status === self::STATUS_PAID;
    }
}