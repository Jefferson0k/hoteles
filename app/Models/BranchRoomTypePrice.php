<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class BranchRoomTypePrice extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'sub_branch_id',
        'room_type_id',
        'rate_type_id',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function subBranch()
    {
        return $this->belongsTo(SubBranch::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function rateType()
    {
        return $this->belongsTo(RateType::class);
    }

    public function pricingRanges()
    {
        return $this->hasMany(PricingRange::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySubBranch($query, $subBranchId)
    {
        return $query->where('sub_branch_id', $subBranchId);
    }

    public function scopeByRoomType($query, $roomTypeId)
    {
        return $query->where('room_type_id', $roomTypeId);
    }

    public function scopeByRateType($query, $rateTypeId)
    {
        return $query->where('rate_type_id', $rateTypeId);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    public function scopeCurrent($query)
    {
        return $query->effectiveOn(now());
    }

    // Métodos auxiliares
    public function isEffectiveOn($date)
    {
        return $this->effective_from <= $date && 
               ($this->effective_to === null || $this->effective_to >= $date);
    }

    public function isCurrentlyEffective()
    {
        return $this->isEffectiveOn(now());
    }

    public function hasExpired()
    {
        return $this->effective_to !== null && $this->effective_to < now();
    }

    /**
     * Calcula el precio según minutos
     */
    public function calculatePrice($minutes)
    {
        $pricingRange = $this->pricingRanges()
            ->active()
            ->forMinutes($minutes)
            ->first();

        return $pricingRange ? $pricingRange->price : null;
    }

    /**
     * Obtener todos los rangos de precio disponibles
     */
    public function getPricingOptions()
    {
        return $this->pricingRanges()
            ->active()
            ->orderByTime()
            ->get()
            ->map(function ($range) {
                return [
                    'id' => $range->id,
                    'time_from_minutes' => $range->time_from_minutes,
                    'time_to_minutes' => $range->time_to_minutes,
                    'formatted_time' => $range->getFormattedTimeRange(),
                    'price' => number_format($range->price, 2, '.', ''),
                ];
            });
    }
}
