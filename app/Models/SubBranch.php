<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class SubBranch extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'address',
        'phone',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function floors()
    {
        return $this->hasMany(Floor::class)->orderBy('floor_number');
    }

    public function branchRoomTypePrices()
    {
        return $this->hasMany(BranchRoomTypePrice::class);
    }

    public function timeSettings()
    {
        return $this->hasOne(SubBranchTimeSetting::class);
    }

    public function checkinSettings()
    {
        return $this->hasOne(SubBranchCheckinSetting::class);
    }

    public function penaltySettings()
    {
        return $this->hasOne(SubBranchPenaltySetting::class);
    }

    public function cancellationPolicies()
    {
        return $this->hasOne(SubBranchCancellationPolicy::class);
    }

    public function depositSettings()
    {
        return $this->hasOne(SubBranchDepositSetting::class);
    }

    public function taxSettings()
    {
        return $this->hasOne(SubBranchTaxSetting::class);
    }

    public function services()
    {
        return $this->hasMany(SubBranchService::class);
    }

    public function discounts()
    {
        return $this->hasMany(SubBranchDiscount::class);
    }

    public function reservationSettings()
    {
        return $this->hasOne(SubBranchReservationSetting::class);
    }

    public function specialDates()
    {
        return $this->hasMany(SubBranchSpecialDate::class);
    }

    public function additionalCharges()
    {
        return $this->hasMany(SubBranchAdditionalCharge::class);
    }

    public function notificationSettings()
    {
        return $this->hasOne(SubBranchNotificationSetting::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function rooms()
    {
        return $this->hasManyThrough(
            Room::class,   // Modelo destino
            Floor::class,  // Modelo intermedio
            'sub_branch_id', // FK en floors → referencia a sub_branch
            'floor_id',      // FK en rooms → referencia a floors
            'id',            // PK en sub_branches
            'id'             // PK en floors
        );
    }

    public function customPrices()
    {
        return $this->hasMany(BranchRoomTypePrice::class, 'sub_branch_id');
    }

    public function subBranchProducts()
    {
        return $this->hasMany(SubBranchProduct::class);
    }

    public function activeFloors()
    {
        return $this->floors()->where('is_active', true);
    }

    public function activeRooms()
    {
        return $this->rooms()->where('rooms.is_active', true);
    }

    public function availableRooms()
    {
        return $this->activeRooms()->where('rooms.status', 'available');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'sub_branch_products')
            ->withPivot([
                'current_stock',
                'min_stock',
                'max_stock',
                'custom_sale_price',
                'is_active'
            ])
            ->withTimestamps();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithCounts($query)
    {
        return $query->withCount([
            'floors',
            'floors as active_floors_count' => function ($query) {
                $query->where('is_active', true);
            },
            'rooms',
            'rooms as active_rooms_count' => function ($query) {
                $query->where('rooms.is_active', true);
            },
            'rooms as available_rooms_count' => function ($query) {
                $query->where('rooms.is_active', true)
                      ->where('rooms.status', 'available');
            }
        ]);
    }

    /**
     * Cargar locales con pisos y habitaciones activas
     */
    public function scopeWithActiveRooms($query)
    {
        return $query->with(['floors.rooms' => function ($q) {
            $q->where('rooms.is_active', true)
              ->orderBy('rooms.room_number');
        }]);
    }

    // ─── Events ───────────────────────────────────────────────────────────────
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = $model->generateUniqueCode('BR');
            }
        });
    }

    protected function generateUniqueCode(string $prefix = 'BR'): string
    {
        do {
            $code = $prefix . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    // ─── Accessors ────────────────────────────────────────────────────────────
    public function getTotalFloorsAttribute()
    {
        return $this->floors()->count();
    }

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
        $totalRooms = $this->getTotalRoomsAttribute();
        if ($totalRooms === 0) return 0;

        $occupiedRooms = $this->rooms()->where('rooms.status', 'occupied')->count();
        return round(($occupiedRooms / $totalRooms) * 100, 2);
    }
}
