<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Floor extends Model implements Auditable{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'sub_branch_id', 'name', 'floor_number', 'description', 'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'floor_number' => 'integer',
    ];
    // Relaciones
    public function subBranch(){
        return $this->belongsTo(SubBranch::class);
    }
    public function rooms(){
        return $this->hasMany(Room::class)->orderBy('room_number');
    }
    public function availableRooms(){
        return $this->rooms()->where('status', 'available')->where('is_active', true);
    }
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRoomCounts($query)
    {
        return $query->withCount(['rooms', 'availableRooms']);
    }

    // Métodos de conveniencia
    public function getTotalRoomsAttribute()
    {
        return $this->rooms()->count();
    }

    public function getAvailableRoomsCountAttribute()
    {
        return $this->availableRooms()->count();
    }

    public function getOccupancyRateAttribute()
    {
        $total = $this->getTotalRoomsAttribute();
        if ($total === 0) return 0;
        
        $occupied = $this->rooms()->where('status', 'occupied')->count();
        return round(($occupied / $total) * 100, 2);
    }

    // Validar que el número de piso no esté duplicado
    public static function boot(){
        parent::boot();
        static::creating(function ($floor) {
            $exists = static::where('sub_branch_id', $floor->sub_branch_id)
                           ->where('floor_number', $floor->floor_number)
                           ->exists();
            if ($exists) {
                throw new \Exception('Ya existe un piso con este número en la sub sucursal.');
            }
        });
    }
}
