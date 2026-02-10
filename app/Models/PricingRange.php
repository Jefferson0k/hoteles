<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PricingRange extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'branch_room_type_price_id',
        'time_from_minutes',
        'time_to_minutes',
        'price',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'time_from_minutes' => 'integer',
        'time_to_minutes' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function branchRoomTypePrice()
    {
        return $this->belongsTo(BranchRoomTypePrice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBranchRoomTypePrice($query, $branchRoomTypePriceId)
    {
        return $query->where('branch_room_type_price_id', $branchRoomTypePriceId);
    }

    public function scopeForMinutes($query, $minutes)
    {
        return $query->where('time_from_minutes', '<=', $minutes)
            ->where('time_to_minutes', '>=', $minutes);
    }

    public function scopeOrderByTime($query)
    {
        return $query->orderBy('time_from_minutes');
    }

    // Métodos auxiliares
    public function isInRange($minutes)
    {
        return $minutes >= $this->time_from_minutes && 
               $minutes <= $this->time_to_minutes;
    }

    public function getDurationInHours()
    {
        return ($this->time_to_minutes - $this->time_from_minutes) / 60;
    }

    public function getFormattedTimeRange()
    {
        $hoursFrom = floor($this->time_from_minutes / 60);
        $minutesFrom = $this->time_from_minutes % 60;
        
        $hoursTo = floor($this->time_to_minutes / 60);
        $minutesTo = $this->time_to_minutes % 60;

        $from = $hoursFrom . 'h';
        if ($minutesFrom > 0) {
            $from .= ' ' . $minutesFrom . 'min';
        }

        $to = $hoursTo . 'h';
        if ($minutesTo > 0) {
            $to .= ' ' . $minutesTo . 'min';
        }

        return $from . ' - ' . $to;
    }

    public function getPricePerHour()
    {
        $durationHours = $this->getDurationInHours();
        return $durationHours > 0 ? $this->price / $durationHours : 0;
    }

    // Validación de solapamiento
    public static function hasOverlap($branchRoomTypePriceId, $timeFrom, $timeTo, $excludeId = null)
    {
        $query = self::where('branch_room_type_price_id', $branchRoomTypePriceId)
            ->where(function ($q) use ($timeFrom, $timeTo) {
                $q->whereBetween('time_from_minutes', [$timeFrom, $timeTo])
                    ->orWhereBetween('time_to_minutes', [$timeFrom, $timeTo])
                    ->orWhere(function ($q2) use ($timeFrom, $timeTo) {
                        $q2->where('time_from_minutes', '<=', $timeFrom)
                            ->where('time_to_minutes', '>=', $timeTo);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
